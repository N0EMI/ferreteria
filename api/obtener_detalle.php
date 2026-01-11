<?php
header('Content-Type: application/json');
include 'conexion.php';

$id_venta = $_GET['id_venta'] ?? '';

if (!$id_venta) {
    echo json_encode(["success" => false, "message" => "ID no enviado"]);
    exit;
}

// Consulta uniendo detalle_ventas con productos para obtener el nombre
$query = "SELECT d.cantidad, d.precio_unitario_final as precio_final, p.nombre 
          FROM detalle_ventas d 
          JOIN productos p ON d.id_producto = p.id 
          WHERE d.id_venta = '$id_venta'";

$resultado = mysqli_query($conexion, $query);
$productos = [];

while ($row = mysqli_fetch_assoc($resultado)) {
    // Convertimos valores a números para evitar errores en JS
    $row['cantidad'] = (int)$row['cantidad'];
    $row['precio_final'] = (float)$row['precio_final'];
    $productos[] = $row;
}

echo json_encode(["success" => true, "productos" => $productos]);
?>