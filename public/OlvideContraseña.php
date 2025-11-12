<?php
session_start(); // Iniciar sesión por si se usa idPersona en auditoría

// Procesar el formulario al enviar
if (isset($_POST['recuperar'])) {
    $nombre = trim($_POST['nombre']);
    $apellidoP = trim($_POST['apellidoPaterno']);
    $apellidoM = trim($_POST['apellidoMaterno']);
    $email = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $nuevaContrasena = $_POST['nuevaContrasena'];

    // Validar contraseña: mínimo 10 caracteres, 3 números, 2 especiales
    if (!preg_match('/^(?=(?:.*\d){3,})(?=(?:.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]){2,}).{10,}$/', $nuevaContrasena)) {
        $mensaje = "<p style='color:red;'>La contraseña debe tener al menos 10 caracteres, 3 números y 2 caracteres especiales.</p>";
    } else {

        // Conexión
        require_once "../includes/conexion.php";

        // (Opcional) asignar variable de sesión para auditoría, si aplica
        if (isset($_SESSION['idPersona'])) {
            $conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));
        }

        // Generar hash seguro de la nueva contraseña
        $hashSeguro = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

        // Llamar al procedimiento almacenado
        $stmt = $conn->prepare("CALL CambiarContrasenaValidada(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $nombre, $apellidoP, $apellidoM, $email, $usuario, $hashSeguro);

        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;'>Contraseña actualizada correctamente. Ahora puedes iniciar sesión.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error: No se pudo actualizar la contraseña. Verifica tus datos.</p>";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña</title>
  <link rel="stylesheet" href="OlvideContraseña.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>
  <div class="container">
    <!-- Lado izquierdo -->
    <div class="signin">
      <h2>Recuperar Contraseña</h2>

      <?php if(isset($mensaje)) echo $mensaje; ?>

      <form action="" method="post" class="recovery-form">
        <div class="input-group">
          <input type="text" name="nombre" placeholder=" " required>
          <label>Nombre</label>
        </div>
        <div class="input-group">
          <input type="text" name="apellidoPaterno" placeholder=" " required>
          <label>Apellido Paterno</label>
        </div>
        <div class="input-group">
          <input type="text" name="apellidoMaterno" placeholder=" " required>
          <label>Apellido Materno</label>
        </div>
        <div class="input-group">
          <input type="email" name="email" placeholder=" " required>
          <label>Correo</label>
        </div>
        <div class="input-group">
          <input type="text" name="usuario" placeholder=" " required>
          <label>Usuario</label>
        </div>

        <!-- Campo de contraseña con ícono funcional 👁️ -->
        <div class="input-group">
          <input type="password" name="nuevaContrasena" id="nuevaContrasena" placeholder=" " required>
          <label>Nueva Contraseña</label>
          <img src="Imagenes/eye-open.png" id="togglePassword" class="toggle-password" alt="Mostrar/Ocultar contraseña">
        </div>

        <button type="submit" name="recuperar">Actualizar Contraseña</button>
      </form>

      <a href="Login.php">Volver al inicio de sesión</a>
    </div>

    <!-- Lado derecho decorativo -->
    <div class="signup-side">
      <img src="Imagenes/logo.png" alt="Logo" class="signup-img">
      <h2>¡No te preocupes!</h2>
      <p>Recupera tu contraseña de manera rápida y segura. Solo necesitas tus datos y una nueva contraseña segura.</p>
    </div>
  </div>

  <!-- ✅ Script funcional para mostrar/ocultar contraseña -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const passwordInput = document.getElementById('nuevaContrasena');
      const toggleImg = document.getElementById('togglePassword');

      toggleImg.addEventListener('click', function() {
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          toggleImg.src = "Imagenes/eye-closed.png"; // 👁️ Ojo cerrado
        } else {
          passwordInput.type = "password";
          toggleImg.src = "Imagenes/eye-open.png"; // 👁️ Ojo abierto
        }
      });
    });
  </script>
  <footer class="site-footer">
    <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
  </footer>
</body>
</html>
