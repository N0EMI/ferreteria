<?php
// Configuración de parámetros
$host = "localhost";
$user = "root"; 
$pass = ""; // Si en tu MySQL Workbench pusiste contraseña, escríbela aquí.
$db   = "ferreteria_db";

// Crear la conexión
$conexion = mysqli_connect($host, $user, $pass, $db);

// Verificar la conexión
if (!$conexion) {
    header('Content-Type: application/json');
    die(json_encode([
        "success" => false, 
        "message" => "Error de conexión: " . mysqli_connect_error()
    ]));
}

// Forzar el juego de caracteres a UTF-8 para evitar problemas con la Ñ y tildes
mysqli_set_charset($conexion, "utf8");

// Definir zona horaria para que coincida con tus registros de ventas
date_default_timezone_set('America/Lima');
?>