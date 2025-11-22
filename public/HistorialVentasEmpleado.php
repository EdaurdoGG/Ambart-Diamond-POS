<?php
session_start();

// Verificar sesión activa y rol (empleado)
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idPersona = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Obtener información del empleado logueado
$stmtEmpleado = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol FROM EmpleadosRegistrados WHERE idEmpleado = ?");
$stmtEmpleado->bind_param("i", $idPersona);
$stmtEmpleado->execute();
$stmtEmpleado->bind_result($nombre, $apellidoP, $apellidoM, $imagen, $rolEmpleado);
$stmtEmpleado->fetch();
$stmtEmpleado->close();

$nombreCompleto = "$nombre $apellidoP $apellidoM";
$imagenPerfil = $imagen ?: 'imagenes/User.png';
$rol = $rolEmpleado ?: 'Empleado';

// Fecha seleccionada
$fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');

//CONSULTA 1: RESUMEN DEL DÍA (solo ventas activas)
$stmtResumen = $conn->prepare("
    SELECT COUNT(DISTINCT NumeroVenta) AS numTransacciones, SUM(Subtotal) AS totalVentas
    FROM VistaVentasEmpleado
    WHERE Fecha = ? AND idEmpleado = ? AND Estatus = 'Activa'
");
$stmtResumen->bind_param("si", $fechaSeleccionada, $idPersona);
$stmtResumen->execute();
$stmtResumen->bind_result($numTransacciones, $totalVentas);
$stmtResumen->fetch();
$stmtResumen->close();

// CONSULTA 2: VENTAS AGRUPADAS POR NUMEROVENTA (solo activas)
$stmtVentas = $conn->prepare("
    SELECT NumeroVenta, MIN(Hora) AS Hora, SUM(Subtotal) AS TotalVenta
    FROM VistaVentasEmpleado
    WHERE Fecha = ? AND idEmpleado = ? AND Estatus = 'Activa'
    GROUP BY NumeroVenta
    ORDER BY Hora ASC
");
$stmtVentas->bind_param("si", $fechaSeleccionada, $idPersona);
$stmtVentas->execute();
$resultVentas = $stmtVentas->get_result();
$ventas = $resultVentas->fetch_all(MYSQLI_ASSOC);
$stmtVentas->close();

// CONSULTA 3: DETALLES DE LAS VENTAS (solo activas) 
$stmtDetalles = $conn->prepare("
    SELECT NumeroVenta, Producto, Cantidad, Subtotal, Estatus
    FROM VistaVentasEmpleado
    WHERE Fecha = ? AND idEmpleado = ? AND Estatus = 'Activa'
    ORDER BY NumeroVenta
");
$stmtDetalles->bind_param("si", $fechaSeleccionada, $idPersona);
$stmtDetalles->execute();
$resultDetalles = $stmtDetalles->get_result();
$detallesPorVenta = [];
while ($row = $resultDetalles->fetch_assoc()) {
    $detallesPorVenta[$row['NumeroVenta']][] = $row;
}
$stmtDetalles->close();

$conn->close();

// --- NUEVO: MENSAJE SI NO HAY RESULTADOS ---
$mostrarMensaje = isset($_GET['fecha']) && empty($ventas);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Ventas</title>
  <link rel="stylesheet" href="HistorialVentasEmpleado.css">
  <link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>

<?php if ($mostrarMensaje): ?>
<div id="alerta" class="alert-message">No se encontraron ventas para esta fecha</div>

<script>
    setTimeout(() => {
        document.getElementById("alerta").classList.add("show");
    }, 200);

    setTimeout(() => {
        document.getElementById("alerta").classList.remove("show");
    }, 3200);
</script>
<?php endif; ?>

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
        <a href="HistorialVentasEmpleado.php" class="menu-item active">
          <img src="imagenes/Ventas.png" alt="Ventas" class="icon"> Historial Ventas
        </a>
        <a href="ListaPedidosEmpleado.php" class="menu-item">
          <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
        </a>
        <a href="ListaDevolucionesEmpleado.php" class="menu-item">
          <img src="imagenes/Devoluciones.png" alt="Devoluciones" class="icon"> Devoluciones
        </a>
        <a href="QuejaSugerenciaEmpleado.php" class="menu-item">
          <img src="imagenes/QuejasSujerencias.png" alt="Quejas" class="icon"> Quejas / Sugerencias
        </a>
        <div class="menu-separator"></div>
        <a href="Login.php" class="menu-item logout">
          <img src="imagenes/salir.png" alt="Salir" class="icon"> Cerrar sesión
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="main-content">
      <header class="topbar">
        <div class="search-box">
          <form method="GET" action="">
            <input type="date" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">
            <button type="submit" class="search-button">
              <img src="imagenes/Buscar.png" alt="Buscar" class="search-icon">
            </button>
          </form>
        </div>

        <div class="user-profile">
          <a href="EditarPerfilEmpleado.php">
            <img src="<?= htmlspecialchars($imagenPerfil) ?>" alt="Avatar" class="avatar">
          </a>
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
            <span class="user-role"><?= htmlspecialchars($rol) ?></span>
          </div>
        </div>
      </header>

      <section class="cards-overview">
        <div class="finance-card highlight">
          <h3>Total de mis ventas (<?= htmlspecialchars($fechaSeleccionada) ?>)</h3>
          <p>$<?= number_format($totalVentas ?? 0, 2) ?></p>
        </div>
        <div class="finance-card">
          <h3>Mis transacciones</h3>
          <p><?= $numTransacciones ?? 0 ?></p>
        </div>
      </section>

      <section class="finance-table">
        <h3>Mis Ventas Activas</h3>
        <table>
          <thead>
            <tr>
              <th>Número Venta</th>
              <th>Hora</th>
              <th>Total</th>
              <th>Detalles</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $venta): ?>
              <tr>
                <td><?= htmlspecialchars($venta['NumeroVenta']) ?></td>
                <td><?= htmlspecialchars($venta['Hora']) ?></td>
                <td>$<?= number_format($venta['TotalVenta'], 2) ?></td>
                <td><button class="btn-primary" onclick='verDetalles("<?= $venta["NumeroVenta"]; ?>")'>Ver detalles</button></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($ventas)): ?>
              <tr><td colspan="4" style="text-align:center; color:gray;">No hay ventas activas para esta fecha.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <footer class="site-footer">
        <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
      </footer>
    </main>
  </div>

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
        contenedor.innerHTML = "<p>No se encontraron detalles para esta venta.</p>";
      } else {
        let html = "<table><thead><tr><th>Producto</th><th>Cantidad</th><th>Total</th></tr></thead><tbody>";
        venta.forEach(item => {
          html += `<tr>
                    <td>${item.Producto}</td>
                    <td class="cantidad">${item.Cantidad}</td>
                    <td>$${parseFloat(item.Subtotal).toFixed(2)}</td>
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
