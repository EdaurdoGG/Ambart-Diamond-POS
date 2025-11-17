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

$stmt = $conn->prepare("SELECT Nombre, ApellidoPaterno, ApellidoMaterno, Imagen, Rol 
                        FROM AdministradoresRegistrados 
                        WHERE idAdministrador = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$stmt->bind_result($adminNombre, $adminApellidoP, $adminApellidoM, $adminImagen, $adminRol);
$stmt->fetch();
$stmt->close();

$nombreCompleto = trim("$adminNombre $adminApellidoP $adminApellidoM") ?: "Administrador desconocido";
$adminRol = $adminRol ?: "Administrador";
$adminImagen = $adminImagen ?: "imagenes/User.png";

// Función para obtener auditoría filtrada por tipo
function obtenerAuditoriaPorTipo($conn, $tipo) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM VistaAuditoriaGeneral 
        WHERE TipoRegistro = ? 
        ORDER BY Fecha DESC
    ");
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}

$auditoriaEmpleados = obtenerAuditoriaPorTipo($conn, "Empleado");
$auditoriaClientes = obtenerAuditoriaPorTipo($conn, "Cliente");
$auditoriaProductos = obtenerAuditoriaPorTipo($conn, "Producto");
$auditoriaDevoluciones = obtenerAuditoriaPorTipo($conn, "Devolución");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Auditorías</title>
    <link rel="stylesheet" href="Auditorias.css">
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
                <a href="Auditorias.php" class="menu-item active">
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
                <h2>Módulo de Control y Auditoría</h2>
                <div class="user-profile">
                    <a href="AlertasAdministrador.php"><img src="imagenes/Notificasion.png" alt="Notificaciones" class="icon notification"></a>
                    <a href="EditarPerfilAdministrador.php">
                        <img src="<?php echo htmlspecialchars($adminImagen); ?>" alt="Avatar" class="avatar">
                    </a>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($adminRol); ?></span>
                    </div>
                </div>
            </header>

            <?php
            $tiposAuditoria = [
                "Empleado" => "Auditoría de Empleados",
                "Cliente" => "Auditoría de Clientes",
                "Producto" => "Auditoría de Productos",
                "Devolución" => "Auditoría de Devoluciones"
            ];

            $auditorias = [
                "Empleado" => $auditoriaEmpleados,
                "Cliente" => $auditoriaClientes,
                "Producto" => $auditoriaProductos,
                "Devolución" => $auditoriaDevoluciones
            ];

            foreach ($tiposAuditoria as $tipo => $titulo):
            ?>
            <section class="auditoria-section">
                <h3><?php echo $titulo; ?></h3>
                <div class="table-container">
                    <table class="auditoria-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Acción</th>
                                <th>Columna</th>
                                <th>Valor Anterior</th>
                                <th>Valor Nuevo</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($auditorias[$tipo] && $auditorias[$tipo]->num_rows > 0): ?>
                            <?php while ($fila = $auditorias[$tipo]->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $fila['idAuditoria'] ?? ''; ?></td>
                                    <td><?php echo htmlspecialchars($fila['Movimiento'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['ColumnaAfectada'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['DatoAnterior'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['DatoNuevo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Fecha'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['NombreCompleto'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['RolUsuario'] ?? ''); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8">Sin registros disponibles</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endforeach; ?>

            <footer class="site-footer">
                <p>&copy; 2025 <strong>Diamonds Corporation</strong> Todos los derechos reservados.</p>
            </footer>
        </main>
    </div>
</body>
</html>
