<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    header("Location: pos.php");
    exit;
}
$pagina_actual = 'inventario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Inventario</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Gestión e Inventario de Productos</h2>

        <div class="grid-inventario">
            <!-- Formulario de Registro -->
            <div class="card">
                <h3>Registrar Nuevo Producto</h3>
                <form id="formProducto" onsubmit="guardarProducto(event)">
                    <div class="form-group">
                        <label>Código de Barras</label>
                        <input type="text" id="codigo" required placeholder="Ej. 78621006">
                    </div>
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <input type="text" id="nombre" required placeholder="Ej. DESINFECTANTE GLACIAL 4L">
                    </div>
                    <div class="form-group">
                        <label>Categoría</label>
                        <select id="categoria_id" required>
                            <option value="1">L+D Alimentos y Desinfección</option>
                            <option value="2">Detergentes y Desengrasantes Industriales</option>
                            <option value="3">Línea Sanitaria y Tratamiento de Baños</option>
                            <option value="4">Línea Agrícola y Agropecuaria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Precio de Venta ($)</label>
                        <input type="number" step="0.01" id="precio" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Stock Inicial</label>
                        <input type="number" id="stock" required placeholder="0">
                    </div>
                    <button type="submit" class="btn-submit" id="btnGuardar">Guardar Producto</button>
                </form>
            </div>

            <!-- Tabla de Inventario -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;">Catálogo de Productos</h3>
                    <input type="text" id="inputBuscarInv" placeholder="🔍 Buscar..." onkeyup="filtrarTablaInventario()" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; width: 220px;">
                </div>
                
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductos">
                            <tr><td colspan="7" style="text-align:center;">Cargando inventario...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Precio -->
    <div class="modal-overlay" id="modalEditarPrecio" style="display:none;">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Editar Precio de Venta</h3>
                <button onclick="cerrarModalPrecio()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">✕</button>
            </div>
            
            <input type="hidden" id="editProductoId">

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:4px;">Producto:</label>
                <div id="editNombreProducto" style="font-weight:bold; font-size:0.95rem; color:#0f172a;"></div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:4px;">Nuevo Precio ($):</label>
                <input type="number" step="0.01" min="0.01" id="editNuevoPrecio" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:1.1rem; font-weight:bold; outline:none;">
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="cerrarModalPrecio()">Cancelar</button>
                <button class="btn-confirm" id="btnGuardarPrecio" onclick="guardarNuevoPrecio()">💾 Guardar Cambio</button>
            </div>
        </div>
    </div>

    <script>
        let listaProductosInv = [];

        document.addEventListener("DOMContentLoaded", () => cargarInventario());

        async function cargarInventario() {
            try {
                const res = await fetch('../controllers/ProductoController.php');
                const json = await res.json();
                if (json.status === 'success') {
                    listaProductosInv = json.data;
                    renderTablaInventario(listaProductosInv);
                }
            } catch (e) { console.error("Error al cargar inventario:", e); }
        }

        function renderTablaInventario(productos) {
            const tbody = document.getElementById("tablaProductos");
            tbody.innerHTML = "";

            if (productos.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay productos guardados.</td></tr>`;
                return;
            }

            productos.forEach(p => {
                const precio = parseFloat(p.precio_venta || p.precio);
                const stock = parseInt(p.stock);
                const badgeClass = stock <= 5 ? 'stock-low' : 'stock-ok';
                const badgeText = stock <= 5 ? 'Stock Bajo' : 'Disponible';
                const codigo = p.codigo_barras || p.codigo || p.id;

                tbody.innerHTML += `
                    <tr>
                        <td><code>${codigo}</code></td>
                        <td><strong>${p.nombre}</strong></td>
                        <td>${p.categoria || 'General'}</td>
                        <td><strong style="color:#059669;">$${precio.toFixed(2)}</strong></td>
                        <td><strong>${stock} u.</strong></td>
                        <td><span class="badge-stock ${badgeClass}">${badgeText}</span></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-add-stock" onclick="ingresarStock(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', ${stock})">➕ Stock</button>
                            <button class="btn-edit-price" onclick="abrirModalPrecio(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', ${precio})">✏️ Precio</button>
                        </td>
                    </tr>`;
            });
        }

        function filtrarTablaInventario() {
            const query = document.getElementById("inputBuscarInv").value.toLowerCase().trim();
            renderTablaInventario(listaProductosInv.filter(p => 
                p.nombre.toLowerCase().includes(query) || 
                (p.codigo_barras && p.codigo_barras.toLowerCase().includes(query)) ||
                (p.codigo && p.codigo.toLowerCase().includes(query))
            ));
        }

        function abrirModalPrecio(id, nombre, precioActual) {
            document.getElementById("editProductoId").value = id;
            document.getElementById("editNombreProducto").innerText = nombre;
            document.getElementById("editNuevoPrecio").value = parseFloat(precioActual).toFixed(2);
            document.getElementById("modalEditarPrecio").style.display = "flex";
        }

        function cerrarModalPrecio() {
            document.getElementById("modalEditarPrecio").style.display = "none";
        }

        async function guardarNuevoPrecio() {
            const id = document.getElementById("editProductoId").value;
            const nuevoPrecio = parseFloat(document.getElementById("editNuevoPrecio").value);
            const btn = document.getElementById("btnGuardarPrecio");

            if (isNaN(nuevoPrecio) || nuevoPrecio <= 0) return alert("Ingresa un precio válido mayor a 0.");

            btn.disabled = true;
            btn.innerText = "Guardando...";

            try {
                const res = await fetch('../controllers/ProductoController.php?action=actualizar_precio', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, precio: nuevoPrecio })
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    alert("✅ " + json.message);
                    cerrarModalPrecio();
                    await cargarInventario();
                } else alert("❌ Error: " + json.message);
            } catch (err) { alert("Ocurrió un problema de conexión."); } 
            finally { btn.disabled = false; btn.innerText = "💾 Guardar Cambio"; }
        }

        async function ingresarStock(id, nombre, stockActual) {
            const cantidad = prompt(`📦 Ingresar nuevo lote para:\n"${nombre}"\n\nStock actual: ${stockActual} u.\n\n¿Cuántas unidades deseas agregar?`);
            if (cantidad === null) return;
            const unidades = parseInt(cantidad.trim());
            if (isNaN(unidades) || unidades <= 0) return alert("⚠️ Ingresa un número entero positivo.");

            try {
                const res = await fetch('../controllers/StockController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ producto_id: id, cantidad: unidades })
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    alert(`✅ ${json.message}`);
                    await cargarInventario();
                } else alert("❌ Error: " + (json.message || "No se pudo actualizar el stock."));
            } catch (e) { alert("Error al comunicar con el servidor."); }
        }

        async function guardarProducto(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardar");
            btn.disabled = true;
            btn.innerText = "Guardando...";

            try {
                const res = await fetch('../controllers/ProductoController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        codigo_barras: document.getElementById("codigo").value.trim(),
                        nombre: document.getElementById("nombre").value.trim(),
                        categoria_id: parseInt(document.getElementById("categoria_id").value),
                        precio: parseFloat(document.getElementById("precio").value),
                        stock: parseInt(document.getElementById("stock").value)
                    })
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    alert("✅ Producto registrado correctamente.");
                    document.getElementById("formProducto").reset();
                    await cargarInventario();
                } else alert("❌ Error: " + json.message);
            } catch (e) { alert("Error al comunicar con el servidor."); } 
            finally { btn.disabled = false; btn.innerText = "Guardar Producto"; }
        }
    </script>
</body>
</html>