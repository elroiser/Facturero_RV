<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir al Login si no hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($pagina_actual)) {
    $pagina_actual = '';
}

// Obtener datos del usuario de la sesión
$rolUsuario = isset($_SESSION['rol_usuario']) ? $_SESSION['rol_usuario'] : 'CAJERO';
$nombreUsuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : 'Usuario';

// Obtener estado real de la caja
require_once __DIR__ . '/../../config/Database.php';
$estadoCaja = 'CERRADA';
try {
    $dbNavbar = Database::getConnection();
    $stmtNav = $dbNavbar->query("SELECT estado FROM cajas WHERE id = 1 LIMIT 1");
    if ($res = $stmtNav->fetch()) {
        $estadoCaja = $res['estado'];
    }
} catch (Exception $e) {
}
?>

<nav class="pos-navbar">
    <a href="pos.php" class="brand">
        <img src="../public/images/logo.png" alt="Logo de la Empresa">
        RV - Limpieza
    </a>

    <ul class="nav-links">
        <li><a href="pos.php" class="nav-link <?= ($pagina_actual === 'pos') ? 'active' : ''; ?>">🛒 Punto de Venta</a></li>
        <li><a href="clientes.php" class="nav-link <?= ($pagina_actual === 'clientes') ? 'active' : ''; ?>">👥 Clientes</a></li>

        <?php if ($rolUsuario === 'ADMIN'): ?>
            <li><a href="inventario.php" class="nav-link <?= ($pagina_actual === 'inventario') ? 'active' : ''; ?>">📦 Inventario</a></li>
            <li><a href="historial_ventas.php" class="nav-link <?= ($pagina_actual === 'historial_ventas') ? 'active' : ''; ?>">📊 Historial Ventas</a></li>
            <li><a href="reportes.php" class="nav-link <?= ($pagina_actual === 'reportes') ? 'active' : ''; ?>">💵 Cierre de Caja</a></li>
            <li><a href="usuarios.php" class="nav-link <?= ($pagina_actual === 'usuarios') ? 'active' : ''; ?>">👤 Usuarios</a></li>
            <li><a href="auditoria.php" class="nav-link <?= ($pagina_actual === 'auditoria') ? 'active' : ''; ?>">🛡️ Auditoría</a></li>
        <?php endif; ?>
    </ul>

    <div style="display: flex; align-items: center;">
        <span class="user-badge">👤 <?= htmlspecialchars($nombreUsuario); ?> (<?= htmlspecialchars($rolUsuario); ?>)</span>

        <?php if ($estadoCaja === 'ABIERTA'): ?>
            <span class="badge-caja-abierta">🟢 Caja #1 - ABIERTA</span>
        <?php else: ?>
            <span class="badge-caja-cerrada">🔴 Caja #1 - CERRADA</span>
        <?php endif; ?>

        <a href="../controllers/AuthController.php?action=logout" class="btn-logout" title="Cerrar Sesión">🚪 Salir</a>
    </div>
</nav>