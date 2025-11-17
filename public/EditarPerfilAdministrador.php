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

$mensaje = "";

// Obtener datos del administrador desde la vista
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Email, Telefono, Imagen, Estado, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $email, $telefono, $imagen, $estado, $rol);
$stmt->fetch();
$stmt->close();

// Actualizar información mediante procedimiento almacenado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizarInfo'])) {

    $nombreNuevo = $_POST['Nombre'] ?? $nombre;
    $apellidoPNuevo = $_POST['ApellidoPaterno'] ?? $apellidoP;
    $apellidoMNuevo = $_POST['ApellidoMaterno'] ?? $apellidoM;
    $emailNuevo = $_POST['Email'] ?? $email;
    $telefonoNuevo = $_POST['Telefono'] ?? $telefono;
    $imagenNueva = $imagen;

    // Si se subió nueva foto
    if (isset($_FILES['Imagen']) && $_FILES['Imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['Imagen']['tmp_name'];
        $fileName = basename($_FILES['Imagen']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "admin_" . $idPersona . "." . $fileExt;

        $uploadDir = "Administradores/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $imagenNueva = $destPath;
        } else {
            $mensaje = "Error al subir la imagen.";
        }
    }

    // Llamar procedimiento almacenado
    $stmt = $conn->prepare("CALL ActualizarPerfilAdministrador(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $idPersona, $nombreNuevo, $apellidoPNuevo, $apellidoMNuevo, $emailNuevo, $telefonoNuevo, $imagenNueva);

    if ($stmt->execute()) {
        $nombre = $nombreNuevo;
        $apellidoP = $apellidoPNuevo;
        $apellidoM = $apellidoMNuevo;
        $email = $emailNuevo;
        $telefono = $telefonoNuevo;
        $imagen = $imagenNueva;
    } else {
        $mensaje = "Error al actualizar el perfil: " . $stmt->error;
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
<title>Perfil de Administrador</title>
<link rel="stylesheet" href="EditarPerfilAdministrador.css">
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
        <a href="ListaProductosAdministrador.php" class="menu-item active">
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
      <div class="user-profile">
        <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" alt="Avatar" class="avatar">
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars("$nombre $apellidoP $apellidoM"); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
        </div>
      </div>
    </header>

    <!-- Sección de perfil -->
    <section class="profile-section">
      <form class="profile-form" method="POST" action="" enctype="multipart/form-data">
        <h2>Editar Información del Administrador</h2>

        <?php if($mensaje != ""): ?>
          <p style="color:green;"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <div class="profile-header">
          <img src="<?php echo htmlspecialchars(($imagen ?: 'imagenes/User.png') . '?t=' . time()); ?>" alt="Foto de perfil" class="profile-pic">
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
