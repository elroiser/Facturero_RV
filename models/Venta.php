<?php
// models/Venta.php

require_once __DIR__ . '/../config/Database.php';

class Venta {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Insertar el encabezado de la venta
    public function registrarEncabezado($caja_id, $total, $metodo_pago) {
        $sql = "INSERT INTO ventas (caja_id, total, metodo_pago, fecha) 
                VALUES (:caja_id, :total, :metodo_pago, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':caja_id' => $caja_id,
            ':total' => $total,
            ':metodo_pago' => $metodo_pago
        ]);
        return $this->db->lastInsertId();
    }

    // Insertar cada ítem del carrito en la tabla detalle_ventas
    public function registrarDetalle($venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal) {
        $sql = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':venta_id' => $venta_id,
            ':producto_id' => $producto_id,
            ':cantidad' => $cantidad,
            ':precio_unitario' => $precio_unitario,
            ':subtotal' => $subtotal
        ]);
    }

    // Obtener historial de ventas asociadas a una caja
    public function obtenerVentasPorCaja($caja_id) {
        $sql = "SELECT id, total, metodo_pago, fecha 
                FROM ventas 
                WHERE caja_id = :caja_id 
                ORDER BY fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $caja_id]);
        return $stmt->fetchAll();
    }
}