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

// Obtener estado real de la caja
require_once __DIR__ . '/../../config/Database.php';
$estadoCaja = 'CERRADA';
try {
    $dbNavbar = Database::getConnection();
    $stmtNav = $dbNavbar->query("SELECT estado FROM cajas WHERE id = 1 LIMIT 1");
    if ($res = $stmtNav->fetch()) {
        $estadoCaja = $res['estado'];
    }
} catch (Exception $e) {}
?>
<style>
    .pos-navbar { background-color: #0f172a; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px; margin-bottom: 20px; }
    .pos-navbar .brand { color: #38bdf8; font-size: 1.25rem; font-weight: 700; text-decoration: none; }
    .pos-navbar .nav-links { display: flex; gap: 8px; list-style: none; margin: 0; padding: 0; }
    .pos-navbar .nav-link { color: #94a3b8; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; }
    .pos-navbar .nav-link:hover { color: #ffffff; background-color: #1e293b; }
    .pos-navbar .nav-link.active { color: #ffffff; background-color: #2563eb; }
    .badge-caja-abierta { background-color: #059669; color: #ffffff; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.75rem; }
    .badge-caja-cerrada { background-color: #dc2626; color: #ffffff; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.75rem; }
    .btn-logout { background-color: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.8rem; margin-left: 10px; }
    .btn-logout:hover { background-color: #dc2626; }
</style>

<nav class="pos-navbar">
    <a href="pos.php" class="brand"><img src="../public/images/logo.png" alt="Logo de la Empresa" width="40" style="margin-left: 12px; vertical-align: middle;"> RV - Limpieza</a>
    
    <ul class="nav-links">
        <li><a href="pos.php" class="nav-link <?= ($pagina_actual === 'pos') ? 'active' : ''; ?>">🛒 Punto de Venta</a></li>
        <li><a href="clientes.php" class="nav-link <?= ($pagina_actual === 'clientes') ? 'active' : ''; ?>">👥 Clientes</a></li>
        <li><a href="inventario.php" class="nav-link <?= ($pagina_actual === 'inventario') ? 'active' : ''; ?>">📦 Inventario</a></li>
        <li><a href="reportes.php" class="nav-link <?= ($pagina_actual === 'reportes') ? 'active' : ''; ?>">📊 Cierre de Caja</a></li>
    </ul>
    <div style="display: flex; align-items: center;">
        <?php if ($estadoCaja === 'ABIERTA'): ?>
            <span class="badge-caja-abierta">🟢 Caja #1 - ABIERTA</span>
        <?php else: ?>
            <span class="badge-caja-cerrada">🔴 Caja #1 - CERRADA</span>
        <?php endif; ?>
        
        <a href="../controllers/AuthController.php?action=logout" class="btn-logout" title="Cerrar Sesión">🚪 Salir</a>
    </div>
</nav>



