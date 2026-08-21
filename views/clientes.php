<?php
$pagina_actual = 'clientes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza - Clientes</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
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

            <!-- Tabla de Clientes -->
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

            // Autocompletado del SRI
            document.getElementById("identificacion").addEventListener("input", async (e) => {
                const val = e.target.value.trim();
                if (val.length === 10 || val.length === 13) {
                    const inputRs = document.getElementById("razon_social");
                    inputRs.placeholder = "🔎 Buscando en el SRI...";
                    try {
                        const res = await fetch(`../controllers/ConsultaCedulaController.php?identificacion=${val}`);
                        const json = await res.json();
                        if (json.status === 'success') {
                            inputRs.value = json.data.razon_social || '';
                            if (json.data.direccion && !document.getElementById("direccion").value) {
                                document.getElementById("direccion").value = json.data.direccion;
                            }
                            if (json.origen === 'local') {
                                if (json.data.telefono) document.getElementById("telefono").value = json.data.telefono;
                                if (json.data.email) document.getElementById("email").value = json.data.email;
                            }
                        }
                    } catch (e) { console.error(e); } 
                    finally { inputRs.placeholder = "Ej. Comercializadora Química S.A."; }
                }
            });
        });

        async function cargarClientes() {
            try {
                const res = await fetch('../controllers/ClienteController.php');
                const json = await res.json();
                const tbody = document.getElementById("tablaClientes");
                tbody.innerHTML = "";

                if (json.status === 'success' && json.data.length > 0) {
                    json.data.forEach(c => {
                        tbody.innerHTML += `
                            <tr>
                                <td><code>${c.identificacion}</code></td>
                                <td><strong>${c.razon_social}</strong></td>
                                <td>${c.telefono || 'N/A'}</td>
                                <td>${c.email || 'N/A'}</td>
                            </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#64748b;">No hay clientes registrados.</td></tr>`;
                }
            } catch (e) { console.error(e); }
        }

        async function guardarCliente(e) {
            e.preventDefault();
            const id = document.getElementById("identificacion").value.trim();
            const rs = document.getElementById("razon_social").value.trim();

            if (id !== '9999999999999' && id.length !== 10 && id.length !== 13) {
                alert("⚠️ Identificación inválida (debe tener 10 o 13 dígitos).");
                return;
            }

            const btn = document.getElementById("btnGuardar");
            btn.disabled = true;
            btn.innerText = "Guardando...";

            try {
                const res = await fetch('../controllers/ClienteController.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        identificacion: id,
                        razon_social: rs,
                        direccion: document.getElementById("direccion").value.trim(),
                        telefono: document.getElementById("telefono").value.trim(),
                        email: document.getElementById("email").value.trim()
                    })
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    alert("✅ Cliente guardado con éxito.");
                    document.getElementById("formCliente").reset();
                    await cargarClientes();
                } else {
                    alert("❌ Error: " + (json.message || "No se pudo guardar."));
                }
            } catch (e) { alert("Error de conexión con el servidor."); } 
            finally {
                btn.disabled = false;
                btn.innerText = "Guardar Cliente";
            }
        }
    </script>
</body>
</html>