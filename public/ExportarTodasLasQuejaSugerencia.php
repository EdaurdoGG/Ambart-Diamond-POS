<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consulta
$sql = "SELECT NombreCompleto, Tipo, Descripcion, Fecha, RolUsuario
        FROM VistaSugerenciasQuejas
        ORDER BY Fecha DESC";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) die("No hay registros disponibles.");

// Logo
$logoPath = __DIR__.'/imagenes/Logo.png';
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

// HTML
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
    <h2>Reporte Completo de Quejas y Sugerencias</h2>
</div>
<table>
<thead>
<tr>
<th>Usuario</th>
<th>Rol</th>
<th>Tipo</th>
<th>Descripción</th>
<th>Fecha</th>
</tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>'.htmlspecialchars($row['NombreCompleto']).'</td>
        <td>'.htmlspecialchars($row['RolUsuario']).'</td>
        <td>'.htmlspecialchars($row['Tipo']).'</td>
        <td>'.htmlspecialchars($row['Descripcion']).'</td>
        <td>'.htmlspecialchars($row['Fecha']).'</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

// PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("SugerenciasQuejas_Todas.pdf", ["Attachment" => false]);
?>
