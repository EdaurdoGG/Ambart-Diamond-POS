<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consulta a la vista DevolucionesRealizadas
$query = "
    SELECT 
        idDevolucion,
        Usuario,
        Fecha,
        Motivo,
        NombreProducto,
        CantidadDevuelta,
        TotalDevuelto
    FROM DevolucionesRealizadas
    ORDER BY Fecha DESC
";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    die("No se encontraron devoluciones registradas.");
}

// Cargar logo si existe
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// Generar HTML del reporte
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
.total { font-weight: bold; color: #000; }
</style>
</head>
<body>
<div class="header">
    <img src="'.$logoBase64.'" alt="Logo">
    <h2>Reporte General de Devoluciones</h2>
</div>
<table>
<thead>
<tr>
<th>ID Devolución</th>
<th>Usuario</th>
<th>Fecha</th>
<th>Motivo</th>
<th>Producto</th>
<th>Cantidad Devuelta</th>
<th>Total Devuelto</th>
</tr>
</thead>
<tbody>';

// Llenar la tabla con datos
$totalGeneral = 0;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>'.htmlspecialchars($row['idDevolucion']).'</td>
        <td>'.htmlspecialchars($row['Usuario']).'</td>
        <td>'.htmlspecialchars($row['Fecha']).'</td>
        <td>'.htmlspecialchars($row['Motivo']).'</td>
        <td>'.htmlspecialchars($row['NombreProducto']).'</td>
        <td>'.htmlspecialchars($row['CantidadDevuelta']).'</td>
        <td>$'.number_format($row['TotalDevuelto'], 2).'</td>
    </tr>';

    $totalGeneral += $row['TotalDevuelto'];
}

// Agregar fila de total general
$html .= '<tr class="total">
    <td colspan="6" style="text-align:right;">Total General:</td>
    <td>$'.number_format($totalGeneral, 2).'</td>
</tr>';

$html .= '</tbody></table></body></html>';

// Crear PDF con Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para ver más columnas
$dompdf->render();

// Mostrar PDF en navegador
$dompdf->stream("Reporte_Devoluciones.pdf", ["Attachment" => false]);
?>
