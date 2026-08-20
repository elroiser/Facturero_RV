<?php
// controllers/CajaController.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getConnection();
    $caja_id = 1;

    // GET: Obtener resumen en tiempo real
    if ($method === 'GET') {
        $stmtCaja = $db->prepare("SELECT * FROM cajas WHERE id = :id");
        $stmtCaja->execute([':id' => $caja_id]);
        $caja = $stmtCaja->fetch();

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
            if ($m['metodo_pago'] === 'EFECTIVO') $ventasEfectivo = (float)$m['subtotal'];
            if ($m['metodo_pago'] === 'TRANSFERENCIA') $ventasTransferencia = (float)$m['subtotal'];
        }

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
                "estado" => $caja['estado'],
                "monto_inicial" => $montoInicial,
                "ventas_efectivo" => $ventasEfectivo,
                "ventas_transferencia" => $ventasTransferencia,
                "total_arqueo" => $totalArqueo,
                "ventas" => $ventasHistorial
            ]
        ]);
        exit;
    }

    // POST: Cierre o Apertura de Caja
    if ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $accion = isset($input['accion']) ? $input['accion'] : '';

        if ($accion === 'cerrar') {
            $monto_final = floatval($input['monto_final']);

            $sql = "UPDATE cajas 
                    SET estado = 'CERRADA', monto_final = :monto_final, fecha_cierre = NOW() 
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':monto_final' => $monto_final,
                ':id' => $caja_id
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Caja cerrada exitosamente."
            ]);
            exit;
        }

        if ($accion === 'reabrir') {
            $monto_inicial = floatval($input['monto_inicial']);

            // Reiniciar caja para nueva jornada
            $sql = "UPDATE cajas 
                    SET estado = 'ABIERTA', monto_inicial = :monto_inicial, monto_final = NULL, fecha_apertura = NOW(), fecha_cierre = NULL 
                    WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':monto_inicial' => $monto_inicial,
                ':id' => $caja_id
            ]);

            // Vaciar ventas para el nuevo turno
            $db->exec("DELETE FROM detalle_ventas");
            $db->exec("DELETE FROM ventas");

            echo json_encode([
                "status" => "success",
                "message" => "Caja reabierta para una nueva jornada."
            ]);
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}