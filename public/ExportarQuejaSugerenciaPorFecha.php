<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Validar fecha
if (!isset($_GET['fecha']) || empty($_GET['fecha'])) die("Debe especificar una fecha. Ejemplo: ExportarSugerenciasPorFecha.php?fecha=2025-10-23");

$fecha = $_GET['fecha'];

// Consulta
$stmt = $conn->prepare("
    SELECT NombreCompleto, Tipo, Descripcion, Fecha, RolUsuario
    FROM VistaSugerenciasQuejas
    WHERE DATE(Fecha) = ?
    ORDER BY Fecha DESC
");
$stmt->bind_param("s", $fecha);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die("No se encontraron registros para la fecha seleccionada.");

// Logo
$logoPath = __DIR__.'/imagenes/Logo.png';
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

// Generar HTML
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
    <h2>Reporte de Quejas/Sugerencias — Fecha: '.htmlspecialchars($fecha).'</h2>
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

// Generar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("SugerenciasQuejas_$fecha.pdf", ["Attachment" => false]);
?>
