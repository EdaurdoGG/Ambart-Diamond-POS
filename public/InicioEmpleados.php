<?php
session_start();

// Verificar que el usuario esté logueado y sea empleado (rol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

$idEmpleado = intval($_SESSION['idPersona']);

$conn->query("SET @id_usuario_actual = " . $idEmpleado);

// AGREGAR AL CARRITO (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idProducto']) && !isset($_POST['busqueda'])) {
    $idProducto = intval($_POST['idProducto']);
    $stmt = $conn->prepare("CALL AgregarAlCarrito(?, ?)");
    $stmt->bind_param("ii", $idEmpleado, $idProducto);
    if ($stmt->execute()) {
        echo "Producto agregado al carrito";
    } else {
        echo "Error al agregar producto";
    }
    $stmt->close();
    $conn->close();
    exit(); // Salimos para que AJAX reciba solo el mensaje
}

// DATOS DEL EMPLEADO
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();
$stmt->close();

// KPI y Datos
$sqlVentasHoy = "SELECT IFNULL(SUM(Subtotal),0) AS TotalVentasHoy FROM VistaVentasEmpleado WHERE idEmpleado=? AND Fecha=CURDATE() AND Estatus='Activa'";
$stmt = $conn->prepare($sqlVentasHoy);
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$resVentas = $stmt->get_result()->fetch_assoc();
$ventasHoy = $resVentas['TotalVentasHoy'] ?? 0;
$stmt->close();

$sqlPedidosPendientes = "SELECT COUNT(*) AS PedidosPendientes FROM PedidosCompletos WHERE Estatus='Pendiente'";
$resPedidos = $conn->query($sqlPedidosPendientes)->fetch_assoc();
$pedidosPendientes = $resPedidos['PedidosPendientes'] ?? 0;

$sqlClientesAtendidos = "SELECT COUNT(DISTINCT v.idPersona) AS ClientesAtendidos FROM Venta v WHERE DATE(v.Fecha)=CURDATE() AND v.idPersona IS NOT NULL AND v.Estatus='Activa'";
$resClientes = $conn->query($sqlClientesAtendidos)->fetch_assoc();
$clientesAtendidos = $resClientes['ClientesAtendidos'] ?? 0;

$sqlUltimasVentas = "SELECT Producto, Subtotal FROM VistaVentasEmpleado WHERE idEmpleado=? ORDER BY Fecha DESC, Hora DESC LIMIT 5";
$stmt = $conn->prepare($sqlUltimasVentas);
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$resUltimasVentas = $stmt->get_result();
$ultimasVentas = $resUltimasVentas->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sqlPedidosRecientes = "SELECT idPedido, Estatus, Fecha FROM PedidosCompletos ORDER BY Fecha DESC LIMIT 5";
$resPedidosRecientes = $conn->query($sqlPedidosRecientes);
$pedidosRecientes = $resPedidosRecientes->fetch_all(MYSQLI_ASSOC);

// BÚSQUEDA DE PRODUCTOS
function limpiarResultados($conn) {
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) $res->free();
    }
}

$productosBusqueda = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);
    limpiarResultados($conn);

    if (preg_match('/^\d+$/', $busqueda)) {
        $stmtBusqueda = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
    } else {
        $stmtBusqueda = $conn->prepare("CALL BuscarProductoPorNombre(?)");
    }

    if ($stmtBusqueda) {
        $stmtBusqueda->bind_param("s", $busqueda);
        $stmtBusqueda->execute();
        $resBusqueda = $stmtBusqueda->get_result();
        $productosBusqueda = $resBusqueda->fetch_all(MYSQLI_ASSOC);
        $stmtBusqueda->close();
        limpiarResultados($conn);
    }
}

$conn->close();

$nombreCompleto = $empleado ? $empleado['Nombre'].' '.$empleado['ApellidoPaterno'].' '.$empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagen = $empleado && $empleado['Imagen'] ? $empleado['Imagen'] : 'imagenes/User.png';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inicio Empleado</title>
<link rel="stylesheet" href="InicioEmpleados.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>

<div class="dashboard-container">
<!-- Sidebar y Topbar -->
<aside class="sidebar">
  <div class="logo"><img src="imagenes/Logo.png" class="icon" alt="Logo"><span>Amber Diamond</span></div>
  <nav class="menu">
    <a href="InicioEmpleados.php" class="menu-item active"><img src="imagenes/Inicio.png" class="icon"> Inicio</a>
    <a href="CarritoEmpleado.php" class="menu-item"><img src="imagenes/Caja.png" class="icon"> Caja</a>
    <a href="ListaProductosEmpleado.php" class="menu-item"><img src="imagenes/Productos.png" class="icon"> Productos</a>
    <a href="HistorialVentasEmpleado.php" class="menu-item"><img src="imagenes/Ventas.png" class="icon"> Historial Ventas</a>
    <a href="ListaPedidosEmpleado.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon"> Pedidos</a>
    <a href="ListaDevolucionesEmpleado.php" class="menu-item"><img src="imagenes/Devoluciones.png" class="icon"> Devoluciones</a>
    <a href="QuejaSugerenciaEmpleado.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas / Sugerencias</a>
    <div class="menu-separator"></div>
    <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon"> Cerrar sesión</a>
  </nav>
</aside>

<main class="main-content">
<header class="topbar">
  <h2>Bienvenido de nuevo, <?=htmlspecialchars($nombreCompleto)?></h2>
  <div class="user-profile">
    <a href="EditarPerfilEmpleado.php"><img src="<?=htmlspecialchars($imagen)?>" class="avatar"></a>
    <div class="user-info">
      <span class="user-name"><?=htmlspecialchars($nombreCompleto)?></span>
      <span class="user-role"><?=htmlspecialchars($rol)?></span>
    </div>
  </div>
</header>

<section class="quick-actions">
<h3>Buscar Productos</h3>
<div class="search-box">
<form method="POST">
  <input type="text" name="busqueda" placeholder="Buscar por nombre o código..." required>
  <button type="submit" class="btn-primary" id="btnBuscar">Buscar</button>
</form>
</div>
</section>

<section class="kpi-cards">
  <div class="card"><h3>Ventas Hoy</h3><p>$<?=number_format($ventasHoy,2)?></p></div>
  <div class="card"><h3>Pedidos Pendientes</h3><p><?=$pedidosPendientes?></p></div>
  <div class="card"><h3>Clientes Atendidos</h3><p><?=$clientesAtendidos?></p></div>
</section>

<section class="dashboard-widgets">
  <div class="widget">
    <h3>Últimas Ventas</h3>
    <ul>
      <?php if(count($ultimasVentas)>0): foreach($ultimasVentas as $venta): ?>
      <li><?=htmlspecialchars($venta['Producto'])?> - $<?=number_format($venta['Subtotal'],2)?></li>
      <?php endforeach; else: ?><li>No hay ventas registradas hoy.</li><?php endif;?>
    </ul>
  </div>
  <div class="widget">
    <h3>Pedidos Recientes</h3>
    <ul>
      <?php if(count($pedidosRecientes)>0): foreach($pedidosRecientes as $pedido): ?>
      <li>#<?=$pedido['idPedido']?> - <?=htmlspecialchars($pedido['Estatus'])?> (<?=$pedido['Fecha']?>)</li>
      <?php endforeach; else: ?><li>No hay pedidos recientes.</li><?php endif;?>
    </ul>
  </div>
</section>

<!-- Modal de Productos -->
<?php if(!empty($productosBusqueda)): ?>
<div class="modal" id="modalBusqueda">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Productos encontrados</h3>
      <span class="close" id="cerrarModal">&times;</span>
    </div>
    <div class="modal-body">
      <?php foreach($productosBusqueda as $prod): ?>
      <div class="product-card">
        <img src="<?=htmlspecialchars($prod['Imagen'] ?? 'imagenes/ProductDefault.png')?>" alt="<?=htmlspecialchars($prod['Producto'])?>">
        <h4><?=htmlspecialchars($prod['Producto'])?></h4>
        <p>Precio: $<?=number_format($prod['PrecioVenta'] ?? 0,2)?></p>
        <p>Cantidad: <?=intval($prod['Existencia'] ?? 0)?></p>
        <button onclick="agregarCarrito(<?=$prod['idProducto']?>)">Agregar al carrito</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<footer class="site-footer">
<p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
</footer>
</main>
</div>

<script>
// Mostrar modal si hay resultados
<?php if(!empty($productosBusqueda)): ?>
document.getElementById('modalBusqueda').style.display = 'block';
<?php endif; ?> 

// Cerrar modal manualmente
document.getElementById('cerrarModal')?.addEventListener('click', function(){
  document.getElementById('modalBusqueda').style.display = 'none';
});

// Función para agregar al carrito usando AJAX y cerrar modal al finalizar
function agregarCarrito(idProducto){
  fetch('', { // misma página
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'idProducto=' + encodeURIComponent(idProducto)
  })
  .then(response => response.text())
  .then(data => {
    alert(data); // Mensaje de confirmación
    // Cerrar modal después de agregar
    const modal = document.getElementById('modalBusqueda');
    if(modal) modal.style.display = 'none';
  })
  .catch(error => console.error('Error:', error));
}
</script>

</body>
</html>
