<?php
session_start();

// Verificar sesión activa y rol de cliente
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 3) { 
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Obtener datos del cliente
$stmtInfo = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM ClientesRegistrados 
    WHERE idCliente = ?
");
$stmtInfo->bind_param("i", $idPersona);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$cliente = $resultInfo->fetch_assoc();
$stmtInfo->close();

$nombreCompleto = $cliente ? $cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno'] : 'Cliente';
$rol = $cliente['Rol'] ?? 'Cliente';
$imagenPerfil = $cliente['Imagen'] ?? 'imagenes/User.png';

// Mensaje flotante dinámico
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

    // Filtrar productos con Existencia > 0
    $productosFiltrados = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['Existencia'] > 0) {
            $productosFiltrados[] = $row;
        }
    }
    $result->close();
    $result = $productosFiltrados;

    while ($conn->more_results() && $conn->next_result()) {;} // Limpiar resultados pendientes
    $stmt->close();
} else {
    // Traer solo productos con existencia > 0
    $sql = "SELECT * FROM ProductosConEstado WHERE Existencia > 0";
    $result = $conn->query($sql);
}

// =======================
// AGREGAR PRODUCTO AL CARRITO
// =======================
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
        // Agregar al carrito
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo de Productos</title>
<link rel="stylesheet" href="ListaProductosCliente.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
<style>
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
<script>
setTimeout(() => {
    const msg = document.getElementById("mensaje-flotante");
    if (msg) msg.remove();
}, 3200);
</script>
<?php endif; ?>

<div class="dashboard-container">

<aside class="sidebar">
    <div class="logo">
        <img src="imagenes/Logo.png" class="icon">
        <span>Amber Diamond</span>
    </div>
    <nav class="menu">
        <a href="InicioCliente.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon">Inicio</a>
        <a href="CarritoCliente.php" class="menu-item"><img src="imagenes/Carrito.png" class="icon">Carrito</a>
        <a href="ListaProductosCliente.php" class="menu-item active"><img src="imagenes/Productos.png" class="icon">Productos</a>
        <a href="ListaPedidosCliente.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon">Pedidos</a>
        <a href="QuejaSugerenciaCliente.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon">Quejas / Sugerencias</a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon">Cerrar sesión</a>
    </nav>
</aside>

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
        <a href="EditarPerfilCliente.php">
            <img src="<?= htmlspecialchars($imagenPerfil) ?>" class="avatar">
        </a>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
        </div>
    </div>
</header>

<div class="table-actions">
    <a href="CarritoCliente.php"><button class="btn-primary">Ver Carrito</button></a>
</div>

<section class="card-section">
<?php if (!empty($result)): ?>
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
