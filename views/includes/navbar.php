<?php
if (!isset($pagina_actual)) {
    $pagina_actual = '';
}
?>
<style>
    .pos-navbar { background-color: #0f172a; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px; margin-bottom: 20px; }
    .pos-navbar .brand { color: #38bdf8; font-size: 1.25rem; font-weight: 700; text-decoration: none; }
    .pos-navbar .nav-links { display: flex; gap: 8px; list-style: none; margin: 0; padding: 0; }
    .pos-navbar .nav-link { color: #94a3b8; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; }
    .pos-navbar .nav-link:hover { color: #ffffff; background-color: #1e293b; }
    .pos-navbar .nav-link.active { color: #ffffff; background-color: #2563eb; }
    .badge-caja { background-color: #059669; color: #ffffff; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.75rem; }
</style>

<nav class="pos-navbar">
    <a href="pos.php" class="brand">🧪 Chemlook POS</a>
    <ul class="nav-links">
        <li><a href="pos.php" class="nav-link <?= ($pagina_actual === 'pos') ? 'active' : ''; ?>">🛒 Punto de Venta</a></li>
        <li><a href="clientes.php" class="nav-link <?= ($pagina_actual === 'clientes') ? 'active' : ''; ?>">👥 Clientes</a></li>
        <li><a href="inventario.php" class="nav-link <?= ($pagina_actual === 'inventario') ? 'active' : ''; ?>">📦 Inventario</a></li>
        <li><a href="reportes.php" class="nav-link <?= ($pagina_actual === 'reportes') ? 'active' : ''; ?>">📊 Cierre de Caja</a></li>
    </ul>
    <div><span class="badge-caja">Caja #1 - ABIERTA</span></div>
</nav>