<?php
session_start();

// Verificar que el usuario esté logueado y sea administrador (rol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];
$mensaje = "";

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Obtener información del empleado
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Email, Telefono, Imagen
                        FROM Persona
                        WHERE idPersona = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $email, $telefono, $imagen);
$stmt->fetch();
$stmt->close();

// Si se envió actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarInfo'])) {

    $nombreNuevo = $_POST['Nombre'] ?? $nombre;
    $apellidoPNuevo = $_POST['ApellidoPaterno'] ?? $apellidoP;
    $apellidoMNuevo = $_POST['ApellidoMaterno'] ?? $apellidoM;
    $emailNuevo = $_POST['Email'] ?? $email;
    $telefonoNuevo = $_POST['Telefono'] ?? $telefono;
    $imagenNueva = $imagen;

    // Subir imagen si la hay
    if (isset($_FILES['Imagen']) && $_FILES['Imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['Imagen']['tmp_name'];
        $nombreArchivo = basename($_FILES['Imagen']['name']);
        $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $nuevoNombre = "empleado_" . $idPersona . "." . $ext;
            $carpeta = "Empleados/";

            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            $rutaFinal = $carpeta . $nuevoNombre;

            if (move_uploaded_file($tmp, $rutaFinal)) {
                $imagenNueva = $rutaFinal;
            } else {
                $mensaje = "Error al subir la imagen.";
            }
        } else {
            $mensaje = "Formato de imagen no permitido.";
        }
    }

    // Actualizar datos via procedimiento
    $stmt = $conn->prepare("CALL ActualizarPerfilEmpleado(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $idPersona, $nombreNuevo, $apellidoPNuevo, $apellidoMNuevo, $emailNuevo, $telefonoNuevo, $imagenNueva);

    if ($stmt->execute()) {
        $mensaje = "Perfil actualizado correctamente.";

        // Actualizar variables locales
        $nombre = $nombreNuevo;
        $apellidoP = $apellidoPNuevo;
        $apellidoM = $apellidoMNuevo;
        $email = $emailNuevo;
        $telefono = $telefonoNuevo;
        $imagen = $imagenNueva;

    } else {
        $mensaje = "Error al actualizar el perfil.";
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
<title>Perfil de Empleado</title>
<link rel="stylesheet" href="EditarPerfilEmpleado.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>

<body>

<!-- MENSAJE FLOTANTE -->
<?php if ($mensaje !== ""): ?>
<div class="alert-message show">
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
      <a href="InicioEmpleados.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon"> Inicio</a>
      <a href="CarritoEmpleado.php" class="menu-item"><img src="imagenes/Caja.png" class="icon"> Carrito</a>
      <a href="ListaProductosEmpleado.php" class="menu-item"><img src="imagenes/Productos.png" class="icon"> Productos</a>
      <a href="HistorialVentasEmpleado.php" class="menu-item"><img src="imagenes/Ventas.png" class="icon"> Historial Ventas</a>
      <a href="ListaPedidosEmpleado.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon"> Pedidos</a>
      <a href="ListaDevolucionesEmpleado.php" class="menu-item"><img src="imagenes/Devoluciones.png" class="icon"> Devoluciones</a>
      <a href="QuejaSugerenciaEmpleado.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas / Sugerencias</a>
      <div class="menu-separator"></div>
      <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon"> Cerrar sesión</a>
    </nav>
  </aside>

  <!-- Contenido principal -->
  <main class="main-content">

    <header class="topbar">
      <div class="user-profile">
        <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" class="avatar">
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars("$nombre $apellidoP $apellidoM"); ?></span>
          <span class="user-role">Empleado</span>
        </div>
      </div>
    </header>

    <section class="profile-section">
      <form class="profile-form" method="POST" enctype="multipart/form-data">
        <h2>Editar Información del Empleado</h2>

        <div class="profile-header">
          <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" class="profile-pic">
          <input type="file" name="Imagen" accept="image/*" class="btn-secondary">
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
