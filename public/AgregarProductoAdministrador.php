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
$tipoMensaje = ""; 

// Obtener datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Email, Telefono, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
if (!$stmt) {
    die("Error en prepare(): " . $conn->error);
}

$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $email, $telefono, $imagen, $rol);
$stmt->fetch();
$stmt->close();

// Obtener todas las categorías
$categorias = [];
$result = $conn->query("SELECT idCategoria, Nombre FROM Categoria ORDER BY Nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    $result->free();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreProducto = $_POST['nombre'] ?? '';
    $precioCompra = $_POST['precioCompra'] ?? 0;
    $precioVenta = $_POST['precioVenta'] ?? 0;
    $codigoBarra = $_POST['codigoBarra'] ?? '';
    $existencia = $_POST['existencia'] ?? 0;
    $idCategoria = $_POST['categoria'] ?? null;
    $minimoInventario = $_POST['minimoInventario'] ?? 30; // valor default
    $imagenPath = '';

    // Manejo de imagen
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
            $tipoMensaje = "error";
        }
    }

    // Llamar procedimiento almacenado
    if ($mensaje === '') {
        $stmt = $conn->prepare("CALL AgregarProducto(?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error en prepare() del procedimiento: " . $conn->error);
        }

        // s = string, d = double, i = int
        $stmt->bind_param(
            "sddsii si",
            $nombreProducto,
            $precioCompra,
            $precioVenta,
            $codigoBarra,
            $existencia,
            $idCategoria,
            $imagenPath,
            $minimoInventario
        );

        if ($stmt->execute()) {
            $mensaje = "Producto agregado correctamente.";
            $tipoMensaje = "exito";
        } else {
            $mensaje = "Error al agregar el producto: " . $stmt->error;
            $tipoMensaje = "error";
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
  <title>Registrar Producto</title>
  <link rel="stylesheet" href="AgregarProductoAdministrador.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>

  <?php if ($mensaje != ""): ?>
    <div class="mensaje-flotante <?= $tipoMensaje ?>">
      <?= htmlspecialchars($mensaje) ?>
    </div>
    <script>
      setTimeout(() => {
        const msg = document.querySelector('.mensaje-flotante');
        if (msg) msg.remove();
      }, 3000); 
    </script>
  <?php endif; ?>

  <div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="imagenes/Logo.png" alt="Logo" class="icon">
        <span>Amber Diamond</span>
      </div>
      <nav class="menu">
        <a href="InicioAdministradores.php" class="menu-item"><img src="imagenes/Inicio.png" class="icon"> Inicio</a>
        <a href="ListaVentasAdministrador.php" class="menu-item"><img src="imagenes/Ventas.png" class="icon"> Ventas</a>
        <a href="ListaPedidosAdministrador.php" class="menu-item"><img src="imagenes/Pedidos.png" class="icon"> Pedidos</a>
        <a href="ListaProductosAdministrador.php" class="menu-item active"><img src="imagenes/Productos.png" class="icon"> Productos</a>
        <a href="ListaClientesAdministrado.php" class="menu-item"><img src="imagenes/Clientes.png" class="icon"> Clientes</a>
        <a href="ListaEmpleadosAdministrador.php" class="menu-item"><img src="imagenes/Empleados.png" class="icon"> Empleados</a>
        <a href="ListaDevolucionesAdministrador.php" class="menu-item"><img src="imagenes/Devoluciones.png" class="icon"> Devoluciones</a>
        <a href="FinanzasAdministrador.php" class="menu-item"><img src="imagenes/Finanzas.png" class="icon"> Finanzas</a>
        <a href="Auditorias.php" class="menu-item"><img src="imagenes/Auditorias.png" class="icon"> Control y Auditoría</a>
        <a href="QuejaSugerenciaAdministrador.php" class="menu-item"><img src="imagenes/QuejasSujerencias.png" class="icon"> QuejasSujerencias</a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" class="icon"> Cerrar sesión</a>
      </nav>
    </aside>

    <main class="main-content">

      <header class="topbar">
        <div class="search-box"><h2>Agregar Producto</h2></div>

        <div class="user-profile">
          <a href="AlertasAdministrador.php"><img src="imagenes/Notificasion.png" class="icon notification"></a>
          <a href="EditarPerfilAdministrador.php">
            <img src="<?= htmlspecialchars($imagen ?: 'imagenes/User.png') ?>" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$nombre $apellidoP $apellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <section class="form-section">
        <form class="form-card" method="POST" enctype="multipart/form-data">

          <div class="form-group">
            <input type="text" id="nombre" name="nombre" placeholder=" " required>
            <label for="nombre">Nombre del Producto</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioCompra" name="precioCompra" placeholder=" " step="0.01" min="0" required>
            <label for="precioCompra">Precio de Compra</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioVenta" name="precioVenta" placeholder=" " step="0.01" min="0" required>
            <label for="precioVenta">Precio de Venta</label>
          </div>

          <div class="form-group">
            <input type="text" id="codigoBarra" name="codigoBarra" placeholder=" ">
            <label for="codigoBarra">Código de Barra</label>
          </div>

          <div class="form-group">
            <input type="number" id="existencia" name="existencia" placeholder=" " min="0" required>
            <label for="existencia">Existencia</label>
          </div>

          <div class="form-group">
            <input type="number" id="minimoInventario" name="minimoInventario" placeholder=" " min="1" required>
            <label for="minimoInventario">Mínimo en Inventario</label>
          </div>

          <div class="form-group">
            <select id="categoria" name="categoria" required>
              <option value="" disabled selected>Selecciona una categoría</option>
              <?php foreach($categorias as $cat): ?>
                <option value="<?= $cat['idCategoria'] ?>"><?= htmlspecialchars($cat['Nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <label for="categoria">Categoría</label>
          </div>

          <div class="form-group">
            <input type="file" id="imagen" name="imagen" accept="image/*">
            <label for="imagen">Imagen del Producto</label>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">Registrar Producto</button>
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
