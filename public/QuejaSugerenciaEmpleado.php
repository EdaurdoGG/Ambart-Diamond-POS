<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

$idEmpleado = $_SESSION['idPersona'];

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($_SESSION['idPersona']));

// Obtener datos del empleado desde la vista EmpleadosRegistrados
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();
$stmt->close();

// Asignar valores seguros
$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagen = $empleado && !empty($empleado['Imagen']) ? $empleado['Imagen'] : 'imagenes/User.png';

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
        $stmt->bind_param("iss", $idEmpleado, $tipo, $descripcion);

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
  <title>Registrar Queja o Sugerencia</title>
  <link rel="stylesheet" href="QuejaSugerenciaEmpleado.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
  <style>
    .mensaje-success { color: green; margin-bottom: 15px; }
    .mensaje-error { color: red; margin-bottom: 15px; }
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
        <a href="InicioEmpleados.php" class="menu-item">
            <img src="imagenes/Inicio.png" alt="Inicio" class="icon"> Inicio
        </a>
        <a href="CarritoEmpleado.php" class="menu-item">
          <img src="imagenes/Caja.png" alt="CarritoEmpleado" class="icon"> Caja
        </a>
        <a href="ListaProductosEmpleado.php" class="menu-item">
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
        <a href="QuejaSugerenciaEmpleado.php" class="menu-item active">
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
        <h2>Buzón de Quejas y Sugerencias</h2>
        <div class="user-profile">
          <a href="EditarPerfilEmpleado.php">
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

          <!-- Mensaje -->
          <?php if (!empty($mensaje)): ?>
            <div class="mensaje-<?php echo $tipoMensaje; ?>">
              <?php echo htmlspecialchars($mensaje); ?>
            </div>
          <?php endif; ?>

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
</body>
</html>
