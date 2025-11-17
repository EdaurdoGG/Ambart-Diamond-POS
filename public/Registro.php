<?php
session_start();
$error = '';
$nombre = $apellidoP = $apellidoM = $telefono = $email = $edad = $sexo = $usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Conexión
    require_once "../includes/conexion.php";

    // Asignar id del usuario logueado a variable de sesión si existe
    if (isset($_SESSION['idPersona'])) {
        $conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));
    }

    // Recibir datos y limpiar entradas
    $nombre = trim($_POST['Nombre']);
    $apellidoP = trim($_POST['ApellidoPaterno']);
    $apellidoM = trim($_POST['ApellidoMaterno']);
    $telefono = trim($_POST['Telefono']);
    $email = trim($_POST['Email']);
    $edad = trim($_POST['Edad']);
    $sexo = $_POST['Sexo'] ?? '';
    $usuario = trim($_POST['Usuario']);
    $contrasena = $_POST['Contrasena'] ?? '';
    $confirmar = $_POST['ConfirmarContrasena'] ?? '';

    // Validaciones
    if (!preg_match("/^[\w\.-]+@(gmail\.com|outlook\.com)$/i", $email)) {
        $error = "El correo debe ser de dominio gmail.com o outlook.com.";
    } elseif ($contrasena !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!preg_match("/^(?=.*\d.*\d)(?=.*[\W_]).{8,}$/", $contrasena)) {
        $error = "La contraseña debe tener al menos 8 caracteres, 2 números y 1 carácter especial.";
    }

    // Insertar si no hay error
    if (empty($error)) {
        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("CALL AgregarPersona(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssisss", $nombre, $apellidoP, $apellidoM, $telefono, $email, $edad, $sexo, $usuario, $contrasenaHash);

        if ($stmt->execute()) {
            echo "<script>alert('Registro exitoso'); window.location='Login.php';</script>";
            exit();
        } else {
            $error = "Error al registrar: " . $stmt->error;
        }

        $stmt->close();
    }

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
<style>
    .error-message { color: red; margin-bottom: 15px; }
</style>
</head>
<body>
<div class="container">
    <div class="signin">
        <h2>Crear Cuenta</h2>

        <?php if (!empty($error)) echo "<div class='error-message'>" . htmlspecialchars($error) . "</div>"; ?>

        <form action="" method="POST">

            <div class="input-group">
                <input type="text" name="Nombre" required placeholder=" " value="<?= htmlspecialchars($nombre) ?>">
                <label>Nombre</label>
            </div>

            <div class="input-group">
                <input type="text" name="ApellidoPaterno" required placeholder=" " value="<?= htmlspecialchars($apellidoP) ?>">
                <label>Apellido Paterno</label>
            </div>

            <div class="input-group">
                <input type="text" name="ApellidoMaterno" placeholder=" " value="<?= htmlspecialchars($apellidoM) ?>">
                <label>Apellido Materno</label>
            </div>

            <div class="input-group">
                <input type="tel" name="Telefono" placeholder=" " value="<?= htmlspecialchars($telefono) ?>">
                <label>Teléfono</label>
            </div>

            <div class="input-group">
                <input type="email" name="Email" required placeholder=" " value="<?= htmlspecialchars($email) ?>">
                <label>Email</label>
            </div>

            <div class="input-group">
                <input type="number" name="Edad" required placeholder=" " min="18" value="<?= htmlspecialchars($edad) ?>">
                <label>Edad</label>
            </div>

            <div class="input-group">
                <select name="Sexo" required>
                    <option value="" disabled <?= $sexo==''?'selected':'' ?>></option>
                    <option value="M" <?= $sexo=='M'?'selected':'' ?>>Masculino</option>
                    <option value="F" <?= $sexo=='F'?'selected':'' ?>>Femenino</option>
                    <option value="Otro" <?= $sexo=='Otro'?'selected':'' ?>>Otro</option>
                </select>
                <label>Sexo</label>
            </div>

            <div class="input-group">
                <input type="text" name="Usuario" required placeholder=" " value="<?= htmlspecialchars($usuario) ?>">
                <label>Usuario</label>
            </div>

            <div class="input-group">
                <input type="password" name="Contrasena" required placeholder=" " id="Contrasena">
                <label>Contraseña</label>
                <img src="Imagenes/eye-open.png" id="togglePassword" class="toggle-password" alt="Mostrar/Ocultar contraseña">
            </div>

            <div class="input-group">
                <input type="password" name="ConfirmarContrasena" required placeholder=" " id="ConfirmarContrasena">
                <label>Confirmar Contraseña</label>
                <img src="Imagenes/eye-open.png" id="toggleConfirm" class="toggle-password" alt="Mostrar/Ocultar contraseña">
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const passwordInput = document.getElementById('Contrasena');
    const toggleImg = document.getElementById('togglePassword');
    const confirmInput = document.getElementById('ConfirmarContrasena');
    const toggleConfirm = document.getElementById('toggleConfirm');

    toggleImg.addEventListener('click', function() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleImg.src = "Imagenes/eye-closed.png";
        } else {
            passwordInput.type = "password";
            toggleImg.src = "Imagenes/eye-open.png";
        }
    });

    toggleConfirm.addEventListener('click', function() {
        if (confirmInput.type === "password") {
            confirmInput.type = "text";
            toggleConfirm.src = "Imagenes/eye-closed.png";
        } else {
            confirmInput.type = "password";
            toggleConfirm.src = "Imagenes/eye-open.png";
        }
    });
});
</script>

<footer class="site-footer">
    <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
</footer>
</body>
</html>
