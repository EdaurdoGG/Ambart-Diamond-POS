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

// Asignar variable de sesión como usuario actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];
$stmt = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM AdministradoresRegistrados 
    WHERE idAdministrador = ?
");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

$nombreCompleto = trim("$adminNombre $adminApellidoP $adminApellidoM") ?: "Administrador desconocido";
$adminRol = $adminRol ?: "Administrador";
$adminImagen = $adminImagen ?: "imagenes/User.png";

// Obtener datos del usuario a editar
$idPersonaEdit = $_GET['id'] ?? 0;
$usuarioData = null;

if ($idPersonaEdit > 0) {
    $stmt = $conn->prepare("
        SELECT idPersona, Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, idRol
        FROM Persona 
        WHERE idPersona = ?
    ");
    $stmt->bind_param("i", $idPersonaEdit);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuarioData = $resultado->fetch_assoc();
    $stmt->close();
}

$mensaje = "";

// ========================
// PROCESAR FORMULARIO
// ========================
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
        $mensaje = "Usuario actualizado correctamente.";

        // Recargar datos
        $stmt->close();
        $stmt = $conn->prepare("
            SELECT idPersona, Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, idRol
            FROM Persona 
            WHERE idPersona = ?
        ");
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
  <title>Editar Usuario</title>
  <link rel="stylesheet" href="EditarUsuarioAdministrador.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>

<!-- ================================
     MENSAJE FLOTANTE
================================ -->
<?php if ($mensaje !== ""): ?>
<div class="floating-message <?= strpos($mensaje, 'Error') !== false ? 'error' : 'success' ?>" id="floatMsg">
    <?= htmlspecialchars($mensaje) ?>
</div>
<?php endif; ?>

<script>
    // Ocultar mensaje después de 3 segundos
    setTimeout(() => {
        let msg = document.getElementById("floatMsg");
        if (msg) {
            msg.style.opacity = "0";
            msg.style.transform = "translate(-50%, -60%) scale(0.8)";
            setTimeout(() => msg.remove(), 500);
        }
    }, 3000);
</script>

<div class="dashboard-container">

<!-- SIDEBAR -->
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

<!-- CONTENIDO -->
    <main class="main-content">
        <header class="topbar">
            <h2>Editar Usuario</h2>

            <div class="user-profile">
                <a href="AlertasAdministrador.php">
                    <img src="imagenes/Notificasion.png" class="icon notification">
                </a>
                <img src="<?= htmlspecialchars($adminImagen) ?>" class="avatar">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
                    <span class="user-role"><?= htmlspecialchars($adminRol) ?></span>
                </div>
            </div>
        </header>

        <section class="form-section">

            <form class="form-card" method="POST">

                <input type="hidden" name="idPersona" value="<?= htmlspecialchars($usuarioData['idPersona'] ?? '') ?>">

                <div class="form-group">
                    <input type="text" name="nombre" required value="<?= htmlspecialchars($usuarioData['Nombre'] ?? '') ?>">
                    <label>Nombre</label>
                </div>

                <div class="form-group">
                    <input type="text" name="apellidoPaterno" required value="<?= htmlspecialchars($usuarioData['ApellidoPaterno'] ?? '') ?>">
                    <label>Apellido Paterno</label>
                </div>

                <div class="form-group">
                    <input type="text" name="apellidoMaterno" value="<?= htmlspecialchars($usuarioData['ApellidoMaterno'] ?? '') ?>">
                    <label>Apellido Materno</label>
                </div>

                <div class="form-group">
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($usuarioData['Telefono'] ?? '') ?>">
                    <label>Teléfono</label>
                </div>

                <div class="form-group">
                    <input type="email" name="email" required value="<?= htmlspecialchars($usuarioData['Email'] ?? '') ?>">
                    <label>Correo electrónico</label>
                </div>

                <div class="form-group">
                    <input type="number" min="18" name="edad" required value="<?= htmlspecialchars($usuarioData['Edad'] ?? '') ?>">
                    <label>Edad</label>
                </div>

                <div class="form-group">
                    <select name="sexo" required>
                        <option value="" disabled></option>
                        <option value="M" <?= ($usuarioData['Sexo'] ?? '')=='M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= ($usuarioData['Sexo'] ?? '')=='F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= ($usuarioData['Sexo'] ?? '')=='Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                    <label>Sexo</label>
                </div>

                <div class="form-group">
                    <select name="estatus" required>
                        <option value="" disabled></option>
                        <option value="Activo" <?= ($usuarioData['Estatus'] ?? '')=='Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= ($usuarioData['Estatus'] ?? '')=='Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                    <label>Estatus</label>
                </div>

                <div class="form-group">
                    <input type="text" name="usuario" required value="<?= htmlspecialchars($usuarioData['Usuario'] ?? '') ?>">
                    <label>Usuario</label>
                </div>

                <div class="form-group">
                    <select name="idRol" required>
                        <option value="" disabled></option>
                        <option value="1" <?= ($usuarioData['idRol'] ?? '')==1 ? 'selected' : '' ?>>Administrador</option>
                        <option value="2" <?= ($usuarioData['idRol'] ?? '')==2 ? 'selected' : '' ?>>Empleado</option>
                        <option value="3" <?= ($usuarioData['idRol'] ?? '')==3 ? 'selected' : '' ?>>Cliente</option>
                    </select>
                    <label>Rol</label>
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
