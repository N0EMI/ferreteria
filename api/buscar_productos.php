<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$busqueda = isset($_GET['q']) ? mysqli_real_escape_string($conexion, $_GET['q']) : '';

// Busca por nombre o por código
$sql = "SELECT id, codigo, nombre, precio_base, stock 
        FROM productos 
        WHERE (nombre LIKE '%$busqueda%' OR codigo LIKE '%$busqueda%') 
        AND stock > 0 
        LIMIT 10";

$resultado = mysqli_query($conexion, $sql);
$productos = [];

while ($fila = mysqli_fetch_assoc($resultado)) {
    $productos[] = $fila;
}

echo json_encode($productos);
?>