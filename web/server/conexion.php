<?php
function conectarDB() {
    $servidor = "localhost"; 
    $usuario = "root"; 
    $clave = "root"; 
    $bd = "UriangatoStyles"; 

    $conn = new mysqli($servidor, $usuario, $clave, $bd, 3306);

    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "Error de conexión: " . $conn->connect_error]));
    }

    return $conn;
}
?>
