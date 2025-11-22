<?php
session_start();

// Verifica que el usuario sea administrador
if (!isset($_SESSION['idPersona']) || ($_SESSION['rol'] ?? 0) != 1) {
    header("Location: Login.php");
    exit();
}

// Conexión
require_once "../includes/conexion.php";

// Validar ID recibido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ListaProductosAdministrador.php?msg=error_id");
    exit();
}

$idProducto = intval($_GET['id']);

// -------------------------------------------------------------------
// VERIFICAR SI EL PRODUCTO EXISTE
// -------------------------------------------------------------------
$stmt = $conn->prepare("SELECT idProducto FROM Producto WHERE idProducto = ?");
$stmt->bind_param("i", $idProducto);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: ListaProductosAdministrador.php?msg=no_existe");
    exit();
}
$stmt->close();

// -------------------------------------------------------------------
// ELIMINAR EL PRODUCTO
// -------------------------------------------------------------------
$stmt = $conn->prepare("DELETE FROM Producto WHERE idProducto = ?");
$stmt->bind_param("i", $idProducto);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ListaProductosAdministrador.php?msg=eliminado");
    exit();
} else {
    // ERROR POR RESTRICCIONES (FK)
    $stmt->close();
    header("Location: ListaProductosAdministrador.php?msg=restriccion");
    exit();
}

$conn->close();
?>
