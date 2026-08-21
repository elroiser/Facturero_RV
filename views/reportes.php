<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    header("Location: pos.php");
    exit;
}
$pagina_actual = 'reportes';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Cierre de Caja y Reportes</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">

    <!-- Chart.js para Gráficos Estadísticos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS para Exportación a Excel (.xlsx) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>

<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Resumen Diario y Cierre de Caja</h2>

        <div id="panelCajaAbierta">
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
                        <tr>
                            <td colspan="4" style="text-align:center;">Cargando resumen...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button class="btn-close-box" id="btnCerrarCaja" onclick="cerrarCaja()">🔒 Ejecutar Cierre de Caja</button>
        </div>

        <div id="panelCajaCerrada" style="display:none; text-align:center; padding: 40px;" class="card">
            <h2 style="color: #dc2626;">🔒 La Caja se encuentra CERRADA</h2>
            <p style="margin: 15px 0; color: #64748b;">Para volver a realizar ventas en el sistema POS, debes iniciar un nuevo turno de caja.</p>
            <button class="btn-open-box" onclick="reabrirCaja()">🔓 Abrir Nueva Caja / Turno</button>
        </div>

        <!-- Barra de Exportación -->
        <div class="export-bar" style="margin-top: 25px;">
            <button class="btn-excel" onclick="exportarInventarioExcel()">📊 Exportar Inventario (Excel)</button>
            <button class="btn-excel" style="background:#0284c7;" onclick="exportarVentasExcel()">📄 Exportar Ventas del Día (Excel)</button>
        </div>

        <!-- Grid de Gráficos -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>🏆 Top 5 Productos Más Vendidos del Mes</h3>
                <canvas id="chartProductos"></canvas>
            </div>
            <div class="chart-card">
                <h3>⏰ Horas Pico de Mayor Flujo de Ventas</h3>
                <canvas id="chartHoras"></canvas>
            </div>
        </div>
    </div>

    <script>
        let totalArqueoMonto = 0;

        document.addEventListener("DOMContentLoaded", () => {
            cargarResumenCaja();
            cargarGraficoProductos();
            cargarGraficoHoras();
        });

        async function cargarResumenCaja() {
            try {
                const res = await fetch('../controllers/CajaController.php');
                const json = await res.json();

                if (json.status === 'success') {
                    const data = json.data;

                    if (data.estado === 'CERRADA') {
                        document.getElementById("panelCajaAbierta").style.display = "none";
                        document.getElementById("panelCajaCerrada").style.display = "block";
                        return;
                    }

                    document.getElementById("panelCajaAbierta").style.display = "block";
                    document.getElementById("panelCajaCerrada").style.display = "none";

                    totalArqueoMonto = data.total_arqueo;
                    document.getElementById("montoInicial").innerText = `$${data.monto_inicial.toFixed(2)}`;
                    document.getElementById("ventasEfectivo").innerText = `$${data.ventas_efectivo.toFixed(2)}`;
                    document.getElementById("ventasTransferencia").innerText = `$${data.ventas_transferencia.toFixed(2)}`;
                    document.getElementById("totalArqueo").innerText = `$${data.total_arqueo.toFixed(2)}`;

                    const tbody = document.getElementById("tablaVentas");
                    tbody.innerHTML = "";

                    if (data.ventas.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#64748b;">No hay ventas registradas hoy.</td></tr>`;
                        return;
                    }

                    data.ventas.forEach(v => {
                        tbody.innerHTML += `
                            <tr>
                                <td><strong>#${v.id}</strong></td>
                                <td>${v.hora}</td>
                                <td><span style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-weight:600; font-size:0.8rem;">${v.metodo_pago}</span></td>
                                <td style="text-align: right;"><strong>$${parseFloat(v.total).toFixed(2)}</strong></td>
                            </tr>`;
                    });
                }
            } catch (e) {
                console.error("Error al cargar datos de caja:", e);
            }
        }

        async function cerrarCaja() {
            if (!confirm(`¿Confirmas el cierre definitivo de caja con un monto total de $${totalArqueoMonto.toFixed(2)}?`)) return;

            try {
                const res = await fetch('../controllers/CajaController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'cerrar', monto_final: totalArqueoMonto })
                });
                const json = await res.json();

                if (res.ok && json.status === 'success') {
                    alert("✅ " + json.message);
                    window.print();
                    await cargarResumenCaja();
                } else alert("❌ Error: " + json.message);
            } catch (e) {
                alert("Error de conexión al cerrar caja.");
            }
        }

        async function reabrirCaja() {
            const montoInicial = prompt("Ingresa el fondo de caja inicial para la nueva jornada ($):", "50.00");
            if (montoInicial === null) return;

            try {
                const res = await fetch('../controllers/CajaController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'reabrir', monto_inicial: parseFloat(montoInicial) || 0 })
                });
                const json = await res.json();

                if (res.ok && json.status === 'success') {
                    alert("✅ " + json.message);
                    await cargarResumenCaja();
                }
            } catch (e) {
                alert("Error al reabrir caja.");
            }
        }

        async function cargarGraficoProductos() {
            try {
                const res = await fetch('../controllers/ReportesAvanzadosController.php?action=top_productos');
                const json = await res.json();
                if (json.status === 'success') {
                    const labels = json.data.map(item => item.nombre);
                    const valores = json.data.map(item => item.total_vendido);

                    new Chart(document.getElementById('chartProductos'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Unidades Vendidas',
                                data: valores,
                                backgroundColor: '#3b82f6'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            } catch (e) { console.error(e); }
        }

        async function cargarGraficoHoras() {
            try {
                const res = await fetch('../controllers/ReportesAvanzadosController.php?action=horas_pico');
                const json = await res.json();
                if (json.status === 'success') {
                    const labels = json.data.map(item => `${item.hora}:00 hs`);
                    const valores = json.data.map(item => item.cantidad_ventas);

                    new Chart(document.getElementById('chartHoras'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Transacciones',
                                data: valores,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: { responsive: true }
                    });
                }
            } catch (e) { console.error(e); }
        }

        async function exportarInventarioExcel() {
            try {
                const res = await fetch('../controllers/ReportesAvanzadosController.php?action=exportar_inventario');
                const json = await res.json();
                if (json.status === 'success') {
                    const worksheet = XLSX.utils.json_to_sheet(json.data);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "Inventario");
                    XLSX.writeFile(workbook, "Reporte_Inventario_RV_Limpieza.xlsx");
                }
            } catch (e) {
                alert("Error al generar reporte de inventario.");
            }
        }

        function exportarVentasExcel() {
            const tabla = document.getElementById("tablaVentas");
            const workbook = XLSX.utils.table_to_book(tabla);
            XLSX.writeFile(workbook, "Cierre_Caja_Transacciones.xlsx");
        }
    </script>
</body>

</html>