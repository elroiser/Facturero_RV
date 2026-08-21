<?php
// controllers/UsuarioController.php

ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

if (file_exists(__DIR__ . '/../config/Auditoria.php')) {
    require_once __DIR__ . '/../config/Auditoria.php';
} elseif (file_exists(__DIR__ . '/../config/auditoria.php')) {
    require_once __DIR__ . '/../config/auditoria.php';
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Permisos insuficientes.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getConnection();

    // 1. LISTAR USUARIOS
    if ($method === 'GET' && empty($action)) {
        $stmt = $db->query("SELECT id, nombre, usuario, rol, COALESCE(estado, 'ACTIVO') AS estado, creado_en FROM usuarios ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // 2. CREAR USUARIO
    if ($method === 'POST' && $action === 'crear') {
        $inputRaw = file_get_contents("php://input");
        $data = json_decode($inputRaw, true);

        if (!$data) {
            echo json_encode(['status' => 'error', 'message' => 'Datos recibidos no válidos.']);
            exit;
        }

        $nombre = trim($data['nombre'] ?? '');
        $usuario = trim($data['usuario'] ?? '');
        $password = trim($data['password'] ?? '');
        $rol = strtoupper(trim($data['rol'] ?? 'CAJERO'));

        if (empty($nombre) || empty($usuario) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE usuario = :u LIMIT 1");
        $stmtCheck->execute([':u' => $usuario]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de usuario ya está registrado.']);
            exit;
        }

        $hashPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, estado) VALUES (:n, :u, :p, :r, 'ACTIVO')");
        $stmt->execute([':n' => $nombre, ':u' => $usuario, ':p' => $hashPassword, ':r' => $rol]);

        if (class_exists('Auditoria')) {
            Auditoria::registrar('CREAR_USUARIO', "Creó al usuario '{$usuario}' ({$nombre}) con rol {$rol}.");
        }

        echo json_encode(['status' => 'success', 'message' => 'Usuario registrado correctamente.']);
        exit;
    }

    // 3. CAMBIAR ESTADO
    if ($method === 'POST' && $action === 'cambiar_estado') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = intval($data['id'] ?? 0);
        $nuevoEstado = ($data['estado'] ?? '') === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

        if ($id === intval($_SESSION['usuario_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'No puedes desactivar tu propia cuenta activa.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE usuarios SET estado = :e WHERE id = :id");
        $stmt->execute([':e' => $nuevoEstado, ':id' => $id]);

        if (class_exists('Auditoria')) {
            Auditoria::registrar('CAMBIO_ESTADO_USUARIO', "Cambió el estado del usuario ID {$id} a {$nuevoEstado}.");
        }

        echo json_encode(['status' => 'success', 'message' => 'Estado del usuario actualizado.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}