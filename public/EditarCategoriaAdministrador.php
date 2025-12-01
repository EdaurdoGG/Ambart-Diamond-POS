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

// Obtener ID de categoría
$idCategoria = $_GET["id"] ?? null;
if (!$idCategoria) {
    die("Error: No se proporcionó un ID de categoría.");
}

$stmt = $conn->prepare("
    SELECT Nombre, Descripcion, Imagen
    FROM Categoria
    WHERE idCategoria = ?
");
$stmt->bind_param("i", $idCategoria);
$stmt->execute();
$stmt->bind_result($nombreCategoria, $descripcionCategoria, $imagenCategoria);
$stmt->fetch();
$stmt->close();

$mensaje = "";

// ================================
// PROCESAR FORMULARIO
// ================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombrePost = trim($_POST["nombre"]);
    $descripcionPost = trim($_POST["descripcion"]);
    $imagenPath = $imagenCategoria;

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
        }
    }

    if ($mensaje === "") {

        // PROCEDIMIENTO ALMACENADO
        $stmt = $conn->prepare("CALL EditarCategoria(?, ?, ?, ?)");
        $stmt->bind_param("isss", $idCategoria, $nombrePost, $descripcionPost, $imagenPath);

        if ($stmt->execute()) {
            $mensaje = "Categoría actualizada correctamente.";

            $nombreCategoria = $nombrePost;
            $descripcionCategoria = $descripcionPost;
            $imagenCategoria = $imagenPath;

        } else {
            $mensaje = "Error al actualizar: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoría</title>
    <link rel="stylesheet" href="EditarCategoriaAdministrador.css">
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

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <h2>Editar Categoría</h2>

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

                <input type="hidden" name="idCategoria" value="<?= $idCategoria ?>">

                <div class="form-group">
                    <input type="text" id="nombre" name="nombre" placeholder=" " required 
                           value="<?= htmlspecialchars($nombreCategoria) ?>">
                    <label for="nombre">Nombre de la Categoría</label>
                </div>

                <div class="form-group">
                    <input type="text" id="descripcion" name="descripcion" placeholder=" "
                           value="<?= htmlspecialchars($descripcionCategoria) ?>">
                    <label for="descripcion">Descripción</label>
                </div>

                <div class="form-group">
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <label for="imagen">Imagen de la Categoría</label>

                    <?php if ($imagenCategoria): ?>
                        <img src="<?= htmlspecialchars($imagenCategoria) ?>" style="width:120px;margin-top:10px;">
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Actualizar Categoría</button>
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
