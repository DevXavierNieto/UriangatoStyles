<?php
require_once 'conexion.php';

header("Content-Type: application/json");

// Solo se permite método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// === MODO 1: RECORDATORIO (se envía solo el código) ===
if (isset($data['codigo']) && !isset($data['nombre'])) {
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

    exit;
}

// === MODO 2: CONFIRMACIÓN AUTOMÁTICA DESPUÉS DE RESERVA ===
// === MODO 2: CONFIRMACIÓN AUTOMÁTICA DESPUÉS DE RESERVA ===
if (isset($data['email'], $data['nombre'], $data['fecha'], $data['servicio'], $data['costo'], $data['codigo'])) {
    $to = $data['email'];
    $subject = "Confirmación de tu cita en Uriangato Style";
    $message = "Hola " . $data['nombre'] . ",\n\n"
             . "Gracias por reservar con nosotros. Aquí están los detalles de tu cita:\n\n"
             . "Código: " . $data['codigo'] . "\n"
             . "Servicio: " . $data['servicio'] . "\n"
             . "Fecha y hora: " . $data['fecha'] . "\n"
             . "Costo: " . $data['costo'] . "\n\n"
             . "Te esperamos en la sucursal principal.\n\n"
             . "Uriangato Style";

    $headers = "From: lalo1540lol@gmail.com";

    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(["status" => "success", "message" => "Correo de confirmación enviado"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo enviar el correo"]);
    }

    exit;
}


// Si no coincide con ninguno
echo json_encode(["status" => "error", "message" => "Datos insuficientes o incorrectos"]);
exit;
