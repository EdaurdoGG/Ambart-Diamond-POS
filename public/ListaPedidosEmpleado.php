<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 2) {
    header("Location: Login.php");
    exit();
}

$idEmpleado = $_SESSION['idPersona'];

// Conexión
require_once "../includes/conexion.php";

// Asignar el id del usuario logueado a la variable @id_usuario_actual
$conn->query("SET @id_usuario_actual = " . intval($idEmpleado));

// Datos del empleado logueado
$stmtEmp = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                           FROM EmpleadosRegistrados 
                           WHERE idEmpleado = ?");
$stmtEmp->bind_param("i", $idEmpleado);
$stmtEmp->execute();
$stmtEmp->bind_result($empNombre, $empApellidoP, $empApellidoM, $empImagen, $empRol);
$stmtEmp->fetch();
$stmtEmp->close();

// Nombre completo y rol
$nombreCompleto = "$empNombre $empApellidoP $empApellidoM";
$rol = $empRol;
$imagen = $empImagen ?: 'imagenes/User.png';

// ----------------------------
// VARIABLES PARA MENSAJE
// ----------------------------
$mensaje = "";
$tipoMensaje = "";

// Procesar acción de pedido si se envía por GET
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $idPedido = $_GET['id'];
    $accion = $_GET['accion'];

    if ($accion === "Cancelar") {
        $stmt = $conn->prepare("CALL CambiarPedidoACancelado(?)");
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $stmt->close();

        $mensaje = "El pedido ha sido cancelado correctamente.";
        $tipoMensaje = "error"; // rojo

    } elseif ($accion === "Atendido") {
        $tipoPago = "Efectivo"; 
        $stmt = $conn->prepare("CALL ProcesarVentaPedido(?, ?, ?)");
        $stmt->bind_param("iis", $idPedido, $idEmpleado, $tipoPago);
        $stmt->execute();
        $stmt->close();

        $mensaje = "El pedido fue atendido exitosamente.";
        $tipoMensaje = "success"; // verde
    }

    // Redirigir enviando mensaje
    header("Location: ListaPedidosEmpleado.php?msg=" . urlencode($mensaje) . "&type=" . urlencode($tipoMensaje));
    exit();
}

// Filtro de fecha
$fechaFiltro = $_GET['fecha'] ?? null;

// Ajustar consulta SQL según si hay fecha o no
if ($fechaFiltro) {
    $stmtPedidos = $conn->prepare("
        SELECT 
            dp.idPedido,
            dp.Usuario,
            dp.Fecha,
            dp.Estatus,
            GROUP_CONCAT(CONCAT(dp.Producto, ' x', dp.Cantidad) SEPARATOR ', ') AS Productos,
            SUM(dp.Total) AS Total
        FROM PedidosCompletos dp
        WHERE DATE(dp.Fecha) = ? AND dp.Estatus = 'Pendiente'
        GROUP BY dp.idPedido, dp.Usuario, dp.Fecha, dp.Estatus
        ORDER BY dp.Fecha DESC
    ");
    $stmtPedidos->bind_param("s", $fechaFiltro);
} else {
    $stmtPedidos = $conn->prepare("
        SELECT 
            dp.idPedido,
            dp.Usuario,
            dp.Fecha,
            dp.Estatus,
            GROUP_CONCAT(CONCAT(dp.Producto, ' x', dp.Cantidad) SEPARATOR ', ') AS Productos,
            SUM(dp.Total) AS Total
        FROM PedidosCompletos dp
        WHERE dp.Estatus = 'Pendiente'
        GROUP BY dp.idPedido, dp.Usuario, dp.Fecha, dp.Estatus
        ORDER BY dp.Fecha DESC
    ");
}

$stmtPedidos->execute();
$resultPedidos = $stmtPedidos->get_result();

// Mensaje si se filtró fecha y no hay resultados
if ($fechaFiltro && $resultPedidos->num_rows == 0) {
    $mensaje = "No se encontraron pedidos para la fecha seleccionada.";
    $tipoMensaje = "error";
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos Pendientes</title>
<link rel="stylesheet" href="ListaPedidosEmpleado.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>

<body>

<!-- MOSTRAR MENSAJE SI EXISTE -->
<?php if (!empty($_GET['msg']) && !empty($_GET['type'])): ?>
<div id="alert" class="alert-message alert-<?= htmlspecialchars($_GET['type']) ?>">
    <?= htmlspecialchars($_GET['msg']) ?>
</div>
<script>
setTimeout(() => {
    const alert = document.getElementById("alert");
    if(alert){
        alert.classList.add("show");
        setTimeout(() => alert.classList.remove("show"), 3000);
    }
}, 200);
</script>
<?php endif; ?>

<?php if (!empty($mensaje) && empty($_GET['msg'])): ?>
<div id="alert2" class="alert-message alert-<?= $tipoMensaje ?>">
    <?= $mensaje ?>
</div>
<script>
setTimeout(() => {
    const alert = document.getElementById("alert2");
    if(alert){
        alert.classList.add("show");
        setTimeout(() => alert.classList.remove("show"), 3000);
    }
}, 200);
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
      <a href="HistorialVentasEmpleado.php" class="menu-item">
        <img src="imagenes/Ventas.png" alt="HistorialVentas" class="icon"> Historial Ventas
      </a>
      <a href="ListaPedidosEmpleado.php" class="menu-item active">
        <img src="imagenes/Pedidos.png" alt="Pedidos" class="icon"> Pedidos
      </a>
      <a href="ListaDevolucionesEmpleado.php" class="menu-item">
        <img src="imagenes/Devoluciones.png" alt="Devoluciones" class="icon"> Devoluciones
      </a>
      <a href="QuejaSugerenciaEmpleado.php" class="menu-item">
        <img src="imagenes/QuejasSujerencias.png" alt="QuejasSujerencias" class="icon"> Quejas / Sugerencias
      </a>
      <div class="menu-separator"></div>
      <a href="Login.php" class="menu-item logout"><img src="imagenes/salir.png" alt="Cerrar sesión" class="icon"> Cerrar sesión</a>
    </nav>
  </aside>

  <!-- Main content -->
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
        <a href="EditarPerfilEmpleado.php">
          <img src="<?= htmlspecialchars($imagen) ?>" alt="Avatar" class="avatar">
        </a>
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
          <span class="user-role"><?= htmlspecialchars($rol) ?></span>
        </div>
      </div>
    </header>

    <div class="table-actions">
      <form method="GET" action="ExportarListaTodosLosPedidosPendientes.php" style="display:inline;">
        <button type="submit" class="btn-primary">Exportar Todos los Pedidos Pendientes</button>
      </form>
    </div>

    <!-- Sección de Pedidos -->
    <section class="card-section">
      <?php if ($resultPedidos->num_rows > 0): ?>
          <?php while($pedido = $resultPedidos->fetch_assoc()): ?>
          <div class="card">
              <div class="card-info">
                  <h3>Pedido de: <span class="usuario-nombre"><?= htmlspecialchars($pedido['Usuario']) ?></span></h3>
                  <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['Fecha']) ?></p>
                  <p><strong>Productos:</strong></p>
                  <ul>
                      <?php 
                      $productos = explode(', ', $pedido['Productos']);
                      foreach($productos as $producto) {
                          echo "<li>" . htmlspecialchars($producto) . "</li>";
                      }
                      ?>
                  </ul>
                  <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>
                  <div class="card-actions">
                      <?php if($pedido['Estatus'] !== 'Atendido' && $pedido['Estatus'] !== 'Cancelado'): ?>
                          <a href="?id=<?= $pedido['idPedido'] ?>&accion=Atendido"><button class="btn-primary">Atendido</button></a>
                          <a href="?id=<?= $pedido['idPedido'] ?>&accion=Cancelar"><button class="btn-secondary">Cancelar</button></a>
                      <?php else: ?>
                          <span class="status <?= strtolower($pedido['Estatus']) ?>"><?= htmlspecialchars($pedido['Estatus']) ?></span>
                      <?php endif; ?>
                  </div>
              </div>
          </div>
          <?php endwhile; ?>
      <?php else: ?>
          <div class="no-pedidos">
              <p>No hay pedidos pendientes.</p>
          </div>
      <?php endif; ?>
    </section>

    <footer class="site-footer">
      <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
    </footer>
  </main>
</div>

</body>
</html>
