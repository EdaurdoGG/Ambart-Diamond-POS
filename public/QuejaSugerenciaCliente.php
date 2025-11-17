<?php
session_start();

// Verificar sesión activa y rol de cliente (rol = 2)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 3) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

$idCliente = $_SESSION['idPersona'];

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($idCliente));

// Obtener datos del cliente desde la vista ClientesRegistrados
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM ClientesRegistrados WHERE idCliente = ?");
$stmt->bind_param("i", $idCliente);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();

// Asignar valores seguros
$nombreCompleto = $cliente ? $cliente['Nombre'] . ' ' . $cliente['ApellidoPaterno'] . ' ' . $cliente['ApellidoMaterno'] : 'Cliente';
$rol = $cliente ? $cliente['Rol'] : 'Cliente';
$imagen = $cliente && !empty($cliente['Imagen']) ? $cliente['Imagen'] : 'imagenes/User.png';

// Variables para mensajes
$mensaje = '';
$tipoMensaje = 'success';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if (empty($tipo) || empty($descripcion)) {
        $mensaje = "Por favor completa todos los campos.";
        $tipoMensaje = 'error';
    } else {
        // Procedimiento almacenado
        $stmt = $conn->prepare("CALL RegistrarSugerenciaQueja(?, ?, ?)");
        if (!$stmt) {
            die("Error al preparar la consulta: " . $conn->error);
        }
        $stmt->bind_param("iss", $idCliente, $tipo, $descripcion);

        if ($stmt->execute()) {
            $mensaje = "Queja o sugerencia registrada correctamente.";
            $tipoMensaje = 'success';
        } else {
            $mensaje = "Error al registrar: " . $stmt->error;
            $tipoMensaje = 'error';
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
  <title>Buzón de Quejas o Sugerencias</title>
  <link rel="stylesheet" href="QuejaSugerenciaCliente.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
  <style>
    .alert-message { 
      position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
      padding: 16px 28px; border-radius: 12px; font-size: 16px; font-weight: 600; text-align: center; color: #fff; 
      z-index: 9999;
    }
    .alert-success { background-color: #4CAF50; }
    .alert-error { background-color: #f44336; }
  </style>
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
          <a href="CarritoCliente.php" class="menu-item">
            <img src="imagenes/Carrito.png" alt="CarritoCliente" class="icon"> Carrito
          </a>
          <a href="ListaProductosCliente.php" class="menu-item">
            <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
          </a>
          <a href="ListaPedidosCliente.php" class="menu-item">
            <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
          </a>
          <a href="QuejaSugerenciaCliente.php" class="menu-item active">
            <img src="imagenes/QuejasSujerencias.png" alt="QuejasSugerencias" class="icon"> Quejas / Sugerencias
          </a>
          <div class="menu-separator"></div>
          <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión</a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <h2>Buzón de Quejas y Sugerencias</h2>
        <div class="user-profile">
          <a href="EditarPerfilCliente.php">
            <img src="<?php echo htmlspecialchars($imagen); ?>" alt="Avatar" class="avatar"> 
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
          </div>
        </div>
      </header>

      <!-- Formulario -->
      <section class="form-section">
        <form class="form-card" action="" method="POST">
          <!-- Tipo -->
          <div class="form-group">
            <select id="tipo" name="tipo" required>
              <option value="" disabled selected>Selecciona una opción</option>
              <option value="Sugerencia">Sugerencia</option>
              <option value="Queja">Queja</option>
            </select>
            <label for="tipo">Tipo</label>
          </div>

          <!-- Descripción -->
          <div class="form-group">
            <textarea id="descripcion" name="descripcion" placeholder=" " rows="4" maxlength="255" required></textarea>
            <label for="descripcion">Descripción</label>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">Enviar</button>
          </div>
        </form>
      </section>

      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>

  <!-- Mensaje flotante -->
  <?php if (!empty($mensaje)): ?>
    <div id="mensaje-flotante" class="alert-message <?= ($tipoMensaje === 'success') ? 'alert-success' : 'alert-error' ?>">
      <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>
</body>
</html>
