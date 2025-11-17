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

// Obtener datos del administrador logueado desde la vista
$idAdmin = $_SESSION['idPersona'];

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
if (!$stmt) {
    die("Error en prepare(): " . $conn->error);
}

$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

// Obtener todos los clientes desde la vista
$sqlClientes = "SELECT idCliente, Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Imagen, Estado
                FROM ClientesRegistrados";
$resultClientes = $conn->query($sqlClientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Clientes</title>
  <link rel="stylesheet" href="ListaClientesAdministrado.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>
  <div class="dashboard-container">
    <!-- Barra lateral -->
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
        <a href="ListaClientesAdministrado.php" class="menu-item active">
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
        <div class="search-box">
          <input type="text" placeholder="Buscar ...">
          <img src="imagenes/Buscar.png" alt="Buscar" class="icon search-icon">
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

      <!-- Tabla de clientes -->
      <section class="user-table-section">

        <div class="table-actions">
          <form action="ExportarListaTodosLosClientes.php" method="GET" style="display:inline;">
              <button type="submit" class="btn-primary">Exportar Todos</button>
          </form>

          <form action="ExportarListaClientesActivos.php" method="GET" style="display:inline;">
              <button type="submit" class="btn-secondary">Exportar Activos</button>
          </form>
          <form action="ExportarListaClientesInactivos.php" method="GET" style="display:inline;">
              <button type="submit" class="btn-secondary">Exportar Inactivos</button>
          </form>
        </div>

        <div class="table-meta">
          <span>Total Clientes: <?= $resultClientes ? $resultClientes->num_rows : 0 ?></span>
        </div>

        <table class="user-table">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nombre completo</th>
              <th>Teléfono</th>
              <th>Email</th>
              <th>Edad</th>
              <th>Sexo</th>
              <th>Estatus</th>
              <th>Operaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($resultClientes && $resultClientes->num_rows > 0): ?>
              <?php while($cliente = $resultClientes->fetch_assoc()): ?>
                <tr>
                  <td><img src="<?= htmlspecialchars($cliente['Imagen'] ?: 'Usuarios/user.png') ?>" alt="<?= htmlspecialchars($cliente['Nombre']) ?>" class="table-avatar"></td>
                  <td><?= htmlspecialchars($cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno']) ?></td>
                  <td><?= htmlspecialchars($cliente['Telefono']) ?></td>
                  <td><?= htmlspecialchars($cliente['Email']) ?></td>
                  <td><?= htmlspecialchars($cliente['Edad']) ?></td>
                  <td><?= htmlspecialchars($cliente['Sexo']) ?></td>
                  <td><span class="status <?= $cliente['Estado'] === 'Activo' ? 'active' : 'inactive' ?>"><?= htmlspecialchars($cliente['Estado']) ?></span></td>
                  <td>
                    <a href="EditarUsuarioAdministrador.php?id=<?= $cliente['idCliente'] ?>">
                     <img src="imagenes/Editar.png" alt="Edit" class="action-icon" title="Editar"> 
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8">No hay clientes registrados.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
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
