<?php
// models/Caja.php

require_once __DIR__ . '/../config/Database.php';

class Caja {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Obtener la caja que está actualmente abierta
    public function obtenerCajaAbierta() {
        $sql = "SELECT id, monto_inicial, fecha_apertura 
                FROM cajas 
                WHERE estado = 'ABIERTA' 
                LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }

    // Abrir una nueva sesión de caja
    public function abrirCaja($monto_inicial) {
        $sql = "INSERT INTO cajas (monto_inicial, estado, fecha_apertura) 
                VALUES (:monto_inicial, 'ABIERTA', NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':monto_inicial' => $monto_inicial]);
    }

    // Realizar el arqueo y cierre definitivo de caja
    public function cerrarCaja($caja_id, $monto_final) {
        $sql = "UPDATE cajas 
                SET monto_final = :monto_final, estado = 'CERRADA', fecha_cierre = NOW() 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':monto_final' => $monto_final,
            ':id' => $caja_id
        ]);
    }
}