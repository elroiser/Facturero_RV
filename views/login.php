<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: pos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .login-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); width: 100%; max-width: 380px; text-align: center; }
        .login-card img { max-height: 80px; margin-bottom: 15px; }
        .login-card h2 { margin-bottom: 20px; color: #0f172a; font-size: 1.4rem; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 5px; color: #334155; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; outline: none; }
        .form-group input:focus { border-color: #2563eb; }
        .btn-login { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; margin-top: 10px; }
        .btn-login:hover { background: #1d4ed8; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 15px; display: none; }
    </style>
</head>
<body>

    <div class="login-card">
        <img src="../public/images/logo.png" alt="Logo RV Limpieza" onerror="this.style.display='none'">
        <h2>🧪 RV - Limpieza</h2>
        
        <div id="errorMsg" class="error-msg"></div>

        <form id="formLogin" onsubmit="procesarLogin(event)">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" id="usuario" required placeholder="Ej. admin" autocomplete="username">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" id="password" required placeholder="••••••••" autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login" id="btnLogin">Ingresar al Sistema</button>
        </form>
    </div>

    <script>
        async function procesarLogin(e) {
            e.preventDefault();
            const btn = document.getElementById("btnLogin");
            const errorMsg = document.getElementById("errorMsg");
            
            errorMsg.style.display = "none";
            btn.disabled = true;
            btn.innerText = "Verificando...";

            const payload = {
                usuario: document.getElementById("usuario").value.trim(),
                password: document.getElementById("password").value.trim()
            };

            try {
                const response = await fetch('../controllers/AuthController.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    window.location.href = 'pos.php';
                } else {
                    errorMsg.innerText = "⚠️ " + (result.message || "Credenciales incorrectas.");
                    errorMsg.style.display = "block";
                }
            } catch (err) {
                console.error("Error en login:", err);
                errorMsg.innerText = "Ocurrió un error al conectar con el servidor.";
                errorMsg.style.display = "block";
            } finally {
                btn.disabled = false;
                btn.innerText = "Ingresar al Sistema";
            }
        }
    </script>
</body>
</html>