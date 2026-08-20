<?php
// controllers/ClienteController.php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getConnection();

    // GET: Obtener lista o consultar un cliente por cédula
    if ($method === 'GET') {
        if (isset($_GET['identificacion'])) {
            $stmt = $db->prepare("SELECT * FROM clientes WHERE identificacion = :identificacion LIMIT 1");
            $stmt->execute([':identificacion' => trim($_GET['identificacion'])]);
            $cliente = $stmt->fetch();

            echo json_encode([
                "status" => "success",
                "encontrado" => $cliente ? true : false,
                "data" => $cliente
            ]);
            exit;
        }

        $stmt = $db->query("SELECT * FROM clientes ORDER BY id DESC");
        $clientes = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "data" => $clientes
        ]);
        exit;
    }

    // POST: Registrar un nuevo cliente
    if ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['identificacion']) || empty($input['razon_social'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Cédula/RUC y Razón Social son obligatorios."]);
            exit;
        }

        // Verificar si la cédula ya existe para evitar duplicados
        $stmtExist = $db->prepare("SELECT id FROM clientes WHERE identificacion = :identificacion LIMIT 1");
        $stmtExist->execute([':identificacion' => trim($input['identificacion'])]);
        if ($stmtExist->fetch()) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "El RUC/Cédula ya se encuentra registrado."]);
            exit;
        }

        $sql = "INSERT INTO clientes (identificacion, razon_social, direccion, telefono, email) 
                VALUES (:identificacion, :razon_social, :direccion, :telefono, :email)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':identificacion' => trim($input['identificacion']),
            ':razon_social' => trim($input['razon_social']),
            ':direccion' => !empty($input['direccion']) ? trim($input['direccion']) : 'Ciudad',
            ':telefono' => !empty($input['telefono']) ? trim($input['telefono']) : 'N/A',
            ':email' => !empty($input['email']) ? trim($input['email']) : 'cliente@correo.com'
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Cliente guardado con éxito.",
            "id" => $db->lastInsertId()
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno: " . $e->getMessage()
    ]);
}