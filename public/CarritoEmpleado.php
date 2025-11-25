<?php
session_start();

// Verificar que el usuario esté logueado y tenga rol de empleado (idRol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

$idPersona = intval($_SESSION['idPersona']);
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Obtener información del empleado
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();
$stmt->close();

$nombreCompleto = $empleado ? "{$empleado['Nombre']} {$empleado['ApellidoPaterno']} {$empleado['ApellidoMaterno']}" : "Empleado";
$rol = $empleado['Rol'] ?? 'Empleado';
$imagenPerfil = !empty($empleado['Imagen']) ? $empleado['Imagen'] : 'imagenes/User.png';

// Función segura para limpiar múltiples resultados
function limpiarResultados($conn) {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

// Función para ejecutar procedimientos almacenados (usada en acciones GET que requieren multi_query)
function ejecutarProcedimiento($conn, $sql) {
    if (!$conn->multi_query($sql)) {
        return ['ok' => false, 'error' => $conn->error];
    }
    limpiarResultados($conn);
    return ['ok' => true];
}

/*
 * NUEVO: Manejo POST para actualizar cantidad manual (acción 'actualizar')
 * Esto permite que al presionar ENTER en el input numérico se actualice la cantidad.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $idDetalle = intval($_POST['idDetalle'] ?? 0);
    $nuevaCantidad = intval($_POST['nuevaCantidad'] ?? 0);

    // Validación básica en servidor
    if ($idDetalle <= 0 || $nuevaCantidad < 0) {
        $_SESSION['mensaje'] = "Datos inválidos para actualizar la cantidad.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: CarritoEmpleado.php");
        exit();
    }

    limpiarResultados($conn);

    $stmtUpd = $conn->prepare("CALL ActualizarCantidadCarrito(?, ?)");
    if ($stmtUpd) {
        if ($stmtUpd->bind_param("ii", $idDetalle, $nuevaCantidad) && $stmtUpd->execute()) {
            // Si la ejecución fue exitosa, el procedimiento actualizó (o eliminó cuando cantidad <= 0)
            $_SESSION['mensaje'] = "Cantidad actualizada correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            // Hubo un error: puede ser un SIGNAL desde el procedimiento con mensaje indicando el máximo
            $error = $stmtUpd->error ?: $conn->error;

            // Intentar obtener la existencia máxima real del producto ligado al detalle para informar al usuario
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

            // Si el mensaje contiene la palabra 'Máximo' o 'Max' o 'stock' tratamos de construir un mensaje claro
            if ($max > 0) {
                $_SESSION['mensaje'] = "No hay suficiente stock. Máximo disponible: $max.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                // Si no pudimos determinar el máximo, usar el error genérico o el mensaje del procedimiento (si existe)
                if (!empty($error)) {
                    // Si el procedimiento devolvió algo como "No hay suficiente stock. Máximo: X", intentar extraer número
                    if (preg_match('/(\d+)/', $error, $m)) {
                        $_SESSION['mensaje'] = "No hay suficiente stock. Máximo disponible: " . $m[1] . ".";
                    } else {
                        $_SESSION['mensaje'] = "No se pudo actualizar la cantidad: " . $error;
                    }
                } else {
                    $_SESSION['mensaje'] = "No se pudo actualizar la cantidad.";
                }
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        $stmtUpd->close();
        limpiarResultados($conn);
    } else {
        $_SESSION['mensaje'] = "Error interno al preparar la actualización.";
        $_SESSION['tipo_mensaje'] = "error";
    }

    header("Location: CarritoEmpleado.php");
    exit();
}

// BÚSQUEDA DE PRODUCTOS (igual que antes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);
    limpiarResultados($conn);

    if (preg_match('/^\d+$/', $busqueda)) {
        $stmt = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
    } else {
        $stmt = $conn->prepare("CALL BuscarProductoPorNombre(?)");
    }

    if (!$stmt) {
        $_SESSION['mensaje'] = "Error al preparar búsqueda.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: CarritoEmpleado.php");
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
            $_SESSION['mensaje'] = "Producto agregado.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = $errorMsg ?: "No se pudo agregar.";
            $_SESSION['tipo_mensaje'] = "error";
        }

        $stmt->close();
        header("Location: CarritoEmpleado.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "No se encontró el producto.";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: CarritoEmpleado.php");
        exit();
    }
}

// ACCIONES: SUMAR / RESTAR / PROCESAR / VACIAR (GET)
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
                    $_SESSION['mensaje'] = "Ya alcanzaste la cantidad máxima disponible.";
                    $_SESSION['tipo_mensaje'] = "error";
                }
            }
            break;

        case 'restar':
            $res = ejecutarProcedimiento($conn, "CALL RestarCantidadCarrito($idDetalle, $cantidad)");

            if (!$res['ok']) {
                $msg = $res['error'];

                if (stripos($msg, "min") !== false || stripos($msg, "0") !== false) {
                    $_SESSION['mensaje'] = "El producto fue retirado del carrito.";
                    $_SESSION['tipo_mensaje'] = "error";
                }
            }
            break;

        case 'procesar':
            $res = ejecutarProcedimiento($conn, "CALL ProcesarVentaNormal($idPersona, 'Efectivo')");
            if ($res['ok']) {
                $_SESSION['mensaje'] = "Venta procesada con éxito.";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al procesar la venta.";
                $_SESSION['tipo_mensaje'] = "error";
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
                $_SESSION['mensaje'] = "Caja vaciada.";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "Error al vaciar la caja.";
                $_SESSION['tipo_mensaje'] = "error";
            }
            break;
    }

    header("Location: CarritoEmpleado.php");
    exit();
}

// OBTENER CARRITO
$carrito = [];
limpiarResultados($conn);

$stmt = $conn->prepare("CALL ObtenerCarritoPorPersona(?)");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $carrito[] = $r;
$stmt->close();
limpiarResultados($conn);

$totalCarrito = array_sum(array_column($carrito, 'Total'));
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Productos</title>
    <link rel="icon" type="image/png" href="imagenes/Logo.png">
    <link rel="stylesheet" href="CarritoEmpleado.css">
    <script>
        // Si se presiona Enter dentro de cualquier input con clase 'qty-input', se envía su formulario padre
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.qty-input').forEach(function(input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        // evitar enviar si valor no es número o < 0
                        var val = parseInt(this.value, 10);
                        if (isNaN(val) || val < 0) {
                            // mostrar mensaje sencillo en caso de client-side invalid
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
<?php if (!empty($_SESSION['mensaje'])): ?>
    <div class="alert-message <?= ($_SESSION['tipo_mensaje'] ?? "") === "error" ? "alert-error" : "alert-success" ?>">
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
    </div>
    <?php
        // Dejamos que el CSS/animación desaparezca el mensaje (3s), luego limpiamos
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
    ?>
<?php endif; ?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="imagenes/Logo.png" alt="Logo" class="icon">
            Amber Diamond
        </div>
        <div class="menu">
            <a href="InicioEmpleados.php" class="menu-item">
              <img src="imagenes/Inicio.png" class="icon"> Inicio
            </a>
            <a href="CarritoEmpleado.php" class="menu-item">
              <img src="imagenes/Caja.png" alt="CarritoEmpleado" class="icon"> Caja
            </a>
            <a href="ListaProductosEmpleado.php" class="menu-item">
              <img src="imagenes/Productos.png" class="icon"> Productos
            </a>
            <a href="HistorialVentasEmpleado.php" class="menu-item">
              <img src="imagenes/Ventas.png" class="icon"> Historial Ventas
            </a>
            <a href="ListaPedidosEmpleado.php" class="menu-item">
              <img src="imagenes/Pedidos.png" class="icon"> Pedidos
            </a>
            <a href="ListaDevolucionesEmpleado.php" class="menu-item">
              <img src="imagenes/Devoluciones.png" class="icon"> Devoluciones
            </a>
            <a href="QuejaSugerenciaEmpleado.php" class="menu-item">
              <img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas / Sugerencias
            </a>
            <div class="menu-separator"></div>
            <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon">Cerrar sesión</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <div class="search-box">
                <form method="POST">
                    <input type="text" name="busqueda" placeholder="Buscar por nombre o código..." required>
                    <button type="submit" class="search-button">
                        <img src="imagenes/Buscar.png" alt="Buscar" class="search-icon">
                    </button>
                </form>
            </div>
            <div class="user-profile">
              <a href="EditarPerfilEmpleado.php">
                <img src="<?= htmlspecialchars($imagenPerfil) ?>" alt="Avatar" class="avatar">
              </a>
              <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
                <span class="user-role"><?= htmlspecialchars($rol) ?></span>
              </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="table-actions">
            <form method="GET" action="CarritoEmpleado.php" style="display:inline-block;">
                <input type="hidden" name="accion" value="procesar">
                <button type="submit" class="btn-primary">Procesar Venta</button>
            </form>
            <form method="GET" action="CarritoEmpleado.php" style="display:inline-block;">
                <input type="hidden" name="accion" value="vaciar">
                <button type="submit" class="btn-secondary">Vaciar Caja</button>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($carrito) > 0): ?>
                        <?php foreach ($carrito as $producto): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($producto['Imagen']) ?>" width="60" alt=""></td>
                                <td><?= htmlspecialchars($producto['Producto']) ?></td>
                                <td>
                                    <!-- Form para actualizar cantidad: se envía al presionar ENTER en el input -->
                                    <form method="POST" action="CarritoEmpleado.php" style="margin:0;">
                                        <input type="hidden" name="accion" value="actualizar">
                                        <input type="hidden" name="idDetalle" value="<?= intval($producto['idDetalleCarrito']) ?>">
                                       <input
                                            type="number"
                                            name="nuevaCantidad"
                                            class="cantidad-input qty-input"
                                            value="<?= intval($producto['Cantidad']) ?>"
                                            min="0"
                                            title="Escribe la cantidad y presiona Enter"
                                        />
                                    </form>
                                </td>
                                <td>$<?= number_format($producto['PrecioUnitario'], 2) ?></td>
                                <td>$<?= number_format($producto['Total'], 2) ?></td>
                                <td>
                                    <div class="card-actions">
                                        <form method="GET" action="CarritoEmpleado.php" style="display:inline-block;">
                                            <input type="hidden" name="accion" value="sumar">
                                            <input type="hidden" name="idDetalle" value="<?= $producto['idDetalleCarrito'] ?>">
                                            <button type="submit" class="btn-primary">+</button>
                                        </form>
                                        <form method="GET" action="CarritoEmpleado.php" style="display:inline-block;">
                                            <input type="hidden" name="accion" value="restar">
                                            <input type="hidden" name="idDetalle" value="<?= $producto['idDetalleCarrito'] ?>">
                                            <button type="submit" class="btn-secondary">−</button>
                                        </form>
                                        <!-- Nota: no hace falta botón para 'Cambiar' — se usa Enter en el input -->
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">El carrito está vacío.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="total-general">Total general: $<?= number_format($totalCarrito, 2) ?></div>
        </div>
        <footer class="site-footer">
          <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
        </footer>
    </main>
</div>

</body>
</html>
