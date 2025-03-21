<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

require_once 'conexion.php';

$conn = conectarDB(); // Conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['codigo'])) {
        // Buscar una cita por código
        $codigo = trim($_POST['codigo']);

        if (!$codigo) {
            echo json_encode(["status" => "error", "message" => "Código de cita requerido."]);
            exit;
        }

        $stmt = $conn->prepare("SELECT fecha_hora, nombre, servicio, costo FROM reservaciones WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $cita = $result->fetch_assoc();
            $fechaHora = new DateTime($cita['fecha_hora']);

            echo json_encode([
                "status" => "success",
                "fecha" => $fechaHora->format('Y-m-d'),
                "hora" => $fechaHora->format('H:i'),
                "cliente" => $cita['nombre'],
                "servicio" => $cita['servicio'],
                "costo" => "$" . number_format($cita['costo'], 2),
                "ubicacion" => "Sucursal Principal" // Puedes modificar esto si tienes ubicación en la BD
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se encontró ninguna cita con ese código."]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    // Registro de una nueva reserva
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

    // Intentar convertir la fecha al formato correcto (YYYY-MM-DD HH:MM:SS)
    try {
        $fecha_hora_obj = new DateTime($fecha_hora);
        $fecha_hora_formateada = $fecha_hora_obj->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Formato de fecha y hora inválido"]);
        exit;
    }

    // Generar código único de 5 caracteres (letras y números)
    function generarCodigo($longitud = 5) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($caracteres), 0, $longitud);
    }

    $codigo = generarCodigo();

    $stmt = $conn->prepare("INSERT INTO reservaciones (nombre, email, telefono, servicio, fecha_hora, costo, codigo) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Error en la preparación de la consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssssss", $nombre, $email, $telefono, $servicio, $fecha_hora_formateada, $costo, $codigo);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Reserva realizada con éxito",
            "codigo" => $codigo
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar la reserva: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>
