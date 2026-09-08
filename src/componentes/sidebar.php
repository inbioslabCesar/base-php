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

$rolSidebar = strtolower((string)($_SESSION['rol'] ?? ''));
$vistaActualSidebar = strtolower(trim((string)($_GET['vista'] ?? '')));

$sidebarSections = [
    [
        'key' => 'paneles',
        'title' => 'Paneles',
        'icon' => 'bi-grid-1x2'
    ],
    [
        'key' => 'gestion',
        'title' => 'Gestion',
        'icon' => 'bi-folder2-open'
    ],
    [
        'key' => 'atencion',
        'title' => 'Atencion',
        'icon' => 'bi-person-lines-fill'
    ],
    [
        'key' => 'control',
        'title' => 'Control',
        'icon' => 'bi-graph-up-arrow'
    ],
    [
        'key' => 'configuracion',
        'title' => 'Configuracion',
        'icon' => 'bi-sliders'
    ],
];

$sidebarItems = [
    'paneles' => [],
    'gestion' => [],
    'atencion' => [],
    'control' => [],
    'configuracion' => [],
];

$sidebarBgColor = (isset($uiTheme['sidebar_bg']) && is_string($uiTheme['sidebar_bg'])) ? $uiTheme['sidebar_bg'] : '#0d6efd';
$sidebarHoverBgColor = (isset($uiTheme['sidebar_hover_bg']) && is_string($uiTheme['sidebar_hover_bg'])) ? $uiTheme['sidebar_hover_bg'] : '#0b5ed7';
$sidebarTextColor = (isset($uiTheme['sidebar_text']) && is_string($uiTheme['sidebar_text'])) ? $uiTheme['sidebar_text'] : '#f9fbff';

$sidebarHoverTextColor = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg($sidebarHoverBgColor)
    : '#f9fbff';

$sidebarPanelBg = function_exists('ui_theme_adjust_brightness')
    ? ui_theme_adjust_brightness($sidebarBgColor, -30)
    : '#123a53';

$sidebarPanelActiveBg = function_exists('ui_theme_adjust_brightness')
    ? ui_theme_adjust_brightness($sidebarBgColor, -45)
    : '#0e2d40';

$sidebarHoverOverlay = strtolower($sidebarHoverTextColor) === '#1f2937'
    ? 'rgba(255, 255, 255, 0.14)'
    : 'rgba(0, 0, 0, 0.24)';

$agregarItemSidebar = static function (array &$coleccion, string $seccion, string $vista, string $label, string $icono) use ($vistaActualSidebar): void {
    $coleccion[$seccion][] = [
        'href' => BASE_URL . 'dashboard.php?vista=' . $vista,
        'label' => $label,
        'icon' => $icono,
        'active' => $vistaActualSidebar === strtolower($vista),
    ];
};

if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_admin')) {
    $agregarItemSidebar($sidebarItems, 'paneles', 'admin', 'Panel Admin', 'bi-people');
}
if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_usuarios')) {
    $agregarItemSidebar($sidebarItems, 'gestion', 'usuarios', 'Usuarios', 'bi-people');
}
if (($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_empresas')) && !$esModoSisSidebar) {
    $agregarItemSidebar($sidebarItems, 'gestion', 'empresas', 'Empresas', 'bi-building');
}
if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_pacientes')) {
    $agregarItemSidebar($sidebarItems, 'atencion', 'clientes', 'Pacientes', 'bi-person');
}
if (($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_convenios')) && !$esModoSisSidebar) {
    $agregarItemSidebar($sidebarItems, 'gestion', 'convenios', 'Convenios', 'bi-person-vcard');
}
if (($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_servicios')) && $esModoSisSidebar) {
    $agregarItemSidebar($sidebarItems, 'gestion', 'servicios', 'Servicios', 'bi-hospital');
}
if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_examenes')) {
    $agregarItemSidebar($sidebarItems, 'gestion', 'examenes', 'Examenes', 'bi-clipboard2-pulse');
}
if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_estadisticas')) {
    $agregarItemSidebar($sidebarItems, 'control', 'estadisticas', 'Estadistica', 'bi-bar-chart');
    $agregarItemSidebar($sidebarItems, 'control', 'offline_monitor', 'Monitoreo Offline', 'bi-wifi-off');
}
if ($_SESSION['rol'] == 'admin' || $puedeSidebar('menu_inventario')) {
    $agregarItemSidebar($sidebarItems, 'control', 'inventario', 'Inventario', 'bi-box-seam');
}

if ($rolSidebar === 'empresa') {
    $agregarItemSidebar($sidebarItems, 'paneles', 'empresa', 'Panel Empresa', 'bi-building');
} elseif ($rolSidebar === 'recepcionista' && $puedeSidebar('menu_recepcion')) {
    $agregarItemSidebar($sidebarItems, 'paneles', 'recepcionista', 'Panel Recepcion', 'bi-person-badge');
} elseif ($rolSidebar === 'laboratorista' && $puedeSidebar('menu_laboratorio')) {
    $agregarItemSidebar($sidebarItems, 'paneles', 'laboratorista', 'Panel Laboratorio', 'bi-eyedropper');
} elseif ($rolSidebar === 'cliente') {
    $agregarItemSidebar($sidebarItems, 'paneles', 'cliente', 'Panel Paciente', 'bi-person');
} elseif ($rolSidebar === 'convenio') {
    $agregarItemSidebar($sidebarItems, 'paneles', 'convenio', 'Panel Convenio', 'bi-person-vcard');
} elseif ($rolSidebar === 'servicio') {
    $agregarItemSidebar($sidebarItems, 'paneles', 'servicio', 'Panel Servicio', 'bi-hospital');
    $agregarItemSidebar($sidebarItems, 'atencion', 'servicio_clientes', 'Pacientes', 'bi-people');
    $agregarItemSidebar($sidebarItems, 'atencion', 'servicio_resultados', 'Resultados', 'bi-file-earmark-pdf');
    $agregarItemSidebar($sidebarItems, 'control', 'servicio_auditoria', 'Auditoria', 'bi-shield-check');
} elseif ($rolSidebar === 'engineer') {
    $agregarItemSidebar($sidebarItems, 'configuracion', 'config_operacion_engineer', 'Configuracion Operativa', 'bi-sliders');
    $agregarItemSidebar($sidebarItems, 'configuracion', 'config_personalizacion_engineer', 'Personalizacion', 'bi-palette');
}

$renderSidebarAccordion = static function (string $accordionId, array $sections, array $items): void {
    $seccionActiva = '';
    foreach ($sections as $section) {
        $key = $section['key'];
        if (empty($items[$key])) {
            continue;
        }
        foreach ($items[$key] as $item) {
            if (!empty($item['active'])) {
                $seccionActiva = $key;
                break 2;
            }
        }
    }

    if ($seccionActiva === '') {
        foreach ($sections as $section) {
            $key = $section['key'];
            if (!empty($items[$key])) {
                $seccionActiva = $key;
                break;
            }
        }
    }

    echo '<div class="accordion sidebar-accordion" id="' . htmlspecialchars($accordionId, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($sections as $section) {
        $key = $section['key'];
        if (empty($items[$key])) {
            continue;
        }

        $collapseId = $accordionId . '-' . $key;
        $headerId = $collapseId . '-header';
        $isOpen = $key === $seccionActiva;

        echo '<div class="accordion-item sidebar-accordion-item">';
        echo '<h2 class="accordion-header" id="' . htmlspecialchars($headerId, ENT_QUOTES, 'UTF-8') . '">';
        echo '<button class="accordion-button sidebar-accordion-btn' . ($isOpen ? '' : ' collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '" aria-controls="' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '">';
        echo '<i class="bi ' . htmlspecialchars((string)$section['icon'], ENT_QUOTES, 'UTF-8') . ' me-2"></i>' . htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8');
        echo '</button>';
        echo '</h2>';
        echo '<div id="' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '" class="accordion-collapse collapse' . ($isOpen ? ' show' : '') . '" aria-labelledby="' . htmlspecialchars($headerId, ENT_QUOTES, 'UTF-8') . '" data-bs-parent="#' . htmlspecialchars($accordionId, ENT_QUOTES, 'UTF-8') . '">';
        echo '<div class="accordion-body py-2 px-2">';

        foreach ($items[$key] as $item) {
            echo '<a class="nav-link sidebar-link' . (!empty($item['active']) ? ' active' : '') . '" href="' . htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') . '">';
            echo '<i class="bi ' . htmlspecialchars((string)$item['icon'], ENT_QUOTES, 'UTF-8') . '"></i>';
            echo '<span>' . htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</a>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
};
?>

<!-- Sidebar fijo en md+ y offcanvas en móvil -->
<aside>
    <div class="d-none d-md-block sidebar-gradient shadow h-100 position-fixed" style="width:260px; min-height:100vh; z-index:1030;">
        <nav class="p-3">
            <?php $renderSidebarAccordion('sidebarDesktopAccordion', $sidebarSections, $sidebarItems); ?>
            <a class="nav-link sidebar-link sidebar-logout-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i><span>Cerrar sesion</span></a>
        </nav>
        <div class="text-center text-white small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
    </div>
    <style>
        .sidebar-gradient {
            background: var(--ui-sidebar-bg, #0d6efd) !important;
            background-color: var(--ui-sidebar-bg, #0d6efd) !important;
            --sidebar-panel-bg: <?= htmlspecialchars($sidebarPanelBg, ENT_QUOTES, 'UTF-8') ?>;
            --sidebar-panel-active-bg: <?= htmlspecialchars($sidebarPanelActiveBg, ENT_QUOTES, 'UTF-8') ?>;
            --sidebar-hover-text: <?= htmlspecialchars($sidebarHoverTextColor, ENT_QUOTES, 'UTF-8') ?>;
            --sidebar-hover-overlay: <?= htmlspecialchars($sidebarHoverOverlay, ENT_QUOTES, 'UTF-8') ?>;
            --sidebar-text-color: <?= htmlspecialchars($sidebarTextColor, ENT_QUOTES, 'UTF-8') ?>;
        }
        .sidebar-gradient .nav-link,
        .sidebar-gradient .nav-link i,
        .offcanvas.sidebar-gradient .nav-link,
        .offcanvas.sidebar-gradient .nav-link i {
            color: var(--ui-sidebar-text, #fff) !important;
        }
        .offcanvas.sidebar-gradient {
            --bs-offcanvas-bg: var(--ui-sidebar-bg, #0d6efd);
            --bs-offcanvas-zindex: 1090;
            background-color: var(--ui-sidebar-bg, #0d6efd) !important;
            z-index: 1090 !important;
        }
        .offcanvas.sidebar-gradient .offcanvas-body {
            background-color: var(--ui-sidebar-bg, #0d6efd) !important;
        }
        .offcanvas-backdrop.show {
            z-index: 1080 !important;
        }
        @media (max-width: 767.98px) {
            .offcanvas.sidebar-gradient {
                top: var(--app-header-offset, 0px) !important;
                height: calc(100dvh - var(--app-header-offset, 0px)) !important;
                max-height: calc(100dvh - var(--app-header-offset, 0px)) !important;
                border-radius: 0;
            }
            .offcanvas-backdrop.show {
                top: var(--app-header-offset, 0px) !important;
                height: calc(100dvh - var(--app-header-offset, 0px)) !important;
            }
        }
        .sidebar-accordion {
            --bs-accordion-bg: transparent;
            --bs-accordion-border-color: transparent;
            --bs-accordion-btn-focus-box-shadow: none;
            --bs-accordion-btn-color: var(--ui-sidebar-text, #fff);
            --bs-accordion-btn-bg: rgba(255, 255, 255, 0.08);
            --bs-accordion-active-color: var(--ui-sidebar-text, #fff);
            --bs-accordion-active-bg: rgba(255, 255, 255, 0.14);
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }
        .sidebar-gradient .accordion-button,
        .sidebar-gradient .accordion-button:focus,
        .sidebar-gradient .accordion-button:hover,
        .sidebar-gradient .accordion-button:not(.collapsed) {
            color: #f5faff !important;
            box-shadow: none !important;
        }
        .sidebar-accordion-item {
            background: transparent !important;
            border: 0 !important;
            border-radius: 0.7rem;
            overflow: hidden;
        }
        .sidebar-accordion .accordion-item {
            background: transparent !important;
            border: 0 !important;
        }
        .sidebar-accordion-item .accordion-collapse,
        .sidebar-accordion-item .accordion-body {
            background: rgba(62, 69, 77, 0.82) !important;
        }
        .sidebar-accordion-btn {
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 0.7rem;
            color: var(--sidebar-text-color, #f5faff) !important;
            background: var(--sidebar-panel-bg, #123a53) !important;
            background-color: var(--sidebar-panel-bg, #123a53) !important;
            border: 1px solid rgba(255, 255, 255, 0.24) !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.45);
            transition: color 0.2s ease, border-color 0.2s ease;
        }
        .sidebar-accordion-btn.collapsed {
            color: var(--sidebar-text-color, #f5faff) !important;
            background: var(--sidebar-panel-bg, #123a53) !important;
            background-color: var(--sidebar-panel-bg, #123a53) !important;
        }
        .sidebar-accordion-btn:not(.collapsed) {
            color: var(--sidebar-text-color, #f5faff) !important;
            background: var(--sidebar-panel-active-bg, #0e2d40) !important;
            background-color: var(--sidebar-panel-active-bg, #0e2d40) !important;
        }
        .sidebar-gradient .sidebar-accordion .accordion-button,
        .sidebar-gradient .sidebar-accordion .accordion-button:hover,
        .sidebar-gradient .sidebar-accordion .accordion-button:focus {
            background-color: var(--sidebar-panel-bg, #123a53) !important;
        }
        .sidebar-gradient .sidebar-accordion .accordion-button:not(.collapsed),
        .sidebar-gradient .sidebar-accordion .accordion-button:not(.collapsed):hover,
        .sidebar-gradient .sidebar-accordion .accordion-button:not(.collapsed):focus {
            background-color: var(--sidebar-panel-active-bg, #0e2d40) !important;
        }
        .sidebar-accordion-btn::after {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }
        .sidebar-accordion-btn i {
            font-size: 1rem !important;
            line-height: 1 !important;
            color: #e6f2ff !important;
        }
        .sidebar-link {
            color: var(--sidebar-text-color, #fff) !important;
            font-size: 0.98rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            padding: 0.7rem 0.85rem;
            border-radius: 0.7rem;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
        }
        .sidebar-link i {
            font-size: 1rem !important;
            line-height: 1 !important;
            vertical-align: middle;
            margin-right: 0.55rem;
            width: 1.1rem;
            text-align: center;
        }
        .sidebar-link.active,
        .sidebar-link:hover {
            background:
                linear-gradient(var(--sidebar-hover-overlay), var(--sidebar-hover-overlay)),
                var(--ui-sidebar-hover-bg, #0b5ed7);
            color: var(--sidebar-hover-text, #f5faff) !important;
            text-shadow: none;
            transform: translateX(2px);
        }
        .sidebar-link.active i,
        .sidebar-link:hover i,
        .sidebar-link:focus-visible,
        .sidebar-link:focus-visible i {
            color: var(--sidebar-hover-text, #f5faff) !important;
        }
        .sidebar-link:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }
        .sidebar-logout-link {
            margin-top: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: var(--sidebar-panel-bg, #123a53);
            font-weight: 600;
        }
        .sidebar-logout-link:hover,
        .sidebar-logout-link.active {
            background:
                linear-gradient(var(--sidebar-hover-overlay), var(--sidebar-hover-overlay)),
                var(--ui-sidebar-hover-bg, #0b5ed7);
        }
        .sidebar-accordion .accordion-collapse {
            transition: height 0.24s ease;
        }
        @media (prefers-reduced-motion: reduce) {
            .sidebar-accordion-btn,
            .sidebar-link,
            .sidebar-accordion .accordion-collapse {
                transition: none !important;
            }
            .sidebar-link.active,
            .sidebar-link:hover {
                transform: none;
            }
        }
    </style>
    <!-- Offcanvas para móvil -->
    <div class="offcanvas offcanvas-start d-md-none sidebar-gradient" tabindex="-1" id="sidebarToggle" aria-labelledby="sidebarToggleLabel">
        <div class="offcanvas-header" style="background: var(--ui-sidebar-bg, #0d6efd); color: var(--ui-sidebar-text, #fff);">
            <h5 class="offcanvas-title fw-bold" id="sidebarToggleLabel"><i class="bi bi-list me-2"></i>Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="p-3 mt-2">
                <?php $renderSidebarAccordion('sidebarMobileAccordion', $sidebarSections, $sidebarItems); ?>
                <a class="nav-link sidebar-link sidebar-logout-link mt-3" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i><span>Cerrar sesion</span></a>
            </nav>
            <div class="text-center text-white small py-2">© <?= date('Y') ?> <?= htmlspecialchars(ucwords(strtolower($config['nombre']))) ?></div>
        </div>
    </div>

</aside>
<main class="flex-grow-1" style="margin-left:250px;">