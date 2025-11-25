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

$idAdmin = $_SESSION['idPersona'];

// Datos del administrador logueado
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol
                        FROM AdministradoresRegistrados
                        WHERE idAdministrador = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// Valores por defecto
$nombre = trim($nombre) ?: "Administrador";
$apellidoP = trim($apellidoP) ?: "";
$apellidoM = trim($apellidoM) ?: "";
$rol = $rol ?: "Administrador";
$imagen = $imagen ?: "imagenes/User.png";

// Notificaciones de inventario (NUEVO)
$resultadoNotificaciones = $conn->query("SELECT * FROM VistaNotificaciones");
$notificaciones = $resultadoNotificaciones ? $resultadoNotificaciones->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificaciones de Inventario</title>
  <link rel="stylesheet" href="AlertasAdministrador.css">
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
          <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> Quejas/Sugerencias
        </a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout">
          <img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <h2>Notificaciones de Inventario</h2>
        <div class="user-profile">
          <a href="ExportarProductosBajos.php">
            <img src="imagenes/Descargas.png" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?= htmlspecialchars($imagen) ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$nombre $apellidoP $apellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <!-- Notificaciones -->
      <section class="notifications-section">
        <?php if (!empty($notificaciones)): ?>
          <?php foreach ($notificaciones as $notif): ?>
            <div class="notification-card low-stock">

              <h3>Producto: <?= htmlspecialchars($notif['NombreProducto']) ?></h3>

              <p>Stock actual: <?= htmlspecialchars($notif['Existencia']) ?> unidades</p>
              <p>Mínimo permitido: <?= htmlspecialchars($notif['MinimoInventario']) ?> unidades</p>

              <p class="alert-message">
                <?= htmlspecialchars($notif['Mensaje']) ?>
              </p>

              <span class="alert">
                <?= ($notif['Existencia'] <= $notif['MinimoInventario']) ? '⚠️ Stock por debajo del mínimo' : '' ?>
              </span>

            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="notification-card">
            <h3>No hay notificaciones de inventario por el momento</h3>
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
