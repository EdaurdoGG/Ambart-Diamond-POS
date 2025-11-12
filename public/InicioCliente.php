<?php
session_start();

// Validar sesión
if (!isset($_SESSION['idPersona'])) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Consulta usando la vista Vista_ClientesRegistrados
$stmt = $conn->prepare("SELECT * FROM ClientesRegistrados WHERE idCliente = ?");
$stmt->bind_param("i", $idPersona);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Usuario no encontrado o inactivo
    $usuario = [
        'Nombre' => 'Invitado',
        'ApellidoPaterno' => '',
        'ApellidoMaterno' => '',
        'Imagen' => 'imagenes/User.png',
        'Rol' => 'Invitado'
    ];
} else {
    $usuario = $result->fetch_assoc();
}

// Construir nombre completo y validar imagen
$nombreCompleto = $usuario['Nombre'] . ' ' . $usuario['ApellidoPaterno'] . ' ' . $usuario['ApellidoMaterno'];
$imagenUsuario = !empty($usuario['Imagen']) ? $usuario['Imagen'] : 'imagenes/User.png';
$rolUsuario = $usuario['Rol'];

// Liberar resultados
$conn->next_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Cliente - Papelería Online</title>
  <link rel="stylesheet" href="InicioCliente.css">
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
          <a href="InicioCliente.php" class="menu-item active">
              <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
          </a>
          <a href="CarritoCliente.php" class="menu-item">
              <img src="imagenes/Carrito.png" alt="CarritoEmpleado" class="icon"> Carrito
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

    <!-- Contenido principal -->
    <main class="main-content">

      <!-- Topbar -->
      <header class="topbar">
        <h2>Bienvenido de nuevo, <span style="color:#985008;"><?php echo htmlspecialchars($usuario['Nombre']); ?></span></h2>
        <div class="user-profile">
          <a href="EditarPerfilCliente.php">
            <img src="<?php echo htmlspecialchars($imagenUsuario); ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
          </div>
        </div>
      </header>

      <!-- KPI Cards -->
      <section class="kpi-cards">
        <div class="card">
          <h3>Pedidos Activos</h3>
          <p>5</p>
        </div>
        <div class="card">
          <h3>Ofertas Disponibles</h3>
          <p>3</p>
        </div>
        <div class="card">
          <h3>Total Gastado</h3>
          <p>$2,450</p>
        </div>
      </section>

      <!-- Widgets -->
      <section class="dashboard-widgets">
        <div class="widget">
          <h3>Últimas Compras</h3>
          <ul>
            <li>Cuaderno Profesional - $120</li>
            <li>Paquete de Lápices - $350</li>
            <li>Agenda 2025 - $90</li>
          </ul>
        </div>
        <div class="widget">
          <h3>Promociones Actuales</h3>
          <ul>
            <li>20% de descuento en cuadernos</li>
            <li>Compra 2 lápices, llévate 1 gratis</li>
            <li>Envío gratis en pedidos mayores a $500</li>
          </ul>
        </div>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>
</body>
</html>
