<?php
function conectarDB() {
    $servidor = "localhost"; 
    $usuario = "root"; 
    $clave = "root1234"; 
    $bd = "UriangatoStyles"; 

    $conn = new mysqli($servidor, $usuario, $clave, $bd, 3306);

    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos"]);
        exit;
    }

    return $conn;
}
?>
