<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consulta usando la vista
$sql = "
SELECT idNotificacion, NombreProducto, Existencia, MinimoInventario, Mensaje, Fecha
FROM VistaNotificaciones
ORDER BY Fecha DESC
";

$result = $conn->query($sql);

$dompdf = new Dompdf();

// Logo
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// HTML PDF
$html = '<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;margin:20px;color:#333;}
.header{text-align:center;margin-bottom:20px;}
.header img{width:80px;margin-bottom:10px;}
.header h1{margin:0;font-size:26px;color:#222;}

table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{border:1px solid #ccc;padding:8px;text-align:center;font-size:14px;}
th{background-color:#f0f0f0;}
td.left{text-align:left;}

.checkbox{
    width:18px;
    height:18px;
    border:1px solid #000;
    display:inline-block;
}

tr:nth-child(even){background-color:#fafafa;}
tr:hover{background-color:#f1f1f1;}

</style></head><body><div class="header">';

if($logoBase64){
    $html .= '<img src="'.$logoBase64.'" alt="Logo">';
}

$html .= '
<h1>Productos con Inventario Bajo</h1>
</div>

<table>
<thead>
<tr>
<th>✔</th>
<th>Producto</th>
<th>Existencia</th>
<th>Mínimo</th>
<th>Precio</th>
<th>Cantidad</th>
</tr>
</thead>
<tbody>
';

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $html .= '
        <tr>
            <td><span class="checkbox"></span></td>
            <td class="left">'.htmlspecialchars($row['NombreProducto']).'</td>
            <td>'.$row['Existencia'].'</td>
            <td>'.$row['MinimoInventario'].'</td>
            <td></td>
            <td></td>
        </tr>';
    }
}else{
    $html .= '<tr><td colspan="6">No hay productos con notificaciones</td></tr>';
}

$html .= '</tbody></table></body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("ProductosInventarioBajo.pdf", ["Attachment" => false]);

$conn->close();
?>
