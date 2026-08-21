<?php
// config/Auditoria.php
require_once __DIR__ . '/Database.php';

class Auditoria {
    public static function registrar($accion, $detalle) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $nombreUsuario = $_SESSION['nombre_usuario'] ?? 'SISTEMA';

        if (!$usuarioId) return;

        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $db->prepare("
                INSERT INTO auditoria (usuario_id, nombre_usuario, accion, detalle, ip) 
                VALUES (:u_id, :nombre, :accion, :detalle, :ip)
            ");
            $stmt->execute([
                ':u_id' => $usuarioId,
                ':nombre' => $nombreUsuario,
                ':accion' => $accion,
                ':detalle' => $detalle,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {
            error_log("Error de auditoría: " . $e->getMessage());
        }
    }
}