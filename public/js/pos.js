// public/js/pos.js

let productosBD = [];
let carrito = [];

// 1. Cargar productos desde el backend
async function cargarProductosDesdeBD() {
    try {
        const response = await fetch('../controllers/ProductoController.php');
        const result = await response.json();

        if (result.status === 'success') {
            productosBD = result.data.map(p => ({
                id: parseInt(p.id),
                nombre: p.nombre,
                precio: parseFloat(p.precio_venta || p.precio),
                stock: parseInt(p.stock),
                codigo: p.codigo || ''
            }));

            renderProductos(productosBD);
        } else {
            console.error("Error al obtener productos:", result.message);
        }
    } catch (error) {
        console.error("Error al conectar con el servidor:", error);
    }
}

// 2. Renderizar las tarjetas de productos
function renderProductos(lista) {
    const grid = document.getElementById("productsGrid");
    if (!grid) return;

    grid.innerHTML = "";

    if (lista.length === 0) {
        grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 20px;">No hay productos disponibles.</div>`;
        return;
    }

    lista.forEach(p => {
        grid.innerHTML += `
            <div class="product-card" onclick="agregarAlCarrito(${p.id})">
                <div class="product-title">${p.nombre}</div>
                <div class="product-price">$${p.precio.toFixed(2)}</div>
                <div class="product-stock">Stock: ${p.stock} u.</div>
            </div>
        `;
    });
}

// 3. Filtrar en la barra de búsqueda
function filtrarProductos() {
    const input = document.getElementById("searchInput");
    if (!input) return;

    const query = input.value.toLowerCase().trim();
    const filtrados = productosBD.filter(p => 
        p.nombre.toLowerCase().includes(query) || p.codigo.toLowerCase().includes(query)
    );

    renderProductos(filtrados);
}

// 4. Agregar producto al Carrito (CORREGIDO)
function agregarAlCarrito(productoId) {
    // Buscar el producto en la lista global
    const producto = productosBD.find(p => p.id === productoId);

    if (!producto) {
        console.error("Producto no encontrado con ID:", productoId);
        return;
    }

    if (producto.stock <= 0) {
        alert(`Sin stock disponible para: ${producto.nombre}`);
        return;
    }

    // Verificar si ya está en el carrito
    const itemEnCarrito = carrito.find(item => item.producto_id === productoId);

    if (itemEnCarrito) {
        if (itemEnCarrito.cantidad + 1 > producto.stock) {
            alert(`Stock insuficiente. Solo quedan ${producto.stock} unidades de ${producto.nombre}.`);
            return;
        }
        itemEnCarrito.cantidad += 1;
    } else {
        carrito.push({
            producto_id: producto.id,
            nombre: producto.nombre,
            precio_unitario: producto.precio,
            cantidad: 1
        });
    }

    actualizarCarritoUI();
}

// 5. Actualizar la interfaz del carrito y total
function actualizarCarritoUI() {
    const cartBody = document.getElementById("cartBody");
    const totalDisplay = document.getElementById("totalDisplay");
    const btnProcesar = document.getElementById("btnProcesar");

    if (!cartBody || !totalDisplay || !btnProcesar) return;

    cartBody.innerHTML = "";
    let totalAcumulado = 0;

    if (carrito.length === 0) {
        cartBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 15px;">Carrito vacío</td></tr>`;
        totalDisplay.innerText = "$0.00";
        btnProcesar.disabled = true;
        return;
    }

    carrito.forEach((item, index) => {
        const subtotal = item.cantidad * item.precio_unitario;
        totalAcumulado += subtotal;

        cartBody.innerHTML += `
            <tr>
                <td><strong>${item.nombre}</strong></td>
                <td>
                    <button class="btn-qty" onclick="cambiarCantidad(${index}, -1)">-</button>
                    <span>${item.cantidad}</span>
                    <button class="btn-qty" onclick="cambiarCantidad(${index}, 1)">+</button>
                </td>
                <td>$${item.precio_unitario.toFixed(2)}</td>
                <td><strong>$${subtotal.toFixed(2)}</strong></td>
                <td><button class="btn-remove" onclick="eliminarDelCarrito(${index})">✕</button></td>
            </tr>
        `;
    });

    totalDisplay.innerText = `$${totalAcumulado.toFixed(2)}`;
    btnProcesar.disabled = false;
}

// 6. Cambiar cantidad desde los botones +/-
function cambiarCantidad(index, cambio) {
    if (!carrito[index]) return;

    const item = carrito[index];
    const productoOriginal = productosBD.find(p => p.id === item.producto_id);

    const nuevaCantidad = item.cantidad + cambio;

    if (nuevaCantidad <= 0) {
        eliminarDelCarrito(index);
        return;
    }

    if (productoOriginal && nuevaCantidad > productoOriginal.stock) {
        alert(`Stock insuficiente. Máximo disponible: ${productoOriginal.stock}`);
        return;
    }

    item.cantidad = nuevaCantidad;
    actualizarCarritoUI();
}

// 7. Eliminar ítem del carrito
function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarCarritoUI();
}

// 8. Abrir modal para elegir cliente (Validando estado de caja primero)
async function abrirModalCliente() {
    if (carrito.length === 0) {
        alert("El carrito está vacío. Agrega al menos un producto.");
        return;
    }

    // Validar si la caja está ABIERTA
    try {
        const resCaja = await fetch('../controllers/CajaController.php');
        const dataCaja = await resCaja.json();

        if (dataCaja.status === 'success' && dataCaja.data.estado === 'CERRADA') {
            alert("⚠️ NO SE PUEDE FACTURAR: La caja se encuentra CERRADA.\n\nPor favor, ve al módulo 'Cierre de Caja' e ingresa el fondo inicial para abrir el turno.");
            window.location.href = "reportes.php";
            return;
        }
    } catch (e) {
        console.error("Error al verificar estado de la caja:", e);
    }

    // Cargar la lista de clientes en el selector
    const modal = document.getElementById("modalCliente");
    const select = document.getElementById("selectCliente");

    try {
        const response = await fetch('../controllers/ClienteController.php');
        const result = await response.json();

        if (result.status === 'success') {
            select.innerHTML = '<option value="9999999999999">CONSUMIDOR FINAL (9999999999999)</option>';
            
            result.data.forEach(c => {
                if (c.identificacion !== '9999999999999') {
                    select.innerHTML += `
                        <option value="${c.identificacion}">
                            ${c.razon_social} (${c.identificacion})
                        </option>
                    `;
                }
            });
        }
    } catch (e) {
        console.error("Error al cargar la lista de clientes:", e);
    }

    if (modal) modal.style.display = "flex";
}

// 9. Cerrar el modal de selección de cliente
function cerrarModalCliente() {
    const modal = document.getElementById("modalCliente");
    if (modal) modal.style.display = "none";
}

// 10. Confirmar Venta y Generar Factura
// public/js/pos.js

async function confirmarVentaConCliente() {
    const select = document.getElementById("selectCliente");
    const metodoPago = document.getElementById("metodoPago") ? document.getElementById("metodoPago").value : "EFECTIVO";
    const tipoComprobante = document.getElementById("tipoComprobante") ? document.getElementById("tipoComprobante").value : "ticket";
    const btnConfirmar = document.getElementById("btnConfirmarVenta");

    if (!select) return;

    const clienteIdentificacion = select.value;

    btnConfirmar.disabled = true;
    btnConfirmar.innerText = "Emitiendo...";

    const payload = {
        caja_id: 1,
        metodo_pago: metodoPago,
        cliente_identificacion: clienteIdentificacion,
        items: carrito.map(item => ({
            producto_id: item.producto_id,
            cantidad: item.cantidad,
            precio_unitario: item.precio_unitario
        }))
    };

    try {
        const response = await fetch('../controllers/VentaController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            alert(`✅ Venta registrada con éxito. Comprobante N° ${result.secuencial}`);
            
            // Abrir según la elección seleccionada
            if (tipoComprobante === 'ticket') {
                window.open(`imprimir_ticket.php?id=${result.factura_id}`, '_blank');
            } else {
                window.open(`imprimir_factura.php?id=${result.factura_id}`, '_blank');
            }

            // Limpiar el carrito y cerrar modal
            carrito = [];
            actualizarCarritoUI();
            cerrarModalCliente();
            await cargarProductosDesdeBD();
        } else {
            alert("❌ Error al procesar venta: " + (result.message || "Error desconocido"));
        }
    } catch (error) {
        console.error("Error en la solicitud de venta:", error);
        alert("Error al conectar con el servidor.");
    } finally {
        btnConfirmar.disabled = false;
        btnConfirmar.innerText = "Emitir Factura";
    }
}
// Inicializar al cargar el DOM
document.addEventListener("DOMContentLoaded", () => {
    cargarProductosDesdeBD();
});