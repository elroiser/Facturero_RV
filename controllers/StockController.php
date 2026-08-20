<?php
// controllers/StockController.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $producto_id = isset($input['producto_id']) ? intval($input['producto_id']) : 0;
    $cantidad_ingresar = isset($input['cantidad']) ? intval($input['cantidad']) : 0;

    if ($producto_id <= 0 || $cantidad_ingresar <= 0) {
        echo json_encode(["status" => "error", "message" => "La cantidad debe ser un número mayor a cero."]);
        exit;
    }

    try {
        $db = Database::getConnection();

        // Sumar la cantidad entrante al stock actual
        $stmt = $db->prepare("
            UPDATE productos 
            SET stock = stock + :cantidad 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':cantidad' => $cantidad_ingresar,
            ':id' => $producto_id
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Stock actualizado correctamente. Se sumaron {$cantidad_ingresar} unidades."
        ]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al actualizar stock: " . $e->getMessage()]);
    }
}