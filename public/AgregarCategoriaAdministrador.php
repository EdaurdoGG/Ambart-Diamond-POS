<?php
session_start();

// Verificar que el usuario esté logueado y sea administrador
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION["idPersona"];

// Conexión
require_once "../includes/conexion.php";

// Obtener datos del administrador
$stmt = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM AdministradoresRegistrados 
    WHERE idAdministrador = ?
");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagenPerfil, $rol);
$stmt->fetch();
$stmt->close();

$mensaje = "";
$tipoMensaje = "";

// ================================
// PROCESAR FORMULARIO
// ================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombrePost = trim($_POST["nombre"]);
    $descripcionPost = trim($_POST["descripcion"]);

    $imagenPath = "";

    // SUBIR IMAGEN
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK) {

        $tmp = $_FILES["imagen"]["tmp_name"];
        $name = basename($_FILES["imagen"]["name"]);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $newName = "categoria_" . time() . "." . $ext;
        $uploadDir = "Categorias/";

        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $dest = $uploadDir . $newName;

        if (move_uploaded_file($tmp, $dest)) {
            $imagenPath = $dest;
        } else {
            $mensaje = "Error al subir la imagen.";
            $tipoMensaje = "error";
        }
    }

    if ($mensaje === "") {

        try {

            // PROCEDIMIENTO ALMACENADO
            $stmt = $conn->prepare("CALL AgregarCategoria(?, ?, ?)");
            $stmt->bind_param("sss", $nombrePost, $descripcionPost, $imagenPath);

            if ($stmt->execute()) {
                $mensaje = "Categoría agregada correctamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Error al agregar: " . $stmt->error;
                $tipoMensaje = "error";
            }

            $stmt->close();

        } catch (Exception $e) {
            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Categoría</title>
    <link rel="stylesheet" href="AgregarCategoriaAdministrador.css">
    <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>

<!-- ================================
     MENSAJE FLOTANTE
================================ -->
<?php if ($mensaje !== ""): ?>
<div class="floating-message <?= $tipoMensaje ?>" id="floatMsg">
    <?= htmlspecialchars($mensaje) ?>
</div>
<?php endif; ?>

<script>
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
              <img src="imagenes/Inicio.png" class="icon"> Inicio
            </a>
            <a href="ListaVentasAdministrador.php" class="menu-item">
              <img src="imagenes/Ventas.png" class="icon"> Ventas
            </a>
            <a href="ListaPedidosAdministrador.php" class="menu-item">
              <img src="imagenes/Pedidos.png" class="icon"> Pedidos
            </a>
            <a href="ListaProductosAdministrador.php" class="menu-item active">
              <img src="imagenes/Productos.png" class="icon"> Productos
            </a>
            <a href="ListaClientesAdministrado.php" class="menu-item">
              <img src="imagenes/Clientes.png" class="icon"> Clientes
            </a>
            <a href="ListaEmpleadosAdministrador.php" class="menu-item">
              <img src="imagenes/Empleados.png" class="icon"> Empleados
            </a>
            <a href="ListaDevolucionesAdministrador.php" class="menu-item">
              <img src="imagenes/Devoluciones.png" class="icon"> Devoluciones
            </a>
            <a href="FinanzasAdministrador.php" class="menu-item">
              <img src="imagenes/Finanzas.png" class="icon"> Finanzas
            </a>
            <a href="Auditorias.php" class="menu-item">
              <img src="imagenes/Auditorias.png" class="icon"> Control y Auditoría
            </a>
            <a href="QuejaSugerenciaAdministrador.php" class="menu-item">
              <img src="imagenes/QuejasSujerencias.png" class="icon"> QuejasSujerencias
            </a>
            <div class="menu-separator"></div>
            <a href="Login.php" class="menu-item logout">
              <img src="imagenes/salir.png" class="icon"> Cerrar sesión
            </a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <h2>Agregar Categoría</h2>

            <div class="user-profile">
                <a href="AlertasAdministrador.php">
                    <img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification">
                </a>
                <img src="<?= $imagenPerfil ?: 'imagenes/User.png' ?>" class="avatar">
                <div class="user-info">
                    <span class="user-name"><?= "$nombre $apellidoP $apellidoM" ?></span>
                    <span class="user-role"><?= $rol ?></span>
                </div>
            </div>
        </header>

        <section class="form-section">

            <form class="form-card" method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <input type="text" id="nombre" name="nombre" placeholder=" " required>
                    <label for="nombre">Nombre de la Categoría</label>
                </div>

                <div class="form-group">
                    <input type="text" id="descripcion" name="descripcion" placeholder=" ">
                    <label for="descripcion">Descripción</label>
                </div>

                <div class="form-group">
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <label for="imagen">Imagen de la Categoría</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Agregar Categoría</button>
                </div>

            </form>
        </section>

        <footer class="site-footer">
            <p>&copy; 2025 <strong>Diamonds Corporation</strong></p>
        </footer>

    </main>
</div>

</body>
</html>
