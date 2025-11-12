<?php
session_start();

// Asegurarse de que haya sesión activa
if (!isset($_SESSION['idPersona'])) {
    die("Error: No hay sesión activa. Inicia sesión nuevamente.");
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Filtrado por fecha si se envía por GET
$fechaFiltro = $_GET['fecha'] ?? null;

// Si se envía una solicitud POST para cancelar
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['idPedido'])) {
    $idPedido = intval($_POST['idPedido']);

    $stmtCancel = $conn->prepare("CALL CambiarPedidoACancelado(?)");
    $stmtCancel->bind_param("i", $idPedido);

    if ($stmtCancel->execute()) {
        $mensaje = "✅ Pedido #$idPedido cancelado exitosamente.";
    } else {
        $mensaje = "❌ Error al cancelar el pedido: " . $stmtCancel->error;
    }

    $stmtCancel->close();
}

// Datos del cliente
$stmtInfo = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM ClientesRegistrados WHERE idCliente = ?");
$stmtInfo->bind_param("i", $idPersona);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$cliente = $resultInfo->fetch_assoc();
$stmtInfo->close();

$nombreCompleto = $cliente ? $cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno'] : 'Cliente';
$rol = $cliente ? $cliente['Rol'] : 'Cliente';
$imagenPerfil = $cliente && $cliente['Imagen'] ? $cliente['Imagen'] : 'imagenes/User.png';

// Construir la consulta de pedidos con filtro opcional
$sql = "
    SELECT 
        idPedido,
        Fecha,
        Estatus,
        idProducto,
        Producto,
        Cantidad,
        PrecioUnitario,
        Total
    FROM PedidosCompletos
    WHERE idCliente = ?
";

$params = [$idPersona];
$tipos = "i";

if (!empty($fechaFiltro)) {
    $sql .= " AND DATE(Fecha) = ?";
    $params[] = $fechaFiltro;
    $tipos .= "s";
}

$sql .= " ORDER BY Fecha DESC, idPedido DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Agrupar los pedidos por ID
$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $idPedido = $row['idPedido'];
    if (!isset($pedidos[$idPedido])) {
        $pedidos[$idPedido] = [
            'Fecha' => $row['Fecha'],
            'Estatus' => $row['Estatus'],
            'Productos' => [],
            'TotalPedido' => 0
        ];
    }
    $pedidos[$idPedido]['Productos'][] = [
        'Producto' => $row['Producto'],
        'Cantidad' => $row['Cantidad'],
        'PrecioUnitario' => $row['PrecioUnitario'],
        'Total' => $row['Total']
    ];
    $pedidos[$idPedido]['TotalPedido'] += $row['Total'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Pedidos</title>
  <link rel="stylesheet" href="ListaPedidosCliente.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
  <style>
    .mensaje {
      background-color: #e6ffe6;
      border: 1px solid #4caf50;
      color: #2e7d32;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 15px;
      font-weight: bold;
    }
    .error {
      background-color: #ffe6e6;
      border: 1px solid #f44336;
      color: #b71c1c;
    }
  </style>
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
        <a href="InicioCliente.php" class="menu-item">
            <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
        </a>
        <a href="CarritoCliente.php" class="menu-item">
            <img src="imagenes/Carrito.png" alt="Carrito" class="icon"> Carrito
        </a>
        <a href="ListaProductosCliente.php" class="menu-item">
            <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
        </a>
        <a href="ListaPedidosCliente.php" class="menu-item active">
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
      <header class="topbar">

        <div class="search-box">
          <form method="GET" action="">
            <input type="date" name="fecha" value="<?= isset($fechaFiltro) ? htmlspecialchars($fechaFiltro) : '' ?>">
            <button type="submit" class="search-button">
              <img src="imagenes/Buscar.png" alt="Buscar" class="search-icon">
            </button>
          </form>
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

      <!-- Mensaje de acción -->
      <?php if (isset($mensaje)): ?>
        <div class="mensaje <?php echo strpos($mensaje, 'Error') !== false ? 'error' : ''; ?>">
          <?php echo htmlspecialchars($mensaje); ?>
        </div>
      <?php endif; ?>

      <!-- Sección de pedidos -->
      <section class="card-section">
        <?php if (empty($pedidos)): ?>
          <p style="text-align:center; color:gray;">No tienes pedidos registrados.</p>
        <?php else: ?>
          <?php foreach ($pedidos as $idPedido => $pedido): ?>
            <div class="card">
              <div class="card-info">
                <h3>Pedido #<?php echo htmlspecialchars($idPedido); ?></h3>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($pedido['Fecha']); ?></p>
                <p><strong>Estatus:</strong> <?php echo htmlspecialchars($pedido['Estatus']); ?></p>
                <hr>

                <ul>
                  <?php foreach ($pedido['Productos'] as $producto): ?>
                    <li>
                      <?php echo htmlspecialchars($producto['Producto']); ?> — 
                      <?php echo intval($producto['Cantidad']); ?> × 
                      $<?php echo number_format($producto['PrecioUnitario'], 2); ?> =
                      $<?php echo number_format($producto['Total'], 2); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>

                <hr>
                <p><strong>Total del pedido:</strong> $<?php echo number_format($pedido['TotalPedido'], 2); ?></p>

                <div class="card-actions">
                  <?php if (strtolower($pedido['Estatus']) === 'pendiente'): ?>
                    <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de cancelar este pedido?');">
                      <input type="hidden" name="idPedido" value="<?php echo $idPedido; ?>">
                      <button type="submit" class="btn-secondary">Cancelar</button>
                    </form>
                  <?php else: ?>
                    <span class="status <?php echo strtolower($pedido['Estatus']); ?>">
                      <?php echo ucfirst(strtolower($pedido['Estatus'])); ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>