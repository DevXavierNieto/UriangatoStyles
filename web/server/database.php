<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
require_once 'conexion.php';

class DAOReservacion {
    public $conn;

    public function __construct() {
        $this->conn = conectarDB();
    }

    public function obtenerCostoYIdServicio($nombreServicio) {
        $stmt = $this->conn->prepare("SELECT id, costo FROM servicios WHERE nombre = ?");
        $stmt->bind_param("s", $nombreServicio);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function insertarReserva($data) {
        $stmt = $this->conn->prepare("INSERT INTO reservaciones (nombre, email, telefono, servicio_id, fecha_hora, codigo, costo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssd", $data['nombre'], $data['email'], $data['telefono'], $data['servicio_id'], $data['fecha_hora'], $data['codigo'], $data['costo']);
        return $stmt->execute();
    }

    public function obtenerHorasOcupadas($fecha) {
        $stmt = $this->conn->prepare("SELECT DATE_FORMAT(fecha_hora, '%H:%i') as hora FROM reservaciones WHERE DATE(fecha_hora) = ?");
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();

        $horasOcupadas = [];
        while ($row = $result->fetch_assoc()) {
            $horasOcupadas[] = $row['hora'];
        }
        return $horasOcupadas;
    }

    public function obtenerServicios() {
        $stmt = $this->conn->prepare("SELECT nombre, costo FROM servicios");
        $stmt->execute();
        $result = $stmt->get_result();

        $servicios = [];
        while ($row = $result->fetch_assoc()) {
            $servicios[] = $row;
        }
        return $servicios;
    }

    public function obtenerCitaPorCodigo($codigo) {
        $stmt = $this->conn->prepare("SELECT r.fecha_hora, r.nombre, s.nombre AS servicio, r.costo FROM reservaciones r JOIN servicios s ON r.servicio_id = s.id WHERE r.codigo = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $fechaHora = new DateTime($row['fecha_hora']);
            return [
                "status" => "success",
                "fecha" => $fechaHora->format('Y-m-d'),
                "hora" => $fechaHora->format('H:i'),
                "cliente" => $row['nombre'],
                "servicio" => $row['servicio'],
                "costo" => "$" . number_format($row['costo'], 2),
                "ubicacion" => "Sucursal Principal"
            ];
        } else {
            return ["status" => "error", "message" => "No se encontró ninguna cita con ese código."];
        }
    }

    public function obtenerCitasPorFecha($fecha) {
        $stmt = $this->conn->prepare("
            SELECT r.nombre, r.email, r.codigo, DATE_FORMAT(r.fecha_hora, '%H:%i') as hora, s.nombre AS servicio, r.costo
            FROM reservaciones r
            JOIN servicios s ON r.servicio_id = s.id
            WHERE DATE(r.fecha_hora) = ?
            ORDER BY r.fecha_hora ASC
        ");
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $citas = [];
        while ($row = $result->fetch_assoc()) {
            $citas[] = $row;
        }
        return $citas;
    }
    

    public function obtenerPromocionPorCodigo($codigo) {
        $stmt = $this->conn->prepare("SELECT p.codigo, p.descuento, s.nombre AS servicio, s.costo FROM promociones p JOIN servicios s ON p.servicio_id = s.id WHERE p.codigo = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function listarPromociones() {
        $stmt = $this->conn->prepare("SELECT p.id, p.codigo, p.descripcion, p.descuento, s.nombre AS servicio FROM promociones p JOIN servicios s ON p.servicio_id = s.id");
        $stmt->execute();
        $result = $stmt->get_result();
        $promos = [];
        while ($row = $result->fetch_assoc()) {
            $promos[] = $row;
        }
        return $promos;
    }

    function insertarPromocion($conn, $data) {
        $codigo = $data['codigo'];
        $descripcion = $data['descripcion'];
        $descuento = $data['descuento'];
        $servicio_id = $data['servicio_id'];
    
        $stmt = $conn->prepare("INSERT INTO promociones (codigo, descripcion, descuento, servicio_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $codigo, $descripcion, $descuento, $servicio_id);
    
        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Promoción guardada correctamente."];
        } else {
            return ["status" => "error", "message" => "No se pudo guardar la promoción."];
        }
    }
    
    function generarCodigo($longitud = 5) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($caracteres), 0, $longitud);
    }
    
    function insertarReservacion($dao, $data) {
        if (!isset($data['nombre'], $data['email'], $data['telefono'], $data['servicio'], $data['fecha_hora'])) {
            return ["status" => "error", "message" => "Faltan campos obligatorios"];
        }
    
        $servicio = $dao->obtenerCostoYIdServicio($data['servicio']);
        if (!$servicio) {
            return ["status" => "error", "message" => "Servicio no encontrado"];
        }
    
        $costoFinal = $servicio['costo'];
        if (!empty($data['promocion'])) {
            $stmt = $dao->conn->prepare("SELECT descuento FROM promociones WHERE codigo = ? AND servicio_id = ?");
            $stmt->bind_param("si", $data['promocion'], $servicio['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($promo = $res->fetch_assoc()) {
                $costoFinal -= $costoFinal * ($promo['descuento'] / 100);
            }
        }
    
        $reserva = [
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'],
            'servicio_id' => $servicio['id'],
            'fecha_hora' => $data['fecha_hora'],
            'codigo' => generarCodigo(),
            'costo' => $costoFinal
        ];
    
        if ($dao->insertarReserva($reserva)) {
            return [
                "status" => "success",
                "message" => "Reserva creada correctamente",
                "codigo" => $reserva['codigo'],
                "costo" => "$" . number_format($costoFinal, 2)
            ];
        } else {
            return ["status" => "error", "message" => "Error al guardar la reserva"];
        }
        
    }
    
    
}

$dao = new DAOReservacion();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['fecha'])) {
        echo json_encode(["status" => "success", "horasOcupadas" => $dao->obtenerHorasOcupadas($_GET['fecha'])]);
    } elseif (isset($_GET['servicios'])) {
        echo json_encode(["status" => "success", "servicios" => $dao->obtenerServicios()]);
    } elseif (isset($_GET['codigo'])) {
        echo json_encode($dao->obtenerCitaPorCodigo($_GET['codigo']));
    } elseif (isset($_GET['citasFecha'])) {
        echo json_encode(["status" => "success", "citas" => $dao->obtenerCitasPorFecha($_GET['citasFecha'])]);
    } elseif (isset($_GET['promocion'])) {
        $promo = $dao->obtenerPromocionPorCodigo($_GET['promocion']);
        echo json_encode($promo ? ["status" => "success", "promocion" => $promo] : ["status" => "error", "message" => "Código no válido"]);
    } elseif (isset($_GET['listarPromociones'])) {
        echo json_encode(["status" => "success", "promociones" => $dao->listarPromociones()]);
    } else {
        echo json_encode(["status" => "error", "message" => "Parámetro no reconocido"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['nuevaPromocion'])) {
        $codigo = $data['codigo'];
        $descripcion = $data['descripcion'];
        $descuento = $data['descuento'];
        $servicio_id = $data['servicio_id'];

        $stmt = $dao->conn->prepare("INSERT INTO promociones (codigo, descripcion, descuento, servicio_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $codigo, $descripcion, $descuento, $servicio_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Promoción guardada correctamente."]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo guardar la promoción."]);
        }
        exit;
    }
    if (!isset($data['nombre'], $data['email'], $data['telefono'], $data['servicio'], $data['fecha_hora'])) {
        echo json_encode(["status" => "error", "message" => "Faltan campos obligatorios"]);
        exit;
    }

    $servicio = $dao->obtenerCostoYIdServicio($data['servicio']);
    if (!$servicio) {
        echo json_encode(["status" => "error", "message" => "Servicio no encontrado"]);
        exit;
    }

    $costoFinal = $servicio['costo'];
    if (!empty($data['promocion'])) {
        $stmt = $dao->conn->prepare("SELECT descuento FROM promociones WHERE codigo = ? AND servicio_id = ?");
        $stmt->bind_param("si", $data['promocion'], $servicio['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($promo = $res->fetch_assoc()) {
            $costoFinal -= $costoFinal * ($promo['descuento'] / 100);
        }
    }

    function generarCodigo($longitud = 5) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($caracteres), 0, $longitud);
    }

    $reserva = [
        'nombre' => $data['nombre'],
        'email' => $data['email'],
        'telefono' => $data['telefono'],
        'servicio_id' => $servicio['id'],
        'fecha_hora' => $data['fecha_hora'],
        'codigo' => generarCodigo(),
        'costo' => $costoFinal
    ];

    if ($dao->insertarReserva($reserva)) {
        echo json_encode([
            "status" => "success",
            "message" => "Reserva creada correctamente",
            "codigo" => $reserva['codigo'],
            "costo" => "$" . number_format($costoFinal, 2)
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar la reserva"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['editarPromocion'])) {
        $stmt = $dao->conn->prepare("UPDATE promociones SET codigo = ?, descripcion = ?, descuento = ?, servicio_id = ? WHERE id = ?");
    $stmt->bind_param("ssdii", $data['codigo'], $data['descripcion'], $data['descuento'], $data['servicio_id'], $data['id']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Promoción actualizada correctamente."]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo actualizar la promoción."]);
        }
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['codigoPromocion'])) {
        $stmt = $dao->conn->prepare("DELETE FROM promociones WHERE codigo = ?");
        $stmt->bind_param("s", $data['codigoPromocion']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Promoción eliminada correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo eliminar la promoción"]);
        }
        exit;
    }

    if (!isset($data['codigo'])) {
        echo json_encode(["status" => "error", "message" => "Código no proporcionado"]);
        exit;
    }

    $stmt = $dao->conn->prepare("DELETE FROM reservaciones WHERE codigo = ?");
    $stmt->bind_param("s", $data['codigo']);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Cita eliminada correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar la cita"]);
    }
}
 else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>
