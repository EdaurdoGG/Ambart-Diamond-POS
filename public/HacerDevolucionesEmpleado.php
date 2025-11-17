<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Conexión
require_once "../includes/conexion.php";

$idEmpleado = $_SESSION['idPersona'];
$conn->query("SET @id_usuario_actual = " . intval($idEmpleado));

// Obtener datos del empleado
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmt->bind_param("i", $idEmpleado);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();
$stmt->close();

$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagen = $empleado && $empleado['Imagen'] ? $empleado['Imagen'] : 'imagenes/User.png';

// Obtener ventas de hoy
$hoy = date("Y-m-d");
$ventasHoy = [];

$sql = "SELECT v.idVenta, dv.idDetalleVenta, p.Nombre AS Producto, dv.Cantidad
        FROM Venta v
        JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
        JOIN Producto p ON dv.idProducto = p.idProducto
        WHERE v.idPersona = ? AND DATE(v.Fecha) = ?
        ORDER BY v.Fecha DESC";

$stmt = $conn->prepare($sql);
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

$mensaje = "";
$tipoMensaje = ""; // success o error

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idVenta = intval($_POST['Venta']);
    $tipoDevolucion = $_POST['tipoDevolucion'];
    $motivo = trim($_POST['motivo']) ?: "Sin motivo especificado";

    try {
        if ($tipoDevolucion === 'Producto') {
            $idDetalleVenta = intval($_POST['idDetalleVenta']);
            $cantidadDevuelta = intval($_POST['cantidadDevuelta']);

            $stmt = $conn->prepare("CALL DevolverProductoIndividual(?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisi", $idVenta, $idDetalleVenta, $cantidadDevuelta, $motivo, $idEmpleado);

        } else {
            $stmt = $conn->prepare("CALL DevolverVentaCompleta(?, ?, ?)");
            $stmt->bind_param("isi", $idVenta, $motivo, $idEmpleado);
        }

        $stmt->execute();
        $mensaje = "Devolución realizada con éxito.";
        $tipoMensaje = "success"; // verde

    } catch (mysqli_sql_exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipoMensaje = "error"; // rojo
    }

    while ($conn->more_results() && $conn->next_result()) {}
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

<?php if ($mensaje): ?>
<div class="floating-message <?php echo $tipoMensaje === 'success' ? 'floating-success' : 'floating-error'; ?>">
    <?php echo htmlspecialchars($mensaje); ?>
</div>
<?php endif; ?>

<div class="dashboard-container">
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
  <main class="main-content">

    <header class="topbar">
      <h2>Registrar Devolución</h2>
      <div class="user-profile">
        <a href="EditarPerfilEmpleado.php">
          <img src="<?php echo htmlspecialchars($imagen); ?>" class="avatar">
        </a>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($rol); ?></span>
        </div>
      </div>
    </header>

    <section class="form-section">
      <form class="form-card" action="" method="POST">

        <div class="form-group">
          <select name="Venta" id="Venta" required>
            <option value="" disabled selected>Seleccione una venta del día</option>
            <?php foreach($ventasHoy as $idVenta => $v): ?>
              <option value="<?= $idVenta ?>">Venta - Número <?= $idVenta ?></option>
            <?php endforeach; ?>
          </select>
          <label>Venta del día</label>
        </div>

        <div class="form-group">
          <select name="tipoDevolucion" id="tipoDevolucion" required>
            <option value="" disabled selected>Seleccione tipo</option>
            <option value="VentaCompleta">Devolver Venta Completa</option>
            <option value="Producto">Devolver Producto</option>
          </select>
          <label>Tipo de Devolución</label>
        </div>

        <div class="form-group conditional producto-only">
          <select id="idDetalleVenta" name="idDetalleVenta">
            <option value="">Seleccione producto</option>
          </select>
          <label>Producto a Devolver</label>
        </div>

        <div class="form-group conditional producto-only">
          <input type="number" name="cantidadDevuelta" min="1" placeholder=" ">
          <label>Cantidad</label>
        </div>

        <div class="form-group">
          <textarea name="motivo" placeholder=" " required></textarea>
          <label>Motivo de la devolución</label>
        </div>

        <div class="form-actions">
          <button class="btn-submit" type="submit">Registrar Devolución</button>
        </div>

      </form>
    </section>

  </main>
</div>

<script>
const tipoDevolucion = document.getElementById("tipoDevolucion");
const productoFields = document.querySelectorAll(".producto-only");

tipoDevolucion.addEventListener("change", () => {
    const visible = tipoDevolucion.value === "Producto";
    productoFields.forEach(e => e.style.display = visible ? "block" : "none");
});
productoFields.forEach(e => e.style.display = "none");

const ventasHoy = <?php echo json_encode($ventasHoy); ?>;
const ventaSelect = document.getElementById("Venta");
const detalleSelect = document.getElementById("idDetalleVenta");

ventaSelect.addEventListener("change", () => {
    const id = ventaSelect.value;
    detalleSelect.innerHTML = '<option value="">Seleccione producto</option>';

    if (ventasHoy[id]) {
        ventasHoy[id].productos.forEach(p => {
            const opt = document.createElement("option");
            opt.value = p.idDetalleVenta;
            opt.textContent = `${p.nombre} (x${p.cantidad})`;
            detalleSelect.appendChild(opt);
        });
    }
});
</script>

</body>
</html>

