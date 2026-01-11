<?php
header('Content-Type: application/json');
include 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);
$correo = $data['correo'] ?? '';
$password = $data['password'] ?? '';

if (empty($correo) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

// Usamos CONCAT para unir nombres y apellidos según tu tabla
$query = "SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, rol 
          FROM usuarios 
          WHERE correo = '$correo' AND password = '$password' LIMIT 1";

$resultado = mysqli_query($conexion, $query);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $usuario = mysqli_fetch_assoc($resultado);
    echo json_encode([
        "success" => true,
        "usuario" => [
            "id" => $usuario['id'],
            "nombre" => $usuario['nombre_completo'],
            "rol" => $usuario['rol']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
}
?>