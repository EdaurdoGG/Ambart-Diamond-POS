<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

// Activar reporte de errores MySQLi (para depuración segura)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

// Datos del usuario
$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagen = $empleado && $empleado['Imagen'] ? $empleado['Imagen'] : 'imagenes/User.png';

// Obtener todas las ventas del día del empleado logueado 
$hoy = date("Y-m-d");
$ventasHoy = [];
$sql = "SELECT v.idVenta, dv.idDetalleVenta, p.Nombre AS Producto, dv.Cantidad
        FROM Venta v
        JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
        JOIN Producto p ON dv.idProducto = p.idProducto
        WHERE v.idPersona = ? AND DATE(v.Fecha) = ?
        ORDER BY v.Fecha DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("is", $idEmpleado, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ventasHoy[$row['idVenta']]['productos'][] = [
            'idDetalleVenta' => $row['idDetalleVenta'],
            'nombre' => $row['Producto'],
            'cantidad' => $row['Cantidad']
        ];
    }
    $stmt->close();
}

// Procesar formulario si se envió
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idVenta = intval($_POST['Venta']);
    $tipoDevolucion = $_POST['tipoDevolucion'];
    $motivo = trim($_POST['motivo']);
    $motivo = $motivo ?: 'Sin motivo especificado'; // evitar NULL

    if ($tipoDevolucion === 'Producto') {
        $idDetalleVenta = intval($_POST['idDetalleVenta']);
        $cantidadDevuelta = intval($_POST['cantidadDevuelta']);

        $stmt = $conn->prepare("CALL DevolverProductoIndividual(?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iiisi", $idVenta, $idDetalleVenta, $cantidadDevuelta, $motivo, $idEmpleado);
        } else {
            $mensaje = "Error al preparar la llamada a DevolverProductoIndividual.";
        }

    } elseif ($tipoDevolucion === 'VentaCompleta') {
        $stmt = $conn->prepare("CALL DevolverVentaCompleta(?, ?, ?)");
        if ($stmt) {
            // Orden correcto: (INT, STRING, INT)
            $stmt->bind_param("isi", $idVenta, $motivo, $idEmpleado);
        } else {
            $mensaje = "Error al preparar la llamada a DevolverVentaCompleta.";
        }
    }

    if (isset($stmt) && $stmt) {
        try {
            $stmt->execute();
            $mensaje = "✅ Devolución registrada correctamente.";
        } catch (mysqli_sql_exception $e) {
            $mensaje = "❌ Error al registrar la devolución: " . $e->getMessage();
        }
        $stmt->close();

        // Limpia resultados de procedimientos previos
        while ($conn->more_results() && $conn->next_result()) {;}
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Devolución</title>
<link rel="stylesheet" href="HacerDevolucionesEmpleado.css">
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
      <a href="ListaProductosEmpleado.php" class="menu-item">
          <img src="imagenes/Productos.png" alt="Productos" class="icon"> Productos
      </a>
      <a href="HistorialVentasEmpleado.php" class="menu-item">
          <img src="imagenes/Ventas.png" alt="HistorialVentas" class="icon"> Historial Ventas
      </a>
      <a href="ListaPedidosEmpleado.php" class="menu-item">
          <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
      </a>
      <a href="ListaDevolucionesEmpleado.php" class="menu-item active">
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
    <!-- Topbar -->
    <header class="topbar">
      <h2>Registrar Devolución</h2>
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

    <!-- Mensaje -->
    <?php if($mensaje): ?>
      <div style="text-align:center; margin:10px; color:green;"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <!-- Formulario -->
    <section class="form-section">
      <form class="form-card" action="" method="POST">
        <!-- SOLO NÚMERO DE VENTA -->
        <div class="form-group">
          <select id="Venta" name="Venta" required>
            <option value="" disabled selected>Seleccione una venta del día</option>
            <?php foreach($ventasHoy as $idVenta => $venta): ?>
              <option value="<?php echo $idVenta; ?>">
                Venta - Numero <?php echo $idVenta; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label for="Venta">Venta del día</label>
        </div>

        <div class="form-group">
          <select id="tipoDevolucion" name="tipoDevolucion" required>
            <option value="" disabled selected>Seleccione tipo de devolución</option>
            <option value="VentaCompleta">Devolver Venta Completa</option>
            <option value="Producto">Devolver Producto Específico</option>
          </select>
          <label for="tipoDevolucion">Tipo de Devolución</label>
        </div>

        <div class="form-group conditional producto-only">
          <select id="idDetalleVenta" name="idDetalleVenta">
            <option value="">Seleccione producto</option>
          </select>
          <label for="idDetalleVenta">Producto a Devolver</label>
        </div>

        <div class="form-group conditional producto-only">
          <input type="number" id="cantidadDevuelta" name="cantidadDevuelta" min="1" placeholder=" ">
          <label for="cantidadDevuelta">Cantidad a Devolver</label>
        </div>

        <div class="form-group">
          <textarea id="motivo" name="motivo" placeholder=" " rows="3" maxlength="200" required></textarea>
          <label for="motivo">Motivo de la Devolución</label>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-submit">Registrar Devolución</button>
        </div>
      </form>
    </section>
    <footer class="site-footer">
      <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
    </footer>
  </main>
</div>

<script>
const tipoDevolucion = document.getElementById("tipoDevolucion");
const productoFields = document.querySelectorAll(".producto-only");

tipoDevolucion.addEventListener("change", () => {
  if (tipoDevolucion.value === "Producto") {
    productoFields.forEach(el => el.style.display = "block");
  } else {
    productoFields.forEach(el => el.style.display = "none");
  }
});
productoFields.forEach(el => el.style.display = "none");

// --- JavaScript para cargar productos según venta seleccionada ---
const ventasHoy = <?php echo json_encode($ventasHoy); ?>;
const ventaSelect = document.getElementById("Venta");
const detalleSelect = document.getElementById("idDetalleVenta");

ventaSelect.addEventListener("change", () => {
    const ventaId = ventaSelect.value;
    detalleSelect.innerHTML = '<option value="">Seleccione producto</option>';
    if (ventasHoy[ventaId]) {
        ventasHoy[ventaId].productos.forEach(p => {
            const opt = document.createElement("option");
            opt.value = p.idDetalleVenta;
            opt.textContent = p.nombre + " (x" + p.cantidad + ")";
            detalleSelect.appendChild(opt);
        });
    }
});
</script>
</body>
</html>
