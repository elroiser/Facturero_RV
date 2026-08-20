<?php
// controllers/VentaController.php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['caja_id']) || empty($input['items'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos de venta incompletos."]);
        exit;
    }

    try {
        $db = Database::getConnection();

        // 0. Validar que la caja esté ABIERTA
        $stmtCajaCheck = $db->prepare("SELECT estado FROM cajas WHERE id = :caja_id LIMIT 1");
        $stmtCajaCheck->execute([':caja_id' => $input['caja_id']]);
        $cajaEstado = $stmtCajaCheck->fetch();

        if (!$cajaEstado || $cajaEstado['estado'] !== 'ABIERTA') {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "⚠️ La caja se encuentra CERRADA. Debes realizar la Apertura de Caja en la sección de Cierre de Caja antes de facturar."
            ]);
            exit;
        }


        $db->beginTransaction();

        $caja_id = $input['caja_id'];
        $metodo_pago = $input['metodo_pago'];
        $items = $input['items'];
        $identificacion = isset($input['cliente_identificacion']) ? trim($input['cliente_identificacion']) : '9999999999999';

        // 1. Validar cliente
        $stmtCliente = $db->prepare("SELECT id FROM clientes WHERE identificacion = :identificacion LIMIT 1");
        $stmtCliente->execute([':identificacion' => $identificacion]);
        $cliente = $stmtCliente->fetch();

        if (!$cliente) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "El RUC/Cédula '{$identificacion}' no está registrado en el sistema. Regístralo en la pestaña Clientes."
            ]);
            exit;
        }

        $cliente_id = $cliente['id'];

        // 2. Validar stock de productos y calcular total
        $total_venta = 0;
        foreach ($items as $item) {
            $stmtStock = $db->prepare("SELECT stock, nombre FROM productos WHERE id = :id FOR UPDATE");
            $stmtStock->execute([':id' => $item['producto_id']]);
            $producto = $stmtStock->fetch();

            if (!$producto) {
                throw new Exception("El producto ID {$item['producto_id']} no existe.");
            }

            if ($producto['stock'] < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: {$producto['nombre']}.");
            }

            $total_venta += $item['cantidad'] * $item['precio_unitario'];
        }

        // 3. Registrar Venta
        $sqlVenta = "INSERT INTO ventas (caja_id, total, metodo_pago, fecha) VALUES (:caja_id, :total, :metodo_pago, NOW())";
        $stmtVenta = $db->prepare("INSERT INTO ventas (caja_id, total, metodo_pago, fecha) VALUES (:caja_id, :total, :metodo_pago, NOW())");
        $stmtVenta->execute([
            ':caja_id' => $caja_id,
            ':total' => $total_venta,
            ':metodo_pago' => $metodo_pago
        ]);

        $venta_id = $db->lastInsertId();

        // 4. Cálculos de IVA (15%)
        $subtotal_sin_impuesto = round($total_venta / 1.15, 2);
        $monto_iva = round($total_venta - $subtotal_sin_impuesto, 2);

        // 5. Insertar la factura temporalmente
        $temp_secuencial = "TEMP-" . microtime(true);
        $sqlFactura = "INSERT INTO facturas (secuencial, cliente_id, caja_id, subtotal_sin_impuesto, monto_iva, total, metodo_pago, fecha_emision) 
                       VALUES (:secuencial, :cliente_id, :caja_id, :subtotal, :iva, :total, :metodo_pago, NOW())";
        $stmtFactura = $db->prepare($sqlFactura);
        $stmtFactura->execute([
            ':secuencial' => $temp_secuencial,
            ':cliente_id' => $cliente_id,
            ':caja_id' => $caja_id,
            ':subtotal' => $subtotal_sin_impuesto,
            ':iva' => $monto_iva,
            ':total' => $total_venta,
            ':metodo_pago' => $metodo_pago
        ]);

        $factura_id = (int)$db->lastInsertId();

        // 6. Generar el secuencial definitivo con 9 dígitos exactos
        $secuencial_definitivo = "001-001-" . sprintf("%09d", $factura_id);

        // Actualizar factura
        $stmtUpdate = $db->prepare("UPDATE facturas SET secuencial = :secuencial WHERE id = :id");
        $stmtUpdate->execute([
            ':secuencial' => $secuencial_definitivo,
            ':id' => $factura_id
        ]);

        // 7. Registrar Detalle y Descontar Stock
        $stmtDetalleVenta = $db->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)");
        $stmtDetalleFactura = $db->prepare("INSERT INTO detalle_facturas (factura_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal)");
        $stmtStockUpdate = $db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id");

        foreach ($items as $item) {
            $subtotal = $item['cantidad'] * $item['precio_unitario'];

            $stmtDetalleVenta->execute([
                ':venta_id' => $venta_id,
                ':producto_id' => $item['producto_id'],
                ':cantidad' => $item['cantidad'],
                ':precio_unitario' => $item['precio_unitario'],
                ':subtotal' => $subtotal
            ]);

            $stmtDetalleFactura->execute([
                ':factura_id' => $factura_id,
                ':producto_id' => $item['producto_id'],
                ':cantidad' => $item['cantidad'],
                ':precio_unitario' => $item['precio_unitario'],
                ':subtotal' => $subtotal
            ]);

            $stmtStockUpdate->execute([
                ':cantidad' => $item['cantidad'],
                ':producto_id' => $item['producto_id']
            ]);
        }

        $db->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Venta y factura procesadas con éxito",
            "venta_id" => $venta_id,
            "factura_id" => $factura_id,
            "secuencial" => $secuencial_definitivo,
            "total" => $total_venta
        ]);
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }

        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error interno: " . $e->getMessage()
        ]);
    }
}
