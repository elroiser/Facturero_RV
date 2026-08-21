<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV - Limpieza | Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="../public/images/logo.png">
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body class="login-body">

    <div class="login-card">
        <img src="../public/images/logo.png" alt="RV Limpieza">
        <h2>RV - Limpieza</h2>
        
        <form action="../controllers/AuthController.php?action=login" method="POST">
            <div class="form-group-login">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required placeholder="Ingresa tu usuario">
            </div>

            <div class="form-group-login">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
    </div>

</body>
</html>