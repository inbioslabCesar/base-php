<!-- Sidebar fijo en md+ y offcanvas en móvil -->
<aside>
  <div class="d-none d-md-block bg-light shadow h-100 position-fixed" style="width:250px; min-height:100vh; z-index:1030;">
    <nav class="nav nav-pills flex-column p-3">
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Clientes</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>
        <?php elseif ($_SESSION['rol'] == 'empresa'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_empresa"><i class="bi bi-building"></i> Panel Empresa</a>
        <?php elseif ($_SESSION['rol'] == 'recepcionista'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
        <?php elseif ($_SESSION['rol'] == 'laboratorista'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
        <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_cliente"><i class="bi bi-person"></i> Panel Cliente</a>
        <?php endif; ?>
        <a class="nav-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </nav>
    <div class="text-center text-muted small py-2">© <?= date('Y') ?> inbioslab</div>
  </div>
  <!-- Offcanvas para móvil -->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="sidebarToggle" aria-labelledby="sidebarToggleLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarToggleLabel">Menú</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="nav nav-pills flex-column p-3 mt-5">
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Clientes</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>

        <?php elseif ($_SESSION['rol'] == 'empresa'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_empresa"><i class="bi bi-building"></i> Panel Empresa</a>
        <?php elseif ($_SESSION['rol'] == 'recepcionista'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
        <?php elseif ($_SESSION['rol'] == 'laboratorista'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
        <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
            <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=panel_cliente"><i class="bi bi-person"></i> Panel Cliente</a>
        <?php endif; ?>
        <a class="nav-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </nav>
    <div class="text-center text-muted small py-2">© <?= date('Y') ?> inbioslab</div>
  </div>
</div>

</aside>
