<?php
// controllers/ConsultaCedulaController.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['identificacion'])) {
    $identificacion = trim($_GET['identificacion']);

    try {
        $db = Database::getConnection();

        // 1. Primero busca en tu base de datos local (MySQL)
        $stmt = $db->prepare("SELECT * FROM clientes WHERE identificacion = :identificacion LIMIT 1");
        $stmt->execute([':identificacion' => $identificacion]);
        $clienteLocal = $stmt->fetch();

        if ($clienteLocal) {
            echo json_encode([
                "status" => "success",
                "origen" => "local",
                "data" => $clienteLocal
            ]);
            exit;
        }

        // 2. Si no está en MySQL, consulta la API gratuita pública de RUC/Cédula
        $url = "https://sri.factura.ec/api/v1/consultar/" . $identificacion;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $dataApi = json_decode($response, true);
            if (isset($dataApi['razonSocial']) || isset($dataApi['nombre'])) {
                $nombreObtenido = isset($dataApi['razonSocial']) ? $dataApi['razonSocial'] : $dataApi['nombre'];
                echo json_encode([
                    "status" => "success",
                    "origen" => "api_gratuitas",
                    "data" => [
                        "identificacion" => $identificacion,
                        "razon_social" => $nombreObtenido,
                        "direccion" => isset($dataApi['direccion']) ? $dataApi['direccion'] : ''
                    ]
                ]);
                exit;
            }
        }

        echo json_encode(["status" => "not_found", "message" => "Cliente no encontrado. Ingrese el nombre manualmente."]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}