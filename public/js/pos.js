// public/js/pos.js

let productosDB = [];
let carrito = [];
const cajaIdActual = 1;

// Inicializar al cargar el DOM
document.addEventListener("DOMContentLoaded", () => {
    cargarProductosDesdeBD();
});

// Cargar catálogo desde ProductoController.php
async function cargarProductosDesdeBD() {
    try {
        const response = await fetch('../controllers/ProductoController.php');
        const result = await response.json();

        if (result.status === 'success') {
            productosDB = result.data.map(p => ({
                ...p,
                precio: parseFloat(p.precio),
                stock: parseInt(p.stock)
            }));
            renderProductos(productosDB);
        } else {
            alert("Error al obtener productos: " + result.message);
        }
    } catch (error) {
        console.error("Error al conectar con el servidor:", error);
    }
}

// Renderizar las tarjetas de productos
function renderProductos(lista) {
    const grid = document.getElementById("productsGrid");
    if (!grid) return;
    
    grid.innerHTML = "";
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

// Filtrar productos en la barra de búsqueda
function filtrarProductos() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const filtrados = productosDB.filter(p => 
        p.nombre.toLowerCase().includes(query) || p.codigo.includes(query)
    );
    renderProductos(filtrados);
}

// Agregar producto al carrito
function agregarAlCarrito(productoId) {
    const producto = productosDB.find(p => p.id === productoId);
    const itemExistente = carrito.find(item => item.producto_id === productoId);

    if (itemExistente) {
        if (itemExistente.cantidad + 1 > producto.stock) {
            alert("No hay suficiente stock en inventario.");
            return;
        }
        itemExistente.cantidad++;
    } else {
        carrito.push({
            producto_id: producto.id,
            nombre: producto.nombre,
            precio_unitario: producto.precio,
            cantidad: 1,
            stockMax: producto.stock
        });
    }
    actualizarCarritoUI();
}

// Cambiar cantidades (+ / -)
function cambiarCantidad(productoId, cambio) {
    const item = carrito.find(i => i.producto_id === productoId);
    if (!item) return;

    item.cantidad += cambio;
    if (item.cantidad <= 0) {
        carrito = carrito.filter(i => i.producto_id !== productoId);
    } else if (item.cantidad > item.stockMax) {
        alert("Supera el stock disponible.");
        item.cantidad -= cambio;
    }
    actualizarCarritoUI();
}

// Actualizar tabla del carrito y totales
function actualizarCarritoUI() {
    const tbody = document.getElementById("cartBody");
    const totalDisplay = document.getElementById("totalDisplay");
    const btnProcesar = document.getElementById("btnProcesar");

    if (!tbody) return;

    tbody.innerHTML = "";
    let total = 0;

    carrito.forEach(item => {
        const subtotal = item.cantidad * item.precio_unitario;
        total += subtotal;

        tbody.innerHTML += `
            <tr>
                <td>${item.nombre}</td>
                <td>
                    <button class="btn-qty" onclick="cambiarCantidad(${item.producto_id}, -1)">-</button>
                    ${item.cantidad}
                    <button class="btn-qty" onclick="cambiarCantidad(${item.producto_id}, 1)">+</button>
                </td>
                <td>$${item.precio_unitario.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-remove" onclick="cambiarCantidad(${item.producto_id}, -${item.cantidad})">✕</button>
                </td>
            </tr>
        `;
    });

    if (totalDisplay) totalDisplay.innerText = `$${total.toFixed(2)}`;
    if (btnProcesar) btnProcesar.disabled = carrito.length === 0;
}

// Abrir el modal y cargar lista de clientes desde la API
async function abrirModalCliente() {
    if (carrito.length === 0) return;

    // 1. Verificar primero si la caja está ABIERTA
    try {
        const resCaja = await fetch('../controllers/CajaController.php');
        const dataCaja = await resCaja.json();

        if (dataCaja.status === 'success' && dataCaja.data.estado === 'CERRADA') {
            alert("⚠️ NO SE PUEDE FACTURAR: La caja se encuentra CERRADA.\n\nPor favor, ve al módulo 'Cierre de Caja' e ingresa el fondo inicial para abrir el turno.");
            window.location.href = "reportes.php"; // Redirigir a la pantalla de caja
            return;
        }
    } catch (e) {
        console.error("Error al verificar estado de la caja:", e);
    }

    // 2. Si la caja está abierta, cargar lista de clientes y abrir el modal
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
        console.error("Error al cargar lista de clientes:", e);
    }

    modal.style.display = "flex";
}                                                                                                               

function cerrarModalCliente() {
    document.getElementById("modalCliente").style.display = "none";
}

// Confirmar la venta enviando la cédula/RUC del cliente seleccionado
async function confirmarVentaConCliente() {
    const btnConfirmar = document.getElementById("btnConfirmarVenta");
    const metodoPago = document.getElementById("metodoPago").value;
    const clienteIdentificacion = document.getElementById("selectCliente").value;

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
            cerrarModalCliente();
            alert(`✅ Factura ${result.secuencial} emitida con éxito.`);
            
            // Abrir la factura en PDF en una nueva pestaña
            window.open(`imprimir_factura.php?id=${result.factura_id}`, '_blank');

            carrito = [];
            actualizarCarritoUI();
            if (typeof cargarProductosDesdeBD === 'function') {
                await cargarProductosDesdeBD();
            }
        } else {
            alert(`❌ Error: ${result.message}`);
        }
    } catch (error) {
        console.error("Error al facturar:", error);
        alert("Ocurrió un error en el servidor al emitir la factura.");
    } finally {
        btnConfirmar.disabled = false;
        btnConfirmar.innerText = "Emitir Factura";
    }
}