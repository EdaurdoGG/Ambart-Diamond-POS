<?php
session_start();

// Verificar sesión activa
$idPersona = $_SESSION['idPersona'] ?? null;
if (!$idPersona) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a variable MySQL (protección int)
$idPersona = intval($idPersona);
$conn->query("SET @id_usuario_actual = " . $idPersona);

// Obtener información del cliente
$stmtInfo = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM ClientesRegistrados WHERE idCliente = ?");
if ($stmtInfo) {
    $stmtInfo->bind_param("i", $idPersona);
    $stmtInfo->execute();
    $resultInfo = $stmtInfo->get_result();
    $cliente = $resultInfo->fetch_assoc();
    $stmtInfo->close();
} else {
    $cliente = null;
}

$nombreCompleto = $cliente ? trim($cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno']) : 'Cliente';
$rol = $cliente ? $cliente['Rol'] : 'Cliente';
$imagenPerfil = $cliente && !empty($cliente['Imagen']) ? $cliente['Imagen'] : 'imagenes/User.png';

// Función segura para limpiar múltiples resultados de multi_query
function limpiarResultados($conn) {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

// Función para ejecutar procedimientos o queries que usan multi_query
function ejecutarProcedimiento($conn, $sql) {
    if (!$conn->multi_query($sql)) {
        return ['ok' => false, 'error' => $conn->error];
    }
    limpiarResultados($conn);
    return ['ok' => true];
}

// Función para mostrar mensajes y tipo (session)
function setMensaje($texto, $tipo = 'success') {
    $_SESSION['mensaje'] = $texto;
    $_SESSION['tipo_mensaje'] = $tipo;
}

/*
 * Manejo POST para actualizar cantidad manual (acción 'actualizar')
 * Permite que al presionar ENTER en el input numérico se actualice la cantidad.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $idDetalle = intval($_POST['idDetalle'] ?? 0);
    $nuevaCantidad = intval($_POST['nuevaCantidad'] ?? 0);

    // Validación básica en servidor
    if ($idDetalle <= 0 || $nuevaCantidad < 0) {
        setMensaje("Datos inválidos para actualizar la cantidad.", "error");
        header("Location: CarritoCliente.php");
        exit();
    }

    limpiarResultados($conn);

    $stmtUpd = $conn->prepare("CALL ActualizarCantidadCarrito(?, ?)");
    if ($stmtUpd) {
        if ($stmtUpd->bind_param("ii", $idDetalle, $nuevaCantidad) && $stmtUpd->execute()) {
            setMensaje("Cantidad actualizada correctamente.", "success");
        } else {
            // Error: intentar extraer info de existencia disponible
            $error = $stmtUpd->error ?: $conn->error;

            $max = 0;
            $sqlMax = "
                SELECT p.Existencia
                FROM DetalleCarrito dc
                JOIN Producto p ON p.idProducto = dc.idProducto
                WHERE dc.idDetalleCarrito = ?
                LIMIT 1
            ";
            $stmtMax = $conn->prepare($sqlMax);
            if ($stmtMax) {
                $stmtMax->bind_param("i", $idDetalle);
                if ($stmtMax->execute()) {
                    $resMax = $stmtMax->get_result();
                    if ($resMax && $rowMax = $resMax->fetch_assoc()) {
                        $max = intval($rowMax['Existencia']);
                    }
                }
                $stmtMax->close();
                limpiarResultados($conn);
            }

            if ($max > 0) {
                setMensaje("No hay suficiente stock. Máximo disponible: $max.", "error");
            } else {
                if (!empty($error)) {
                    if (preg_match('/(\d+)/', $error, $m)) {
                        setMensaje("No hay suficiente stock. Máximo disponible: " . $m[1] . ".", "error");
                    } else {
                        setMensaje("No se pudo actualizar la cantidad: " . htmlspecialchars($error), "error");
                    }
                } else {
                    setMensaje("No se pudo actualizar la cantidad.", "error");
                }
            }
        }
        $stmtUpd->close();
        limpiarResultados($conn);
    } else {
        setMensaje("Error interno al preparar la actualización.", "error");
    }

    header("Location: CarritoCliente.php");
    exit();
}

/*
 * BÚSQUEDA DE PRODUCTOS vía POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);
    limpiarResultados($conn);

    if (preg_match('/^\d+$/', $busqueda)) {
        $stmt = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
    } else {
        $stmt = $conn->prepare("CALL BuscarProductoPorNombre(?)");
    }

    if (!$stmt) {
        setMensaje("Error al preparar búsqueda.", "error");
        header("Location: CarritoCliente.php");
        exit();
    }

    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $anyAdded = false;
    $anyError = false;
    $errorMsg = "";

    if ($resultado && $resultado->num_rows > 0) {
        while ($prod = $resultado->fetch_assoc()) {
            $idProducto = intval($prod['idProducto']);
            limpiarResultados($conn);

            $stmtAdd = $conn->prepare("CALL AgregarAlCarrito(?, ?)");
            if ($stmtAdd) {
                if ($stmtAdd->bind_param("ii", $idPersona, $idProducto) && $stmtAdd->execute()) {
                    $anyAdded = true;
                } else {
                    $anyError = true;
                    $err = $stmtAdd->error ?: $conn->error;

                    if (stripos($err, 'stock') !== false || stripos($err, 'cantidad') !== false || stripos($err, 'max') !== false) {
                        $errorMsg = "No se pudo agregar: alcanzado máximo disponible.";
                    } else {
                        $errorMsg = "Error al agregar producto.";
                    }
                }
                $stmtAdd->close();
                limpiarResultados($conn);
            }
        }

        if ($anyAdded && !$anyError) {
            setMensaje("Producto agregado.", "success");
        } else {
            setMensaje($errorMsg ?: "No se pudo agregar.", "error");
        }

        $stmt->close();
        header("Location: CarritoCliente.php");
        exit();
    } else {
        setMensaje("No se encontró el producto.", "error");
        header("Location: CarritoCliente.php");
        exit();
    }
}

/*
 * ACCIONES: SUMAR / RESTAR / PROCESAR / VACIAR (GET)
 */
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $idDetalle = intval($_GET['idDetalle'] ?? 0);
    $cantidad = 1;

    switch ($accion) {

        case 'sumar':
            $res = ejecutarProcedimiento($conn, "CALL SumarCantidadCarrito($idDetalle, $cantidad)");
            if (!$res['ok']) {
                $msg = $res['error'];
                if (stripos($msg, "stock") !== false || stripos($msg, "max") !== false) {
                    setMensaje("Ya alcanzaste la cantidad máxima disponible.", "error");
                } else {
                    setMensaje("No se pudo incrementar la cantidad: " . htmlspecialchars($msg), "error");
                }
            }
            break;

        case 'restar':
            $res = ejecutarProcedimiento($conn, "CALL RestarCantidadCarrito($idDetalle, $cantidad)");
            if (!$res['ok']) {
                $msg = $res['error'];
                if (stripos($msg, "min") !== false || stripos($msg, "0") !== false) {
                    setMensaje("El producto fue retirado del carrito.", "error");
                } else {
                    setMensaje("No se pudo disminuir la cantidad: " . htmlspecialchars($msg), "error");
                }
            }
            break;

        case 'procesar':
            // ⚠ AHORA CREA UN PEDIDO EN VEZ DE HACER UNA VENTA
            $res = ejecutarProcedimiento($conn, "CALL CrearPedidoDesdeCarrito($idPersona)");
            if ($res['ok']) {
                setMensaje("Pedido creado con éxito.", "success");
            } else {
                setMensaje("Error al crear el pedido: " . htmlspecialchars($res['error'] ?? ''), "error");
            }
            break;

        case 'vaciar':
            $sql = "
                DELETE dc FROM DetalleCarrito dc 
                JOIN Carrito c ON dc.idCarrito = c.idCarrito 
                WHERE c.idPersona = $idPersona;
                DELETE FROM Carrito WHERE idPersona = $idPersona;
            ";
            $res = ejecutarProcedimiento($conn, $sql);
            if ($res['ok']) {
                setMensaje("Carrito vaciado.", "success");
            } else {
                setMensaje("Error al vaciar el carrito: " . htmlspecialchars($res['error'] ?? ''), "error");
            }
            break;
    }

    header("Location: CarritoCliente.php");
    exit();
}

// OBTENER CARRITO
$carrito = [];
limpiarResultados($conn);

$stmt = $conn->prepare("CALL ObtenerCarritoPorPersona(?)");
if ($stmt) {
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $carrito[] = $r;
    $stmt->close();
    limpiarResultados($conn);
}

$totalCarrito = array_sum(array_column($carrito, 'Total'));
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Productos</title>
  <link rel="stylesheet" href="CarritoCliente.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
  <script>
    // Si se presiona Enter dentro de cualquier input con clase 'qty-input', se envía su formulario padre
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.qty-input').forEach(function(input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var val = parseInt(this.value, 10);
                    if (isNaN(val) || val < 0) {
                        alert('Ingresa una cantidad válida (0 o mayor).');
                        return;
                    }
                    this.form.submit();
                }
            });
        });
    });
  </script>
</head>
<body>

<!-- MENSAJE DINÁMICO -->
<?php if (!empty($_SESSION['mensaje'])): ?>
<div class="alert-message <?= ($_SESSION['tipo_mensaje'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?>">
    <?= htmlspecialchars($_SESSION['mensaje']) ?>
</div>
<?php
unset($_SESSION['mensaje']);
unset($_SESSION['tipo_mensaje']);
?>
<?php endif; ?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="imagenes/Logo.png" alt="Logo" class="icon">
        <span>Amber Diamond</span>
      </div>
      <nav class="menu">
          <a href="InicioCliente.php" class="menu-item">
              <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
          </a>
          <a href="CarritoCliente.php" class="menu-item active">
              <img src="imagenes/Carrito.png" alt="Carrito" class="icon"> Carrito
          </a>
          <a href="ListaProductosCliente.php" class="menu-item">
              <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
          </a>
          <a href="ListaPedidosCliente.php" class="menu-item">
              <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
          </a>
          <a href="QuejaSugerenciaCliente.php" class="menu-item">
              <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> Quejas / Sugerencias
          </a>
          <div class="menu-separator"></div>
          <a href="Login.php" class="menu-item logout">
              <img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión
          </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <header class="topbar">
        <div class="search-box">

          <div class="resumen-compra">
            <div class="resumen-card">
              <h3>Resumen de Compra</h3>
              <div class="resumen-monto">
                <span>Total:</span>
                <span class="total-monto">$<?= number_format($totalCarrito, 2) ?></span>
              </div>
              <div class="detalle-items">
                <span><?= count($carrito) ?> producto(s) en el carrito</span>
              </div>
            </div>
          </div>
        </div>
        <div class="user-profile">
          <a href="EditarPerfilCliente.php">
            <img src="<?php echo htmlspecialchars($imagenPerfil); ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
          </div>
        </div>
      </header>

      <!-- Botones principales -->
      <div class="table-actions">
        <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
            <input type="hidden" name="accion" value="procesar">
            <button type="submit" class="btn-primary">Crear Pedido</button>
        </form>

        <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
            <input type="hidden" name="accion" value="vaciar">
            <button type="submit" class="btn-secondary">Limpiar Carrito</button>
        </form>
      </div>

      <!-- Productos del carrito -->
      <section class="card-section">
        <?php if(count($carrito) > 0): ?>
          <?php foreach($carrito as $producto): ?>
            <div class="card">
              <img src="<?= htmlspecialchars($producto['Imagen']) ?>" 
                   alt="<?= htmlspecialchars($producto['Producto']) ?>" class="card-img">
              <div class="card-info">
                <h3><?= htmlspecialchars($producto['Producto']) ?></h3>
                <p>Descripción corta del producto</p>
                <span class="price">$<?= number_format($producto['Total'], 2) ?></span>
                <div class="card-actions">
                  <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
                      <input type="hidden" name="accion" value="sumar">
                      <input type="hidden" name="idDetalle" value="<?= intval($producto['idDetalleCarrito']) ?>">
                      <button type="submit" class="btn-secondary">Sumar</button>
                  </form>

                  <form method="POST" action="CarritoCliente.php" style="display:inline-block; margin:0 8px;">
                      <input type="hidden" name="accion" value="actualizar">
                      <input type="hidden" name="idDetalle" value="<?= intval($producto['idDetalleCarrito']) ?>">
                      <input
                          type="number"
                          name="nuevaCantidad"
                          class="cantidad-input qty-input"
                          value="<?= intval($producto['Cantidad']) ?>"
                          min="0"
                          title="Escribe la cantidad y presiona Enter"
                          style="width:70px;"
                      />
                  </form>

                  <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
                      <input type="hidden" name="accion" value="restar">
                      <input type="hidden" name="idDetalle" value="<?= intval($producto['idDetalleCarrito']) ?>">
                      <button type="submit" class="btn-primary">Restar</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>El carrito está vacío.</p>
        <?php endif; ?>
      </section>

      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>

    </main>
  </div>
</body>
</html>
