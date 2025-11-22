<?php
session_start();

// Verificar que el usuario esté logueado y sea cliente (rol = 3)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Registrar id del usuario actual
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

/* =====================================================
       OBTENER INFORMACIÓN DEL CLIENTE
===================================================== */
$stmt = $conn->prepare("SELECT * FROM ClientesRegistrados WHERE idCliente = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $usuario = [
        'Nombre' => 'Invitado',
        'ApellidoPaterno' => '',
        'ApellidoMaterno' => '',
        'Imagen' => 'imagenes/User.png',
        'Rol' => 'Invitado'
    ];
} else {
    $usuario = $result->fetch_assoc();
}

$nombreCompleto = $usuario['Nombre'] . ' ' . $usuario['ApellidoPaterno'] . ' ' . $usuario['ApellidoMaterno'];
$imagenUsuario = !empty($usuario['Imagen']) ? $usuario['Imagen'] : 'imagenes/User.png';
$rolUsuario = $usuario['Rol'];

$conn->next_result();

/* =====================================================
       KPI: PEDIDOS ACTIVOS
===================================================== */
$sqlActivos = "
    SELECT COUNT(*) AS totalActivos
    FROM PedidosCompletos
    WHERE idCliente = ? AND Estatus = 'Activo';
";
$stmtActivos = $conn->prepare($sqlActivos);
$stmtActivos->bind_param("i", $idPersona);
$stmtActivos->execute();
$pedidosActivos = $stmtActivos->get_result()->fetch_assoc()['totalActivos'] ?? 0;

/* =====================================================
       KPI: TOTAL GASTADO
===================================================== */
$sqlGastado = "
    SELECT SUM(Total) AS totalGastado
    FROM PedidosCompletos
    WHERE idCliente = ? AND Estatus = 'Completado';
";
$stmtGastado = $conn->prepare($sqlGastado);
$stmtGastado->bind_param("i", $idPersona);
$stmtGastado->execute();
$totalGastado = $stmtGastado->get_result()->fetch_assoc()['totalGastado'] ?? 0;

/* =====================================================
       KPI: PRODUCTOS EN CARRITO
===================================================== */
$sqlCarrito = "
    SELECT COUNT(*) AS totalProductos
    FROM VistaCarritoPorPersona
    WHERE idPersona = ?;
";
$stmtCarrito = $conn->prepare($sqlCarrito);
$stmtCarrito->bind_param("i", $idPersona);
$stmtCarrito->execute();
$productosCarrito = $stmtCarrito->get_result()->fetch_assoc()['totalProductos'] ?? 0;

/* =====================================================
       ULTIMAS 7 COMPRAS (PEDIDOS COMPLETADOS)
===================================================== */
$sqlUltimasCompras = "
    SELECT idPedido, Producto, Total, Fecha
    FROM PedidosCompletos
    WHERE idCliente = ? AND Estatus = 'Completado'
    ORDER BY Fecha DESC
    LIMIT 7;
";
$stmtUltimas = $conn->prepare($sqlUltimasCompras);
$stmtUltimas->bind_param("i", $idPersona);
$stmtUltimas->execute();
$ultimasCompras = $stmtUltimas->get_result();

/* =====================================================
       ULTIMOS 3 PEDIDOS (PROMOCIONES ACTUALES)
===================================================== */
$sqlUltimosPedidos = "
    SELECT idPedido, Producto, Total, Fecha, Estatus
    FROM PedidosCompletos
    WHERE idCliente = ?
    ORDER BY Fecha DESC
    LIMIT 3;
";
$stmtPedidos = $conn->prepare($sqlUltimosPedidos);
$stmtPedidos->bind_param("i", $idPersona);
$stmtPedidos->execute();
$ultimosPedidos = $stmtPedidos->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Cliente - Papelería Online</title>
  <link rel="stylesheet" href="InicioCliente.css">
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
          <a href="InicioCliente.php" class="menu-item active">
              <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
          </a>
          <a href="CarritoCliente.php" class="menu-item">
              <img src="imagenes/Carrito.png" alt="Carrito" class="icon"> Carrito
          </a>
          <a href="ListaProductosCliente.php" class="menu-item">
              <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
          </a>
          <a href="ListaPedidosCliente.php" class="menu-item">
              <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
          </a>
          <a href="QuejaSugerenciaCliente.php" class="menu-item">
              <img src="imagenes/QuejasSujerencias.png" alt="Quejas" class="icon"> Quejas / Sugerencias
          </a>
          <div class="menu-separator"></div>
          <a href="Login.php" class="menu-item logout">
              <img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión
          </a>
      </nav>
    </aside>

    <!-- Contenido principal -->
    <main class="main-content">

      <!-- Topbar -->
      <header class="topbar">
        <h2>Bienvenido de nuevo, <span style="color:#985008;"><?php echo htmlspecialchars($usuario['Nombre']); ?></span></h2>
        <div class="user-profile">
          <a href="EditarPerfilCliente.php">
            <img src="<?php echo htmlspecialchars($imagenUsuario); ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
          </div>
        </div>
      </header>

      <!-- KPI Cards -->
      <section class="kpi-cards">
        <div class="card">
          <h3>Pedidos Activos</h3>
          <p><?php echo $pedidosActivos; ?></p>
        </div>

        <div class="card">
          <h3>Productos en Carrito</h3>
          <p><?php echo $productosCarrito; ?></p>
        </div>

        <div class="card">
          <h3>Total Gastado</h3>
          <p>$<?php echo number_format($totalGastado, 2); ?></p>
        </div>
      </section>

      <!-- Widgets -->
      <section class="dashboard-widgets">

        <!-- Últimas compras -->
        <div class="widget">
          <h3>Últimas Compras</h3>
          <ul>
            <?php while ($row = $ultimasCompras->fetch_assoc()): ?>
              <li>
                <?php echo $row['Producto']; ?> - $<?php echo number_format($row['Total'], 2); ?> 
                <br><small><?php echo $row['Fecha']; ?></small>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>

        <!-- Últimos pedidos -->
        <div class="widget">
          <h3>Pedidos Recientes</h3>
          <ul>
            <?php while ($row = $ultimosPedidos->fetch_assoc()): ?>
              <li>
                Pedido #<?php echo $row['idPedido']; ?> - <?php echo $row['Producto']; ?>
                <br><strong>Estatus:</strong> <?php echo $row['Estatus']; ?>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>

      </section>

      <!-- Footer -->
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>

    </main>
  </div>
</body>
</html>
