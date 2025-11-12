<?php
session_start(); // Inicia sesión para manejar roles

$mensaje = "";

// Mostrar errores (solo para desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['Email'];
    $contrasena = $_POST['Contrasena'];

    // Conexión
    require_once "../includes/conexion.php";

    // Buscar usuario
    $stmt = $conn->prepare("SELECT idPersona, Usuario, Contrasena, idRol FROM Persona WHERE Email = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    // Asignar el id del usuario logueado a la variable @id_usuario_actual
    if (isset($_SESSION['idPersona'])) {
        $conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));
    }

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($idPersonaDB, $usuarioDB, $hashDB, $rol);
        $stmt->fetch();

        if (password_verify($contrasena, $hashDB)) {
            // Login correcto
            $_SESSION['idPersona'] = $idPersonaDB;
            $_SESSION['usuario'] = $usuarioDB;
            $_SESSION['rol'] = $rol;

            // Redireccionar según rol
            switch ($rol) {
                case 1: // Administrador
                    header("Location: InicioAdministradores.php");
                    exit();
                case 2: // Empleado
                    header("Location: InicioEmpleados.php");
                    exit();
                case 3: // Cliente
                    header("Location: InicioCliente.php");
                    exit();
                default:
                    $mensaje = "Rol no reconocido.";
            }
        } else {
            $mensaje = "Contraseña incorrecta.";
        }
    } else {
        $mensaje = "Usuario no encontrado.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="Login.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>
  <div class="container">
    <div class="signin">
      <h2>Acceder</h2>

      <!-- Mostrar mensaje de error -->
      <?php if ($mensaje != ""): ?>
        <p style="color:red;"><?php echo htmlspecialchars($mensaje); ?></p>
      <?php endif; ?>

      <!-- Formulario -->
      <form method="POST" action="">
        <div class="input-group">
          <input type="email" name="Email" required placeholder=" ">
          <label>Correo</label>
        </div>

        <!-- Campo de contraseña con icono funcional -->
        <div class="input-group">
          <input type="password" name="Contrasena" required placeholder=" " id="Contrasena">
          <label>Contraseña</label>
          <img src="Imagenes/eye-open.png" id="togglePassword" class="toggle-password" alt="Mostrar/Ocultar contraseña">
        </div>

        <a href="OlvideContraseña.php">¿Olvidaste tu contraseña?</a>
        <button type="submit">Iniciar sesión</button>
      </form>
    </div>

    <div class="signup-side">
      <img src="Imagenes/logo.png" alt="Logo" class="signup-img">
      <h2>Hola, Amigo!</h2>
      <p>Introduce tus datos y comienza tu experiencia con nosotros.</p>
      <a href="Registro.php"><button>Crear cuenta</button></a>
    </div>
  </div>

  <!-- ✅ Script funcional para mostrar/ocultar contraseña -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const passwordInput = document.getElementById('Contrasena');
      const toggleImg = document.getElementById('togglePassword');

      toggleImg.addEventListener('click', function() {
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          toggleImg.src = "Imagenes/eye-closed.png"; // 👁️ Cambia al icono de ojo cerrado
        } else {
          passwordInput.type = "password";
          toggleImg.src = "Imagenes/eye-open.png"; // 👁️ Cambia al icono de ojo abierto
        }
      });
    });
  </script>
  <footer class="site-footer">
    <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
  </footer>
</body>
</html>
