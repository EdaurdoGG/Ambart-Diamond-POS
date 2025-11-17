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

// Manejo de búsqueda
$result = null;
$busqueda = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);

    if (preg_match('/^\d+$/', $busqueda)) {
        // Buscar por código de barra
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
} else {
    // Mostrar todos los productos si no hay búsqueda
    $sql = "SELECT idProducto, Producto, Categoria, Existencia, PrecioCompra, PrecioVenta, Imagen FROM ProductosConEstado";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo de Productos</title>
  <link rel="stylesheet" href="ListaProductosAdministrador.css">
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

    <!-- Main content -->
    <main class="main-content">
      <!-- Topbar -->
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

      <div class="table-actions">
        <a href="AgregarProductoAdministrador.php">
          <button class="btn-primary">Agregar</button>
        </a>
        <form action="ExportarListaProductos.php" method="GET">
          <button type="submit" class="btn-secondary">Exportar Productos</button>
        </form>
      </div>

      <!-- Catalogo de tarjetas -->
      <section class="card-section">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <div class="card">
              <img src="<?= htmlspecialchars($row['Imagen']) ?>" alt="<?= htmlspecialchars($row['Producto']) ?>" class="card-img">
              <div class="card-info">
                <h3><?= htmlspecialchars($row['Producto']) ?></h3>
                <p>Categoría: <?= htmlspecialchars($row['Categoria']) ?> | Cantidad: <?= htmlspecialchars($row['Existencia']) ?></p>
                <span class="price">$<?= number_format($row['PrecioVenta'], 2) ?></span>
                <div class="card-actions">
                  <a href="EditarProductoAdministrador.php?id=<?= $row['idProducto'] ?>">
                    <button class="btn-secondary">Editar</button>
                  </a>
                  <a href="EliminarProductoAdministrador.php?id=<?= $row['idProducto'] ?>">
                    <button class="btn-primary">Eliminar</button>
                  </a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No hay productos disponibles.</p>
        <?php endif; ?>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>

<?php
$conn->close();
?>
