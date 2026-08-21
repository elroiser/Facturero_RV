<?php
require_once __DIR__ . '/../config/Database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID de factura/comprobante no válido.");

try {
    $db = Database::getConnection();

    // Consultar Encabezado
    $stmtFactura = $db->prepare("
        SELECT f.*, c.razon_social, c.identificacion, c.direccion, c.telefono, c.email 
        FROM facturas f INNER JOIN clientes c ON f.cliente_id = c.id 
        WHERE f.id = :id LIMIT 1
    ");
    $stmtFactura->execute([':id' => $id]);
    $factura = $stmtFactura->fetch();

    if (!$factura) die("Comprobante no encontrado.");

    // Consultar Detalle
    $stmtDetalle = $db->prepare("
        SELECT df.*, p.nombre 
        FROM detalle_facturas df INNER JOIN productos p ON df.producto_id = p.id 
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
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body class="ticket-body" onload="window.print()">

    <button class="btn-ticket-print" onclick="window.print()">🖨️ Imprimir Ticket</button>

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