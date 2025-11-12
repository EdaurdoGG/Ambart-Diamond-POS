<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Conexión a la base de datos
    $host = "db";
    $db = "AmbarDiamond";
    $user = "root";
    $pass = "clave";

    // Conexión
    require_once "../includes/conexion.php";

    // Asignar el id del usuario logueado a la variable @id_usuario_actual
    if (isset($_SESSION['idPersona'])) {
        $conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));
    }

    // Recibir datos
    $nombre = $_POST['Nombre'];
    $apellidoP = $_POST['ApellidoPaterno'];
    $apellidoM = $_POST['ApellidoMaterno'];
    $telefono = $_POST['Telefono'];
    $email = $_POST['Email'];
    $edad = $_POST['Edad'];
    $sexo = $_POST['Sexo'];
    $usuario = $_POST['Usuario'];
    $contrasena = password_hash($_POST['Contrasena'], PASSWORD_DEFAULT);

    // Preparar y ejecutar procedimiento
    $stmt = $conn->prepare("CALL AgregarPersona(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisss", $nombre, $apellidoP, $apellidoM, $telefono, $email, $edad, $sexo, $usuario, $contrasena);

    if ($stmt->execute()) {
        echo "<script>alert('Registro exitoso'); window.location='Login.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
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
  <title>Registro</title>
  <link rel="stylesheet" href="Registro.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>

<body>
  <div class="container">
    <div class="signin">
      <h2>Crear Cuenta</h2>

      <!-- Formulario -->
      <form action="" method="POST">

        <div class="input-group">
          <input type="text" name="Nombre" required placeholder=" ">
          <label>Nombre</label>
        </div>

        <div class="input-group">
          <input type="text" name="ApellidoPaterno" required placeholder=" ">
          <label>Apellido Paterno</label>
        </div>

        <div class="input-group">
          <input type="text" name="ApellidoMaterno" placeholder=" ">
          <label>Apellido Materno</label>
        </div>

        <div class="input-group">
          <input type="tel" name="Telefono" placeholder=" ">
          <label>Teléfono</label>
        </div>

        <div class="input-group">
          <input type="email" name="Email" required placeholder=" ">
          <label>Email</label>
        </div>

        <div class="input-group">
          <input type="number" name="Edad" required placeholder=" " min="18">
          <label>Edad</label>
        </div>

        <div class="input-group">
          <select name="Sexo" required>
            <option value="" disabled selected></option>
            <option value="M">Masculino</option>
            <option value="F">Femenino</option>
            <option value="Otro">Otro</option>
          </select>
          <label>Sexo</label>
        </div>

        <div class="input-group">
          <input type="text" name="Usuario" required placeholder=" ">
          <label>Usuario</label>
        </div>

        <!-- Campo de contraseña con icono funcional -->
        <div class="input-group">
          <input type="password" name="Contrasena" required placeholder=" " id="Contrasena">
          <label>Contraseña</label>
          <img src="Imagenes/eye-open.png" id="togglePassword" class="toggle-password" alt="Mostrar/Ocultar contraseña">
        </div>

        <button type="submit">Registrar</button>
      </form>
    </div>

    <div class="signup-side">
      <img src="Imagenes/logo.png" alt="Logo" class="signup-img">
      <h2>Hola, Amigo!</h2>
      <p>Crea tu cuenta con nosotros y comienza tu experiencia de manera segura y confiable.</p>
      <a href="Login.php"><button>Iniciar sesión</button></a>
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
          toggleImg.src = "Imagenes/eye-closed.png"; // 👁️ Imagen de ojo cerrado
        } else {
          passwordInput.type = "password";
          toggleImg.src = "Imagenes/eye-open.png"; // 👁️ Imagen de ojo abierto
        }
      });
    });
  </script>
  <footer class="site-footer">
    <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
  </footer>
</body>
</html>
