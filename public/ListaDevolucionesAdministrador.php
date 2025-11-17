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

// Obtener datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
if (!$stmt) die("Error en prepare(): " . $conn->error);
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

// Filtro de fecha si se envió
$fechaFiltro = $_GET['fecha'] ?? '';

// Preparar consulta de devoluciones con filtro opcional
$sqlDevoluciones = "
SELECT 
    d.idDevolucion,
    d.Usuario,
    d.Fecha,
    d.Motivo,
    dd.idDetalleDevolucion,
    dd.idVenta,
    dd.idDetalleVenta,
    dd.CantidadDevuelta,
    dd.TotalDevuelto,
    p.Nombre AS NombreProducto
FROM DevolucionesRealizadas d
JOIN DetalleDevolucion dd ON d.idDevolucion = dd.idDevolucion
JOIN DetalleVenta dv ON dd.idDetalleVenta = dv.idDetalleVenta
JOIN Producto p ON dv.idProducto = p.idProducto
";

$params = [];
$types = "";
if (!empty($fechaFiltro)) {
    $sqlDevoluciones .= " WHERE DATE(d.Fecha) = ?";
    $params[] = $fechaFiltro;
    $types .= "s";
}

$sqlDevoluciones .= " ORDER BY d.Fecha DESC";

// Preparar statement
$stmtDevoluciones = $conn->prepare($sqlDevoluciones);
if (!$stmtDevoluciones) die("Error en prepare(): " . $conn->error);

// Vincular parámetros si hay filtro
if (!empty($params)) {
    $stmtDevoluciones->bind_param($types, ...$params);
}

$stmtDevoluciones->execute();
$resultDevoluciones = $stmtDevoluciones->get_result();

// Organizar devoluciones por idDevolucion
$devoluciones = [];
if ($resultDevoluciones && $resultDevoluciones->num_rows > 0) {
    while ($row = $resultDevoluciones->fetch_assoc()) {
        $id = $row['idDevolucion'];
        if (!isset($devoluciones[$id])) {
            $devoluciones[$id] = [
                'Usuario' => $row['Usuario'],  // <-- nombre del usuario
                'Fecha' => $row['Fecha'],
                'Motivo' => $row['Motivo'],
                'Productos' => [],
                'TotalDevuelto' => 0
            ];
        }
        $devoluciones[$id]['Productos'][] = $row['NombreProducto'] . " x" . $row['CantidadDevuelta'];
        $devoluciones[$id]['TotalDevuelto'] += $row['TotalDevuelto'];
    }
}
$stmtDevoluciones->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Devoluciones</title>
  <link rel="stylesheet" href="ListaDevolucionesAdministrador.css">
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
        <a href="ListaPedidosAdministrador.php" class="menu-item">
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
        <a href="ListaDevolucionesAdministrador.php" class="menu-item active">
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

      <!-- Sección de acciones -->
      <div class="table-actions">
          <!-- Botón para exportar devoluciones filtradas por fecha -->
          <form method="GET" action="ExportarDevolucionesPorFecha.php" style="display:inline;">
              <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
              <button type="submit" class="btn-primary">Exportar Devoluciones</button>
          </form>

          <!-- Botón para exportar todas las devoluciones -->
          <form method="GET" action="ExportarTodasLasDevoluciones.php" style="display:inline;">
              <button type="submit" class="btn-secondary">Exportar Todas las Devoluciones</button>
          </form>
      </div>

      <!-- Sección de Devoluciones -->
      <section class="card-section">
        <div class="card-list">
          <?php if (!empty($devoluciones)): ?>
            <?php foreach ($devoluciones as $id => $devolucion): ?>
              <div class="card">
                <div class="card-info">
                  <h3>Devolución de: <span class="usuario-nombre"><?= htmlspecialchars($devolucion['Usuario']) ?></span></h3>
                  <p><strong>Fecha:</strong> <?= htmlspecialchars($devolucion['Fecha']) ?></p>
                  <p><strong>Productos devueltos:</strong></p>
                  <ul>
                    <?php foreach ($devolucion['Productos'] as $prod): ?>
                      <li><?= htmlspecialchars($prod) ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <p><strong>Motivo:</strong> <?= htmlspecialchars($devolucion['Motivo']) ?></p>
                  <p><strong>Total devuelto:</strong> $<?= number_format($devolucion['TotalDevuelto'],2) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No hay devoluciones registradas.</p>
          <?php endif; ?>
        </div>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>

<?php
$conn->close();
?>
