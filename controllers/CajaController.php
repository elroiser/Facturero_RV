<?php
// controllers/CajaController.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getConnection();
    $caja_id = 1; // ID de la caja activa

    // 1. Obtener datos de la caja actual
    $stmtCaja = $db->prepare("SELECT * FROM cajas WHERE id = :id");
    $stmtCaja->execute([':id' => $caja_id]);
    $caja = $stmtCaja->fetch();

    if (!$caja) {
        echo json_encode(["status" => "error", "message" => "No hay caja activa."]);
        exit;
    }

    // 2. Calcular total vendido por método de pago
    $stmtMetodos = $db->prepare("
        SELECT metodo_pago, COALESCE(SUM(total), 0) AS subtotal 
        FROM ventas 
        WHERE caja_id = :caja_id 
        GROUP BY metodo_pago
    ");
    $stmtMetodos->execute([':caja_id' => $caja_id]);
    $metodos = $stmtMetodos->fetchAll();

    $ventasEfectivo = 0;
    $ventasTransferencia = 0;

    foreach ($metodos as $m) {
        if ($m['metodo_pago'] === 'EFECTIVO') {
            $ventasEfectivo = (float)$m['subtotal'];
        } elseif ($m['metodo_pago'] === 'TRANSFERENCIA') {
            $ventasTransferencia = (float)$m['subtotal'];
        }
    }

    // 3. Obtener el historial de transacciones de la caja
    $stmtVentas = $db->prepare("
        SELECT id, DATE_FORMAT(fecha, '%h:%i %p') AS hora, metodo_pago, total 
        FROM ventas 
        WHERE caja_id = :caja_id 
        ORDER BY fecha DESC
    ");
    $stmtVentas->execute([':caja_id' => $caja_id]);
    $ventasHistorial = $stmtVentas->fetchAll();

    $montoInicial = (float)$caja['monto_inicial'];
    $totalArqueo = $montoInicial + $ventasEfectivo + $ventasTransferencia;

    echo json_encode([
        "status" => "success",
        "data" => [
            "caja_id" => $caja['id'],
            "monto_inicial" => $montoInicial,
            "ventas_efectivo" => $ventasEfectivo,
            "ventas_transferencia" => $ventasTransferencia,
            "total_arqueo" => $totalArqueo,
            "ventas" => $ventasHistorial
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}