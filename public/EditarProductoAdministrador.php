<?php
session_start();

// Verificar que el usuario esté logueado y sea administrador (rol = 1)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Obtener datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
if (!$stmt) {
    die("Error en prepare(): " . $conn->error);
}
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// ===========================
// Obtener los datos del producto a editar
// ===========================
$idProducto = $_GET['id'] ?? null;
if (!$idProducto) {
    die("ID de producto no proporcionado.");
}

$stmt = $conn->prepare("SELECT Nombre, PrecioCompra, PrecioVenta, CodigoBarra, Existencia, idCategoria, Imagen 
                        FROM Producto 
                        WHERE idProducto = ?");
$stmt->bind_param("i", $idProducto);
$stmt->execute();
$stmt->bind_result($nombreProducto, $precioCompra, $precioVenta, $codigoBarra, $existencia, $categoriaId, $imagenProducto);
$stmt->fetch();
$stmt->close();

// ===========================
// Obtener todas las categorías
// ===========================
$categorias = [];
$result = $conn->query("SELECT idCategoria, Nombre FROM Categoria ORDER BY Nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    $result->free();
}

// ===========================
// Procesar formulario al enviar
// ===========================
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreProductoPost = $_POST['nombre'] ?? '';
    $precioCompraPost = $_POST['precioCompra'] ?? 0;
    $precioVentaPost = $_POST['precioVenta'] ?? 0;
    $codigoBarraPost = $_POST['codigoBarra'] ?? '';
    $existenciaPost = $_POST['existencia'] ?? 0;
    $categoriaPost = $_POST['categoria'] ?? null;
    $imagenPath = $imagenProducto; // Por defecto se mantiene la imagen actual

    // Manejo de la imagen si suben una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName = basename($_FILES['imagen']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "producto_" . time() . "." . $fileExt;

        $uploadDir = "Productos/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $imagenPath = $destPath;
        } else {
            $mensaje = "Error al subir la imagen del producto.";
        }
    }

    // Ejecutar procedimiento almacenado si no hay error de imagen
    if ($mensaje === '') {
        $stmt = $conn->prepare("CALL ActualizarProductoCompleto(?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error en prepare(): " . $conn->error);
        }

        $stmt->bind_param(
            "isddsiis",
            $idProducto,
            $nombreProductoPost,
            $precioCompraPost,
            $precioVentaPost,
            $codigoBarraPost,
            $existenciaPost,
            $categoriaPost,
            $imagenPath
        );

        if ($stmt->execute()) {
            $mensaje = "Producto actualizado correctamente.";
            // Actualizar variables para mostrar en el formulario
            $nombreProducto = $nombreProductoPost;
            $precioCompra = $precioCompraPost;
            $precioVenta = $precioVentaPost;
            $codigoBarra = $codigoBarraPost;
            $existencia = $existenciaPost;
            $categoriaId = $categoriaPost;
            $imagenProducto = $imagenPath;
        } else {
            $mensaje = "Error al actualizar el producto: " . $stmt->error;
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Producto</title>
  <link rel="stylesheet" href="EditarProductoAdministrador.css">
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

    <main class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <div class="search-box">
          <h2>Editar Producto</h2>
        </div>
        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?= htmlspecialchars($imagen ?: 'imagenes/User.png') ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$nombre $apellidoP $apellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <!-- Formulario -->
      <section class="form-section">
        <form class="form-card" method="POST" action="" enctype="multipart/form-data">

          <?php if($mensaje != ""): ?>
            <p style="color:green;"><?= htmlspecialchars($mensaje) ?></p>
          <?php endif; ?>

          <input type="hidden" name="idProducto" value="<?= htmlspecialchars($idProducto) ?>">

          <div class="form-group">
            <input type="text" id="nombre" name="nombre" placeholder=" " required value="<?= htmlspecialchars($nombreProducto) ?>">
            <label for="nombre">Nombre del Producto</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioCompra" name="precioCompra" placeholder=" " step="0.01" min="0" required value="<?= htmlspecialchars($precioCompra) ?>">
            <label for="precioCompra">Precio de Compra</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioVenta" name="precioVenta" placeholder=" " step="0.01" min="0" required value="<?= htmlspecialchars($precioVenta) ?>">
            <label for="precioVenta">Precio de Venta</label>
          </div>

          <div class="form-group">
            <input type="text" id="codigoBarra" name="codigoBarra" placeholder=" " value="<?= htmlspecialchars($codigoBarra) ?>">
            <label for="codigoBarra">Código de Barra</label>
          </div>

          <div class="form-group">
            <input type="number" id="existencia" name="existencia" placeholder=" " min="0" required value="<?= htmlspecialchars($existencia) ?>">
            <label for="existencia">Existencia</label>
          </div>

          <div class="form-group">
            <select id="categoria" name="categoria" required>
              <option value="" disabled>Selecciona una categoría</option>
              <?php foreach($categorias as $cat): ?>
                <option value="<?= $cat['idCategoria'] ?>" <?= $cat['idCategoria'] == $categoriaId ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['Nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="categoria">Categoría</label>
          </div>

          <div class="form-group">
            <input type="file" id="imagen" name="imagen" accept="image/*">
            <label for="imagen">Imagen del Producto</label>
            <?php if($imagenProducto): ?>
              <img src="<?= htmlspecialchars($imagenProducto) ?>" alt="Imagen actual" style="width:100px;margin-top:10px;">
            <?php endif; ?>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">Actualizar Producto</button>
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
