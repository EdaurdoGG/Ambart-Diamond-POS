<?php
session_start();

// Verificar administrador
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Helper para consultas
function safe_query($conn, $sql) {
    $res = $conn->query($sql);
    if ($res === false) {
        error_log("MySQL ERROR: (" . $conn->errno . ") " . $conn->error . " -- SQL: " . $sql);
        return null;
    }
    return $res;
}

$conn->query("SET @id_usuario_actual = " . intval($idPersona));

/* ============================
   DATOS DEL ADMINISTRADOR
============================ */
$stmt = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM AdministradoresRegistrados 
    WHERE idAdministrador = ?
");

if (!$stmt) {
    $nombre="Administrador"; $apellidoP=""; $apellidoM=""; $imagen="imagenes/User.png"; $rol="Administrador";
} else {
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rol);
    $stmt->fetch();
    $stmt->close();

    $nombre = trim($nombre) ?: "Administrador";
    $apellidoP = trim($apellidoP) ?: "";
    $apellidoM = trim($apellidoM) ?: "";
    $rol = $rol ?: "Administrador";
    $imagen = $imagen ?: "imagenes/User.png";
}

/* ============================
   USUARIOS ACTIVOS
============================ */
$sqlUsuarios = "
SELECT COUNT(*) AS TotalActivos FROM (
    SELECT idAdministrador FROM AdministradoresRegistrados WHERE Estado = 'Activo'
    UNION ALL
    SELECT idEmpleado FROM EmpleadosRegistrados WHERE Estado = 'Activo'
    UNION ALL
    SELECT idCliente FROM ClientesRegistrados WHERE Estado = 'Activo'
) AS UsuariosActivos";

$res = safe_query($conn, $sqlUsuarios);
$usuariosActivos = ($res && $row=$res->fetch_assoc()) ? (int)$row['TotalActivos'] : 0;

/* ============================
   VENTAS DEL DÍA
============================ */
$sqlVentasDia = "
SELECT IFNULL(SUM(Total), 0) AS TotalDia
FROM VentasDiariasPorEmpleado
WHERE Fecha = CURDATE()
";

$res = safe_query($conn, $sqlVentasDia);
$ventasDia = ($res && $row=$res->fetch_assoc()) ? (float)$row['TotalDia'] : 0;

/* ============================
   INVENTARIO BAJO/CRÍTICO (KPI)
   CORREGIDO
============================ */
$sqlInvBajo = "
SELECT COUNT(*) AS TotalBajoCritico
FROM VistaNotificaciones
WHERE Existencia <= MinimoInventario
";

$res = safe_query($conn, $sqlInvBajo);
$inventarioBajo = ($res && $row=$res->fetch_assoc()) ? (int)$row['TotalBajoCritico'] : 0;

/* ============================
   PEDIDOS PENDIENTES
============================ */
$sqlPedidosPend = "
SELECT COUNT(DISTINCT idPedido) AS Pendientes
FROM PedidosCompletos
WHERE Estatus = 'Pendiente'
";

$res = safe_query($conn, $sqlPedidosPend);
$pedidosPendientes = ($res && $row=$res->fetch_assoc()) ? (int)$row['Pendientes'] : 0;

/* ============================
   ÚLTIMAS 5 VENTAS
============================ */
$sqlUltimas = "
SELECT Producto, Total
FROM VentasDiariasPorEmpleado
ORDER BY Fecha DESC, Hora DESC
LIMIT 5
";
$ultimasVentas = safe_query($conn, $sqlUltimas);

/* ============================
   INVENTARIO BAJO + CRÍTICO (LISTA)
============================ */
$sqlCritico = "
SELECT NombreProducto, Existencia, MinimoInventario
FROM VistaNotificaciones
WHERE Existencia <= MinimoInventario
ORDER BY Existencia ASC
LIMIT 5
";

$inventarioCritico = safe_query($conn, $sqlCritico);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Administrador</title>
  <link rel="stylesheet" href="InicioAdministradores.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>

<body>
  <div class="dashboard-container">

    <!-- Menú lateral -->
    <aside class="sidebar">
      <div class="logo">
        <img src="imagenes/Logo.png" class="icon">
        <span>Amber Diamond</span>
      </div>
      <nav class="menu">
        <a href="InicioAdministradores.php" class="menu-item active">
          <img src="imagenes/Inicio.png" class="icon"> Inicio
        </a>
        <a href="ListaVentasAdministrador.php" class="menu-item">
          <img src="imagenes/Ventas.png" class="icon"> Ventas
        </a>
        <a href="ListaPedidosAdministrador.php" class="menu-item">
          <img src="imagenes/Pedidos.png" class="icon"> Pedidos
        </a>
        <a href="ListaProductosAdministrador.php" class="menu-item">
          <img src="imagenes/Productos.png" class="icon"> Productos
        </a>
        <a href="ListaClientesAdministrado.php" class="menu-item">
          <img src="imagenes/Clientes.png" class="icon"> Clientes
        </a>
        <a href="ListaEmpleadosAdministrador.php" class="menu-item">
          <img src="imagenes/Empleados.png" class="icon"> Empleados
        </a>
        <a href="ListaDevolucionesAdministrador.php" class="menu-item">
          <img src="imagenes/Devoluciones.png" class="icon"> Devoluciones
        </a>
        <a href="FinanzasAdministrador.php" class="menu-item">
          <img src="imagenes/Finanzas.png" class="icon"> Finanzas
        </a>
        <a href="Auditorias.php" class="menu-item">
          <img src="imagenes/Auditorias.png" class="icon"> Control y Auditoría
        </a>
        <a href="QuejaSugerenciaAdministrador.php" class="menu-item">
          <img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas/Sugerencias
        </a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout">
          <img src="imagenes/salir.png" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

      <header class="topbar">
        <h2>Es un placer tenerte de vuelta</h2>
        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?php echo htmlspecialchars($imagen . '?t=' . time()); ?>" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars("$nombre $apellidoP $apellidoM"); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
          </div>
        </div>
      </header>

      <!-- KPIs -->
      <section class="kpi-cards">

        <div class="card">
          <h3>Usuarios Activos</h3>
          <p><?php echo $usuariosActivos; ?></p>
        </div>

        <div class="card">
          <h3>Ventas del Día</h3>
          <p>$<?php echo number_format($ventasDia, 2); ?></p>
        </div>

        <!-- INVENTARIO BAJO/CRITICO -->
        <div class="card <?php echo ($inventarioBajo > 0 ? 'alert-red' : ''); ?>">
          <h3>Inventario Bajo/Crítico</h3>
          <p><?php echo $inventarioBajo; ?> Productos</p>
        </div>

        <div class="card">
          <h3>Pedidos Pendientes</h3>
          <p><?php echo $pedidosPendientes; ?></p>
        </div>

      </section>

      <!-- Widgets -->
      <section class="dashboard-widgets">

        <!-- Últimas ventas -->
        <div class="widget">
          <h3>Últimas Ventas</h3>
          <ul>
            <?php if ($ultimasVentas && $ultimasVentas->num_rows > 0): ?>
              <?php while ($row = $ultimasVentas->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($row['Producto']); ?> - $<?php echo number_format($row['Total'], 2); ?></li>
              <?php endwhile; ?>
            <?php else: ?>
              <li>No hay ventas recientes</li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Inventario Crítico/Bajo -->
        <div class="widget">
          <h3>Inventario Bajo/Crítico</h3>
          <ul>
            <?php if ($inventarioCritico && $inventarioCritico->num_rows > 0): ?>

              <?php while ($row = $inventarioCritico->fetch_assoc()): ?>
                <li>
                  <strong><?php echo htmlspecialchars($row['NombreProducto']); ?></strong> —
                  <?php echo $row['Existencia']; ?> unidades
                  (<?php echo ($row['Existencia'] < $row['MinimoInventario']) ? "Crítico" : "Bajo"; ?>)
                </li>
              <?php endwhile; ?>

            <?php else: ?>
              <li>No hay productos críticos ni bajos</li>
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
