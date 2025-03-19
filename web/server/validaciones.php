<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'conexion.php';

$conn = conectarDB(); // 🔹 Ahora la conexión se usa correctamente

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$servicio = trim($_POST["servicio"] ?? "");
$fecha_hora = trim($_POST["fecha_hora"] ?? "");
$costo = trim($_POST["costo"] ?? "");

if (empty($nombre) || empty($email) || empty($telefono) || empty($servicio) || empty($fecha_hora) || empty($costo)) {
    echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios"]);
    exit;
}

if (!preg_match("/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/", $nombre)) {
    echo json_encode(["status" => "error", "message" => "El nombre contiene caracteres inválidos"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Correo electrónico no válido"]);
    exit;
}

if (!preg_match("/^\d{10}$/", $telefono)) {
    echo json_encode(["status" => "error", "message" => "Número de teléfono no válido, debe contener 10 dígitos"]);
    exit;
}

// Validar que la fecha es válida
try {
    if (empty($fecha_hora)) {
        throw new Exception("Fecha no proporcionada.");
    }
    $fecha_ingresada = new DateTime($fecha_hora);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Fecha no válida: " . $e->getMessage()]);
    exit;
}

$fecha_actual = new DateTime();
if ($fecha_ingresada < $fecha_actual) {
    echo json_encode(["status" => "error", "message" => "No puedes seleccionar una fecha anterior al día actual"]);
    exit;
}

if ($fecha_ingresada->format('Y-m-d') == $fecha_actual->format('Y-m-d')) {
    $fecha_minima = clone $fecha_actual;
    $fecha_minima->modify('+1 hour');

    if ($fecha_ingresada < $fecha_minima) {
        echo json_encode(["status" => "error", "message" => "Si reservas para hoy, la hora debe ser al menos una hora después"]);
        exit;
    }
}

$hora_ingresada = (int)$fecha_ingresada->format('H');
$minutos_ingresados = (int)$fecha_ingresada->format('i');

if ($hora_ingresada < 8 || $hora_ingresada > 19 || ($hora_ingresada === 19 && $minutos_ingresados > 0)) {
    echo json_encode(["status" => "error", "message" => "Selecciona un horario entre 8:00 AM y 8:00 PM"]);
    exit;
}

// Validar el costo del servicio
$precios = [
    "Manicura" => 150,
    "Cabello" => 200,
    "Corte" => 100,
    "Reflejos" => 250,
    "Pestañas permanentes" => 300
];

if (!isset($precios[$servicio]) || $precios[$servicio] != $costo) {
    echo json_encode(["status" => "error", "message" => "El costo no coincide con el servicio seleccionado"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO reservaciones (nombre, email, telefono, servicio, fecha_hora, costo) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $nombre, $email, $telefono, $servicio, $fecha_hora, $costo);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Error en la consulta SQL: " . $stmt->error]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Reserva realizada con éxito"]);

$stmt->close();
$conn->close();
?>
