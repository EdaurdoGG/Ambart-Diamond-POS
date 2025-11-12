<?php
session_start();

// Verificar sesión activa
$idPersona = $_SESSION['idPersona'] ?? null;
if (!$idPersona) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a variable MySQL
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Datos del cliente
$stmtInfo = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM ClientesRegistrados WHERE idCliente = ?");
$stmtInfo->bind_param("i", $idPersona);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$cliente = $resultInfo->fetch_assoc();
$stmtInfo->close();

$nombreCompleto = $cliente ? $cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno'] : 'Cliente';
$rol = $cliente ? $cliente['Rol'] : 'Cliente';
$imagenPerfil = $cliente && $cliente['Imagen'] ? $cliente['Imagen'] : 'imagenes/User.png';

// Función para ejecutar procedimientos
function ejecutarProcedimiento($conn, $sql) {
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        die("Error en procedimiento: " . $conn->error);
    }
}

// Manejar acciones del carrito
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $idDetalle = $_GET['idDetalle'] ?? 0;
    $cantidad = 1;

    if ($accion === 'sumar') {
        ejecutarProcedimiento($conn, "CALL SumarCantidadCarrito($idDetalle, $cantidad)");
    }

    if ($accion === 'restar') {
        ejecutarProcedimiento($conn, "CALL RestarCantidadCarrito($idDetalle, $cantidad)");
    }

    if ($accion === 'procesar') {
        ejecutarProcedimiento($conn, "CALL CrearPedidoDesdeCarrito($idPersona)");
    }

    // 🔹 Nuevo: Vaciar carrito
    if ($accion === 'vaciar') {
        ejecutarProcedimiento($conn, "CALL VaciarCarritoPorPersona($idPersona)");
    }

    header("Location: CarritoCliente.php");
    exit();
}

//  Obtener productos del carrito
$carrito = [];
if ($stmt = $conn->prepare("CALL ObtenerCarritoPorPersona(?)")) {
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $carrito[] = $row;
    }
    $stmt->close();
}

// Calcular total del carrito
$totalCarrito = 0;
foreach ($carrito as $producto) {
    $totalCarrito += $producto['Total'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Productos</title>
  <link rel="stylesheet" href="CarritoCliente.css">
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
          <a href="InicioCliente.php" class="menu-item">
              <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
          </a>
          <a href="CarritoCliente.php" class="menu-item active">
              <img src="imagenes/Carrito.png" alt="Carrito" class="icon"> Carrito
          </a>
          <a href="ListaProductosCliente.php" class="menu-item">
              <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
          </a>
          <a href="ListaPedidosCliente.php" class="menu-item">
              <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
          </a>
          <a href="QuejaSugerenciaCliente.php" class="menu-item">
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
      <!-- Topbar -->
      <header class="topbar">
        <div class="search-box">
          <div class="resumen-compra">
            <div class="resumen-card">
              <h3>Resumen de Compra</h3>
              <div class="resumen-monto">
                <span>Total:</span>
                <span class="total-monto">$<?= number_format($totalCarrito, 2) ?></span>
              </div>
              <div class="detalle-items">
                <span><?= count($carrito) ?> producto(s) en el carrito</span>
              </div>
            </div>
          </div>
        </div>
        <div class="user-profile">
          <a href="EditarPerfilCliente.php">
            <img src="<?php echo htmlspecialchars($imagenPerfil); ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
          </div>
        </div>
      </header>

      <!-- Botones principales -->
      <div class="table-actions">
        <!-- Hacer Pedido -->
        <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
            <input type="hidden" name="accion" value="procesar">
            <button type="submit" class="btn-primary">Hacer Pedido</button>
        </form>

        <!-- Vaciar Carrito -->
        <form method="GET" action="CarritoCliente.php" style="display:inline-block;">
            <input type="hidden" name="accion" value="vaciar">
            <button type="submit" class="btn-secondary">Limpiar Carrito</button>
        </form>
      </div>

      <!-- Productos del carrito -->
      <section class="card-section">
        <?php if(count($carrito) > 0): ?>
          <?php foreach($carrito as $producto): ?>
            <div class="card">
              <img src="<?= htmlspecialchars($producto['Imagen']) ?>" 
                   alt="<?= htmlspecialchars($producto['Producto']) ?>" class="card-img">
              <div class="card-info">
                <h3><?= htmlspecialchars($producto['Producto']) ?></h3>
                <p>Descripción corta del producto</p>
                <span class="price">$<?= number_format($producto['Total'], 2) ?></span>
                <div class="card-actions">
                  <a class="btn-secondary" href="CarritoCliente.php?accion=sumar&idDetalle=<?= $producto['idDetalleCarrito'] ?>">Sumar</a>
                  <span class="quantity"><?= $producto['Cantidad'] ?></span>
                  <a class="btn-primary" href="CarritoCliente.php?accion=restar&idDetalle=<?= $producto['idDetalleCarrito'] ?>">Restar</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>El carrito está vacío.</p>
        <?php endif; ?>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>
