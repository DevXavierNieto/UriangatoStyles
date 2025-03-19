<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

require_once 'conexion.php';

$conn = conectarDB();

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Error en la conexión a la base de datos."]));
}

// Función para generar código aleatorio
function generarCodigo($longitud = 5) {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($caracteres), 0, $longitud);
}

// Función para manejar la reserva (sin envío de correo)
function reservarCita($conn) {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $servicio = isset($_POST['servicio']) ? trim($_POST['servicio']) : null;
    $fecha_hora = isset($_POST['fecha_hora']) ? trim($_POST['fecha_hora']) : null;
    $costo = isset($_POST['costo']) ? trim($_POST['costo']) : null;

    if (!$nombre || !$email || !$telefono || !$servicio || !$fecha_hora || !$costo) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios"]);
        exit;
    }

    // Convertir fecha al formato correcto de MySQL
    $fecha_hora = str_replace("T", " ", $fecha_hora) . ":00";

    $codigo = generarCodigo();

    $stmt = $conn->prepare("INSERT INTO reservaciones (nombre, email, telefono, servicio, fecha_hora, costo, codigo) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssssss", $nombre, $email, $telefono, $servicio, $fecha_hora, $costo, $codigo);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Reserva realizada con éxito.",
            "codigo" => $codigo
        ]);
        
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar la reserva: " . $stmt->error]);
    }
    $stmt->close();
}

// Controlador para manejar las solicitudes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : null;

    switch ($accion) {
        case "reservar":
            reservarCita($conn);
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Acción no válida"]);
            break;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>
