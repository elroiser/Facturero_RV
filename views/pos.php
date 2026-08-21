<?php
$pagina_actual = 'pos';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Punto de Venta</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        /* Estilos para el Modal de Selección de Cliente */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-card {
            background: #fff;
            border-radius: 8px;
            width: 450px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-confirm {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;

        }

        .filtros-presentacion {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .btn-filtro-cat {
            background: #e2e8f0;
            color: #334155;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-filtro-cat:hover {
            background: #cbd5e1;
        }

        .btn-filtro-cat.active {
            background: #2563eb;
            color: white;
        }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <div class="filtros-presentacion">
        <button class="btn-filtro-cat active" onclick="filtrarPorPresentacion('TODOS', this)">🧪 Todos</button>
        <button class="btn-filtro-cat" onclick="filtrarPorPresentacion('Galón 4L', this)">🧴 Galones (4L)</button>
        <button class="btn-filtro-cat" onclick="filtrarPorPresentacion('1 Litro', this)">🍼 Litros (1L)</button>
        <button class="btn-filtro-cat" onclick="filtrarPorPresentacion('Caneca 20L', this)">🛢️ Canecas (20L)</button>
    </div>

    <main class="pos-container">

        <!-- Izquierda: Catálogo -->
        <div class="card">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Buscar por nombre o código (ej. CHEMLOK)..." onkeyup="filtrarProductos()">
            </div>
            <div class="products-grid" id="productsGrid"></div>
        </div>



        <!-- Derecha: Carrito -->
        <div class="card">
            <h3>Carrito de Compra</h3>
            <div style="max-height: 300px; overflow-y: auto;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody"></tbody>
                </table>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Método de Pago:</span>
                    <select id="metodoPago" style="padding: 4px; border-radius: 4px;">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TRANSFERENCIA">Transferencia</option>
                    </select>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span id="totalDisplay">$0.00</span>
                </div>
            </div>

            <button class="btn-checkout" id="btnProcesar" onclick="abrirModalCliente()" disabled>
                Procesar Venta
            </button>
        </div>
    </main>


    <!-- Modal de Selección de Cliente y Tipo de Comprobante -->
    <div class="modal-overlay" id="modalCliente">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Seleccionar Cliente para Facturación</h3>
                <button onclick="cerrarModalCliente()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">✕</button>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Selecciona un Cliente:</label>
                <select id="selectCliente" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem; outline:none;">
                    <option value="9999999999999">CONSUMIDOR FINAL (9999999999999)</option>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Tipo de Comprobante:</label>
                <select id="tipoComprobante" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem; outline:none;">
                    <option value="ticket">🎟️ Ticket Térmico (80mm)</option>
                    <option value="factura">📄 Factura Grande (A4 / PDF)</option>
                </select>
            </div>

            <div style="font-size: 0.8rem; color: #64748b;">
                * Si el cliente no aparece en la lista, regístralo previamente en la sección de <strong>Clientes</strong>.
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="cerrarModalCliente()">Cancelar</button>
                <button class="btn-confirm" id="btnConfirmarVenta" onclick="confirmarVentaConCliente()">Emitir Comprobante</button>
            </div>
        </div>
    </div>

    <script src="../public/js/pos.js"></script>
</body>

</html>