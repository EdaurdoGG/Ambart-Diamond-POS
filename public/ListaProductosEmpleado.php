<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar id a sesión SQL
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Obtener datos del empleado
$stmtInfo = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM EmpleadosRegistrados 
    WHERE idEmpleado = ?
");
$stmtInfo->bind_param("i", $idPersona);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$empleado = $resultInfo->fetch_assoc();
$stmtInfo->close();

$nombreCompleto = $empleado ? ($empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno']) : 'Empleado';
$rol = $empleado['Rol'] ?? 'Empleado';
$imagenPerfil = $empleado['Imagen'] ?? 'imagenes/User.png';

$mensaje = ""; 

// Manejo de búsqueda
$busqueda = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);

    if (preg_match('/^\d+$/', $busqueda)) {
        $stmt = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
    } else {
        $stmt = $conn->prepare("CALL BuscarProductoPorNombre(?)");
    }
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    while ($conn->more_results() && $conn->next_result()) {;}
} else {
    $sql = "SELECT * FROM ProductosConEstado";
    $result = $conn->query($sql);
}

// GREGAR PRODUCTO AL CARRITO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idProducto'])) {
    $idProducto = intval($_POST['idProducto']);
    $mensaje = ""; // Reiniciar mensaje

    // Consultar producto
    $stmtProd = $conn->prepare("SELECT idProducto, Existencia, Estado FROM ProductosConEstado WHERE idProducto = ?");
    $stmtProd->bind_param("i", $idProducto);
    $stmtProd->execute();
    $prodBD = $stmtProd->get_result()->fetch_assoc();
    $stmtProd->close();

    if (!$prodBD) {
        $mensaje = "Error: Producto no encontrado.";
    } elseif ($prodBD['Existencia'] <= 0) {
        $mensaje = "Error: Producto sin stock.";
    } elseif ($prodBD['Estado'] === "Inactivo") {
        $mensaje = "Error: Producto inactivo.";
    } else {
        // Solo agregar al carrito si no hay errores
        $stmt = $conn->prepare("CALL AgregarAlCarrito(?, ?)");
        if ($stmt) {
            $stmt->bind_param("ii", $idPersona, $idProducto);
            if ($stmt->execute()) {
                $mensaje = "Producto agregado correctamente.";
            } else {
                $mensaje = "Error al agregar el producto al carrito.";
            }
            while ($conn->more_results() && $conn->next_result()) {;}
            $stmt->close();
        } else {
            $mensaje = "Error interno al preparar la consulta.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo de Productos</title>
<link rel="stylesheet" href="ListaProductosEmpleado.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
<style>
/* Clase para simular "deshabilitado" visualmente */
.disabled-btn {
    background-color: #ccc;
    cursor: not-allowed;
}
</style>
</head>
<body>

<!-- MENSAJE FLOTANTE DINÁMICO -->
<?php if (!empty($mensaje)): ?>
<div id="mensaje-flotante" 
     class="alert-message <?= (strpos($mensaje, 'correctamente') !== false) ? 'alert-success' : 'alert-error' ?>">
     <?= htmlspecialchars($mensaje) ?>
</div>
<?php endif; ?>

<script>
// Ocultar el mensaje después de 3.2 segundos
setTimeout(() => {
    const msg = document.getElementById("mensaje-flotante");
    if (msg) msg.remove();
}, 3200);
</script>

<div class="dashboard-container">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="logo">
        <img src="imagenes/Logo.png" class="icon">
        <span>Amber Diamond</span>
    </div>

    <nav class="menu">
        <a href="InicioEmpleados.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon">Inicio</a>
        <a href="CarritoEmpleado.php" class="menu-item"><img src="imagenes/Caja.png" class="icon">Caja</a>
        <a href="ListaProductosEmpleado.php" class="menu-item active"><img src="imagenes/Productos.png" class="icon">Productos</a>
        <a href="HistorialVentasEmpleado.php" class="menu-item"><img src="imagenes/Ventas.png" class="icon">Historial Ventas</a>
        <a href="ListaPedidosEmpleado.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon">Pedidos</a>
        <a href="ListaDevolucionesEmpleado.php" class="menu-item"><img src="imagenes/Devoluciones.png" class="icon">Devoluciones</a>
        <a href="QuejaSugerenciaEmpleado.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon">Quejas / Sugerencias</a>

        <div class="menu-separator"></div>

        <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon">Cerrar sesión</a>
    </nav>
</aside>

<!-- Main content -->
<main class="main-content">

<header class="topbar">
    <div class="search-box">
        <form method="POST">
            <input type="text" name="busqueda" placeholder="Buscar producto..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="search-button">
                <img src="imagenes/Buscar.png" class="search-icon">
            </button>
        </form>
    </div>

    <div class="user-profile">
        <a href="EditarPerfilEmpleado.php">
            <img src="<?= htmlspecialchars($imagenPerfil) ?>" class="avatar">
        </a>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
        </div>
    </div>
</header>

<div class="table-actions">
    <a href="CarritoEmpleado.php"><button class="btn-primary">Ir a Caja</button></a>
</div>

<section class="card-section">
<?php if ($result && $result->num_rows > 0): ?>
    <?php foreach($result as $prod): ?>
        <div class="card">
            <img src="productos/<?= htmlspecialchars(basename($prod['Imagen'])) ?>" class="card-img">

            <div class="card-info">
                <h3><?= htmlspecialchars($prod['Producto']) ?></h3>
                <p>Categoría: <?= htmlspecialchars($prod['Categoria']) ?></p>
                <p>Existencia: <?= htmlspecialchars($prod['Existencia']) ?></p>
                <span class="price">$<?= number_format($prod['PrecioVenta'], 2) ?></span>

                <div class="card-actions">

                    <form method="post">
                        <input type="hidden" name="idProducto" value="<?= $prod['idProducto'] ?>">
                        <button type="submit" class="btn-primary <?= ($prod['Estado'] == 'Inactivo' || $prod['Existencia'] <= 0) ? 'disabled-btn' : '' ?>">
                            Agregar
                        </button>
                    </form>

                    <form action="EditarProductoEmpleado.php" method="get">
                        <input type="hidden" name="idProducto" value="<?= $prod['idProducto'] ?>">
                        <button class="btn-secondary">Editar</button>
                    </form>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;color:gray;">No hay productos disponibles.</p>
<?php endif; ?>
</section>

<footer class="site-footer">
    <p>&copy; 2025 Diamonds Corporation</p>
</footer>

</main>
</div>

</body>
</html>
