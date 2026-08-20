<?php
// models/Producto.php

require_once __DIR__ . '/../config/Database.php';

class Producto {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Obtener todos los productos ordenados por nombre
    public function obtenerTodos() {
        $sql = "SELECT p.id, p.codigo_barras AS codigo, p.nombre, p.precio, p.stock, c.nombre AS categoria 
                FROM productos p 
                INNER JOIN categorias c ON p.categoria_id = c.id 
                ORDER BY p.nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Obtener un producto específico por ID con bloqueo para transacciones (FOR UPDATE)
    public function obtenerPorIdConBloqueo($id) {
        $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id = :id FOR UPDATE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Registrar un nuevo producto desde la vista de inventario
    public function crear($categoria_id, $codigo_barras, $nombre, $precio, $stock) {
        $sql = "INSERT INTO productos (categoria_id, codigo_barras, nombre, precio, stock) 
                VALUES (:categoria_id, :codigo_barras, :nombre, :precio, :stock)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':categoria_id' => $categoria_id,
            ':codigo_barras' => $codigo_barras,
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':stock' => $stock
        ]);
    }

    // Descontar stock al realizar una venta
    public function descontarStock($id, $cantidad) {
        $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cantidad' => $cantidad,
            ':id' => $id
        ]);
    }
}