<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consulta todos los empleados
$sql = "SELECT * FROM EmpleadosRegistrados ORDER BY Estado, Nombre";
$result = $conn->query($sql);

$dompdf = new Dompdf();

// Carpeta donde están las imágenes de los empleados dentro del contenedor
$empleadosFolder = __DIR__ . '/Empleados/';

// Logo
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
.header { text-align: center; margin-bottom: 20px; }
.header img { width: 80px; margin-bottom: 10px; }
.header h1 { margin: 0; font-size: 26px; color: #222; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; font-size: 14px; }
th { background-color: #f0f0f0; }
td.left { text-align: left; }
td img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
tr:nth-child(even) { background-color: #fafafa; }
tr:hover { background-color: #f1f1f1; }
</style>
</head>
<body>
<div class="header">
    <img src="'.$logoBase64.'" alt="Logo">
    <h1>Todos los Empleados</h1>
</div>
<table>
<thead>
<tr>
<th>ID</th><th>Foto</th><th>Nombre</th><th>Apellido Paterno</th><th>Apellido Materno</th>
<th>Email</th><th>Teléfono</th><th>Edad</th><th>Sexo</th><th>Rol</th><th>Estado</th>
</tr>
</thead>
<tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ruta de la foto del empleado dentro del contenedor
        $empleadoImgPath = $empleadosFolder . basename($row['Imagen']); // basename limpia cualquier ruta
        $imgSrc = '';
        if (file_exists($empleadoImgPath)) {
            $imgSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($empleadoImgPath));
        } else {
            // Imagen placeholder si no existe
            $imgSrc = '';
        }

        $html .= '<tr>
        <td>'.$row['idEmpleado'].'</td>
        <td><img src="'.$imgSrc.'" alt="Foto"></td>
        <td class="left">'.htmlspecialchars($row['Nombre']).'</td>
        <td class="left">'.htmlspecialchars($row['ApellidoPaterno']).'</td>
        <td class="left">'.htmlspecialchars($row['ApellidoMaterno']).'</td>
        <td class="left">'.htmlspecialchars($row['Email']).'</td>
        <td>'.$row['Telefono'].'</td>
        <td>'.$row['Edad'].'</td>
        <td>'.$row['Sexo'].'</td>
        <td>'.$row['Rol'].'</td>
        <td>'.$row['Estado'].'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="11">No hay empleados registrados</td></tr>';
}

$html .= '</tbody></table></body></html>';

// Generar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("TodosEmpleados.pdf", ["Attachment" => false]);

$conn->close();
?>
