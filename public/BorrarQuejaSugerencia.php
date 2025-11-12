<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Obtener ID del registro a eliminar desde GET
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM SugerenciaQueja WHERE idSugerenciaQueja = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Cerrar conexión y redirigir de nuevo a la página de quejas/sugerencias
$conn->close();
header("Location: QuejaSugerenciaAdministrador.php");
exit();
?>
