<?php
session_start();

// Verificar que el usuario esté logueado y sea administrador (rol = 3)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];
$mensaje = "";
$error = false;

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

//  Obtener datos del usuario desde la vista
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Email, Telefono, Imagen, Rol 
                        FROM ClientesRegistrados 
                        WHERE idCliente = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $email, $telefono, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// Actualizar información del usuario mediante procedimiento almacenado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarInfo'])) {

    $nombreNuevo = $_POST['Nombre'] ?? $nombre;
    $apellidoPNuevo = $_POST['ApellidoPaterno'] ?? $apellidoP;
    $apellidoMNuevo = $_POST['ApellidoMaterno'] ?? $apellidoM;
    $emailNuevo = $_POST['Email'] ?? $email;
    $telefonoNuevo = $_POST['Telefono'] ?? $telefono;
    $imagenNueva = $imagen;

    // Si se subió nueva foto
    if (isset($_FILES['Imagen']) && $_FILES['Imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['Imagen']['tmp_name'];
        $fileName = basename($_FILES['Imagen']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "perfil_" . $idPersona . "." . $ext;

        $uploadDir = "Usuarios/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($tmp, $destPath)) {
            $imagenNueva = $destPath;
        } else {
            $mensaje = "Error al subir la imagen.";
            $error = true;
        }
    }

    // Actualizar información en BD
    $stmt = $conn->prepare("CALL ActualizarPerfilCliente(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", 
        $idPersona, 
        $nombreNuevo, 
        $apellidoPNuevo, 
        $apellidoMNuevo, 
        $emailNuevo, 
        $telefonoNuevo, 
        $imagenNueva
    );

    if ($stmt->execute()) {
        // Actualizar variables locales
        $nombre = $nombreNuevo;
        $apellidoP = $apellidoPNuevo;
        $apellidoM = $apellidoMNuevo;
        $email = $emailNuevo;
        $telefono = $telefonoNuevo;
        $imagen = $imagenNueva;

        $mensaje = "Perfil actualizado correctamente.";
        $error = false;
    } else {
        $mensaje = "Error al actualizar el perfil.";
        $error = true;
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil de Usuario</title>
<link rel="stylesheet" href="EditarPerfilCliente.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>

<body>

<?php if ($mensaje !== ""): ?>
<div class="alert-message show <?php echo $error ? 'error' : 'success'; ?>">
    <?php echo htmlspecialchars($mensaje); ?>
</div>
<?php endif; ?>

<script>
setTimeout(() => {
    const msg = document.querySelector(".alert-message");
    if (msg) msg.style.opacity = "0";
}, 3000);
</script>

<div class="dashboard-container">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <img src="imagenes/Logo.png" class="icon">
      <span>Amber Diamond</span>
    </div>

    <nav class="menu">
      <a href="InicioCliente.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon"> Inicio</a>
      <a href="CarritoCliente.php" class="menu-item"><img src="imagenes/Carrito.png" class="icon"> Carrito</a>
      <a href="ListaProductosCliente.php" class="menu-item"><img src="imagenes/Productos.png" class="icon"> Productos</a>
      <a href="ListaPedidosCliente.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon"> Pedidos</a>
      <a href="QuejaSugerenciaCliente.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas / Sugerencias</a>
      <div class="menu-separator"></div>
      <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon"> Cerrar sesión</a>
    </nav>
  </aside>

  <!-- Contenido principal -->
  <main class="main-content">

    <header class="topbar">
      <div class="user-profile">
        <a href="EditarPerfilCliente.php">
          <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" class="avatar">
        </a>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars("$nombre $apellidoP $apellidoM"); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
        </div>
      </div>
    </header>

    <!-- Perfil -->
    <section class="profile-section">

      <form class="profile-form" method="POST" enctype="multipart/form-data">
        <h2>Editar Información del Usuario</h2>

        <div class="profile-header">
          <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" class="profile-pic">
          <input type="file" class="btn-secondary" name="Imagen" accept="image/*">
        </div>

        <div class="form-group">
          <label>Nombre</label>
          <input type="text" name="Nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
        </div>

        <div class="form-group">
          <label>Apellido Paterno</label>
          <input type="text" name="ApellidoPaterno" value="<?php echo htmlspecialchars($apellidoP); ?>" required>
        </div>

        <div class="form-group">
          <label>Apellido Materno</label>
          <input type="text" name="ApellidoMaterno" value="<?php echo htmlspecialchars($apellidoM); ?>">
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="Email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" name="Telefono" value="<?php echo htmlspecialchars($telefono); ?>">
        </div>

        <button class="btn-primary" type="submit" name="actualizarInfo">Actualizar Información</button>
      </form>

    </section>

    <footer class="site-footer">
      <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
    </footer>

  </main>

</div>
</body>
</html>
