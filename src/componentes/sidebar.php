<?php
require_once __DIR__ . '/../auth/empresa_config.php';
require_once __DIR__ . '/../usuarios/funciones/usuarios_privilegios.php';

$operacionSidebar = function_exists('app_operacion_context') && isset($pdo) && $pdo instanceof PDO
    ? app_operacion_context($pdo)
    : ['es_sis' => false];
$esModoSisSidebar = !empty($operacionSidebar['es_sis']);
$privilegiosSidebar = isset($_SESSION['privilegios']) && is_array($_SESSION['privilegios'])
    ? $_SESSION['privilegios']
    : ((isset($pdo) && $pdo instanceof PDO) ? usuarios_privilegios_usuario_actual($pdo) : []);
$puedeSidebar = static function (string $clave) use ($privilegiosSidebar): bool {
    return usuarios_tiene_privilegio($privilegiosSidebar, $clave);
};
?>

<!-- Sidebar fijo en md+ y offcanvas en móvil -->
<aside>
    <div class="d-none d-md-block sidebar-gradient shadow h-100 position-fixed" style="width:260px; min-height:100vh; z-index:1030;">
        <nav class="nav nav-pills flex-column p-3">
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_admin')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_usuarios')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_empresas')): ?>
                <?php if (!$esModoSisSidebar): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_pacientes')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Pacientes</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_convenios')): ?>
                <?php if (!$esModoSisSidebar): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_servicios')): ?>
                <?php if ($esModoSisSidebar): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicios"><i class="bi bi-hospital"></i> Servicios</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_examenes')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_estadisticas')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=estadisticas"><i class="bi bi-bar-chart"></i> Estadística</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=offline_monitor"><i class="bi bi-wifi-off"></i> Monitoreo Offline</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_inventario')): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=inventario"><i class="bi bi-box-seam"></i> Inventario</a>
            <?php endif; ?>
            <?php if ($_SESSION['rol'] == 'empresa'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresa"><i class="bi bi-building"></i> Panel Empresa</a>
            <?php elseif ($_SESSION['rol'] == 'recepcionista' && $puedeSidebar('menu_recepcion')): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
                <?php if ($puedeSidebar('menu_estadisticas')): ?><a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=estadisticas"><i class="bi bi-bar-chart"></i> Estadística</a><?php endif; ?>
                <?php if ($puedeSidebar('menu_inventario')): ?><a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=inventario"><i class="bi bi-box-seam"></i> Inventario</a><?php endif; ?>
            <?php elseif ($_SESSION['rol'] == 'laboratorista' && $puedeSidebar('menu_laboratorio')): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
            <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=cliente"><i class="bi bi-person"></i> Panel Paciente</a>
            <?php elseif ($_SESSION['rol'] == 'convenio'): ?>
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenio"><i class="bi bi-person"></i> Panel Convenio</a>
            <?php elseif ($_SESSION['rol'] == 'servicio'): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio"><i class="bi bi-hospital"></i> Panel Servicio</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_clientes"><i class="bi bi-people"></i> Pacientes</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_resultados"><i class="bi bi-file-earmark-pdf"></i> Resultados</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_auditoria"><i class="bi bi-shield-check"></i> Auditoria</a>
            <?php elseif ($_SESSION['rol'] == 'engineer'): ?>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=config_operacion_engineer"><i class="bi bi-sliders"></i> Configuracion Operativa</a>
                <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=config_personalizacion_engineer"><i class="bi bi-palette"></i> Personalizacion</a>
            <?php endif; ?>
            <a class="nav-link sidebar-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </nav>
        <div class="text-center text-white small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
    </div>
    <style>
        .sidebar-gradient {
            background: var(--ui-sidebar-bg, #0d6efd);
        }
        .sidebar-gradient .nav-link,
        .sidebar-gradient .nav-link i,
        .offcanvas.sidebar-gradient .nav-link,
        .offcanvas.sidebar-gradient .nav-link i {
            color: var(--ui-sidebar-text, #fff) !important;
        }
        .offcanvas.sidebar-gradient {
            --bs-offcanvas-bg: var(--ui-sidebar-bg, #0d6efd);
            background-color: var(--ui-sidebar-bg, #0d6efd);
        }
        .offcanvas.sidebar-gradient .offcanvas-body {
            background-color: var(--ui-sidebar-bg, #0d6efd);
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
            background: var(--ui-sidebar-hover-bg, #0b5ed7);
            color: var(--ui-sidebar-text, #fff) !important;
        }
    </style>
    <!-- Offcanvas para móvil -->
    <div class="offcanvas offcanvas-start d-md-none sidebar-gradient" tabindex="-1" id="sidebarToggle" aria-labelledby="sidebarToggleLabel">
        <div class="offcanvas-header" style="background: var(--ui-sidebar-bg, #0d6efd); color: var(--ui-sidebar-text, #fff);">
            <h5 class="offcanvas-title fw-bold" id="sidebarToggleLabel"><i class="bi bi-list me-2"></i>Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav nav-pills flex-column p-3 mt-4">
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_admin')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=admin"><i class="bi bi-people"></i> Panel Admin</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_usuarios')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=usuarios"><i class="bi bi-people"></i> Usuarios</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_empresas')): ?>
                    <?php if (!$esModoSisSidebar): ?>
                        <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=empresas"><i class="bi bi-building"></i> Empresas</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_pacientes')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=clientes"><i class="bi bi-person"></i> Pacientes</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_convenios')): ?>
                    <?php if (!$esModoSisSidebar): ?>
                        <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=convenios"><i class="bi bi-person"></i> Convenios</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_servicios')): ?>
                    <?php if ($esModoSisSidebar): ?>
                        <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicios"><i class="bi bi-hospital"></i> Servicios</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_examenes')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=examenes"><i class="bi bi-person"></i> Examenes</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_estadisticas')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=estadisticas"><i class="bi bi-bar-chart"></i> Estadística</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=offline_monitor"><i class="bi bi-wifi-off"></i> Monitoreo Offline</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_inventario')): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=inventario"><i class="bi bi-box-seam"></i> Inventario</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] == 'empresa'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=empresa"><i class="bi bi-building"></i> Panel Empresa</a>
                <?php elseif ($_SESSION['rol'] == 'recepcionista' && $puedeSidebar('menu_recepcion')): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=recepcionista"><i class="bi bi-person-badge"></i> Panel Recepción</a>
                    <?php if ($puedeSidebar('menu_estadisticas')): ?><a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=estadisticas"><i class="bi bi-bar-chart"></i> Estadística</a><?php endif; ?>
                    <?php if ($puedeSidebar('menu_inventario')): ?><a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=inventario"><i class="bi bi-box-seam"></i> Inventario</a><?php endif; ?>
                <?php elseif ($_SESSION['rol'] == 'laboratorista' && $puedeSidebar('menu_laboratorio')): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=laboratorista"><i class="bi bi-eyedropper"></i> Panel Laboratorio</a>
                <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=cliente"><i class="bi bi-person"></i> Panel Paciente</a>
                <?php elseif ($_SESSION['rol'] == 'convenio'): ?>
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php?vista=convenio"><i class="bi bi-person"></i> Panel Convenio</a>
                <?php elseif ($_SESSION['rol'] == 'servicio'): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio"><i class="bi bi-hospital"></i> Panel Servicio</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_clientes"><i class="bi bi-people"></i> Pacientes</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_resultados"><i class="bi bi-file-earmark-pdf"></i> Resultados</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=servicio_auditoria"><i class="bi bi-shield-check"></i> Auditoria</a>
                <?php elseif ($_SESSION['rol'] == 'engineer'): ?>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=config_operacion_engineer"><i class="bi bi-sliders"></i> Configuracion Operativa</a>
                    <a class="nav-link sidebar-link" href="<?= BASE_URL ?>dashboard.php?vista=config_personalizacion_engineer"><i class="bi bi-palette"></i> Personalizacion</a>
                <?php endif; ?>
                <a class="nav-link sidebar-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
            </nav>
            <div class="text-center text-white small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
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
                background: var(--ui-sidebar-hover-bg, #0b5ed7);
                color: var(--ui-sidebar-text, #fff) !important;
            }
        </style>
    </div>

</aside>
<main class="flex-grow-1" style="margin-left:250px;">