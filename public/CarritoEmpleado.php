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

// --- Función segura para limpiar múltiples resultados ---
function limpiarResultados($conn) {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

// --- Función segura para ejecutar procedimientos sin resultado ---
function ejecutarProcedimiento($conn, $sql) {
    if (!$conn->multi_query($sql)) {
        die("Error al ejecutar procedimiento: " . $conn->error);
    }
    limpiarResultados($conn);
}

// --- 🔍 Búsqueda de producto ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);

    limpiarResultados($conn);

    // Seleccionar el procedimiento correcto
    if (preg_match('/^\d+$/', $busqueda)) {
        $stmt = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
    } else {
        $stmt = $conn->prepare("CALL BuscarProductoPorNombre(?)");
    }

    if (!$stmt) {
        die("Error al preparar búsqueda: " . $conn->error);
    }

    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        while ($prod = $resultado->fetch_assoc()) {
            $idProducto = intval($prod['idProducto']);
            limpiarResultados($conn);

            $stmtAdd = $conn->prepare("CALL AgregarAlCarrito(?, ?)");
            if ($stmtAdd) {
                $stmtAdd->bind_param("ii", $idPersona, $idProducto);
                $stmtAdd->execute();
                $stmtAdd->close();
                limpiarResultados($conn);
            } else {
                die("Error al preparar AgregarAlCarrito: " . $conn->error);
            }
        }

        echo "<script> window.location.href='CarritoEmpleado.php';</script>";
        exit();
    } else {
        echo "<script>alert('No se encontró ningún producto.'); window.location.href='CarritoEmpleado.php';</script>";
        exit();
    }

    limpiarResultados($conn);
    
    $stmt->close();
}

// --- 🛠️ Acciones del carrito ---
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $idDetalle = intval($_GET['idDetalle'] ?? 0);
    $cantidad = 1;

    switch ($accion) {
        case 'sumar':
            ejecutarProcedimiento($conn, "CALL SumarCantidadCarrito($idDetalle, $cantidad)");
            break;
        case 'restar':
            ejecutarProcedimiento($conn, "CALL RestarCantidadCarrito($idDetalle, $cantidad)");
            break;
        case 'procesar':
            ejecutarProcedimiento($conn, "CALL ProcesarVentaNormal($idPersona, 'Efectivo')");
            break;
        case 'vaciar':
            $sql = "DELETE dc FROM DetalleCarrito dc
                    JOIN Carrito c ON dc.idCarrito = c.idCarrito
                    WHERE c.idPersona = $idPersona;
                    DELETE FROM Carrito WHERE idPersona = $idPersona;";
            ejecutarProcedimiento($conn, $sql);
            break;
    }

    header("Location: CarritoEmpleado.php");
    exit();
}

// --- 🧾 Obtener carrito del usuario ---
$carrito = [];
limpiarResultados($conn);

$stmt = $conn->prepare("CALL ObtenerCarritoPorPersona(?)");
if ($stmt) {
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $carrito[] = $row;
    }
    $stmt->close();
    limpiarResultados($conn);
} else {
    die("Error al obtener carrito: " . $conn->error);
}

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
</head>
<body>
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
            <form method="GET" action="CarritoEmpleado.php">
                <input type="hidden" name="accion" value="procesar">
                <button type="submit" class="btn-primary">Procesar Venta</button>
            </form>
            <form method="GET" action="CarritoEmpleado.php">
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
                                <td><img src="<?= htmlspecialchars($producto['Imagen']) ?>" width="60"></td>
                                <td><?= htmlspecialchars($producto['Producto']) ?></td>
                                <td><?= intval($producto['Cantidad']) ?></td>
                                <td>$<?= number_format($producto['PrecioUnitario'], 2) ?></td>
                                <td>$<?= number_format($producto['Total'], 2) ?></td>
                                <td>
                                    <div class="card-actions">
                                        <form method="GET" action="CarritoEmpleado.php">
                                            <input type="hidden" name="accion" value="sumar">
                                            <input type="hidden" name="idDetalle" value="<?= $producto['idDetalleCarrito'] ?>">
                                            <button type="submit" class="btn-primary">+</button>
                                        </form>
                                        <form method="GET" action="CarritoEmpleado.php">
                                            <input type="hidden" name="accion" value="restar">
                                            <input type="hidden" name="idDetalle" value="<?= $producto['idDetalleCarrito'] ?>">
                                            <button type="submit" class="btn-secondary">−</button>
                                        </form>
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
