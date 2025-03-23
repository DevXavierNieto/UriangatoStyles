<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['codigo'])) {
    echo json_encode(["status" => "error", "message" => "Código no proporcionado"]);
    exit;
}

$codigo = $data['codigo'];

$conn = conectarDB();
$stmt = $conn->prepare("SELECT r.nombre, r.email, r.fecha_hora, s.nombre AS servicio, r.costo 
                        FROM reservaciones r 
                        JOIN servicios s ON r.servicio_id = s.id 
                        WHERE r.codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $to = $row['email'];
    $subject = "Recordatorio de tu cita en Uriangato Style";
    $message = "Hola " . $row['nombre'] . ",\n\n"
             . "Este es un recordatorio de tu cita:\n"
             . "Fecha y hora: " . $row['fecha_hora'] . "\n"
             . "Servicio: " . $row['servicio'] . "\n"
             . "Costo: $" . number_format($row['costo'], 2) . "\n\n"
             . "Te esperamos en Uriangato Style.\n";

    $headers = "From: lalo1540lol@gmail.com";

    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(["status" => "success", "message" => "Correo enviado a $to"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al enviar el correo"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Cita no encontrada"]);
}
