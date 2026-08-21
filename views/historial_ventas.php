<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    header("Location: pos.php");
    exit;
}
$pagina_actual = 'historial_ventas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Historial de Ventas</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Historial de Ventas y Facturas</h2>

        <!-- Resumen -->
        <div class="summary-card">
            <div>
                <h3>Total Ventas en Período</h3>
                <span style="font-size: 0.85rem; color: #94a3b8;">Suma acumulada del rango seleccionado</span>
            </div>
            <div class="total-amount" id="lblTotalAcumulado">$0.00</div>
        </div>

        <!-- Filtros -->
        <div class="filter-card">
            <div class="filter-group">
                <label>Desde:</label>
                <input type="date" id="fechaInicio" value="<?= date('Y-m-01'); ?>">
            </div>
            <div class="filter-group">
                <label>Hasta:</label>
                <input type="date" id="fechaFin" value="<?= date('Y-m-d'); ?>">
            </div>
            <div class="filter-group" style="flex-grow: 1;">
                <label>Buscar (Cliente / RUC / N° Factura):</label>
                <input type="text" id="inputBusqueda" placeholder="Ej. Comercializadora o 099123...">
            </div>
            <button class="btn-filter" onclick="consultarVentas()">🔎 Filtrar</button>
        </div>

        <!-- Tabla -->
        <div class="card">
            <h3 style="margin-bottom: 15px;">Facturas Emitidas</h3>
            <div style="max-height: 500px; overflow-y: auto;">
                <table class="cart-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="padding:10px;">N° Factura</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Identificación</th>
                            <th>Pago</th>
                            <th>Total</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaVentas">
                        <tr><td colspan="7" style="text-align:center;">Cargando historial...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal-overlay" id="modalDetalle" style="display:none;">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="lblTituloDetalle">Detalle de Factura</h3>
                <button onclick="cerrarModalDetalle()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">✕</button>
            </div>
            <div style="margin: 15px 0;">
                <table class="cart-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align:center;">Cant.</th>
                            <th style="text-align:right;">P.Unit</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDetalleItems">
                        <tr><td colspan="4" style="text-align:center;">Cargando productos...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="cerrarModalDetalle()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => consultarVentas());

        async function consultarVentas() {
            const inicio = document.getElementById("fechaInicio").value;
            const fin = document.getElementById("fechaFin").value;
            const busqueda = encodeURIComponent(document.getElementById("inputBusqueda").value.trim());
            const tbody = document.getElementById("tablaVentas");
            
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px;">Cargando historial...</td></tr>`;

            try {
                const res = await fetch(`../controllers/ReporteVentasController.php?action=listar&fecha_inicio=${inicio}&fecha_fin=${fin}&busqueda=${busqueda}`);
                const json = await res.json();

                if (json.status === 'success') {
                    tbody.innerHTML = "";
                    document.getElementById("lblTotalAcumulado").innerText = `$${json.total_acumulado}`;

                    if (json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px;">No se encontraron ventas para este filtro.</td></tr>`;
                        return;
                    }

                    json.data.forEach(f => {
                        tbody.innerHTML += `
                            <tr>
                                <td style="padding:10px;"><code>${f.secuencial}</code></td>
                                <td>${f.fecha_emision}</td>
                                <td><strong>${f.razon_social}</strong></td>
                                <td>${f.identificacion}</td>
                                <td><span style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:0.8rem; font-weight:bold;">${f.metodo_pago}</span></td>
                                <td><strong>$${parseFloat(f.total).toFixed(2)}</strong></td>
                                <td style="text-align: center;">
                                    <button class="btn-action btn-view" onclick="verDetalle(${f.id}, '${f.secuencial}')" title="Ver Detalle">👁️</button>
                                    <a class="btn-action btn-ticket" href="imprimir_ticket.php?id=${f.id}" target="_blank" title="Reimprimir Ticket">🎟️</a>
                                    <a class="btn-action btn-pdf" href="imprimir_factura.php?id=${f.id}" target="_blank" title="Reimprimir A4">📄</a>
                                </td>
                            </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red; padding: 20px;">Error: ${json.message}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red; padding: 20px;">Error al conectar con el servidor.</td></tr>`;
            }
        }

        async function verDetalle(facturaId, numSecuencial) {
            document.getElementById("lblTituloDetalle").innerText = `Detalle de Factura: ${numSecuencial}`;
            const tbody = document.getElementById("tablaDetalleItems");
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">Cargando productos...</td></tr>`;
            document.getElementById("modalDetalle").style.display = "flex";

            try {
                const res = await fetch(`../controllers/ReporteVentasController.php?action=detalle&id=${facturaId}`);
                const json = await res.json();

                if (json.status === 'success') {
                    tbody.innerHTML = "";
                    json.data.forEach(item => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${item.nombre}</td>
                                <td style="text-align:center;">${item.cantidad}</td>
                                <td style="text-align:right;">$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                <td style="text-align:right;"><strong>$${parseFloat(item.subtotal).toFixed(2)}</strong></td>
                            </tr>`;
                    });
                }
            } catch (err) { console.error("Error al cargar detalle:", err); }
        }

        function cerrarModalDetalle() {
            document.getElementById("modalDetalle").style.display = "none";
        }
    </script>
</body>
</html>