<?php
$pagina_actual = 'clientes';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza - Clientes</title>
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        .grid-clientes {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            outline: none;
        }

        .btn-submit {
            width: 100%;
            padding: 10px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
        }
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
                            <tr>
                                <td colspan="4" style="text-align:center;">Cargando clientes...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarClientes();

            // Listener para autocompletar nombre al escribir la Cédula/RUC
            const inputId = document.getElementById("identificacion");
            inputId.addEventListener("input", async (e) => {
                const valor = e.target.value.trim();

                if (valor.length === 10 || valor.length === 13) {
                    document.getElementById("razon_social").placeholder = "🔎 Buscando en el SRI...";
                    
                    try {
                        const res = await fetch(`../controllers/ConsultaCedulaController.php?identificacion=${valor}`);
                        const json = await res.json();

                        if (json.status === 'success') {
                            document.getElementById("razon_social").value = json.data.razon_social || '';
                            
                            if (json.data.direccion && document.getElementById("direccion").value === '') {
                                document.getElementById("direccion").value = json.data.direccion;
                            }

                            if (json.origen === 'local') {
                                if (json.data.telefono) document.getElementById("telefono").value = json.data.telefono;
                                if (json.data.email) document.getElementById("email").value = json.data.email;
                            }
                        }
                    } catch (err) {
                        console.error("Error al consultar la identificación:", err);
                    } finally {
                        document.getElementById("razon_social").placeholder = "Ej. Comercializadora Química S.A.";
                    }
                }
            });
        });

        async function cargarClientes() {
            try {
                const response = await fetch('../controllers/ClienteController.php');
                const result = await response.json();

                if (result.status === 'success') {
                    const tbody = document.getElementById("tablaClientes");
                    tbody.innerHTML = "";

                    if (result.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#64748b;">No hay clientes registrados aún.</td></tr>`;
                        return;
                    }

                    result.data.forEach(c => {
                        tbody.innerHTML += `
                            <tr>
                                <td><code>${c.identificacion}</code></td>
                                <td><strong>${c.razon_social}</strong></td>
                                <td>${c.telefono || 'N/A'}</td>
                                <td>${c.email || 'N/A'}</td>
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

            const identificacionInput = document.getElementById("identificacion").value.trim();
            const razonSocialInput = document.getElementById("razon_social").value.trim();

            // Validar cédula (10 dígitos) o RUC (13 dígitos)
            if (identificacionInput !== '9999999999999' && identificacionInput.length !== 10 && identificacionInput.length !== 13) {
                alert("⚠️ La identificación debe ser una Cédula válida de 10 dígitos o un RUC de 13 dígitos.");
                document.getElementById("identificacion").focus();
                return;
            }

            if (!razonSocialInput) {
                alert("⚠️ La Razón Social / Nombre es obligatoria.");
                document.getElementById("razon_social").focus();
                return;
            }

            const btn = document.getElementById("btnGuardar");
            btn.disabled = true;
            btn.innerText = "Guardando...";

            const payload = {
                identificacion: identificacionInput,
                razon_social: razonSocialInput,
                direccion: document.getElementById("direccion").value.trim(),
                telefono: document.getElementById("telefono").value.trim(),
                email: document.getElementById("email").value.trim()
            };

            try {
                const response = await fetch('../controllers/ClienteController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert("✅ Cliente guardado con éxito.");
                    document.getElementById("formCliente").reset();
                    await cargarClientes();
                } else {
                    alert("❌ Error: " + (result.message || "No se pudo guardar el cliente."));
                }
            } catch (error) {
                console.error("Error al enviar solicitud:", error);
                alert("No se pudo conectar con el servidor.");
            } finally {
                btn.disabled = false;
                btn.innerText = "Guardar Cliente";
            }
        }
    </script>
</body>

</html>