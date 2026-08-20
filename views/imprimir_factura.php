<?php
// views/imprimir_factura.php

require_once __DIR__ . '/../config/Database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID de factura no válido.");
}

try {
    $db = Database::getConnection();

    // Consultar Encabezado de la Factura y datos del Cliente
    $stmtFactura = $db->prepare("
        SELECT f.*, c.razon_social, c.identificacion, c.direccion, c.telefono, c.email 
        FROM facturas f 
        INNER JOIN clientes c ON f.cliente_id = c.id 
        WHERE f.id = :id 
        LIMIT 1
    ");
    $stmtFactura->execute([':id' => $id]);
    $factura = $stmtFactura->fetch();

    if (!$factura) {
        die("Factura no encontrada.");
    }

    // Consultar Detalle de la Factura (Sin seleccionar p.codigo)
    $stmtDetalle = $db->prepare("
        SELECT df.*, p.nombre 
        FROM detalle_facturas df 
        INNER JOIN productos p ON df.producto_id = p.id 
        WHERE df.factura_id = :id
    ");
    $stmtDetalle->execute([':id' => $id]);
    $detalles = $stmtDetalle->fetchAll();

} catch (Exception $e) {
    die("Error al cargar la factura: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($factura['secuencial']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-container { display: flex; align-items: center; gap: 15px; }
        .logo-img { max-height: 70px; width: auto; object-fit: contain; }
        .company-details h2 { margin: 0 0 5px 0; color: #0f172a; font-size: 18px; }
        .company-details p { margin: 2px 0; font-size: 11px; color: #64748b; }
        .invoice-box { text-align: right; border: 1px solid #cbd5e1; padding: 12px 20px; border-radius: 6px; background-color: #f8fafc; }
        .invoice-box h3 { margin: 0 0 5px 0; color: #0f172a; font-size: 16px; }
        .client-box { background: #f1f5f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .table-invoice { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-invoice th { background-color: #0f172a; color: white; padding: 8px; text-align: left; }
        .table-invoice td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
        .totals-container { width: 300px; float: right; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals-row.final { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; padding-top: 8px; }
        .btn-print { background: #16a34a; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; float: right; margin-bottom: 15px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    <div style="clear: both;"></div>

    <!-- Encabezado con Logo -->
    <div class="header">
        <div class="logo-container">
            <!-- Logo de la empresa -->
            <img src="../public/images/logo.png" alt="Logo RV Limpieza" class="logo-img" onerror="this.style.display='none'">
            <div class="company-details">
                <h2>RV - Limpieza</h2>
                <p><strong>RUC:</strong> 0999999999001</p>
                <p><strong>Dirección:</strong> Matriz - Ecuador</p>
                <p><strong>Teléfono:</strong> 0991234567 | <strong>Email:</strong> facturacion@rvlimpieza.com</p>
            </div>
        </div>

        <div class="invoice-box">
            <h3>FACTURA</h3>
            <p><strong>N°:</strong> <?= htmlspecialchars($factura['secuencial']); ?></p>
            <p><strong>Fecha:</strong> <?= date('Y-m-d H:i', strtotime($factura['fecha_emision'])); ?></p>
        </div>
    </div>

    <!-- Datos del Cliente -->
    <div class="client-box">
        <p style="margin:2px 0;"><strong>Razón Social:</strong> <?= htmlspecialchars($factura['razon_social']); ?></p>
        <p style="margin:2px 0;"><strong>RUC / Cédula:</strong> <?= htmlspecialchars($factura['identificacion']); ?></p>
        <p style="margin:2px 0;"><strong>Dirección:</strong> <?= htmlspecialchars($factura['direccion'] ?: 'N/A'); ?> | <strong>Teléfono:</strong> <?= htmlspecialchars($factura['telefono'] ?: 'N/A'); ?></p>
        <p style="margin:2px 0;"><strong>Forma de Pago:</strong> <?= htmlspecialchars($factura['metodo_pago']); ?></p>
    </div>

    <!-- Tabla de Detalles -->
    <table class="table-invoice">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
    <?php foreach ($detalles as $det): ?>
        <tr>
            <td>PROD-<?= str_pad($det['producto_id'], 4, "0", STR_PAD_LEFT); ?></td>
            <td><?= htmlspecialchars($det['nombre']); ?></td>
            <td style="text-align: center;"><?= intval($det['cantidad']); ?></td>
            <td style="text-align: right;">$<?= number_format($det['precio_unitario'], 2); ?></td>
            <td style="text-align: right;">$<?= number_format($det['subtotal'], 2); ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>
    </table>

    <!-- Totales con IVA 15% -->
    <div class="totals-container">
        <div class="totals-row">
            <span>Subtotal 0%:</span>
            <span>$0.00</span>
        </div>
        <div class="totals-row">
            <span>Subtotal 15%:</span>
            <span>$<?= number_format($factura['subtotal_sin_impuesto'], 2); ?></span>
        </div>
        <div class="totals-row">
            <span>IVA (15%):</span>
            <span>$<?= number_format($factura['monto_iva'], 2); ?></span>
        </div>
        <div class="totals-row final">
            <span>VALOR TOTAL:</span>
            <span>$<?= number_format($factura['total'], 2); ?></span>
        </div>
    </div>

</body>
</html>