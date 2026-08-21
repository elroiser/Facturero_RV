<?php
// controllers/AuditoriaController.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM auditoria ORDER BY id DESC LIMIT 100");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}