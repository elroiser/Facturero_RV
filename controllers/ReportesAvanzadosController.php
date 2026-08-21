<?php
// controllers/ReportesAvanzadosController.php

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $db = Database::getConnection();

    // 1. TOP 5 PRODUCTOS MÁS VENDIDOS DEL MES
    if ($action === 'top_productos') {
        $sql = "SELECT p.nombre, SUM(dv.cantidad) AS total_vendido
                FROM detalle_ventas dv
                INNER JOIN ventas v ON dv.venta_id = v.id
                INNER JOIN productos p ON dv.producto_id = p.id
                WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE()) 
                  AND YEAR(v.fecha) = YEAR(CURRENT_DATE())
                GROUP BY p.id
                ORDER BY total_vendido DESC
                LIMIT 5";
        $stmt = $db->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // 2. HORAS PICO DE VENTAS DEL MES
    if ($action === 'horas_pico') {
        $sql = "SELECT HOUR(fecha) AS hora, COUNT(id) AS cantidad_ventas, SUM(total) AS total_monto
                FROM ventas
                WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                  AND YEAR(fecha) = YEAR(CURRENT_DATE())
                GROUP BY HOUR(fecha)
                ORDER BY hora ASC";
        $stmt = $db->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // 3. EXPORTAR DATOS COMPLETOS DE INVENTARIO
    if ($action === 'exportar_inventario') {
        $sql = "SELECT p.id, p.nombre, c.nombre AS categoria, p.precio, p.stock, 
                       IF(p.stock <= 5, 'BAJO', 'OK') AS estado_stock
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.nombre ASC";
        $stmt = $db->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}