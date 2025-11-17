<?php
session_start();

// Verificar que el usuario esté logueado y sea administrador (rol = 1)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];

$stmtAdmin = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                             FROM AdministradoresRegistrados 
                             WHERE idAdministrador = ?");
$stmtAdmin->bind_param("i", $idAdmin);
$stmtAdmin->execute();
$stmtAdmin->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmtAdmin->fetch();
$stmtAdmin->close();

// Filtro de fecha si se envió
$fechaFiltro = $_GET['fecha'] ?? '';

// Obtener pedidos completos según fecha
if (!empty($fechaFiltro)) {
    $stmtPedidos = $conn->prepare("
        SELECT 
            dp.idPedido,
            dp.Usuario,
            dp.Fecha,
            dp.Estatus,
            GROUP_CONCAT(CONCAT(dp.Producto, ' x', dp.Cantidad) SEPARATOR ', ') AS Productos,
            SUM(dp.Total) AS Total
        FROM PedidosCompletos dp
        WHERE DATE(dp.Fecha) = ?
        GROUP BY dp.idPedido, dp.Usuario, dp.Fecha, dp.Estatus
        ORDER BY dp.Fecha DESC
    ");
    $stmtPedidos->bind_param("s", $fechaFiltro);
} else {
    // Si no se pasa fecha, traer todos los pedidos sin filtrar
    $stmtPedidos = $conn->prepare("
        SELECT 
            dp.idPedido,
            dp.Usuario,
            dp.Fecha,
            dp.Estatus,
            GROUP_CONCAT(CONCAT(dp.Producto, ' x', dp.Cantidad) SEPARATOR ', ') AS Productos,
            SUM(dp.Total) AS Total
        FROM PedidosCompletos dp
        GROUP BY dp.idPedido, dp.Usuario, dp.Fecha, dp.Estatus
        ORDER BY dp.Fecha DESC
    ");
}

$stmtPedidos->execute();
$resultPedidos = $stmtPedidos->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos</title>
  <link rel="stylesheet" href="ListaPedidosAdministrador.css">
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
        <a href="InicioAdministradores.php" class="menu-item">
          <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
        </a>
        <a href="ListaVentasAdministrador.php" class="menu-item">
          <img src="imagenes/Ventas.png" alt="Ventas" class="icon"> Ventas
        </a>
        <a href="ListaPedidosAdministrador.php" class="menu-item active">
          <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
        </a>
        <a href="ListaProductosAdministrador.php" class="menu-item">
          <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
        </a>
        <a href="ListaClientesAdministrado.php" class="menu-item">
          <img src="imagenes/Clientes.png" alt="Clientes" class="icon"> Clientes
        </a>
        <a href="ListaEmpleadosAdministrador.php" class="menu-item">
          <img src="imagenes/Empleados.png" alt="Empleados" class="icon"> Empleados
        </a>
        <a href="ListaDevolucionesAdministrador.php" class="menu-item">
          <img src="imagenes/Devoluciones.png" alt="Devoluciones" class="icon"> Devoluciones
        </a>
        <a href="FinanzasAdministrador.php" class="menu-item">
          <img src="imagenes/Finanzas.png" alt="Finanzas" class="icon"> Finanzas
        </a>
        <a href="Auditorias.php" class="menu-item">
          <img src="imagenes/Auditorias.png" alt="Auditorias" class="icon"> Control y Auditoría
        </a>
        <a href="QuejaSugerenciaAdministrador.php" class="menu-item">
          <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> QuejasSujerencias
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
          <form method="GET" action="">
            <input type="date" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
            <button type="submit" class="search-button">
              <img src="imagenes/Buscar.png" alt="Buscar" class="search-icon">
            </button>
          </form>
        </div>

        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?= htmlspecialchars($adminImagen ?: 'imagenes/User.png') ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$adminNombre $adminApellidoP $adminApellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($adminRol) ?></span>
          </div>
        </div>
      </header>

     <div class="table-actions">
      <!-- Exportar pedidos filtrados por fecha -->
      <form method="GET" action="ExportarListaPedidosFecha.php" style="display:inline;">
          <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
          <button type="submit" class="btn-primary">Exportar Pedidos</button>
      </form>

      <!-- Exportar todos los pedidos -->
      <form method="GET" action="ExportarListaTodosLosPedidos.php" style="display:inline;">
          <button type="submit" class="btn-secondary">Exportar Todos los Pedidos</button>
      </form>
    </div>

      <!-- Sección de Pedidos -->
      <section class="card-section">
        <?php if ($resultPedidos->num_rows > 0): ?>
            <?php while($pedido = $resultPedidos->fetch_assoc()): ?>
            <div class="card">
                <div class="card-info">
                    <h3>Pedido de: <span class="usuario-nombre"><?= htmlspecialchars($pedido['Usuario']) ?></span></h3>
                    <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['Fecha']) ?></p>
                    <p><strong>Productos:</strong></p>
                    <ul>
                        <?php 
                        $productos = explode(', ', $pedido['Productos']);
                        foreach($productos as $producto) {
                            echo "<li>" . htmlspecialchars($producto) . "</li>";
                        }
                        ?>
                    </ul>
                    <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>
                    <span class="status <?= strtolower($pedido['Estatus']) ?>"><?= htmlspecialchars($pedido['Estatus']) ?></span>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-pedidos">
                <p>No hay pedidos registrados.</p>
            </div>
        <?php endif; ?>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>
