<?php
require __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

// Conexión
require_once "../includes/conexion.php";

// Consulta productos con estado, ordenados por categoría y nombre
$sql = "SELECT * FROM ProductosConEstado ORDER BY Categoria, Producto";
$result = $conn->query($sql);

// Rutas de imágenes
$logoPath = __DIR__ . '/Imagenes/Logo.png';
$productosFolder = __DIR__ . '/Productos';

// Logo en base64
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $logoBase64 = 'data:image/'.$logoExt.';base64,'.base64_encode(file_get_contents($logoPath));
}

// Iniciar Dompdf
$dompdf = new Dompdf();

// Construir HTML
$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
.header { text-align: center; margin-bottom: 20px; }
.header img { width: 80px; margin-bottom: 10px; }
.header h1 { margin: 0; font-size: 24px; }
h2.categoria { margin-top: 30px; font-size: 20px; color: #333; border-bottom: 1px solid #444; padding-bottom: 5px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
th { background-color: #f2f2f2; }
td.left { text-align: left; }
td img { width: 50px; height: 50px; object-fit: cover; }
tr:nth-child(even) { background-color: #fafafa; }
</style>
</head>
<body>
<div class="header">';
if ($logoBase64) {
    $html .= '<img src="'.$logoBase64.'" alt="Logo">';
}
$html .= '<h1>Inventario de Productos</h1></div>';

// Control de categoría
$categoriaActual = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Si cambia categoría
        if ($row['Categoria'] !== $categoriaActual) {
            if ($categoriaActual !== '') {
                $html .= '</tbody></table>';
            }
            $categoriaActual = $row['Categoria'];
            $html .= '<h2 class="categoria">'.htmlspecialchars($categoriaActual).'</h2>
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Numero de Registro</th>
                        <th>Producto</th>
                        <th>Existencia</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        }

        // Imagen del producto
        $imgSrc = '';
        $productoImgPath = realpath($productosFolder . '/' . $row['Imagen']);
        if ($productoImgPath && file_exists($productoImgPath)) {
            $imgExt = strtolower(pathinfo($productoImgPath, PATHINFO_EXTENSION));
            $imgSrc = 'data:image/'.$imgExt.';base64,'.base64_encode(file_get_contents($productoImgPath));
        }

        $html .= '<tr>
            <td><img src="'.$imgSrc.'" alt="'.htmlspecialchars($row['Producto']).'"></td>
            <td>'.$row['idProducto'].'</td>
            <td class="left">'.htmlspecialchars($row['Producto']).'</td>
            <td>'.$row['Existencia'].'</td>
            <td>$'.number_format($row['PrecioCompra'],2).'</td>
            <td>$'.number_format($row['PrecioVenta'],2).'</td>
            <td>'.$row['Estado'].'</td>
        </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p>No hay productos registrados.</p>';
}

$html .= '</body></html>';

// Cargar HTML en Dompdf
$dompdf->loadHtml($html);

// Configurar tamaño y orientación
$dompdf->setPaper('A4', 'landscape');

// Renderizar PDF
$dompdf->render();

// Abrir PDF en el navegador
$dompdf->stream("InventarioProductosPorCategoria.pdf", ["Attachment" => false]);

$conn->close();
?>
