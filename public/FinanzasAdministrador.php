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

// Datos del administrador logueado
$idAdmin = $_SESSION['idPersona'];

// Obtener información del administrador
$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, r.NombreRol 
                        FROM Persona p
                        JOIN Rol r ON p.idRol = r.idRol
                        WHERE p.idPersona = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

// Manejar filtro de fecha
$fechaFiltro = $_GET['fecha'] ?? date('Y-m-d'); // si no hay fecha, tomar fecha actual

// Consultar la vista VistaFinanzasPorFecha filtrando por fecha
$sqlFinanzas = "SELECT *
                FROM VistaFinanzasPorFecha
                WHERE DATE(FechaVenta) = ?
                ORDER BY FechaVenta DESC";

$stmt = $conn->prepare($sqlFinanzas);
$stmt->bind_param("s", $fechaFiltro);
$stmt->execute();
$resultFinanzas = $stmt->get_result();

$ingresos = 0;
$gastos = 0;
$pedidos = 0;
$movimientosRecientes = [];

while ($row = $resultFinanzas->fetch_assoc()) {
    $ingresos += floatval($row['TotalVenta']);
    $gastos += floatval($row['TotalInvertido']);
    $pedidos++;
    $movimientosRecientes[] = [
        'Fecha' => $row['FechaVenta'],
        'Concepto' => 'Venta producto',
        'Tipo' => 'Ingreso',
        'Monto' => floatval($row['TotalVenta']),
        'Estado' => 'Activa' // Puedes adaptarlo según el estatus de la venta
    ];
}

$gananciaNeta = $ingresos - $gastos;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finanzas</title>
<link rel="stylesheet" href="FinanzasAdministrador.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
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
        <a href="ListaProductosAdministrador.php" class="menu-item">
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
        <a href="FinanzasAdministrador.php" class="menu-item active">
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

  <!-- Contenido principal -->
  <main class="main-content">
    <header class="topbar">

      <div class="search-box">
        <form method="GET" action="">
          <input type="date" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
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
            <img src="<?= htmlspecialchars($adminImagen ?: 'imagenes/User.png') ?>" alt="Avatar" class="avatar">
          </a>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars("$adminNombre $adminApellidoP $adminApellidoM") ?></span>
            <span class="user-role"><?= htmlspecialchars($adminRol) ?></span>
        </div>
      </div>
    </header>

    <!-- Tarjetas resumen -->
    <section class="cards-overview">
      <div class="card finance-card">
        <h3>Ingresos</h3>
        <p>$<?= number_format($ingresos, 2) ?></p>
      </div>
      <div class="card finance-card">
        <h3>Gastos</h3>
        <p>$<?= number_format($gastos, 2) ?></p>
      </div>
      <div class="card finance-card highlight">
        <h3>Ganancia Neta</h3>
        <p>$<?= number_format($gananciaNeta, 2) ?></p>
      </div>
      <div class="card finance-card">
        <h3>Operaciones</h3>
        <p><?= $pedidos ?></p>
      </div>
    </section>

    <!-- Gráfica -->
    <section class="finance-chart">
      <h3>Movimientos del día <?= htmlspecialchars($fechaFiltro) ?></h3>
      <canvas id="financeChart"></canvas>
    </section>

    <!-- Tabla -->
    <section class="finance-table">
      <h3>Movimientos Recientes</h3>
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Tipo</th>
            <th>Monto</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($movimientosRecientes)): ?>
            <?php foreach ($movimientosRecientes as $mov): ?>
              <tr>
                <td><?= htmlspecialchars($mov['Fecha']) ?></td>
                <td><?= htmlspecialchars($mov['Concepto']) ?></td>
                <td><?= htmlspecialchars($mov['Tipo']) ?></td>
                <td>$<?= number_format($mov['Monto'],2) ?></td>
                <td><?= htmlspecialchars($mov['Estado']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">No hay movimientos registrados para esta fecha.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
    <footer class="site-footer">
      <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
    </footer>
  </main>
</div>

<script>
const ctx = document.getElementById('financeChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($movimientosRecientes, 'Fecha')) ?>,
    datasets: [{
      label: 'Ingresos',
      data: <?= json_encode(array_column($movimientosRecientes, 'Monto')) ?>,
      borderColor: '#985008',
      backgroundColor: 'rgba(152, 80, 8, 0.2)',
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { labels: { color: '#333' } } },
    scales: { x: { ticks: { color: '#333' } }, y: { ticks: { color: '#333' } } }
  }
});
</script>

</body>
</html>

<?php $conn->close(); ?>
