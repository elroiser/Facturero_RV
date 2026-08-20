<?php
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
    <style>
        .grid-inventario { display: grid; grid-template-columns: 340px 1fr; gap: 20px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; }
        .form-group input, .form-group select {
            width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--accent); }
        .btn-submit {
            width: 100%; padding: 10px; background: var(--accent); color: white; border: none;
            border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 8px;
        }
        .btn-submit:hover { background: var(--accent-hover); }
        .badge-stock { padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .stock-ok { background: #dcfce7; color: #15803d; }
        .stock-low { background: #fee2e2; color: #b91c1c; }
        .btn-add-stock {
            background-color: #059669; color: white; border: none; padding: 4px 8px;
            border-radius: 4px; font-size: 0.75rem; font-weight: bold; cursor: pointer;
        }
        .btn-add-stock:hover { background-color: #047857; }
    </style>
</head>
<body>

    <!-- Incluir Navbar Modular -->
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

            <!-- Tabla de Inventario Reales -->
            <div class="card">
                <h3>Catálogo de Productos en MySQL</h3>
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
                                <th style="text-align: center;">Acción</th>
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarInventario();
        });

        // Obtener la lista de productos de la BD
        async function cargarInventario() {
            try {
                const response = await fetch('../controllers/ProductoController.php');
                const result = await response.json();

                if (result.status === 'success') {
                    const tbody = document.getElementById("tablaProductos");
                    tbody.innerHTML = "";

                    if (result.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No hay productos guardados.</td></tr>`;
                        return;
                    }

                    result.data.forEach(p => {
                        const precio = parseFloat(p.precio_venta || p.precio);
                        const stock = parseInt(p.stock);
                        const badgeClass = stock <= 5 ? 'stock-low' : 'stock-ok';
                        const badgeText = stock <= 5 ? 'Stock Bajo' : 'Disponible';

                        tbody.innerHTML += `
                            <tr>
                                <td><code>${p.codigo || p.id}</code></td>
                                <td><strong>${p.nombre}</strong></td>
                                <td>${p.categoria || 'General'}</td>
                                <td>$${precio.toFixed(2)}</td>
                                <td><strong>${stock} u.</strong></td>
                                <td><span class="badge-stock ${badgeClass}">${badgeText}</span></td>
                                <td style="text-align: center;">
                                    <button class="btn-add-stock" onclick="ingresarStock(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', ${stock})">
                                        ➕ Stock
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } catch (error) {
                console.error("Error al cargar inventario:", error);
            }
        }

        // Ingresar/sumar nuevas unidades a un producto existente
        async function ingresarStock(id, nombre, stockActual) {
            const cantidadIngresar = prompt(`📦 Ingresar nuevo lote para:\n"${nombre}"\n\nStock actual: ${stockActual} u.\n\n¿Cuántas unidades nuevas deseas agregar al inventario?`);

            if (cantidadIngresar === null) return; // Si presiona Cancelar

            const unidades = parseInt(cantidadIngresar.trim());

            if (isNaN(unidades) || unidades <= 0) {
                alert("⚠️ Por favor, ingresa un número entero positivo.");
                return;
            }

            try {
                const response = await fetch('../controllers/StockController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        producto_id: id,
                        cantidad: unidades
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert(`✅ ${result.message}`);
                    await cargarInventario(); // Recargar lista para ver el stock actualizado
                } else {
                    alert("❌ Error: " + (result.message || "No se pudo actualizar el stock."));
                }
            } catch (error) {
                console.error("Error al enviar solicitud:", error);
                alert("Ocurrió un problema al comunicar con el servidor.");
            }
        }

        // Guardar nuevo producto en la BD vía POST
        async function guardarProducto(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardar");

            const payload = {
                codigo_barras: document.getElementById("codigo").value.trim(),
                nombre: document.getElementById("nombre").value.trim(),
                categoria_id: parseInt(document.getElementById("categoria_id").value),
                precio: parseFloat(document.getElementById("precio").value),
                stock: parseInt(document.getElementById("stock").value)
            };

            btn.disabled = true;
            btn.innerText = "Guardando...";

            try {
                const response = await fetch('../controllers/ProductoController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert("✅ Producto registrado correctamente en la base de datos.");
                    document.getElementById("formProducto").reset();
                    await cargarInventario(); // Recargar tabla
                } else {
                    alert("❌ Error: " + result.message);
                }
            } catch (error) {
                console.error("Error al guardar producto:", error);
                alert("Ocurrió un problema al comunicar con el servidor.");
            } finally {
                btn.disabled = false;
                btn.innerText = "Guardar Producto";
            }
        }
    </script>
</body>
</html>