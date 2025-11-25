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

// Obtener datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// OBTENER DATOS DEL PRODUCTO A EDITAR
$idProducto = $_GET['id'] ?? null;
if (!$idProducto) {
    die("ID de producto no proporcionado.");
}

$stmt = $conn->prepare("
    SELECT Nombre, PrecioCompra, PrecioVenta, CodigoBarra, Existencia, idCategoria, Imagen, MinimoInventario
    FROM Producto 
    WHERE idProducto = ?
");
$stmt->bind_param("i", $idProducto);
$stmt->execute();
$stmt->bind_result(
    $nombreProducto,
    $precioCompra,
    $precioVenta,
    $codigoBarra,
    $existencia,
    $categoriaId,
    $imagenProducto,
    $minimoInventario
);
$stmt->fetch();
$stmt->close();

// CARGAR CATEGORÍAS
$categorias = [];
$result = $conn->query("SELECT idCategoria, Nombre FROM Categoria ORDER BY Nombre");
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

// PROCESAR FORMULARIO
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombreProductoPost = $_POST['nombre'] ?? '';
    $precioCompraPost = $_POST['precioCompra'] ?? 0;
    $precioVentaPost = $_POST['precioVenta'] ?? 0;
    $codigoBarraPost = $_POST['codigoBarra'] ?? '';
    $existenciaPost = $_POST['existencia'] ?? 0;
    $categoriaPost = $_POST['categoria'] ?? null;
    $minimoInventarioPost = $_POST['minimoInventario'] ?? 30;

    $imagenPath = $imagenProducto;

    // Subida de imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['imagen']['tmp_name'];
        $name = basename($_FILES['imagen']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $newName = "producto_" . time() . "." . $ext;

        $uploadDir = "Productos/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $dest = $uploadDir . $newName;

        if (move_uploaded_file($tmp, $dest)) {
            $imagenPath = $dest;
        } else {
            $mensaje = "Error al subir la imagen.";
        }
    }

    if ($mensaje === "") {

        // LLAMADA AL NUEVO PROCEDIMIENTO ALMACENADO
        $stmt = $conn->prepare("CALL ActualizarProductoCompleto(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isddsiisi",
            $idProducto,
            $nombreProductoPost,
            $precioCompraPost,
            $precioVentaPost,
            $codigoBarraPost,
            $existenciaPost,
            $categoriaPost,
            $imagenPath,
            $minimoInventarioPost
        );

        if ($stmt->execute()) {
            $mensaje = "Producto actualizado correctamente.";

            // Actualizar valores mostrados
            $nombreProducto = $nombreProductoPost;
            $precioCompra = $precioCompraPost;
            $precioVenta = $precioVentaPost;
            $codigoBarra = $codigoBarraPost;
            $existencia = $existenciaPost;
            $categoriaId = $categoriaPost;
            $imagenProducto = $imagenPath;
            $minimoInventario = $minimoInventarioPost;

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Producto</title>
  <link rel="stylesheet" href="EditarProductoAdministrador.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>
  <?php if ($mensaje != ""): ?>
    <div class="alert-message <?= strpos($mensaje, 'Error') !== false ? 'alert-error' : '' ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <div class="dashboard-container">

    <!-- Sidebar -->
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
          <img src="imagenes/QuejasSujerencias.png" class="icon"> Quejas/Sugerencias
        </a>

        <div class="menu-separator"></div>

        <a href="Login.php" class="menu-item logout">
          <img src="imagenes/salir.png" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">

      <!-- Topbar -->
      <header class="topbar">
        <div class="search-box">
          <h2>Editar Producto</h2>
        </div>
        <div class="user-profile">
          <a href="AlertasAdministrador.php">
            <img src="imagenes/Notificasion.png" class="icon notification">
          </a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?= htmlspecialchars($imagen ?: 'imagenes/User.png') ?>" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$nombre $apellidoP $apellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <!-- Form -->
      <section class="form-section">
        <form class="form-card" method="POST" enctype="multipart/form-data">

          <input type="hidden" name="idProducto" value="<?= htmlspecialchars($idProducto) ?>">

          <div class="form-group">
            <input type="text" id="nombre" name="nombre" placeholder=" " required value="<?= htmlspecialchars($nombreProducto) ?>">
            <label for="nombre">Nombre del Producto</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioCompra" name="precioCompra" step="0.01" min="0" required value="<?= htmlspecialchars($precioCompra) ?>">
            <label for="precioCompra">Precio de Compra</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioVenta" name="precioVenta" step="0.01" min="0" required value="<?= htmlspecialchars($precioVenta) ?>">
            <label for="precioVenta">Precio de Venta</label>
          </div>

          <div class="form-group">
            <input type="text" id="codigoBarra" name="codigoBarra" placeholder=" " value="<?= htmlspecialchars($codigoBarra) ?>">
            <label for="codigoBarra">Código de Barra</label>
          </div>

          <div class="form-group">
            <input type="number" id="existencia" name="existencia" min="0" required value="<?= htmlspecialchars($existencia) ?>">
            <label for="existencia">Existencia</label>
          </div>

          <div class="form-group">
            <input type="number" id="minimoInventario" name="minimoInventario" min="1" required value="<?= htmlspecialchars($minimoInventario) ?>">
            <label for="minimoInventario">Mínimo en Inventario</label>
          </div>

          <div class="form-group">
            <select id="categoria" name="categoria" required>
              <option value="" disabled>Selecciona una categoría</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['idCategoria'] ?>" <?= $cat['idCategoria'] == $categoriaId ? 'selected':'' ?>>
                  <?= htmlspecialchars($cat['Nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="categoria">Categoría</label>
          </div>

          <div class="form-group">
            <input type="file" id="imagen" name="imagen" accept="image/*">
            <label for="imagen">Imagen del Producto</label>

            <?php if ($imagenProducto): ?>
              <img src="<?= htmlspecialchars($imagenProducto) ?>" style="width:100px;margin-top:10px;">
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
