<?php
$pagina_actual = 'reportes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemlook POS - Cierre de Caja</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        .stat-title { font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 1.5rem; font-weight: 800; margin-top: 6px; }

        .btn-close-box {
            background: #dc2626; color: white; border: none; padding: 14px 24px;
            border-radius: 6px; font-weight: bold; cursor: pointer; float: right; font-size: 1rem;
        }
        .btn-close-box:hover { background: #b91c1c; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Resumen Diario y Cierre de Caja #1</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Fondo Inicial</div>
                <div class="stat-value" id="montoInicial">$0.00</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Ventas Efectivo</div>
                <div class="stat-value" style="color: #16a34a;" id="ventasEfectivo">$0.00</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Ventas Transferencia</div>
                <div class="stat-value" style="color: #2563eb;" id="ventasTransferencia">$0.00</div>
            </div>
            <div class="stat-card" style="background: #f0f9ff; border-color: #bae6fd;">
                <div class="stat-title" style="color: #0369a1;">Total Esperado</div>
                <div class="stat-value" style="color: #0369a1;" id="totalArqueo">$0.00</div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Transacciones Registradas Hoy</h3>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th># Venta</th>
                        <th>Hora</th>
                        <th>Método de Pago</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody id="tablaVentas">
                    <tr><td colspan="4" style="text-align:center;">Cargando ventas...</td></tr>
                </tbody>
            </table>
        </div>

        <button class="btn-close-box" onclick="cerrarCaja()">Cerrar Caja</button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarResumenCaja();
        });

        async function cargarResumenCaja() {
            try {
                const response = await fetch('../controllers/CajaController.php');
                const result = await response.json();

                if (result.status === 'success') {
                    const data = result.data;

                    // Actualizar métricas
                    document.getElementById("montoInicial").innerText = `$${data.monto_inicial.toFixed(2)}`;
                    document.getElementById("ventasEfectivo").innerText = `$${data.ventas_efectivo.toFixed(2)}`;
                    document.getElementById("ventasTransferencia").innerText = `$${data.ventas_transferencia.toFixed(2)}`;
                    document.getElementById("totalArqueo").innerText = `$${data.total_arqueo.toFixed(2)}`;

                    // Dibujar la tabla de ventas
                    const tbody = document.getElementById("tablaVentas");
                    tbody.innerHTML = "";

                    if (data.ventas.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#64748b;">No hay ventas registradas en esta caja aún.</td></tr>`;
                        return;
                    }

                    data.ventas.forEach(v => {
                        const total = parseFloat(v.total);
                        tbody.innerHTML += `
                            <tr>
                                <td><strong>#${v.id}</strong></td>
                                <td>${v.hora}</td>
                                <td><span style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-weight:600; font-size:0.8rem;">${v.metodo_pago}</span></td>
                                <td style="text-align: right;"><strong>$${total.toFixed(2)}</strong></td>
                            </tr>
                        `;
                    });
                } else {
                    alert("Error al obtener reporte: " + result.message);
                }
            } catch (error) {
                console.error("Error al conectar con la API de caja:", error);
            }
        }

        function cerrarCaja() {
            const total = document.getElementById("totalArqueo").innerText;
            if (confirm(`¿Deseas realizar el cierre de caja con un total acumulado de ${total}?`)) {
                alert("Caja cerrada exitosamente.");
            }
        }
    </script>
</body>
</html>