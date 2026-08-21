<?php
// controllers/ReporteVentasController.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'listar';

try {
    $db = Database::getConnection();

    // 1. LISTAR TODAS LAS FACTURAS/VENTAS
    if ($action === 'listar') {
        $fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
        $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

        // Consulta directa a facturas uniendo con clientes por id
        $sql = "
            SELECT 
                f.id,
                f.secuencial,
                f.fecha_emision,
                f.total,
                f.metodo_pago,
                f.cliente_id,
                c.razon_social,
                c.identificacion
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $sql .= " AND DATE(f.fecha_emision) BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $fecha_inicio;
            $params[':fecha_fin'] = $fecha_fin;
        }

        if (!empty($busqueda)) {
            $sql .= " AND (c.razon_social LIKE :busqueda OR c.identificacion LIKE :busqueda OR f.secuencial LIKE :busqueda OR f.id LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }

        $sql .= " ORDER BY f.id DESC LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $facturas = $stmt->fetchAll();

        $totalGeneral = 0;
        $dataNormalizada = [];

        foreach ($facturas as $f) {
            $totalGeneral += floatval($f['total']);
            
            // Si el cliente es nulo o no se encontró, asignar Consumidor Final
            $clienteNombre = !empty($f['razon_social']) ? $f['razon_social'] : 'CONSUMIDOR FINAL';
            $clienteIdent = !empty($f['identificacion']) ? $f['identificacion'] : '9999999999999';
            $numSecuencial = !empty($f['secuencial']) ? $f['secuencial'] : 'FAC-' . str_pad($f['id'], 6, '0', STR_PAD_LEFT);
            $metodo = !empty($f['metodo_pago']) ? $f['metodo_pago'] : 'EFECTIVO';

            $dataNormalizada[] = [
                'id' => $f['id'],
                'secuencial' => $numSecuencial,
                'fecha_emision' => $f['fecha_emision'],
                'total' => $f['total'],
                'metodo_pago' => $metodo,
                'razon_social' => $clienteNombre,
                'identificacion' => $clienteIdent
            ];
        }

        echo json_encode([
            "status" => "success",
            "data" => $dataNormalizada,
            "total_acumulado" => number_format($totalGeneral, 2, '.', '')
        ]);
        exit;
    }

    // 2. DETALLE DE FACTURA
    if ($action === 'detalle') {
        $factura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($factura_id <= 0) {
            echo json_encode(["status" => "error", "message" => "ID inválido."]);
            exit;
        }

        $stmt = $db->prepare("
            SELECT df.*, p.nombre 
            FROM detalle_facturas df 
            INNER JOIN productos p ON df.producto_id = p.id 
            WHERE df.factura_id = :id
        ");
        $stmt->execute([':id' => $factura_id]);
        $items = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "data" => $items
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}