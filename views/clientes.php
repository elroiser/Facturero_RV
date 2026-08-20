<?php
$pagina_actual = 'clientes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemlook POS - Clientes</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        .grid-clientes { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; }
        .form-group input { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; outline: none; }
        .btn-submit { width: 100%; padding: 10px; background: var(--accent); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 8px; }
        .btn-submit:hover { background: var(--accent-hover); }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Gestión de Clientes</h2>

        <div class="grid-clientes">
            <!-- Formulario de Registro -->
            <div class="card">
                <h3>Registrar Cliente</h3>
                <form id="formCliente" onsubmit="guardarCliente(event)">
                    <div class="form-group">
                        <label>RUC / Cédula</label>
                        <input type="text" id="identificacion" maxlength="13" required placeholder="Ej. 0991234567001">
                    </div>
                    <div class="form-group">
                        <label>Razón Social / Nombre</label>
                        <input type="text" id="razon_social" required placeholder="Ej. Comercializadora Química S.A.">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" id="direccion" placeholder="Ej. Av. Principal #123">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" id="telefono" placeholder="Ej. 0991234567">
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" id="email" placeholder="Ej. cliente@empresa.com">
                    </div>
                    <button type="submit" class="btn-submit" id="btnGuardar">Guardar Cliente</button>
                </form>
            </div>

            <!-- Tabla de Clientes en MySQL -->
            <div class="card">
                <h3>Clientes Registrados</h3>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>RUC/Cédula</th>
                                <th>Razón Social</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                            </tr>
                        </thead>
                        <tbody id="tablaClientes">
                            <tr><td colspan="4" style="text-align:center;">Cargando clientes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarClientes();
        });

        async function cargarClientes() {
            try {
                const response = await fetch('../controllers/ClienteController.php');
                const result = await response.json();

                if (result.status === 'success') {
                    const tbody = document.getElementById("tablaClientes");
                    tbody.innerHTML = "";

                    result.data.forEach(c => {
                        tbody.innerHTML += `
                            <tr>
                                <td><code>${c.identificacion}</code></td>
                                <td><strong>${c.razon_social}</strong></td>
                                <td>${c.telefono}</td>
                                <td>${c.email}</td>
                            </tr>
                        `;
                    });
                }
            } catch (error) {
                console.error("Error al cargar clientes:", error);
            }
        }

        async function guardarCliente(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardar");

            const payload = {
                identificacion: document.getElementById("identificacion").value.trim(),
                razon_social: document.getElementById("razon_social").value.trim(),
                direccion: document.getElementById("direccion").value.trim(),
                telefono: document.getElementById("telefono").value.trim(),
                email: document.getElementById("email").value.trim()
            };

            btn.disabled = true;

            try {
                const response = await fetch('../controllers/ClienteController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert("✅ Cliente guardado con éxito.");
                    document.getElementById("formCliente").reset();
                    await cargarClientes();
                } else {
                    alert("❌ Error: " + result.message);
                }
            } catch (error) {
                alert("Error de conexión al guardar cliente.");
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>