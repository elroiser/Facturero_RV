<?php
// views/imprimir_factura.php

require_once __DIR__ . '/../config/Database.php';

$factura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($factura_id === 0) {
    die("ID de factura no válido.");
}

$db = Database::getConnection();

// Consultar datos de la empresa
$emisor = $db->query("SELECT * FROM configuracion_emisor LIMIT 1")->fetch();

// Consultar datos de la factura y cliente
$stmtFactura = $db->prepare("
    SELECT f.*, c.identificacion, c.razon_social, c.direccion, c.telefono, c.email 
    FROM facturas f
    INNER JOIN clientes c ON f.cliente_id = c.id
    WHERE f.id = :id
");
$stmtFactura->execute([':id' => $factura_id]);
$factura = $stmtFactura->fetch();

if (!$factura) {
    die("Factura no encontrada.");
}

// Consultar detalle de la factura
$stmtDetalle = $db->prepare("
    SELECT df.*, p.nombre, p.codigo_barras 
    FROM detalle_facturas df
    INNER JOIN productos p ON df.producto_id = p.id
    WHERE df.factura_id = :id
");
$stmtDetalle->execute([':id' => $factura_id]);
$detalles = $stmtDetalle->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= $factura['secuencial']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 15px; }
        .company-title { font-size: 18px; font-weight: bold; color: #2563eb; }
        .invoice-box { border: 1px solid #ccc; padding: 10px; border-radius: 5px; width: 250px; text-align: center; }
        .client-info { margin: 20px 0; background: #f8fafc; padding: 10px; border-radius: 5px; border: 1px solid #e2e8f0; }
        .table-items { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-items th, .table-items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table-items th { background-color: #0f172a; color: white; }
        .totals-table { width: 300px; margin-left: auto; margin-top: 15px; border-collapse: collapse; }
        .totals-table td { padding: 6px; }
        .btn-print { background: #16a34a; color: white; border: none; padding: 10px 18px; font-size: 14px; cursor: pointer; border-radius: 4px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    </div>

    <div class="invoice-header">
        <div>
            <div class="company-title"><?= $emisor['nombre_comercial']; ?></div>
            <div><strong><?= $emisor['razon_social']; ?></strong></div>
            <div>RUC: <?= $emisor['ruc']; ?></div>
            <div>Matriz: <?= $emisor['direccion_matriz']; ?></div>
            <div>Teléfono: <?= $emisor['telefono']; ?> | Email: <?= $emisor['email']; ?></div>
        </div>
        <div class="invoice-box">
            <h3 style="margin: 0; color: #0f172a;">FACTURA</h3>
            <div style="font-size: 14px; font-weight: bold; margin-top: 5px;"><?= $factura['secuencial']; ?></div>
            <div style="font-size: 10px; margin-top: 5px;">Fecha: <?= $factura['fecha_emision']; ?></div>
        </div>
    </div>

    <div class="client-info">
        <strong>Razón Social:</strong> <?= $factura['razon_social']; ?><br>
        <strong>RUC / Cédula:</strong> <?= $factura['identificacion']; ?><br>
        <strong>Dirección:</strong> <?= $factura['direccion']; ?> | <strong>Teléfono:</strong> <?= $factura['telefono']; ?><br>
        <strong>Forma de Pago:</strong> <?= $factura['metodo_pago']; ?>
    </div>

    <table class="table-items">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Cant.</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $item): ?>
                <tr>
                    <td><?= $item['codigo_barras']; ?></td>
                    <td><?= $item['nombre']; ?></td>
                    <td><?= $item['cantidad']; ?></td>
                    <td>$<?= number_format($item['precio_unitario'], 2); ?></td>
                    <td>$<?= number_format($item['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal 0%:</td>
            <td style="text-align: right;">$0.00</td>
        </tr>
        <tr>
            <td>Subtotal 15%:</td>
            <td style="text-align: right;">$<?= number_format($factura['subtotal_sin_impuesto'], 2); ?></td>
        </tr>
        <tr>
            <td>IVA (15%):</td>
            <td style="text-align: right;">$<?= number_format($factura['monto_iva'], 2); ?></td>
        </tr>
        <tr style="font-size: 16px; font-weight: bold; background: #f1f5f9;">
            <td>VALOR TOTAL:</td>
            <td style="text-align: right; color: #2563eb;">$<?= number_format($factura['total'], 2); ?></td>
        </tr>
    </table>

</body>
</html>