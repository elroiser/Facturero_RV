<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_usuario'] !== 'ADMIN') {
    header("Location: pos.php");
    exit;
}
$pagina_actual = 'auditoria';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Bitácora de Auditoría</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Bitácora y Auditoría del Sistema</h2>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Historial de Operaciones y Eventos</h3>
                <button class="btn-filter" onclick="cargarAuditoria()">🔄 Actualizar Logs</button>
            </div>

            <div style="max-height: 550px; overflow-y: auto;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Detalle de la Operación</th>
                            <th>Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody id="tablaAuditoria">
                        <tr><td colspan="5" style="text-align:center;">Cargando registros de bitácora...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => cargarAuditoria());

        async function cargarAuditoria() {
            const tbody = document.getElementById("tablaAuditoria");
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Cargando registros...</td></tr>`;

            try {
                const res = await fetch('../controllers/AuditoriaController.php');
                const json = await res.json();
                tbody.innerHTML = "";

                if (json.status === 'success' && json.data.length > 0) {
                    json.data.forEach(log => {
                        tbody.innerHTML += `
                            <tr>
                                <td><small style="color:#64748b; font-weight:600;">${log.fecha_hora}</small></td>
                                <td><strong>${log.nombre_usuario}</strong></td>
                                <td><span class="log-tag">${log.accion}</span></td>
                                <td>${log.detalle}</td>
                                <td><code>${log.ip || '127.0.0.1'}</code></td>
                            </tr>`;
                    });
                } else if (json.status === 'success' && json.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#64748b;">No hay eventos registrados.</td></tr>`;
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error: ${json.message}</td></tr>`;
                }
            } catch (e) {
                console.error("Error:", e);
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">No se pudo conectar con el servidor.</td></tr>`;
            }
        }
    </script>
</body>
</html>