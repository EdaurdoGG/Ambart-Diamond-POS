<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consultar todas las devoluciones
$stmt = $conn->prepare("
    SELECT 
        d.idDevolucion,
        p.Nombre AS NombrePersona,
        d.Fecha,
        d.Motivo,
        GROUP_CONCAT(CONCAT(pr.Nombre, ' x', dd.CantidadDevuelta) SEPARATOR ', ') AS Productos,
        SUM(dd.TotalDevuelto) AS TotalDevuelto
    FROM Devolucion d
    JOIN Persona p ON d.idPersona = p.idPersona
    JOIN DetalleDevolucion dd ON d.idDevolucion = dd.idDevolucion
    JOIN DetalleVenta dv ON dd.idDetalleVenta = dv.idDetalleVenta
    JOIN Producto pr ON dv.idProducto = pr.idProducto
    GROUP BY d.idDevolucion, p.Nombre, d.Fecha, d.Motivo
    ORDER BY d.Fecha DESC
");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No se encontraron devoluciones registradas.");
}

// Logo
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// HTML del PDF
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
    <h2>Reporte de Todas las Devoluciones</h2>
</div>
<table>
<thead>
<tr>
<th>ID</th>
<th>Usuario</th>
<th>Productos</th>
<th>Total Devuelto</th>
<th>Motivo</th>
</tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>'.htmlspecialchars($row['idDevolucion']).'</td>
        <td>'.htmlspecialchars($row['NombrePersona']).'</td>
        <td>'.htmlspecialchars($row['Productos']).'</td>
        <td>$'.number_format($row['TotalDevuelto'], 2).'</td>
        <td>'.htmlspecialchars($row['Motivo']).'</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

// Crear PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("TodasLasDevoluciones.pdf", ["Attachment" => false]);
?>
