<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    header("Location: pos.php");
    exit;
}
$pagina_actual = 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Gestión de Usuarios</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Gestión de Usuarios y Permisos</h2>

        <div class="grid-usuarios">
            <!-- Formulario de Registro -->
            <div class="card">
                <h3>Crear Nuevo Usuario</h3>
                <form id="formUsuario" onsubmit="guardarUsuario(event)">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" id="nombre" required placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <input type="text" id="usuario" required placeholder="Ej. jperez">
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" id="password" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Rol del Sistema</label>
                        <select id="rol" required>
                            <option value="CAJERO">Cajero (Punto de Venta)</option>
                            <option value="ADMIN">Administrador (Acceso Total)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" id="btnGuardar">Registrar Usuario</button>
                </form>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="card">
                <h3>Usuarios Registrados</h3>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaUsuarios">
                            <tr><td colspan="5" style="text-align:center;">Cargando usuarios...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => cargarUsuarios());

        async function cargarUsuarios() {
            const tbody = document.getElementById("tablaUsuarios");
            try {
                const res = await fetch('../controllers/UsuarioController.php');
                const text = await res.text();
                
                let json;
                try {
                    json = JSON.parse(text);
                } catch(e) {
                    console.error("Respuesta no es JSON:", text);
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error del servidor: ${text}</td></tr>`;
                    return;
                }

                tbody.innerHTML = "";

                if (json.status === 'success' && json.data.length > 0) {
                    json.data.forEach(u => {
                        const rolClass = u.rol === 'ADMIN' ? 'badge-admin' : 'badge-cajero';
                        const estClass = u.estado === 'ACTIVO' ? 'estado-activo' : 'estado-inactivo';
                        const btnClass = u.estado === 'ACTIVO' ? 'btn-desactivar' : 'btn-activar';
                        const btnText = u.estado === 'ACTIVO' ? '🚫 Desactivar' : '✅ Activar';
                        const nuevoEstado = u.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

                        tbody.innerHTML += `
                            <tr>
                                <td><strong>${u.nombre}</strong></td>
                                <td><code>${u.usuario}</code></td>
                                <td><span class="badge-rol ${rolClass}">${u.rol}</span></td>
                                <td><span class="badge-estado ${estClass}">${u.estado}</span></td>
                                <td style="text-align: center;">
                                    <button class="btn-toggle-status ${btnClass}" onclick="cambiarEstado(${u.id}, '${nuevoEstado}')">
                                        ${btnText}
                                    </button>
                                </td>
                            </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No hay usuarios registrados.</td></tr>`;
                }
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error de conexión.</td></tr>`;
            }
        }

        async function guardarUsuario(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardar");
            btn.disabled = true;
            btn.innerText = "Registrando...";

            try {
                const res = await fetch('../controllers/UsuarioController.php?action=crear', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nombre: document.getElementById("nombre").value.trim(),
                        usuario: document.getElementById("usuario").value.trim(),
                        password: document.getElementById("password").value.trim(),
                        rol: document.getElementById("rol").value
                    })
                });

                const text = await res.text();
                let json;
                try {
                    json = JSON.parse(text);
                } catch(err) {
                    alert("❌ Error de respuesta PHP:\n\n" + text);
                    return;
                }

                if (res.ok && json.status === 'success') {
                    alert("✅ " + json.message);
                    document.getElementById("formUsuario").reset();
                    await cargarUsuarios();
                } else {
                    alert("❌ Error: " + json.message);
                }
            } catch (e) { 
                alert("Error de red o servidor no disponible."); 
            } finally { 
                btn.disabled = false; 
                btn.innerText = "Registrar Usuario"; 
            }
        }

        async function cambiarEstado(id, nuevoEstado) {
            if (!confirm(`¿Deseas cambiar el estado de este usuario a ${nuevoEstado}?`)) return;

            try {
                const res = await fetch('../controllers/UsuarioController.php?action=cambiar_estado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, estado: nuevoEstado })
                });

                const text = await res.text();
                let json = JSON.parse(text);

                if (res.ok && json.status === 'success') {
                    await cargarUsuarios();
                } else {
                    alert("❌ Error: " + json.message);
                }
            } catch (e) { 
                alert("Error de conexión al cambiar estado."); 
            }
        }
    </script>
</body>
</html>