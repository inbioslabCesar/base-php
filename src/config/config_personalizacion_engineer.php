<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
    return;
}

$presets = ui_theme_predefined();
$empresaIdGet = (int)($_GET['empresa_cfg_id'] ?? 0);
$stmtEmpresas = $pdo->query('SELECT id, nombre, dominio FROM config_empresa ORDER BY id ASC');
$empresas = $stmtEmpresas ? ($stmtEmpresas->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

$empresa = ui_theme_fetch_company_config($pdo, $empresaIdGet > 0 ? $empresaIdGet : null);
$empresaActualId = (int)($empresa['id'] ?? 0);
$portalEstilo = strtolower(trim((string)($empresa['portal_publico_estilo'] ?? 'clasico')));
if ($portalEstilo === 'premium') {
    $portalEstilo = 'premium_a';
}
if (!in_array($portalEstilo, ['clasico', 'premium_a', 'premium_b'], true)) {
    $portalEstilo = 'clasico';
}

$scopeLabel = 'Global por dominio';
if ($empresaActualId > 0) {
    $scopeLabel = 'Empresa ID ' . $empresaActualId;
}

$active = strtolower(trim((string)($empresa['tema_ui_activo'] ?? '')));
if ($active === '' || !isset($presets[$active])) {
    $active = 'corporativo_azul';
}
?>

<div class="container mt-4">
    <h4>Personalizacion Visual (Engineer)</h4>
    <p class="text-muted mb-3">Define apariencias preconfiguradas para aplicar un estilo completo y coherente en navbar, sidebar, botones, fondos y texto.</p>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars((string)$_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="alert alert-warning">Alcance actual: <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?><?= !empty($empresa['dominio']) ? ' (' . htmlspecialchars((string)$empresa['dominio'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>.</div>

    <?php if (!empty($empresas)): ?>
        <form method="GET" action="dashboard.php" class="row g-2 mb-3 align-items-end">
            <input type="hidden" name="vista" value="config_personalizacion_engineer">
            <div class="col-12 col-md-6 col-lg-5">
                <label for="empresa_cfg_id" class="form-label">Empresa objetivo</label>
                <select class="form-select" name="empresa_cfg_id" id="empresa_cfg_id">
                    <?php foreach ($empresas as $emp): ?>
                        <?php
                        $id = (int)($emp['id'] ?? 0);
                        $nombre = trim((string)($emp['nombre'] ?? 'Empresa'));
                        $dominio = trim((string)($emp['dominio'] ?? ''));
                        $label = $nombre . ($dominio !== '' ? ' - ' . $dominio : '');
                        ?>
                        <option value="<?= $id ?>" <?= $id === $empresaActualId ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Cambiar empresa</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="theme-live-preview mb-4" id="themeLivePreview" aria-live="polite">
        <div class="preview-navbar">Navbar / Header</div>
        <div class="preview-content">
            <aside class="preview-sidebar">Sidebar</aside>
            <section class="preview-main">
                <h6 class="mb-2">Vista previa en vivo</h6>
                <p class="mb-2">Selecciona un preset para ver inmediatamente como cambia el contexto visual.</p>
                <button type="button" class="preview-button">Boton principal</button>
            </section>
        </div>
        <div class="preview-footer">Footer</div>
    </div>

    <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>dashboard.php?action=config_personalizacion_engineer_guardar" autocomplete="off">
        <input type="hidden" name="empresa_cfg_id" value="<?= (int)$empresaActualId ?>">
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <label for="portal_publico_estilo" class="form-label fw-semibold">Experiencia del Portal Publico</label>
                <select name="portal_publico_estilo" id="portal_publico_estilo" class="form-select">
                    <option value="clasico" <?= $portalEstilo === 'clasico' ? 'selected' : '' ?>>Clasico (actual)</option>
                    <option value="premium_a" <?= $portalEstilo === 'premium_a' ? 'selected' : '' ?>>Premium A (editorial y limpio)</option>
                    <option value="premium_b" <?= $portalEstilo === 'premium_b' ? 'selected' : '' ?>>Premium B (impacto visual alto)</option>
                </select>
                <small class="text-muted d-block mt-1">Puedes combinar el estilo del portal con cualquier preset de color.</small>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label fw-semibold">Vista previa de experiencia</label>
                <div class="portal-style-switcher" id="portalStyleSwitcher">
                    <button type="button" class="portal-style-option" data-style-option="clasico">Clasico</button>
                    <button type="button" class="portal-style-option" data-style-option="premium_a">Premium A</button>
                    <button type="button" class="portal-style-option" data-style-option="premium_b">Premium B</button>
                </div>
                <div class="portal-style-preview" id="portalStylePreview">
                    <div class="portal-style-frame" data-style-frame="clasico">
                        <div class="ps-navbar ps-clasico-nav"></div>
                        <div class="ps-body ps-clasico-body">
                            <div class="ps-block"></div>
                            <div class="ps-block short"></div>
                        </div>
                        <div class="ps-footer ps-clasico-footer"></div>
                    </div>
                    <div class="portal-style-frame" data-style-frame="premium_a" hidden>
                        <div class="ps-navbar ps-premiuma-nav"></div>
                        <div class="ps-body ps-premiuma-body">
                            <div class="ps-hero"></div>
                            <div class="ps-grid">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                        <div class="ps-footer ps-premiuma-footer"></div>
                    </div>
                    <div class="portal-style-frame" data-style-frame="premium_b" hidden>
                        <div class="ps-navbar ps-premiumb-nav"></div>
                        <div class="ps-body ps-premiumb-body">
                            <div class="ps-kpi-row"><span></span><span></span><span></span><span></span></div>
                            <div class="ps-block"></div>
                        </div>
                        <div class="ps-footer ps-premiumb-footer"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($presets as $key => $preset): ?>
                <?php
                $selected = ($key === $active);
                $principal = htmlspecialchars($preset['primary'], ENT_QUOTES, 'UTF-8');
                $secundario = htmlspecialchars($preset['secondary'], ENT_QUOTES, 'UTF-8');
                $footer = htmlspecialchars($preset['footer'], ENT_QUOTES, 'UTF-8');
                $button = htmlspecialchars($preset['button'], ENT_QUOTES, 'UTF-8');
                $text = htmlspecialchars($preset['text'], ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-md-4 col-lg-3">
                    <label class="theme-card <?= $selected ? 'active' : '' ?>" for="tema_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                        <input class="form-check-input theme-radio" type="radio" name="tema_ui_activo" id="tema_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'checked' : '' ?>
                            data-primary="<?= $principal ?>"
                            data-secondary="<?= $secundario ?>"
                            data-footer="<?= $footer ?>"
                            data-button="<?= $button ?>"
                            data-text="<?= $text ?>">
                        <div class="theme-title"><?= htmlspecialchars($preset['label']) ?></div>
                        <div class="theme-swatches">
                            <span style="background: <?= $principal ?>" title="Principal"></span>
                            <span style="background: <?= $secundario ?>" title="Secundario"></span>
                            <span style="background: <?= $footer ?>" title="Footer"></span>
                            <span style="background: <?= $button ?>" title="Botones"></span>
                            <span style="background: <?= $text ?>" title="Texto"></span>
                        </div>
                        <div class="theme-mini-preview" style="background: <?= $secundario ?>; border-color: <?= $footer ?>;">
                            <div class="mini-header" style="background: <?= $principal ?>"></div>
                            <div class="mini-button" style="background: <?= $button ?>"></div>
                            <div class="mini-footer" style="background: <?= $footer ?>"></div>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-dark">Guardar apariencia activa</button>
            <a class="btn btn-outline-secondary" href="dashboard.php?vista=config_empresa_datos&empresa_cfg_id=<?= (int)$empresaActualId ?>">Ir a ajuste fino de empresa</a>
        </div>
    </form>
</div>

<style>
.theme-card {
    display: block;
    border: 2px solid #dce3ee;
    border-radius: 12px;
    padding: 10px;
    background: #fff;
    cursor: pointer;
    transition: .2s ease;
}
.theme-card:hover { border-color: #93b4f4; transform: translateY(-1px); }
.theme-card.active { border-color: #2b6de0; box-shadow: 0 0 0 3px rgba(43,109,224,.15); }
.theme-card input { margin-bottom: 8px; }
.theme-title { font-weight: 700; font-size: .95rem; margin-bottom: 6px; }
.theme-swatches { display: flex; gap: 6px; margin-bottom: 8px; }
.theme-swatches span { width: 22px; height: 22px; border-radius: 6px; border: 1px solid rgba(0,0,0,.08); }
.theme-mini-preview { height: 78px; border: 1px solid; border-radius: 10px; padding: 6px; }
.mini-header { height: 16px; border-radius: 6px; margin-bottom: 8px; }
.mini-button { height: 14px; width: 46%; border-radius: 7px; margin-bottom: 10px; }
.mini-footer { height: 14px; border-radius: 7px; margin-top: auto; }

.theme-live-preview {
    border: 1px solid #d7deea;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
.preview-navbar,
.preview-footer {
    padding: 10px 14px;
    font-weight: 600;
}
.preview-content {
    display: flex;
    min-height: 140px;
}
.preview-sidebar {
    width: 28%;
    min-width: 140px;
    padding: 12px;
    font-weight: 600;
}
.preview-main {
    flex: 1;
    padding: 14px;
}
.preview-button {
    border: 0;
    border-radius: 8px;
    padding: 8px 14px;
    font-weight: 600;
}

.portal-style-switcher {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.portal-style-option {
    border: 1px solid #c9d4e6;
    background: #fff;
    color: #1f2a3b;
    border-radius: 999px;
    padding: 5px 12px;
    font-size: .86rem;
    font-weight: 600;
}

.portal-style-option.active {
    background: #143a66;
    border-color: #143a66;
    color: #fff;
}

.portal-style-preview {
    border: 1px solid #d8e0ef;
    border-radius: 12px;
    padding: 10px;
    background: #fff;
}

.portal-style-frame {
    border: 1px solid #d7deea;
    border-radius: 10px;
    overflow: hidden;
}

.ps-navbar,
.ps-footer {
    height: 20px;
}

.ps-body {
    padding: 8px;
}

.ps-block {
    height: 18px;
    border-radius: 6px;
    background: rgba(0,0,0,.09);
    margin-bottom: 8px;
}

.ps-block.short {
    width: 65%;
}

.ps-clasico-nav,
.ps-clasico-footer {
    background: #2f7a63;
}

.ps-clasico-body {
    background: #ecf8f2;
}

.ps-premiuma-nav,
.ps-premiuma-footer {
    background: #1f4f82;
}

.ps-premiuma-body {
    background: #f3f9ff;
}

.ps-hero {
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(120deg, #1f4f82, #173a60);
    margin-bottom: 8px;
}

.ps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}

.ps-grid span {
    height: 18px;
    border-radius: 6px;
    background: rgba(0,0,0,.1);
}

.ps-premiumb-nav,
.ps-premiumb-footer {
    background: #262f57;
}

.ps-premiumb-body {
    background: #eef2fb;
}

.ps-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-bottom: 8px;
}

.ps-kpi-row span {
    height: 14px;
    border-radius: 5px;
    background: rgba(38, 47, 87, 0.25);
}

@media (max-width: 767.98px) {
    .preview-content {
        flex-direction: column;
    }
    .preview-sidebar {
        width: 100%;
        min-width: 100%;
    }
}
</style>

<script>
(function () {
    const cards = Array.prototype.slice.call(document.querySelectorAll('.theme-card'));
    const radios = Array.prototype.slice.call(document.querySelectorAll('.theme-radio'));
    const live = document.getElementById('themeLivePreview');
    if (!live || radios.length === 0) {
        return;
    }

    const navbar = live.querySelector('.preview-navbar');
    const footer = live.querySelector('.preview-footer');
    const sidebar = live.querySelector('.preview-sidebar');
    const main = live.querySelector('.preview-main');
    const button = live.querySelector('.preview-button');
    const styleSelect = document.getElementById('portal_publico_estilo');
    const styleOptions = Array.prototype.slice.call(document.querySelectorAll('[data-style-option]'));
    const styleFrames = Array.prototype.slice.call(document.querySelectorAll('[data-style-frame]'));

    function luminance(hex) {
        const clean = String(hex || '').replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
            return 1;
        }
        const r = parseInt(clean.slice(0, 2), 16);
        const g = parseInt(clean.slice(2, 4), 16);
        const b = parseInt(clean.slice(4, 6), 16);
        return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    }

    function textFor(bg) {
        return luminance(bg) > 0.58 ? '#1f2937' : '#f9fbff';
    }

    function applyPreview(input) {
        const primary = input.getAttribute('data-primary') || '#1f4f82';
        const secondary = input.getAttribute('data-secondary') || '#e9f3fb';
        const footerBg = input.getAttribute('data-footer') || '#173a60';
        const buttonBg = input.getAttribute('data-button') || '#2f74bd';
        const text = input.getAttribute('data-text') || '#1c2a3b';

        navbar.style.background = primary;
        navbar.style.color = textFor(primary);
        footer.style.background = footerBg;
        footer.style.color = textFor(footerBg);
        sidebar.style.background = primary;
        sidebar.style.color = textFor(primary);
        main.style.background = secondary;
        main.style.color = text;
        button.style.background = buttonBg;
        button.style.color = textFor(buttonBg);
        live.style.borderColor = footerBg;
    }

    function activateCard(input) {
        cards.forEach(function (card) {
            card.classList.remove('active');
        });
        const card = input.closest('.theme-card');
        if (card) {
            card.classList.add('active');
        }
    }

    radios.forEach(function (input) {
        input.addEventListener('change', function () {
            applyPreview(input);
            activateCard(input);
        });
    });

    function applyPortalStylePreview(style) {
        const normalized = String(style || 'clasico').toLowerCase();
        styleFrames.forEach(function (frame) {
            frame.hidden = frame.getAttribute('data-style-frame') !== normalized;
        });
        styleOptions.forEach(function (opt) {
            opt.classList.toggle('active', opt.getAttribute('data-style-option') === normalized);
        });
    }

    styleOptions.forEach(function (opt) {
        opt.addEventListener('click', function () {
            if (!styleSelect) {
                return;
            }
            const style = opt.getAttribute('data-style-option') || 'clasico';
            styleSelect.value = style;
            applyPortalStylePreview(style);
        });
    });

    if (styleSelect) {
        styleSelect.addEventListener('change', function () {
            applyPortalStylePreview(styleSelect.value);
        });
        applyPortalStylePreview(styleSelect.value);
    }

    const initial = document.querySelector('.theme-radio:checked') || radios[0];
    if (initial) {
        applyPreview(initial);
        activateCard(initial);
    }
})();
</script>
