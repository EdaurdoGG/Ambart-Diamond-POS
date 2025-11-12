<?php
session_start();

// Verificar sesión de administrador
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM AdministradoresRegistrados WHERE idAdministrador = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

$nombreCompleto = trim("$adminNombre $adminApellidoP $adminApellidoM");
if (empty($nombreCompleto)) $nombreCompleto = "Administrador desconocido";
if (empty($adminRol)) $adminRol = "Administrador";
if (empty($adminImagen)) $adminImagen = "imagenes/User.png";

// Obtener datos del usuario a editar
$idPersonaEdit = $_GET['id'] ?? 0;
$usuarioData = null;

if ($idPersonaEdit > 0) {
    $stmt = $conn->prepare("SELECT idPersona, Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, idRol FROM Persona WHERE idPersona = ?");
    $stmt->bind_param("i", $idPersonaEdit);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuarioData = $resultado->fetch_assoc();
    $stmt->close();
}

// ============================
// 📝 Procesar el formulario
// ============================
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPersona      = $_POST['idPersona'];
    $nombre         = $_POST['nombre'];
    $apellidoP      = $_POST['apellidoPaterno'];
    $apellidoM      = $_POST['apellidoMaterno'];
    $telefono       = $_POST['telefono'];
    $email          = $_POST['email'];
    $edad           = $_POST['edad'];
    $sexo           = $_POST['sexo'];
    $estatus        = $_POST['estatus'];
    $usuario        = $_POST['usuario'];
    $idRol          = $_POST['idRol'];

    $stmt = $conn->prepare("CALL ActualizarPersonaCompleto(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isssssssssi",
        $idPersona,
        $nombre,
        $apellidoP,
        $apellidoM,
        $telefono,
        $email,
        $edad,
        $sexo,
        $estatus,
        $usuario,
        $idRol
    );

    if ($stmt->execute()) {
        $mensaje = "Usuario actualizado correctamente ✅";
        // Recargar datos actualizados
        $stmt->close();
        $stmt = $conn->prepare("SELECT idPersona, Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, idRol FROM Persona WHERE idPersona = ?");
        $stmt->bind_param("i", $idPersona);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuarioData = $resultado->fetch_assoc();
        $stmt->close();
    } else {
        $mensaje = "Error al actualizar el usuario: " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Usuario</title>
  <link rel="stylesheet" href="EditarUsuarioAdministrador.css">
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

    <main class="main-content">
      <header class="topbar">
        <div class="search-box">
          <h2>Editar Usuario</h2>
        </div>
        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
           <img src="<?php echo htmlspecialchars($adminImagen); ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($adminRol); ?></span>
          </div>
        </div>
      </header>

      <!-- Formulario -->
      <section class="form-section">
        <?php if(!empty($mensaje)): ?>
          <div class="alert-message"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <form class="form-card" method="POST" action="">
          <input type="hidden" name="idPersona" value="<?php echo htmlspecialchars($usuarioData['idPersona'] ?? ''); ?>">

          <div class="form-group">
            <input type="text" id="nombre" name="nombre" placeholder=" " required value="<?php echo htmlspecialchars($usuarioData['Nombre'] ?? ''); ?>">
            <label for="nombre">Nombre</label>
          </div>

          <div class="form-group">
            <input type="text" id="apellidoPaterno" name="apellidoPaterno" placeholder=" " required value="<?php echo htmlspecialchars($usuarioData['ApellidoPaterno'] ?? ''); ?>">
            <label for="apellidoPaterno">Apellido Paterno</label>
          </div>

          <div class="form-group">
            <input type="text" id="apellidoMaterno" name="apellidoMaterno" placeholder=" " value="<?php echo htmlspecialchars($usuarioData['ApellidoMaterno'] ?? ''); ?>">
            <label for="apellidoMaterno">Apellido Materno</label>
          </div>

          <div class="form-group">
            <input type="tel" id="telefono" name="telefono" placeholder=" " value="<?php echo htmlspecialchars($usuarioData['Telefono'] ?? ''); ?>">
            <label for="telefono">Teléfono</label>
          </div>

          <div class="form-group">
            <input type="email" id="email" name="email" placeholder=" " required value="<?php echo htmlspecialchars($usuarioData['Email'] ?? ''); ?>">
            <label for="email">Correo electrónico</label>
          </div>

          <div class="form-group">
            <input type="number" id="edad" name="edad" placeholder=" " min="18" required value="<?php echo htmlspecialchars($usuarioData['Edad'] ?? ''); ?>">
            <label for="edad">Edad</label>
          </div>

          <div class="form-group">
            <select id="sexo" name="sexo" required>
              <option value="" disabled></option>
              <option value="M" <?php if(($usuarioData['Sexo'] ?? '')=='M') echo 'selected'; ?>>Masculino</option>
              <option value="F" <?php if(($usuarioData['Sexo'] ?? '')=='F') echo 'selected'; ?>>Femenino</option>
              <option value="Otro" <?php if(($usuarioData['Sexo'] ?? '')=='Otro') echo 'selected'; ?>>Otro</option>
            </select>
            <label for="sexo">Sexo</label>
          </div>

          <div class="form-group">
            <select id="estatus" name="estatus" required>
              <option value="" disabled></option>
              <option value="Activo" <?php if(($usuarioData['Estatus'] ?? '')=='Activo') echo 'selected'; ?>>Activo</option>
              <option value="Inactivo" <?php if(($usuarioData['Estatus'] ?? '')=='Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select>
            <label for="estatus">Estatus</label>
          </div>

          <div class="form-group">
            <input type="text" id="usuario" name="usuario" placeholder=" " required value="<?php echo htmlspecialchars($usuarioData['Usuario'] ?? ''); ?>">
            <label for="usuario">Usuario</label>
          </div>

          <div class="form-group">
            <select id="rol" name="idRol" required>
              <option value="" disabled></option>
              <option value="1" <?php if(($usuarioData['idRol'] ?? '')==1) echo 'selected'; ?>>Administrador</option>
              <option value="2" <?php if(($usuarioData['idRol'] ?? '')==2) echo 'selected'; ?>>Empleado</option>
              <option value="3" <?php if(($usuarioData['idRol'] ?? '')==3) echo 'selected'; ?>>Cliente</option>
            </select>
            <label for="rol">Rol</label>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">Actualizar Usuario</button>
          </div>
        </form>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>
