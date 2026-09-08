<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../auth/empresa_config.php';
require_once __DIR__ . '/../config/currency.php';
require_once __DIR__ . '/../config/ui_theme.php';

$usuarioSesion = $_SESSION['usuario'] ?? 'Usuario';

if (is_array($usuarioSesion)) {
    $nombreBase = trim((string)($usuarioSesion['nombre'] ?? ''));
    $apellidoBase = trim((string)($usuarioSesion['apellido'] ?? ''));
    $nombreUsuario = trim($nombreBase . ' ' . $apellidoBase);
    if ($nombreUsuario === '') {
        $nombreUsuario = trim((string)($usuarioSesion['usuario'] ?? ''));
    }
    if ($nombreUsuario === '') {
        $nombreUsuario = 'Usuario';
    }
} else {
    $nombreUsuario = trim((string)$usuarioSesion);
    if ($nombreUsuario === '') {
        $nombreUsuario = 'Usuario';
    }
}

// Convierte solo la primera letra en mayúscula, el resto en minúscula
$nombreFormateado = ucfirst(mb_strtolower($nombreUsuario, 'UTF-8'));
$appCurrency = currency_get_config($pdo);
$uiTheme = ui_theme_get_active($pdo);

$logoRaw = isset($config['logo']) ? trim((string)$config['logo']) : '';
if ($logoRaw === '' || preg_match('/^data:image\//i', $logoRaw)) {
    $logoRaw = '../uploads/empresa/logo_empresa.png';
}

$logoPublic = $logoRaw;
if (!preg_match('/^(https?:)?\/\//i', $logoPublic)) {
    $logoPublic = str_replace('\\', '/', $logoPublic);
    $logoPublic = preg_replace('#/+#', '/', $logoPublic);
    $logoPublic = preg_replace('#^(\./|\.\./)+#', '', $logoPublic);

    if (strpos($logoPublic, 'src/images/empresa/') === 0) {
        $logoPublic = 'uploads/empresa/' . substr($logoPublic, strlen('src/images/empresa/'));
    } elseif (strpos($logoPublic, 'images/empresa/') === 0) {
        $logoPublic = 'uploads/empresa/' . substr($logoPublic, strlen('images/empresa/'));
    } elseif (strpos($logoPublic, 'uploads/empresa/') !== 0) {
        $logoPublic = 'uploads/empresa/logo_empresa.png';
    }

    $logoPublic = rtrim((string)BASE_URL, '/\\') . '/../' . ltrim($logoPublic, '/');
}

$logoVersion = time();
if (!preg_match('/^(https?:)?\/\//i', $logoPublic)) {
    $logoLocalRel = preg_replace('#^' . preg_quote(rtrim((string)BASE_URL, '/\\') . '/../', '#') . '#', '', $logoPublic);
    $logoLocalAbs = dirname(__DIR__, 2) . '/' . ltrim((string)$logoLocalRel, '/');
    if (file_exists($logoLocalAbs)) {
        $logoVersion = (int)filemtime($logoLocalAbs);
    }
}

$logoTagSrc = $logoPublic . '?v=' . $logoVersion;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - <?= htmlspecialchars($config['nombre']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= htmlspecialchars($logoTagSrc, ENT_QUOTES, 'UTF-8') ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($logoTagSrc, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --ui-primary: <?= htmlspecialchars($uiTheme['primary'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-secondary: <?= htmlspecialchars($uiTheme['secondary'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-footer: <?= htmlspecialchars($uiTheme['footer'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-button: <?= htmlspecialchars($uiTheme['button_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-text: <?= htmlspecialchars($uiTheme['text'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-navbar-bg: <?= htmlspecialchars($uiTheme['navbar_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-navbar-text: <?= htmlspecialchars($uiTheme['navbar_text'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-navbar-hover-bg: <?= htmlspecialchars($uiTheme['navbar_hover_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-sidebar-bg: <?= htmlspecialchars($uiTheme['sidebar_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-sidebar-text: <?= htmlspecialchars($uiTheme['sidebar_text'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-sidebar-hover-bg: <?= htmlspecialchars($uiTheme['sidebar_hover_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-footer-bg: <?= htmlspecialchars($uiTheme['footer_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-footer-text: <?= htmlspecialchars($uiTheme['footer_text'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-button-text: <?= htmlspecialchars($uiTheme['button_text'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-body-bg: <?= htmlspecialchars($uiTheme['body_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --ui-card-bg: <?= htmlspecialchars($uiTheme['card_bg'], ENT_QUOTES, 'UTF-8') ?>;
        }

        body {
            background: var(--ui-body-bg);
            color: var(--ui-text);
        }

        .sidebar-custom {
            background: var(--ui-sidebar-bg);
            color: var(--ui-sidebar-text);
        }

        .sidebar-custom .nav-link,
        .sidebar-custom .nav-link i {
            color: var(--ui-sidebar-text);
        }

        .sidebar-custom .nav-link.active,
        .sidebar-custom .nav-link:hover {
            background: var(--ui-sidebar-hover-bg);
            color: var(--ui-sidebar-text);
        }

        .sidebar-custom .nav-link {
            font-size: 1.15rem;
            margin-bottom: 0.7rem;
            padding: 1rem 1.5rem;
            border-radius: 0.7rem;
            transition: background 0.2s;
        }

        .sidebar-custom .nav-link i {
            font-size: 1.6rem;
            vertical-align: middle;
            margin-right: 0.7rem;
        }

        @media (max-width: 991.98px) {
            #sidebarMenu {
                position: fixed;
                top: 0;
                left: -270px;
                width: 270px;
                height: 100%;
                z-index: 1045;
                transition: left 0.3s;
            }

            #sidebarMenu.show {
                left: 0;
            }

            header {
                z-index: 1050;
                position: relative;
            }

            @media (max-width: 767.98px) {
                main[style] {
                    margin-left: 0 !important;
                }
            }

        }

        .color-input {
            width: 32px;
            height: 32px;
            border: none;
        }

        .valores-ref-group {
            margin-bottom: 0.25rem;
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .valores-ref-group input[type="text"] {
            width: 100px;
        }

        .valores-ref-group input[type="text"].desc {
            width: 120px;
        }

        textarea.form-control {
            min-width: 180px;
            min-height: 32px;
        }

        .opciones-input {
            min-width: 180px;
        }

        #formula-panel {
            min-width: 220px;
            min-height: 40px;
            position: absolute;
            z-index: 1000;
            background: #fff;
            border: 1px solid #ccc;
            padding: 7px;
            border-radius: 7px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 340px;
        }

        .btn-cotizar-cta-global {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            border: 1px solid rgba(21, 115, 71, 0.9);
            color: #fff !important;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.28);
        }

        .btn-cotizar-cta-global:hover,
        .btn-cotizar-cta-global:focus {
            background: linear-gradient(135deg, #20a866 0%, #198754 100%);
            border-color: #157347;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
        }
        
    </style>
    <script>
        window.APP_CURRENCY = <?= json_encode($appCurrency, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.formatMoney = function(amount) {
            const cfg = window.APP_CURRENCY || {
                symbol: 'S/',
                position: 'prefix',
                decimals: 2,
                decimal_separator: '.',
                thousands_separator: ','
            };
            const numeric = Number(amount || 0);
            const fixed = Number.isFinite(numeric) ? numeric.toFixed(Number(cfg.decimals || 2)) : '0.00';
            const parts = fixed.split('.');
            const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousands_separator || ',');
            const decimalPart = (cfg.decimals || 0) > 0 ? (cfg.decimal_separator || '.') + (parts[1] || '') : '';
            const amountText = integerPart + decimalPart;
            return (cfg.position === 'suffix')
                ? (amountText + ' ' + (cfg.symbol || ''))
                : ((cfg.symbol || '') + ' ' + amountText);
        };
    </script>
</head>

<body>

    <header class="header-gradient shadow mb-3 position-relative" style="z-index: 1050;">
        <div class="container-fluid d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center">
                <div class="header-logo-box me-3">
                    <img src="<?= htmlspecialchars($logoTagSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($config['nombre']) ?>" style="height:64px; border-radius:16px; box-shadow:0 2px 12px #764ba233;">
                </div>
                <div>
                    <span class="fw-bold text-white" style="font-size:1.5rem; letter-spacing:1px;">
                        <?= htmlspecialchars($config['nombre']) ?>
                    </span><br>
                    <span class="text-white" style="font-size:1.1rem;">Bienvenido, <?= htmlspecialchars($nombreFormateado) ?>!</span>
                </div>
            </div>
            <!-- Botón solo visible en móvil -->
            <button class="btn btn-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarToggle" aria-controls="sidebarToggle" aria-label="Menú">
                <i class="bi bi-list fs-2"></i>
            </button>
        </div>
    </header>
    <style>
        .header-gradient {
            background: var(--ui-navbar-bg);
            border-radius: 0 0 24px 24px;
        }
        .header-gradient .text-white,
        .header-gradient .fw-bold {
            color: var(--ui-navbar-text) !important;
        }
        .btn-primary,
        .btn-success {
            background: var(--ui-button) !important;
            border-color: var(--ui-button) !important;
            color: var(--ui-button-text) !important;
        }
        .btn-primary:hover,
        .btn-success:hover {
            filter: brightness(0.92);
        }
        .header-logo-box {
            background: #fff;
            border-radius: 16px;
            padding: 6px;
            box-shadow: 0 2px 12px #667eea22;
        }
        @media (max-width: 767.98px) {
            .header-gradient {
                position: sticky;
                top: 0;
                z-index: 1100 !important;
                margin-bottom: 0 !important;
                border-radius: 0 0 18px 18px;
            }
        }
    </style>
    <script>
        (function () {
            function syncHeaderOffset() {
                var header = document.querySelector('.header-gradient');
                if (!header) return;
                var height = Math.ceil(header.getBoundingClientRect().height || 0);
                document.documentElement.style.setProperty('--app-header-offset', height + 'px');
            }

            document.addEventListener('DOMContentLoaded', syncHeaderOffset);
            window.addEventListener('load', syncHeaderOffset);
            window.addEventListener('resize', syncHeaderOffset);
        })();
    </script>