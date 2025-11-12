<?php
session_start();

// Verificar que el usuario esté logueado y sea empleado (rol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

$idEmpleado = $_SESSION['idPersona'];

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($idEmpleado));

// Obtener datos del empleado desde la vista EmpleadosRegistrados
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();
$stmt->close();

// ==========================
// TARJETAS DE INFORMACIÓN
// ==========================

// 1️⃣ Ventas del día del empleado (VistaVentasEmpleado)
$sqlVentasHoy = "
    SELECT 
        IFNULL(SUM(Subtotal), 0) AS TotalVentasHoy
    FROM VistaVentasEmpleado
    WHERE idEmpleado = ? 
    AND Fecha = CURDATE()
    AND Estatus = 'Activa';
";
$stmt = $conn->prepare($sqlVentasHoy);
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$resVentas = $stmt->get_result()->fetch_assoc();
$ventasHoy = $resVentas['TotalVentasHoy'] ?? 0;
$stmt->close();

// 2️⃣ Pedidos pendientes (PedidosCompletos con estatus pendiente)
$sqlPedidosPendientes = "
    SELECT COUNT(*) AS PedidosPendientes 
    FROM PedidosCompletos 
    WHERE Estatus = 'Pendiente';
";
$resPedidos = $conn->query($sqlPedidosPendientes)->fetch_assoc();
$pedidosPendientes = $resPedidos['PedidosPendientes'] ?? 0;

// 3️⃣ Clientes atendidos (Clientes con ventas del día)
$sqlClientesAtendidos = "
    SELECT COUNT(DISTINCT v.idPersona) AS ClientesAtendidos
    FROM Venta v
    WHERE DATE(v.Fecha) = CURDATE() AND v.idPersona IS NOT NULL AND v.Estatus = 'Activa';
";
$resClientes = $conn->query($sqlClientesAtendidos)->fetch_assoc();
$clientesAtendidos = $resClientes['ClientesAtendidos'] ?? 0;

// 4️⃣ Últimas ventas (VistaVentasEmpleado)
$sqlUltimasVentas = "
    SELECT Producto, Subtotal 
    FROM VistaVentasEmpleado 
    WHERE idEmpleado = ? 
    ORDER BY Fecha DESC, Hora DESC 
    LIMIT 5;
";
$stmt = $conn->prepare($sqlUltimasVentas);
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$resUltimasVentas = $stmt->get_result();
$ultimasVentas = $resUltimasVentas->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5️⃣ Pedidos recientes (PedidosCompletos)
$sqlPedidosRecientes = "
    SELECT idPedido, Estatus, Fecha 
    FROM PedidosCompletos 
    ORDER BY Fecha DESC 
    LIMIT 5;
";
$resPedidosRecientes = $conn->query($sqlPedidosRecientes);
$pedidosRecientes = $resPedidosRecientes->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Datos del usuario
$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
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
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="imagenes/Logo.png" alt="Logo" class="icon">
        <span>Amber Diamond</span>
      </div>
      <nav class="menu">
        <a href="InicioEmpleados.php" class="menu-item active">
            <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
        </a>
        <a href="CarritoEmpleado.php" class="menu-item">
            <img src="imagenes/Caja.png" alt="CarritoEmpleado" class="icon"> Caja
        </a>
        <a href="ListaProductosEmpleado.php" class="menu-item">
            <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
        </a>
        <a href="HistorialVentasEmpleado.php" class="menu-item">
            <img src="imagenes/Ventas.png" alt="HistorialVentas" class="icon"> Historial Ventas
        </a>
        <a href="ListaPedidosEmpleado.php" class="menu-item">
            <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
        </a>
        <a href="ListaDevolucionesEmpleado.php" class="menu-item">
            <img src="imagenes/Devoluciones.png" alt="Devoluciones" class="icon"> Devoluciones
        </a>
        <a href="QuejaSugerenciaEmpleado.php" class="menu-item">
            <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> Quejas / Sugerencias
        </a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout">
            <img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <h2>Bienvenido de nuevo, <?php echo htmlspecialchars($nombreCompleto); ?></h2>
        <div class="user-profile">
          <a href="EditarPerfilEmpleado.php">
            <img src="<?php echo htmlspecialchars($imagen); ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
          </div>
        </div>
      </header>

      <!-- Quick Actions -->
      <section class="quick-actions">
        <h3>Buscar Productos</h3>
        <div class="search-box">
          <input type="text" placeholder="Buscar ...">
          <button class="btn-primary">Buscar</button>
        </div>
      </section>

      <!-- KPI Section -->
      <section class="kpi-cards">
        <div class="card">
          <h3>Ventas Hoy</h3>
          <p>$<?php echo number_format($ventasHoy, 2); ?></p>
        </div>
        <div class="card">
          <h3>Pedidos Pendientes</h3>
          <p><?php echo $pedidosPendientes; ?></p>
        </div>
        <div class="card">
          <h3>Clientes Atendidos</h3>
          <p><?php echo $clientesAtendidos; ?></p>
        </div>
      </section>

      <!-- Widgets -->
      <section class="dashboard-widgets">
        <div class="widget">
          <h3>Últimas Ventas</h3>
          <ul>
            <?php if (count($ultimasVentas) > 0): ?>
              <?php foreach ($ultimasVentas as $venta): ?>
                <li><?php echo htmlspecialchars($venta['Producto']); ?> - $<?php echo number_format($venta['Subtotal'], 2); ?></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>No hay ventas registradas hoy.</li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="widget">
          <h3>Pedidos Recientes</h3>
          <ul>
            <?php if (count($pedidosRecientes) > 0): ?>
              <?php foreach ($pedidosRecientes as $pedido): ?>
                <li>#<?php echo $pedido['idPedido']; ?> - <?php echo htmlspecialchars($pedido['Estatus']); ?> (<?php echo $pedido['Fecha']; ?>)</li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>No hay pedidos recientes.</li>
            <?php endif; ?>
          </ul>
        </div>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
    
  </div>
</body>
</html>
