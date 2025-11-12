<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Validar parámetro de fecha
if (!isset($_GET['fecha']) || empty($_GET['fecha'])) {
    die("Debe especificar una fecha. Ejemplo: pdf_pedidos_fecha.php?fecha=2025-10-23");
}

$fecha = $_GET['fecha'];

// Consultar pedidos de esa fecha
$stmt = $conn->prepare("
    SELECT 
        dp.idPedido,
        dp.Usuario,
        dp.Fecha,
        dp.Estatus,
        GROUP_CONCAT(CONCAT(dp.Producto, ' x', dp.Cantidad) SEPARATOR ', ') AS Productos,
        SUM(dp.Total) AS Total
    FROM PedidosCompletos dp
    WHERE DATE(dp.Fecha) = ?
    GROUP BY dp.idPedido, dp.Usuario, dp.Fecha, dp.Estatus
    ORDER BY dp.Fecha DESC
");
$stmt->bind_param("s", $fecha);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No se encontraron pedidos para la fecha seleccionada.");
}

// Ruta del logo
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// Generar HTML para el PDF
$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
.header { text-align: center; margin-bottom: 20px; }
.header img { width: 80px; margin-bottom: 10px; }
h2 { margin: 0; font-size: 20px; }
table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 15px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
th { background-color: #f2f2f2; }
tr:nth-child(even) { background-color: #fafafa; }
</style>
</head>
<body>
<div class="header">
    <img src="'.$logoBase64.'" alt="Logo">
    <h2>Reporte de Pedidos — Fecha: '.htmlspecialchars($fecha).'</h2>
</div>
<table>
<thead>
<tr>
<th>ID</th>
<th>Cliente</th>
<th>Productos</th>
<th>Total</th>
<th>Estatus</th>
</tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>'.htmlspecialchars($row['idPedido']).'</td>
        <td>'.htmlspecialchars($row['Usuario']).'</td>
        <td>'.htmlspecialchars($row['Productos']).'</td>
        <td>$'.number_format($row['Total'], 2).'</td>
        <td>'.htmlspecialchars($row['Estatus']).'</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

// Crear instancia Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Abrir PDF en el navegador
$dompdf->stream("Pedidos_$fecha.pdf", ["Attachment" => false]);
?>
