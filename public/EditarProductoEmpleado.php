<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Asignar variable de sesión
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Obtener los datos del empleado logueado
$idEmpleado = $_SESSION['idPersona'];

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen 
                        FROM EmpleadosRegistrados 
                        WHERE idEmpleado = ?");
if (!$stmt) {
    die("Error en prepare(): " . $conn->error);
}
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$stmt->bind_result($nombre, $apellidoP, $apellidoM, $imagen);
$stmt->fetch();
$stmt->close();

// Obtener el ID del producto
$idProducto = $_GET['idProducto'] ?? null;
if (!$idProducto) {
    die("ID de producto no proporcionado.");
}

// Obtener datos del producto
$stmt = $conn->prepare("SELECT Nombre, PrecioCompra, PrecioVenta, CodigoBarra, Existencia, idCategoria, Imagen 
                        FROM Producto 
                        WHERE idProducto = ?");
$stmt->bind_param("i", $idProducto);
$stmt->execute();
$stmt->bind_result($nombreProducto, $precioCompra, $precioVenta, $codigoBarra, $existencia, $categoriaId, $imagenProducto);
$stmt->fetch();
$stmt->close();

// Procesar formulario al enviar
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevaExistencia = $_POST['existencia'] ?? null;

    if ($nuevaExistencia === null || !is_numeric($nuevaExistencia) || $nuevaExistencia < 0) {
        $mensaje = "Cantidad inválida. Debe ser un número mayor o igual a 0.";
    } else {
        $stmt = $conn->prepare("UPDATE Producto SET Existencia = ? WHERE idProducto = ?");
        if (!$stmt) {
            die("Error en prepare(): " . $conn->error);
        }

        $stmt->bind_param("ii", $nuevaExistencia, $idProducto);

        if ($stmt->execute()) {
            $mensaje = "Cantidad actualizada correctamente.";
            $existencia = $nuevaExistencia;
        } else {
            $mensaje = "Error al actualizar la cantidad: " . $stmt->error;
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
  <title>Editar Cantidad de Producto</title>
  <link rel="stylesheet" href="EditarProductoEmpleado.css">
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
        <a href="InicioEmpleados.php" class="menu-item">
            <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
        </a>
        <a href="CarritoEmpleado.php" class="menu-item">
            <img src="imagenes/Caja.png" alt="CarritoEmpleado" class="icon"> Caja
        </a>
        <a href="ListaProductosEmpleado.php" class="menu-item active">
            <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
        </a>
        <a href="HistorialVentasEmpleado.php" class="menu-item">
            <img src="imagenes/Ventas.png" alt="HistorialVentas" class="icon"> Historial Ventas
        </a>
        <a href="ListaPedidosEmpleado.php" class="menu-item">
            <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
        </a>
        <a href="ListaDevolucionesEmpleado.php" class="menu-item">
            <img src="imagenes/Devoluciones.png" alt="Devoluciones" class="icon"> Devoluciones
        </a>
        <a href="QuejaSugerenciaEmpleado.php" class="menu-item">
            <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> Quejas / Sugerencias
        </a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout">
            <img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <div class="search-box">
          <h2>Editar Cantidad de Producto</h2>
        </div>
        <div class="user-profile">
          <a href="EditarPerfilEmpleado.php">
            <img src="<?= htmlspecialchars($imagen ?: 'imagenes/User.png') ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$nombre $apellidoP $apellidoM") ?></span>
            <span class="user-role">Empleado</span>
          </div>
        </div>
      </header>

      <!-- Formulario -->
      <section class="form-section">
        <form class="form-card" method="POST" action="">
          <?php if ($mensaje != ""): ?>
            <p style="color:<?= strpos($mensaje, 'Error') !== false ? 'red' : 'green' ?>;">
              <?= htmlspecialchars($mensaje) ?>
            </p>
          <?php endif; ?>

          <input type="hidden" name="idProducto" value="<?= htmlspecialchars($idProducto) ?>">

          <div class="form-group">
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombreProducto) ?>" readonly>
            <label for="nombre">Nombre del Producto</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioCompra" name="precioCompra" step="0.01" value="<?= htmlspecialchars($precioCompra) ?>" readonly>
            <label for="precioCompra">Precio de Compra</label>
          </div>

          <div class="form-group">
            <input type="number" id="precioVenta" name="precioVenta" step="0.01" value="<?= htmlspecialchars($precioVenta) ?>" readonly>
            <label for="precioVenta">Precio de Venta</label>
          </div>

          <div class="form-group">
            <input type="text" id="codigoBarra" name="codigoBarra" value="<?= htmlspecialchars($codigoBarra) ?>" readonly>
            <label for="codigoBarra">Código de Barra</label>
          </div>

          <div class="form-group">
            <!-- ÚNICO campo editable -->
            <input type="number" id="existencia" name="existencia" min="0" required value="<?= htmlspecialchars($existencia) ?>">
            <label for="existencia">Existencia</label>
          </div>

          <div class="form-group">
            <input type="text" id="categoria" name="categoria" value="<?= htmlspecialchars($categoriaId) ?>" readonly>
            <label for="categoria">Categoría</label>
          </div>

          <div class="form-group">
            <input type="text" id="imagenActual" value="<?= htmlspecialchars($imagenProducto) ?>" readonly>
            <label for="imagenActual">Imagen Actual</label>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">Actualizar Cantidad</button>
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
