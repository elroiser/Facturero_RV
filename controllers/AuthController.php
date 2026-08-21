<?php
// controllers/AuthController.php

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);

    $usuarioInput = isset($input['usuario']) ? trim($input['usuario']) : '';
    $passwordInput = isset($input['password']) ? trim($input['password']) : '';

    if (empty($usuarioInput) || empty($passwordInput)) {
        echo json_encode(["status" => "error", "message" => "Ingresa el usuario y la contraseña."]);
        exit;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $usuarioInput]);
        $user = $stmt->fetch();

        // Validar usuario y contraseña (Soporta clave encriptada o comparación 'admin123' inicial)
        if ($user && (password_verify($passwordInput, $user['password']) || $passwordInput === 'admin123')) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre'];
            $_SESSION['rol_usuario'] = $user['rol'];

            echo json_encode(["status" => "success", "message" => "Bienvenido"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header("Location: ../views/login.php");
    exit;
}