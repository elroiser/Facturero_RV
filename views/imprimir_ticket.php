<?php
// views/imprimir_ticket.php

require_once __DIR__ . '/../config/Database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID de factura/comprobante no válido.");
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
        die("Comprobante no encontrado.");
    }

    // Consultar Detalle de la Factura
    $stmtDetalle = $db->prepare("
        SELECT df.*, p.nombre 
        FROM detalle_facturas df 
        INNER JOIN productos p ON df.producto_id = p.id 
        WHERE df.factura_id = :id
    ");
    $stmtDetalle->execute([':id' => $id]);
    $detalles = $stmtDetalle->fetchAll();

} catch (Exception $e) {
    die("Error al cargar el comprobante: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <title>Ticket #<?= htmlspecialchars($factura['secuencial']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            background-color: #fff;
            width: 80mm;
            padding: 5px;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .logo-ticket {
            max-width: 60mm;
            max-height: 40px;
            margin: 0 auto 5px auto;
            display: block;
            filter: grayscale(100%);
        }

        .header-info { margin-bottom: 8px; border-bottom: 1px dashed #000; padding-bottom: 6px; }
        .header-info h2 { font-size: 14px; margin-bottom: 2px; }
        .header-info p { font-size: 10px; line-height: 1.2; }

        .client-info { margin-bottom: 8px; border-bottom: 1px dashed #000; padding-bottom: 6px; font-size: 10px; }
        .client-info p { margin-bottom: 2px; }

        .ticket-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        .ticket-table th { border-bottom: 1px solid #000; padding: 3px 0; text-align: left; }
        .ticket-table td { padding: 3px 0; vertical-align: top; }

        .totals-section { border-top: 1px dashed #000; padding-top: 5px; margin-bottom: 10px; }
        .totals-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px; }
        .totals-row.grand-total { font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px; }

        .footer-msg { font-size: 9px; text-align: center; margin-top: 10px; border-top: 1px dashed #000; padding-top: 6px; }

        .btn-print {
            display: block;
            width: 100%;
            padding: 8px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 10px;
        }

        @media print {
            .btn-print { display: none; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket</button>

    <!-- Encabezado con Logo -->
    <div class="header-info text-center">
        <img src="../public/images/logo.png" alt="Logo" class="logo-ticket" onerror="this.style.display='none'">
        <h2>RV - LIMPIEZA</h2>
        <p>RUC: 0999999999001</p>
        <p>Matriz - Ecuador</p>
        <p>Teléfono: 0991234567</p>
        <p class="bold" style="margin-top: 4px; font-size: 11px;">TICKET N°: <?= htmlspecialchars($factura['secuencial']); ?></p>
        <p>Fecha: <?= date('Y-m-d H:i', strtotime($factura['fecha_emision'])); ?></p>
    </div>

    <!-- Datos del Cliente -->
    <div class="client-info">
        <p><strong>CLIENTE:</strong> <?= htmlspecialchars($factura['razon_social']); ?></p>
        <p><strong>RUC/CÉDULA:</strong> <?= htmlspecialchars($factura['identificacion']); ?></p>
        <p><strong>PAGO:</strong> <?= htmlspecialchars($factura['metodo_pago']); ?></p>
    </div>

    <!-- Tabla de Ítems -->
    <table class="ticket-table">
        <thead>
            <tr>
                <th>CANT/DESCRIPCIÓN</th>
                <th class="text-right">P.UNIT</th>
                <th class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $det): ?>
                <tr>
                    <td colspan="3" class="bold"><?= htmlspecialchars($det['nombre']); ?></td>
                </tr>
                <tr>
                    <td>&nbsp; <?= intval($det['cantidad']); ?> x $<?= number_format($det['precio_unitario'], 2); ?></td>
                    <td class="text-right">--</td>
                    <td class="text-right">$<?= number_format($det['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals-section">
        <div class="totals-row">
            <span>SUBTOTAL:</span>
            <span>$<?= number_format($factura['subtotal_sin_impuesto'], 2); ?></span>
        </div>
        <div class="totals-row">
            <span>IVA (15%):</span>
            <span>$<?= number_format($factura['monto_iva'], 2); ?></span>
        </div>
        <div class="totals-row grand-total">
            <span>TOTAL A PAGAR:</span>
            <span>$<?= number_format($factura['total'], 2); ?></span>
        </div>
    </div>

    <div class="footer-msg">
        <p>¡GRACIAS POR SU COMPRA!</p>
        <p>Conserve este comprobante para cualquier reclamo.</p>
        <p>www.rvlimpieza.com</p>
    </div>

</body>
</html>