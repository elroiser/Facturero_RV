<?php
// controllers/ProductoController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $db = Database::getConnection();

    // GET: Obtener lista completa de productos
    if ($method === 'GET') {
        $stmt = $db->query("
            SELECT p.id, p.codigo_barras AS codigo, p.nombre, p.precio, p.stock, c.nombre AS categoria 
            FROM productos p 
            INNER JOIN categorias c ON p.categoria_id = c.id 
            ORDER BY p.id DESC
        ");
        $productos = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "data" => $productos
        ]);
        exit;
    }

    // POST: Acciones de registro y actualización
    if ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);

        // 1. ACTUALIZAR PRECIO (Solo Administrador)
        if ($action === 'actualizar_precio') {
            if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "No tienes permisos de Administrador para modificar precios."]);
                exit;
            }

            $producto_id = isset($input['id']) ? intval($input['id']) : 0;
            $nuevo_precio = isset($input['precio']) ? floatval($input['precio']) : 0.0;

            if ($producto_id <= 0 || $nuevo_precio <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID de producto o precio inválido."]);
                exit;
            }

            $stmt = $db->prepare("UPDATE productos SET precio = :precio WHERE id = :id");
            $stmt->execute([
                ':precio' => $nuevo_precio,
                ':id' => $producto_id
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Precio actualizado exitosamente."
            ]);
            exit;
        }

        // 2. REGISTRAR NUEVO PRODUCTO
        if (
            empty($input['codigo_barras']) || 
            empty($input['nombre']) || 
            empty($input['categoria_id']) || 
            !isset($input['precio']) || 
            !isset($input['stock'])
        ) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
            exit;
        }

        $sql = "INSERT INTO productos (categoria_id, codigo_barras, nombre, precio, stock) 
                VALUES (:categoria_id, :codigo_barras, :nombre, :precio, :stock)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $input['categoria_id'],
            ':codigo_barras' => $input['codigo_barras'],
            ':nombre' => $input['nombre'],
            ':precio' => $input['precio'],
            ':stock' => $input['stock']
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Producto registrado exitosamente.",
            "id" => $db->lastInsertId()
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
}