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

// Obtener información del administrador logueado
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// Usuarios activos (administradores, empleados y clientes activos)
$sqlUsuarios = "
SELECT COUNT(*) AS TotalActivos FROM (
    SELECT idAdministrador AS id FROM AdministradoresRegistrados WHERE Estado = 'Activo'
    UNION ALL
    SELECT idEmpleado AS id FROM EmpleadosRegistrados WHERE Estado = 'Activo'
    UNION ALL
    SELECT idCliente AS id FROM ClientesRegistrados WHERE Estado = 'Activo'
) AS UsuariosActivos";
$res = $conn->query($sqlUsuarios);
$usuariosActivos = $res->fetch_assoc()['TotalActivos'] ?? 0;

// Total de ventas del día actual
$sqlVentasDia = "
SELECT IFNULL(SUM(Total), 0) AS TotalDia
FROM VentasDiariasPorEmpleado
WHERE Fecha = CURDATE()";
$res = $conn->query($sqlVentasDia);
$ventasDia = $res->fetch_assoc()['TotalDia'] ?? 0;

// Inventario bajo
$sqlInvBajo = "SELECT COUNT(*) AS Bajo FROM VistaProductosBajoStock";
$res = $conn->query($sqlInvBajo);
$inventarioBajo = $res->fetch_assoc()['Bajo'] ?? 0;

// Pedidos pendientes
$sqlPedidosPend = "
SELECT COUNT(DISTINCT idPedido) AS Pendientes 
FROM PedidosCompletos 
WHERE Estatus = 'Pendiente'";
$res = $conn->query($sqlPedidosPend);
$pedidosPendientes = $res->fetch_assoc()['Pendientes'] ?? 0;

$sqlUltimas = "
SELECT Producto, Total 
FROM VentasDiariasPorEmpleado 
ORDER BY Fecha DESC, Hora DESC 
LIMIT 5";
$ultimasVentas = $conn->query($sqlUltimas);

$sqlCritico = "
SELECT Nombre, Existencia 
FROM Producto 
WHERE Existencia < 10 
ORDER BY Existencia ASC 
LIMIT 5";
$inventarioCritico = $conn->query($sqlCritico);

$conn->close();
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
        <img src="imagenes/Logo.png" alt="Logo" class="icon">
        <span>Amber Diamond</span>
      </div>
      <nav class="menu">
        <a href="InicioAdministradores.php" class="menu-item active">
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

    <!-- Contenido principal -->
    <main class="main-content">
      <!-- Barra superior -->
      <header class="topbar">
        <h2>Es un placer tenerte de vuelta</h2>
        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" 
                 alt="Avatar" class="avatar"> 
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
        <div class="card">
          <h3>Inventario Bajo</h3>
          <p><?php echo $inventarioBajo; ?> Productos</p>
        </div>
        <div class="card">
          <h3>Pedidos Pendientes</h3>
          <p><?php echo $pedidosPendientes; ?></p>
        </div>
      </section>

      <!-- Gráficos y actividad -->
      <section class="dashboard-widgets">
        <div class="widget">
          <h3>Últimas Ventas</h3>
          <ul>
            <?php while ($row = $ultimasVentas->fetch_assoc()): ?>
              <li><?php echo htmlspecialchars($row['Producto']); ?> - $<?php echo number_format($row['Total'], 2); ?></li>
            <?php endwhile; ?>
          </ul>
        </div>
        <div class="widget">
          <h3>Inventario Crítico (menos de 9 unidades)</h3>
          <ul>
            <?php while ($row = $inventarioCritico->fetch_assoc()): ?>
              <li><?php echo htmlspecialchars($row['Nombre']); ?> - <?php echo $row['Existencia']; ?> unidades</li>
            <?php endwhile; ?>
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
