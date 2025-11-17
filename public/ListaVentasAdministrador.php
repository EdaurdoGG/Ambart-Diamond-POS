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

// Datos del administrador
$idAdmin = $_SESSION['idPersona'];
$stmtAdmin = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                             FROM AdministradoresRegistrados 
                             WHERE idAdministrador = ?");
$stmtAdmin->bind_param("i", $idAdmin);
$stmtAdmin->execute();
$stmtAdmin->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmtAdmin->fetch();
$stmtAdmin->close();

// Filtrado por fecha
$fechaFiltro = $_GET['date'] ?? date('Y-m-d');

// Resumen general
$stmtResumen = $conn->prepare("
    SELECT COUNT(DISTINCT NumeroVenta) AS numTransacciones, SUM(Total) AS totalVentas
    FROM VentasDiariasPorEmpleado
    WHERE Fecha = ?
");
$stmtResumen->bind_param("s", $fechaFiltro);
$stmtResumen->execute();
$stmtResumen->bind_result($numTransacciones, $totalVentas);
$stmtResumen->fetch();
$stmtResumen->close();

// Ventas por empleado
$stmtEmpleados = $conn->prepare("
    SELECT Empleado, COUNT(DISTINCT NumeroVenta) AS numVentas, SUM(Total) AS totalVentasEmpleado
    FROM VentasDiariasPorEmpleado
    WHERE Fecha = ?
    GROUP BY Empleado
");
$stmtEmpleados->bind_param("s", $fechaFiltro);
$stmtEmpleados->execute();
$resultEmpleados = $stmtEmpleados->get_result();
$empleados = $resultEmpleados->fetch_all(MYSQLI_ASSOC);
$stmtEmpleados->close();

// Ventas agrupadas por NumeroVenta
$stmtVentas = $conn->prepare("
    SELECT NumeroVenta, Empleado, MIN(Hora) AS Hora, SUM(Total) AS TotalVenta
    FROM VentasDiariasPorEmpleado
    WHERE Fecha = ?
    GROUP BY NumeroVenta, Empleado
    ORDER BY Hora ASC
");
$stmtVentas->bind_param("s", $fechaFiltro);
$stmtVentas->execute();
$resultVentas = $stmtVentas->get_result();
$ventas = $resultVentas->fetch_all(MYSQLI_ASSOC);
$stmtVentas->close();

// Detalles completos por venta (para modal)
$stmtDetalles = $conn->prepare("
    SELECT NumeroVenta, Producto, Cantidad, Total
    FROM VentasDiariasPorEmpleado
    WHERE Fecha = ?
    ORDER BY NumeroVenta
");
$stmtDetalles->bind_param("s", $fechaFiltro);
$stmtDetalles->execute();
$resultDetalles = $stmtDetalles->get_result();
$detallesPorVenta = [];
while ($row = $resultDetalles->fetch_assoc()) {
    $detallesPorVenta[$row['NumeroVenta']][] = $row;
}
$stmtDetalles->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ventas por Empleado</title>
  <link rel="stylesheet" href="ListaVentasAdministrador.css">
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
        <a href="ListaVentasAdministrador.php" class="menu-item active">
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
          <form method="GET" action="">
            <input type="date" name="date" value="<?= htmlspecialchars($fechaFiltro) ?>">
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
            <img src="<?php echo htmlspecialchars($adminImagen); ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars("$adminNombre $adminApellidoP $adminApellidoM"); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($adminRol); ?></span>
          </div>
        </div>
      </header>

      <!-- Tarjetas resumen -->
      <section class="cards-overview">
        <div class="finance-card highlight">
          <h3>Total ventas del día</h3>
          <p>$<?php echo number_format($totalVentas ?? 0, 2); ?></p>
        </div>
        <div class="finance-card">
          <h3>Número de transacciones</h3>
          <p><?php echo $numTransacciones ?? 0; ?></p>
        </div>
      </section>

      <!-- Ventas por empleado -->
      <section class="employee-sales">
        <h3>Ventas por empleado</h3>
        <div class="employee-grid">
          <?php foreach ($empleados as $emp): ?>
            <div class="employee-card">
              <h4><?php echo htmlspecialchars($emp['Empleado']); ?></h4>
              <p>Total ventas: $<?php echo number_format($emp['totalVentasEmpleado'],2); ?></p>
              <p>Transacciones: <?php echo $emp['numVentas']; ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Detalle de ventas -->
      <section class="finance-table">
        <h3>Ventas del dia</h3>
        <table>
          <thead>
            <tr>
              <th>Número Venta</th>
              <th>Hora</th>
              <th>Empleado</th>
              <th>Total</th>
              <th>Detalles</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $venta): ?>
              <tr>
                <td><?= htmlspecialchars($venta['NumeroVenta']); ?></td>
                <td><?= htmlspecialchars($venta['Hora']); ?></td>
                <td><?= htmlspecialchars($venta['Empleado']); ?></td>
                <td>$<?= number_format($venta['TotalVenta'], 2); ?></td>
                <td>
                  <button class="btn-primary" onclick='verDetalles("<?= $venta["NumeroVenta"]; ?>")'>Ver detalles</button>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(empty($ventas)): ?>
              <tr><td colspan="5">No hay ventas para esta fecha.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>

  <!-- MODAL -->
  <div id="modalDetalle" class="modal">
    <div class="modal-content">
      <span class="close" onclick="cerrarModal()">&times;</span>
      <h3>Detalle de la Venta</h3>
      <div id="detalle-contenido"></div>
    </div>
  </div>

  <script>
    const detalles = <?= json_encode($detallesPorVenta); ?>;

    function verDetalles(numero) {
      const modal = document.getElementById('modalDetalle');
      const contenedor = document.getElementById('detalle-contenido');
      const venta = detalles[numero];
      if (!venta) {
        contenedor.innerHTML = "<p>No se encontraron detalles.</p>";
      } else {
        let html = "<table><thead><tr><th>Producto</th><th>Cantidad</th><th>Total</th></tr></thead><tbody>";
        venta.forEach(item => {
          html += `<tr>
                    <td>${item.Producto}</td>
                    <td class="cantidad">${item.Cantidad}</td>
                    <td>$${parseFloat(item.Total).toFixed(2)}</td>
                   </tr>`;
        });
        html += "</tbody></table>";
        contenedor.innerHTML = html;
      }
      modal.style.display = "flex";
    }

    function cerrarModal() {
      document.getElementById('modalDetalle').style.display = 'none';
    }

    window.onclick = function(e) {
      if (e.target == document.getElementById('modalDetalle')) cerrarModal();
    }
  </script>
</body>
</html>

<?php $conn->close(); ?>
