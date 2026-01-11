<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Datos no recibidos"]);
    exit;
}

$cliente = $data['cliente'];
$carrito = $data['carrito'];
$totalFinal = $data['totalFinal'];

// 1. Manejo del Cliente (DNI/RUC es único)
$dni = mysqli_real_escape_string($conexion, $cliente['dni']);
$nombre = mysqli_real_escape_string($conexion, $cliente['nombre']);
$direccion = mysqli_real_escape_string($conexion, $cliente['direccion']);

$queryCli = "INSERT INTO clientes (dni_ruc, nombre_completo, direccion) 
             VALUES ('$dni', '$nombre', '$direccion') 
             ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
mysqli_query($conexion, $queryCli);
$idCliente = mysqli_insert_id($conexion);

// 2. Crear Cabecera de Venta
$numBoleta = "BOL-" . date('Ymd') . "-" . rand(1000, 9999);
$queryVenta = "INSERT INTO ventas (numero_boleta, id_cliente, id_usuario, metodo_pago, total_final) 
               VALUES ('$numBoleta', $idCliente, 1, 'efectivo', $totalFinal)";

if (mysqli_query($conexion, $queryVenta)) {
    $idVenta = mysqli_insert_id($conexion);

    // 3. Detalles y Stock
    foreach ($carrito as $item) {
        $idProd = $item['id'];
        $cant = $item['cantidad'];
        $pct = $item['porcentaje'];
        $precioUnit = $item['precio_base'] * (1 + ($pct / 100));
        $subtotal = $precioUnit * $cant;

        // Guardar detalle
        mysqli_query($conexion, "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, porcentaje_aplicado, precio_unitario_final, subtotal) 
                                 VALUES ($idVenta, $idProd, $cant, $pct, $precioUnit, $subtotal)");
        
        // Descontar Stock
        mysqli_query($conexion, "UPDATE productos SET stock = stock - $cant WHERE id = $idProd");
    }
    echo json_encode(["success" => true, "boleta" => $numBoleta]);
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar venta"]);
}
?>