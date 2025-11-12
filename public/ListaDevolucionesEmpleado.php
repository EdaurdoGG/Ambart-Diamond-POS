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

// Datos del usuario 
$nombreCompleto = $empleado ? $empleado['Nombre'] . ' ' . $empleado['ApellidoPaterno'] . ' ' . $empleado['ApellidoMaterno'] : 'Empleado';
$rol = $empleado ? $empleado['Rol'] : 'Empleado';
$imagen = $empleado && $empleado['Imagen'] ? $empleado['Imagen'] : 'imagenes/User.png';

// Obtener todas las devoluciones 
$sql = "SELECT * FROM DevolucionesRealizadas ORDER BY Fecha DESC";
$result = $conn->query($sql);

$devoluciones = [];
$totalHoy = 0;
$totalProductosHoy = 0;
$hoy = date("Y-m-d");

// Agrupar devoluciones por ID
while ($row = $result->fetch_assoc()) {
    $idDevolucion = $row['idDevolucion'];

    if (!isset($devoluciones[$idDevolucion])) {
        $devoluciones[$idDevolucion] = [
            'idDevolucion' => $idDevolucion,
            'Fecha' => $row['Fecha'],
            'Motivo' => $row['Motivo'],
            'Productos' => [],
            'TotalCantidad' => 0
        ];
    }

    $devoluciones[$idDevolucion]['Productos'][] = [
        'NombreProducto' => $row['NombreProducto'],
        'idVenta' => $row['idVenta'],
        'CantidadDevuelta' => $row['CantidadDevuelta'],
        'TotalDevuelto' => $row['TotalDevuelto']
    ];

    $devoluciones[$idDevolucion]['TotalCantidad'] += $row['CantidadDevuelta'];

    // Calcular totales del día actual
    if (substr($row['Fecha'],0,10) === $hoy) {
        $totalHoy++;
        $totalProductosHoy += $row['CantidadDevuelta'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Devoluciones</title>
<link rel="stylesheet" href="ListaDevolucionesEmpleado.css">
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
      <h2>Mis Devoluciones</h2>
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

    <div class="table-actions">
        <a href="HacerDevolucionesEmpleado.php">
          <button class="btn-primary">Hacer Devolución</button>
        </a>

        <form method="GET" action="ExportarDevolucionesEmpleado.php" style="display:inline;">
          <button type="submit" class="btn-secondary">Exportar Devolucione</button>
        </form>
      </div>

    <!-- Resumen -->
    <section class="cards-overview">
      <div class="finance-card highlight">
        <h3>Total de devoluciones hoy</h3>
        <p><?php echo $totalHoy; ?></p>
      </div>
      <div class="finance-card">
        <h3>Productos devueltos</h3>
        <p><?php echo $totalProductosHoy; ?></p>
      </div>
    </section>

    <!-- Tabla de devoluciones -->
    <section class="finance-table">
      <h3>Detalle de mis devoluciones</h3>
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Numero de Devolución</th>
            <th>Numero de Venta</th>
            <th>Producto</th>
            <th>Cantidad Devuelta</th>
            <th>Motivo</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($devoluciones) > 0): ?>
            <?php foreach($devoluciones as $dev): ?>
              <?php foreach($dev['Productos'] as $prod): ?>
                <tr>
                  <td><?php echo $dev['Fecha']; ?></td>
                  <td><?php echo $dev['idDevolucion']; ?></td>
                  <td><?php echo $prod['idVenta']; ?></td>
                  <td><?php echo htmlspecialchars($prod['NombreProducto']); ?></td>
                  <td><?php echo $prod['CantidadDevuelta']; ?></td>
                  <td><?php echo htmlspecialchars($dev['Motivo']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align:center; color:gray;">No tienes devoluciones registradas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
    <footer class="site-footer">
      <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
    </footer>
  </main>
</div>
</body>
</html>
