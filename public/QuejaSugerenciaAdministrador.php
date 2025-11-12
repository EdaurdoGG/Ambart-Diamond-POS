<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Datos del usuario logeado
$idUsuario = $_SESSION['idPersona'];
$stmt = $conn->prepare("
    SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, r.NombreRol
    FROM Persona p
    JOIN Rol r ON p.idRol = r.idRol
    WHERE p.idPersona = ?
");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($userNombre, $userApellidoP, $userApellidoM, $userImagen, $userRol);
$stmt->fetch();
$stmt->close();

$nombreCompleto = trim("$userNombre $userApellidoP $userApellidoM") ?: "Usuario desconocido";
$userRol = $userRol ?: "Administrador";
$userImagen = $userImagen ?: "imagenes/User.png";

// Manejo de filtro de fecha: por defecto fecha actual
$fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');

// Consultar la vista VistaSugerenciasQuejas filtrando por fecha
$sql = "SELECT *
        FROM VistaSugerenciasQuejas
        WHERE DATE(Fecha) = ?
        ORDER BY Fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fechaFiltro);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quejas y Sugerencias</title>
<link rel="stylesheet" href="QuejaSugerenciaAdministrador.css">
<link rel="icon" type="image/png" href="imagenes/Logo.png">
</head>
<body>
<div class="dashboard-container">
    <!-- Barra lateral -->
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
        <a href="FinanzasAdministrador.php" class="menu-item">
          <img src="imagenes/Finanzas.png" alt="Finanzas" class="icon"> Finanzas
        </a>
        <a href="Auditorias.php" class="menu-item">
          <img src="imagenes/Auditorias.png" alt="Auditorias" class="icon"> Control y Auditoría
        </a>
        <a href="QuejaSugerenciaAdministrador.php" class="menu-item active">
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
                  <img src="<?= htmlspecialchars($userImagen) ?>" alt="Avatar" class="avatar"> 
              </a>
              <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($nombreCompleto) ?></span>
                <span class="user-role"><?= htmlspecialchars($userRol) ?></span>
              </div>
            </div>
        </header>

        <!-- Tabla de Quejas / Sugerencias -->
        <section class="user-table-section">

            <div class="table-actions">
                <!-- Exportar datos filtrados por fecha -->
                <form method="GET" action="ExportarQuejaSugerenciaPorFecha.php" style="display:inline;">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
                    <button type="submit" class="btn-primary">Exportar por Fecha</button>
                </form>

                <!-- Exportar todos los datos -->
                <form method="GET" action="ExportarTodasLasQuejaSugerencia.php" style="display:inline;">
                    <button type="submit" class="btn-secondary">Exportar Todo</button>
                </form>
            </div>

            <div class="table-meta">
                <span>Total registros: <?= $resultado ? $resultado->num_rows : 0; ?></span>
            </div>

            <table class="user-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Operaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($fila = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($fila['NombreCompleto']) ?></td>
                                <td><?= htmlspecialchars($fila['Tipo']) ?></td>
                                <td><?= htmlspecialchars($fila['Descripcion']) ?></td>
                                <td><?= htmlspecialchars($fila['Fecha']) ?></td>
                                <td>
                                  <a href="BorrarQuejaSugerencia.php?id=<?= $fila['idSugerenciaQueja'] ?>">
                                      <img src="imagenes/Borrar.png" alt="Eliminar" class="action-icon" title="Eliminar"> 
                                  </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Sin registros disponibles para <?= htmlspecialchars($fechaFiltro) ?></td></tr>
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

<?php
$stmt->close();
$conn->close();
?>
