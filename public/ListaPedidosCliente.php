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

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Filtrado por fecha si se envía por GET
$fechaFiltro = $_GET['fecha'] ?? null;

$mensajeFlotante = ""; // <-- Nuevo: mensaje flotante

// Si se envía una solicitud POST para cancelar
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['idPedido'])) {
    $idPedido = intval($_POST['idPedido']);

    $stmtCancel = $conn->prepare("CALL CambiarPedidoACancelado(?)");
    $stmtCancel->bind_param("i", $idPedido);

    if ($stmtCancel->execute()) {
        $mensajeFlotante = "Pedido #$idPedido cancelado exitosamente.";
    } else {
        $mensajeFlotante = "Error al cancelar el pedido.";
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

// Si buscó por fecha y no hubo resultados → mensaje flotante
if ($fechaFiltro && empty($pedidos)) {
    $mensajeFlotante = "No se encontraron pedidos en esa fecha.";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Pedidos</title>
  <link rel="stylesheet" href="ListaPedidosCliente.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
<body>

<!-- MENSAJE FLOTANTE -->
<?php if (!empty($mensajeFlotante)): ?>
<div id="alerta" class="alert-message"><?= htmlspecialchars($mensajeFlotante) ?></div>
<script>
    const alerta = document.getElementById('alerta');
    alerta.classList.add('show');
    setTimeout(() => {
        alerta.classList.remove('show');
    }, 3000);
</script>
<?php endif; ?>


  <div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="imagenes/Logo.png" alt="Logo" class="icon">
        <span>Amber Diamond</span>
      </div>

      <nav class="menu">
        <a href="InicioCliente.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon"> Inicio</a>
        <a href="CarritoCliente.php" class="menu-item"><img src="imagenes/Carrito.png" class="icon"> Carrito</a>
        <a href="ListaProductosCliente.php" class="menu-item"><img src="imagenes/Productos.png" class="icon"> Productos</a>
        <a href="ListaPedidosCliente.php" class="menu-item active"><img src="imagenes/Pedidos.png" class="icon"> Pedidos</a>
        <a href="QuejaSugerenciaCliente.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas / Sugerencias</a>

        <div class="menu-separator"></div>

        <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon"> Cerrar sesión</a>
      </nav>
    </aside>

    <!-- Contenido principal -->
    <main class="main-content">

      <header class="topbar">
        <div class="search-box">
          <form method="GET" action="">
            <input type="date" name="fecha" value="<?= isset($fechaFiltro) ? htmlspecialchars($fechaFiltro) : '' ?>">
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

      <!-- Sección de pedidos -->
      <section class="card-section">
        <?php if (empty($pedidos)): ?>
          <p style="text-align:center; color:gray;">No tienes pedidos registrados.</p>
        <?php else: ?>
          <?php foreach ($pedidos as $idPedido => $pedido): ?>
            <div class="card">
              <div class="card-info">
                <h3>Pedido #<?= htmlspecialchars($idPedido) ?></h3>
                <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['Fecha']) ?></p>
                <p><strong>Estatus:</strong> <?= htmlspecialchars($pedido['Estatus']) ?></p>
                <hr>

                <ul>
                  <?php foreach ($pedido['Productos'] as $producto): ?>
                    <li>
                      <?= htmlspecialchars($producto['Producto']) ?> — 
                      <?= intval($producto['Cantidad']) ?> × 
                      $<?= number_format($producto['PrecioUnitario'], 2) ?> =
                      $<?= number_format($producto['Total'], 2) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>

                <hr>
                <p><strong>Total del pedido:</strong> $<?= number_format($pedido['TotalPedido'], 2) ?></p>

                <div class="card-actions">
                  <?php if (strtolower($pedido['Estatus']) === 'pendiente'): ?>
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de cancelar este pedido?');">
                      <input type="hidden" name="idPedido" value="<?= $idPedido ?>">
                      <button type="submit" class="btn-secondary">Cancelar</button>
                    </form>
                  <?php else: ?>
                    <span class="status <?= strtolower($pedido['Estatus']) ?>">
                      <?= ucfirst(strtolower($pedido['Estatus'])) ?>
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
