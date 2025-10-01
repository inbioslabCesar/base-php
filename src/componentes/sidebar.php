<?php
require_once __DIR__ . '/../auth/empresa_config.php';
?>

<!-- Sidebar fijo en md+ y offcanvas en móvil -->
<aside>
    <div class="d-none d-md-block sidebar-gradient shadow h-100 position-fixed" style="width:260px; min-height:100vh; z-index:1030;">
        <nav class="nav nav-pills flex-column p-3">
            <?php if ($_SESSION['rol'] == 'admin'): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Pacientes</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>
            <?php elseif ($_SESSION['rol'] == 'empresa'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresa"><i class="bi bi-building"></i> Panel Empresa</a>
            <?php elseif ($_SESSION['rol'] == 'recepcionista'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
            <?php elseif ($_SESSION['rol'] == 'laboratorista'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
            <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=cliente"><i class="bi bi-person"></i> Panel Paciente</a>
            <?php elseif ($_SESSION['rol'] == 'convenio'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenio"><i class="bi bi-person"></i> Panel Convenio</a>
            <?php endif; ?>
            <a class="nav-link sidebar-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </nav>
        <div class="text-center text-white-50 small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
    </div>
    <style>
        .sidebar-gradient {
            background: linear-gradient(135deg, #143a51 0%, #667eea 100%);
        }
        .sidebar-link {
            color: #fff !important;
            font-size: 1.15rem;
            margin-bottom: 0.7rem;
            padding: 1rem 1.5rem;
            border-radius: 0.7rem;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-link i {
            font-size: 1.6rem;
            vertical-align: middle;
            margin-right: 0.7rem;
        }
        .sidebar-link.active,
        .sidebar-link:hover {
            background: #764ba2;
            color: #fff !important;
        }
    </style>
    </div>
    <!-- Offcanvas para móvil -->
    <div class="offcanvas offcanvas-start d-md-none sidebar-gradient" tabindex="-1" id="sidebarToggle" aria-labelledby="sidebarToggleLabel">
        <div class="offcanvas-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
            <h5 class="offcanvas-title fw-bold" id="sidebarToggleLabel"><i class="bi bi-list me-2"></i>Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav nav-pills flex-column p-3 mt-4">
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Pacientes</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>

                <?php elseif ($_SESSION['rol'] == 'empresa'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresa"><i class="bi bi-building"></i> Panel Empresa</a>
                <?php elseif ($_SESSION['rol'] == 'recepcionista'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
                <?php elseif ($_SESSION['rol'] == 'laboratorista'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
                <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=cliente"><i class="bi bi-person"></i> Panel Paciente</a>
                <?php elseif ($_SESSION['rol'] == 'convenio'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenio"><i class="bi bi-person"></i> Panel Convenio</a>
                <?php endif; ?>
                <a class="nav-link sidebar-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
            </nav>
            <div class="text-center text-white-50 small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
        </div>
        <style>
            .sidebar-link {
                color: #fff !important;
                font-size: 1.15rem;
                margin-bottom: 0.7rem;
                padding: 1rem 1.5rem;
                border-radius: 0.7rem;
                transition: background 0.2s, color 0.2s;
            }
            .sidebar-link i {
                font-size: 1.6rem;
                vertical-align: middle;
                margin-right: 0.7rem;
            }
            .sidebar-link.active,
            .sidebar-link:hover {
                background: #764ba2;
                color: #fff !important;
            }
        </style>
        </div>
    </div>

</aside>
<main class="flex-grow-1" style="margin-left:250px;">