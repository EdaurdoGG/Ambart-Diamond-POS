<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado
$conn->query("SET @id_usuario_actual = " . intval($idPersona));

// Obtener datos del empleado desde la vista
$stmtInfo = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
    FROM EmpleadosRegistrados 
    WHERE idEmpleado = ?
");
$stmtInfo->bind_param("i", $idPersona);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$empleado = $resultInfo->fetch_assoc();
$stmtInfo->close();

$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagenPerfil = $empleado && $empleado['Imagen'] ? $empleado['Imagen'] : 'imagenes/User.png';

// Manejo de búsqueda
$busqueda = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);

    if (preg_match('/^\d+$/', $busqueda)) {
        // Buscar por código de barras
        $stmt = $conn->prepare("CALL BuscarProductoPorCodigoBarra(?)");
        $stmt->bind_param("s", $busqueda);
    } else {
        // Buscar por nombre
        $stmt = $conn->prepare("CALL BuscarProductoPorNombre(?)");
        $stmt->bind_param("s", $busqueda);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    // Limpiar resultados pendientes del procedimiento
    while ($conn->more_results() && $conn->next_result()) {;}
} else {
    // Mostrar todos los productos si no hay búsqueda
    $sql = "SELECT * FROM ProductosConEstado";
    $result = $conn->query($sql);
}

// Agregar producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idProducto'])) {
    $idProducto = intval($_POST['idProducto']);

    $stmt = $conn->prepare("CALL AgregarAlCarrito(?, ?)");
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("ii", $idPersona, $idProducto);

    if ($stmt->execute()) {
        // Limpiar resultados pendientes del procedimiento
        while ($conn->more_results() && $conn->next_result()) {;}

        echo "<script>
            alert('Producto agregado correctamente al carrito');
            window.location.href='ListaProductosEmpleado.php';
        </script>";
        exit();
    } else {
        die("Error al ejecutar el procedimiento: " . $stmt->error);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo de Productos</title>
  <link rel="stylesheet" href="ListaProductosEmpleado.css">
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

    <!-- Main content -->
    <main class="main-content">
      <header class="topbar">
        <div class="search-box">
          <form method="POST">
              <input type="text" name="busqueda" placeholder="Buscar producto..." value="<?= htmlspecialchars($busqueda) ?>">
              <button type="submit" class="search-button">
                  <img src="imagenes/Buscar.png" alt="Buscar" class="search-icon">
              </button>
          </form>
        </div>

        <div class="user-profile">
          <a href="EditarPerfilEmpleado.php">
            <img src="<?= htmlspecialchars($imagenPerfil) ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <div class="table-actions">
        <a href="CarritoEmpleado.php">
          <button class="btn-primary">Ir a Caja</button>
        </a>
      </div>

      <!-- Catálogo dinámico -->
      <section class="card-section">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($prod = $result->fetch_assoc()): ?>
            <div class="card">
              <img src="productos/<?= htmlspecialchars(basename($prod['Imagen'])) ?>" alt="Producto" class="card-img">
              <div class="card-info">
                <h3><?= htmlspecialchars($prod['Producto']) ?></h3>
                <p>Categoría: <?= htmlspecialchars($prod['Categoria']) ?></p>
                <p>Existencia: <?= htmlspecialchars($prod['Existencia']) ?></p>
                <span class="price">$<?= number_format($prod['PrecioVenta'], 2) ?></span>

                <div class="card-actions">
                  <!-- Botón Agregar al carrito -->
                  <form action="" method="post" style="display:inline-block;">
                    <input type="hidden" name="idProducto" value="<?= $prod['idProducto'] ?>">
                    <button type="submit" class="btn-primary"
                      <?= ($prod['Estado'] == 'Inactivo') ? 'disabled' : '' ?>>
                      Agregar
                    </button>
                  </form>

                  <!-- Botón Editar producto -->
                  <form action="EditarProductoEmpleado.php" method="get" style="display:inline-block;">
                    <input type="hidden" name="idProducto" value="<?= $prod['idProducto'] ?>">
                    <button type="submit" class="btn-secondary">Editar</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="text-align:center; color:gray;">No hay productos disponibles.</p>
        <?php endif; ?>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>
