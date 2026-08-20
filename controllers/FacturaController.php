<?php
// controllers/FacturaController.php

require_once __DIR__ . '/../config/Database.php';

class FacturaController {

    // Crear factura desde la venta
    public static function crearFactura($db, $cliente_id, $caja_id, $metodo_pago, $items) {
        // Generar secuencial de factura automático (001-001-000000XXX)
        $stmtSec = $db->query("SELECT COUNT(*) + 1 AS proximo FROM facturas");
        $proximo = $stmtSec->fetch()['proximo'];
        $secuencial = "001-001-" . str_pad($proximo, 9, "0", STR_PAD_LEFT);

        // Calcular impuestos (Tasa del 12% de IVA sobre subtotal)
        $total_general = 0;
        foreach ($items as $item) {
            $total_general += $item['cantidad'] * $item['precio_unitario'];
        }

        $subtotal_sin_impuesto = round($total_general / 1.12, 2);
        $monto_iva = round($total_general - $subtotal_sin_impuesto, 2);

        // 1. Guardar Encabezado de Factura
        $sqlFactura = "INSERT INTO facturas (secuencial, cliente_id, caja_id, subtotal_sin_impuesto, monto_iva, total, metodo_pago) 
                       VALUES (:secuencial, :cliente_id, :caja_id, :subtotal, :iva, :total, :metodo_pago)";
        $stmt = $db->prepare($sqlFactura);
        $stmt->execute([
            ':secuencial' => $secuencial,
            ':cliente_id' => $cliente_id,
            ':caja_id' => $caja_id,
            ':subtotal' => $subtotal_sin_impuesto,
            ':iva' => $monto_iva,
            ':total' => $total_general,
            ':metodo_pago' => $metodo_pago
        ]);

        $factura_id = $db->lastInsertId();

        // 2. Guardar Detalle de Factura
        $stmtDetalle = $db->prepare("INSERT INTO detalle_facturas (factura_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal)");

        foreach ($items as $item) {
            $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
            $stmtDetalle->execute([
                ':factura_id' => $factura_id,
                ':producto_id' => $item['producto_id'],
                ':cantidad' => $item['cantidad'],
                ':precio_unitario' => $item['precio_unitario'],
                ':subtotal' => $subtotal_item
            ]);
        }

        return [
            "factura_id" => $factura_id,
            "secuencial" => $secuencial
        ];
    }
}