<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$empresaIdGet = (int)($_GET['empresa_cfg_id'] ?? 0);
$empresa = ui_theme_fetch_company_config($pdo, $empresaIdGet > 0 ? $empresaIdGet : null);
$empresa = is_array($empresa) ? $empresa : [];
$empresaActualId = (int)($empresa['id'] ?? 0);

// Valores por defecto
$logo = !empty($empresa['logo']) ? $empresa['logo'] : '../uploads/empresa/logo_empresa.png';
$firma = !empty($empresa['firma']) ? $empresa['firma'] : '../uploads/empresa/firma.png';
$suggestedPalette = [
    'color_principal' => '#294f7a',
    'color_secundario' => '#e8f4fb',
    'color_footer' => '#1d3552',
    'color_botones' => '#3cc0cf',
    'color_texto' => '#24344a',
];

$normalizeHexColor = static function ($value): string {
    $v = strtolower(trim((string)$value));
    if (preg_match('/^#[0-9a-f]{6}$/', $v)) {
        return $v;
    }
    return '';
};

$bootstrapLegacyDefaults = [
    'color_principal' => '#0d6efd',
    'color_secundario' => '#f8f9fa',
    'color_footer' => '#343a40',
    'color_botones' => '#198754',
    'color_texto' => '#212529',
];

$pickCompanyColor = static function (string $field) use ($empresa, $suggestedPalette, $bootstrapLegacyDefaults, $normalizeHexColor): string {
    $stored = $normalizeHexColor($empresa[$field] ?? '');
    if ($stored === '' || $stored === $bootstrapLegacyDefaults[$field]) {
        return $suggestedPalette[$field];
    }
    return $stored;
};

$color_principal = $pickCompanyColor('color_principal');
$color_secundario = $pickCompanyColor('color_secundario');
$color_footer = $pickCompanyColor('color_footer');
$color_botones = $pickCompanyColor('color_botones');
$color_texto = $pickCompanyColor('color_texto');
$logo_fondo_navbar = $normalizeHexColor($empresa['logo_fondo_navbar'] ?? '') ?: '#ffffff';
$tamano_letra = $empresa['tamano_letra'] ?? '1rem';
$frase_promocion = $empresa['frase_promocion'] ?? '';
$oferta_mes = $empresa['oferta_mes'] ?? '';

// Arrays seguros
$imagenes_carrusel = [];
if (!empty($empresa['imagenes_carrusel'])) {
    $tmp = json_decode($empresa['imagenes_carrusel'], true);
    if (is_array($tmp)) $imagenes_carrusel = $tmp;
}
$imagenes_institucionales = [];
if (!empty($empresa['imagenes_institucionales'])) {
    $tmp = json_decode($empresa['imagenes_institucionales'], true);
    if (is_array($tmp)) $imagenes_institucionales = $tmp;
}
$servicios = [];
if (!empty($empresa['servicios'])) {
    $tmp = json_decode($empresa['servicios'], true);
    if (is_array($tmp)) $servicios = $tmp;
}
$testimonios = [];
if (!empty($empresa['testimonios'])) {
    $tmp = json_decode($empresa['testimonios'], true);
    if (is_array($tmp)) $testimonios = $tmp;
}
$redes_sociales = [];
if (!empty($empresa['redes_sociales'])) {
    $tmp = json_decode($empresa['redes_sociales'], true);
    if (is_array($tmp)) $redes_sociales = $tmp;
}
$menu_inicio = $empresa['menu_inicio'] ?? 'Inicio';
$menu_servicios = $empresa['menu_servicios'] ?? 'Servicios';
$menu_testimonios = $empresa['menu_testimonios'] ?? 'Testimonios';
$menu_contacto = $empresa['menu_contacto'] ?? 'Contacto';
$moneda_codigo = strtoupper(trim((string)($empresa['moneda_codigo'] ?? 'PEN')));
$moneda_simbolo = trim((string)($empresa['moneda_simbolo'] ?? 'S/'));
$moneda_posicion = strtolower(trim((string)($empresa['moneda_posicion'] ?? 'prefix')));
if (!in_array($moneda_posicion, ['prefix', 'suffix'], true)) {
    $moneda_posicion = 'prefix';
}
$moneda_decimales = (int)($empresa['moneda_decimales'] ?? 2);
if ($moneda_decimales < 0 || $moneda_decimales > 4) {
    $moneda_decimales = 2;
}
$moneda_separador_decimal = (string)($empresa['moneda_separador_decimal'] ?? '.');
$moneda_separador_miles = (string)($empresa['moneda_separador_miles'] ?? ',');

$toPreviewUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $path) || strpos($path, 'data:') === 0 || strpos($path, '/') === 0) {
        return $path;
    }
    return $path;
};

$srcDir = realpath(__DIR__ . '/..');
if ($srcDir === false) {
    $srcDir = __DIR__ . '/..';
}

$projectRoot = realpath(__DIR__ . '/../..');
if ($projectRoot === false) {
    $projectRoot = __DIR__ . '/../..';
}

$resolveStoredAbsolutePath = static function (string $storedPath) use ($srcDir, $projectRoot): string {
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));
    if (strpos($normalized, '../uploads/') === 0) {
        $normalized = substr($normalized, 3);
    }
    if (strpos($normalized, 'uploads/') === 0) {
        return rtrim((string)$projectRoot, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }
    return rtrim((string)$srcDir, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
};

$logoAbs = $resolveStoredAbsolutePath((string)$logo);
if (!is_file($logoAbs)) {
    $logo = '';
}

$firmaAbs = $resolveStoredAbsolutePath((string)$firma);
if (!is_file($firmaAbs)) {
    $firma = '';
}

$logoUrl = $toPreviewUrl((string)$logo);
$firmaUrl = $toPreviewUrl((string)$firma);

$ubicaciones = [];
if (!empty($empresa['ubicaciones_json'])) {
    $tmpUb = json_decode((string)$empresa['ubicaciones_json'], true);
    if (is_array($tmpUb)) {
        $ubicaciones = $tmpUb;
    }
}
if (empty($ubicaciones)) {
    $ubicaciones[] = [
        'nombre' => 'Sede principal',
        'direccion' => (string)($empresa['direccion'] ?? ''),
        'celular' => (string)($empresa['celular'] ?? ''),
        'telefonos' => !empty($empresa['celular']) ? [(string)$empresa['celular']] : [],
        'maps_embed' => (string)($empresa['maps_embed'] ?? ''),
    ];
}

foreach ($ubicaciones as &$ubItem) {
    if (!is_array($ubItem)) {
        continue;
    }
    $telefonosUb = [];
    if (!empty($ubItem['telefonos']) && is_array($ubItem['telefonos'])) {
        foreach ($ubItem['telefonos'] as $telItem) {
            $telStr = trim((string)$telItem);
            if ($telStr !== '') {
                $telefonosUb[] = $telStr;
            }
        }
    }
    $celularUb = trim((string)($ubItem['celular'] ?? ''));
    if ($celularUb !== '' && !in_array($celularUb, $telefonosUb, true)) {
        array_unshift($telefonosUb, $celularUb);
    }
    $ubItem['telefonos'] = array_values(array_unique($telefonosUb));
    if (!empty($ubItem['telefonos']) && empty($ubItem['celular'])) {
        $ubItem['celular'] = (string)$ubItem['telefonos'][0];
    }
}
unset($ubItem);
$ubicacionesJsonPretty = json_encode($ubicaciones, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($ubicacionesJsonPretty) || $ubicacionesJsonPretty === '') {
    $ubicacionesJsonPretty = '[]';
}
?>
<div class="container mt-4">
    <h4>Configuración de Empresa</h4>
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>
    <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>dashboard.php?action=config_empresa_guardar" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="empresa_cfg_id" value="<?= (int)$empresaActualId ?>">
        <div class="row">
            <!-- Datos básicos -->
            <div class="col-md-6 mb-3">
                <label for="dominio" class="form-label">Dominio (ej: ejemplo.com)</label>
                <input type="text" class="form-control" id="dominio" name="dominio"
                    value="<?= htmlspecialchars($empresa['dominio'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="ruc" class="form-label">RUC *</label>
                <input type="text" class="form-control" id="ruc" name="ruc"
                    value="<?= htmlspecialchars($empresa['ruc'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="direccion" class="form-label">Dirección *</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>" required>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Sucursales y ubicaciones (multi sede)</label>
                <div id="ubicacionesBuilder" class="border rounded p-2 mb-2"></div>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddUbicacion">Agregar sucursal</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSyncUbicaciones">Actualizar JSON</button>
                </div>
                <textarea class="form-control" id="ubicaciones_json" name="ubicaciones_json" rows="6" spellcheck="false"><?= htmlspecialchars($ubicacionesJsonPretty) ?></textarea>
                <div class="form-text">Formato JSON: [{"nombre":"Sede Centro","direccion":"...","telefonos":["519XXXXXXXX","519YYYYYYYY"],"maps_embed":"https://www.google.com/maps/embed?pb=..."}]</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" class="form-control" id="celular" name="celular"
                    value="<?= htmlspecialchars($empresa['celular'] ?? '') ?>">
            </div>
            <div class="col-12"><hr></div>
            <div class="col-md-3 mb-3">
                <label for="moneda_codigo" class="form-label">Código moneda (ISO)</label>
                <input type="text" class="form-control" id="moneda_codigo" name="moneda_codigo" maxlength="10"
                    value="<?= htmlspecialchars($moneda_codigo) ?>" placeholder="PEN, USD, EUR...">
            </div>
            <div class="col-md-2 mb-3">
                <label for="moneda_simbolo" class="form-label">Símbolo</label>
                <input type="text" class="form-control" id="moneda_simbolo" name="moneda_simbolo" maxlength="10"
                    value="<?= htmlspecialchars($moneda_simbolo) ?>" placeholder="S/">
            </div>
            <div class="col-md-2 mb-3">
                <label for="moneda_posicion" class="form-label">Posición</label>
                <select class="form-select" id="moneda_posicion" name="moneda_posicion">
                    <option value="prefix" <?= $moneda_posicion === 'prefix' ? 'selected' : '' ?>>Antes (S/ 10.00)</option>
                    <option value="suffix" <?= $moneda_posicion === 'suffix' ? 'selected' : '' ?>>Después (10.00 $)</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="moneda_decimales" class="form-label">Decimales</label>
                <input type="number" class="form-control" id="moneda_decimales" name="moneda_decimales" min="0" max="4"
                    value="<?= htmlspecialchars((string)$moneda_decimales) ?>">
            </div>
            <div class="col-md-1 mb-3">
                <label for="moneda_separador_decimal" class="form-label">Sep. dec.</label>
                <input type="text" class="form-control" id="moneda_separador_decimal" name="moneda_separador_decimal" maxlength="1"
                    value="<?= htmlspecialchars($moneda_separador_decimal) ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label for="moneda_separador_miles" class="form-label">Sep. miles</label>
                <input type="text" class="form-control" id="moneda_separador_miles" name="moneda_separador_miles" maxlength="1"
                    value="<?= htmlspecialchars($moneda_separador_miles) ?>">
            </div>
            <!-- Colores y tipografía -->
            <div class="col-md-3 mb-3">
                <label for="color_principal" class="form-label">Color principal</label>
                <input type="color" class="form-control form-control-color" id="color_principal" name="color_principal"
                    value="<?= htmlspecialchars($color_principal) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_secundario" class="form-label">Color secundario</label>
                <input type="color" class="form-control form-control-color" id="color_secundario" name="color_secundario"
                    value="<?= htmlspecialchars($color_secundario) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_footer" class="form-label">Color del footer</label>
                <input type="color" class="form-control form-control-color" id="color_footer" name="color_footer"
                    value="<?= htmlspecialchars($color_footer) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="color_botones" class="form-label">Color de los botones</label>
                <input type="color" class="form-control form-control-color" id="color_botones" name="color_botones"
                    value="<?= htmlspecialchars($color_botones) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="color_texto" class="form-label">Color del texto</label>
                <input type="color" class="form-control form-control-color" id="color_texto" name="color_texto"
                    value="<?= htmlspecialchars($color_texto) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="logo_fondo_navbar" class="form-label">Fondo del logo (navbar)</label>
                <input type="color" class="form-control form-control-color" id="logo_fondo_navbar" name="logo_fondo_navbar"
                    value="<?= htmlspecialchars($logo_fondo_navbar) ?>">
                <small class="text-muted">Se aplica detrás del logo en el header público para igualar logos con fondo blanco u otros tonos.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label for="tamano_letra" class="form-label">Tamaño de letra (ej: 1rem, 18px)</label>
                <input type="text" class="form-control" id="tamano_letra" name="tamano_letra"
                    value="<?= htmlspecialchars($tamano_letra) ?>">
            </div>
            <div class="col-md-12 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2 p-2 border rounded">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAplicarPaletaLogo">
                        Aplicar paleta sugerida
                    </button>
                    <span class="small text-muted">Paleta corporativa sugerida:</span>
                    <span class="badge" style="background:#294f7a;color:#fff;">Principal</span>
                    <span class="badge" style="background:#e8f4fb;color:#24344a;border:1px solid #c8dcea;">Secundario</span>
                    <span class="badge" style="background:#1d3552;color:#fff;">Footer</span>
                    <span class="badge" style="background:#3cc0cf;color:#103240;">Botones</span>
                    <span class="badge" style="background:#24344a;color:#fff;">Texto</span>
                </div>
            </div>
            <!-- Logo y firma -->
            <div class="col-md-6 mb-3 text-center">
                <label class="form-label fw-bold">Logo actual:</label><br>
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($logoUrl) ?>?v=<?= time() ?>" alt="Logo de la empresa" style="max-height: 80px;">
                <?php else: ?>
                    <span class="text-muted">Sin logo cargado</span>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3 text-center">
                <label class="form-label fw-bold">Firma actual:</label><br>
                <?php if ($firmaUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($firmaUrl) ?>?v=<?= time() ?>" alt="Firma de la empresa" style="max-height: 80px;">
                <?php else: ?>
                    <span class="text-muted">Sin firma cargada</span>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="logo" class="form-label">Actualizar logo (PNG):</label>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/png">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="quitar_logo" name="quitar_logo" value="1">
                    <label class="form-check-label" for="quitar_logo">Quitar logo actual</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="firma" class="form-label">Actualizar firma (PNG):</label>
                <input type="file" class="form-control" id="firma" name="firma" accept="image/png">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="quitar_firma" name="quitar_firma" value="1">
                    <label class="form-check-label" for="quitar_firma">Quitar firma actual</label>
                </div>
            </div>
            <!-- Frase y oferta -->
            <div class="col-md-12 mb-3">
                <label for="frase_promocion" class="form-label">Frase promocional</label>
                <input type="text" class="form-control" id="frase_promocion" name="frase_promocion"
                    value="<?= htmlspecialchars($frase_promocion) ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label for="oferta_mes" class="form-label">Oferta del mes</label>
                <input type="text" class="form-control" id="oferta_mes" name="oferta_mes"
                    value="<?= htmlspecialchars($oferta_mes) ?>">
            </div>
            <!-- Imágenes del carrusel -->
            <div class="col-md-12 mb-3">
                <label class="form-label">Imágenes del carrusel actuales:</label>
                <div class="d-flex flex-wrap">
                    <?php
                    if (!is_array($imagenes_carrusel)) $imagenes_carrusel = [];
                    foreach ($imagenes_carrusel as $idx => $img): ?>
                        <div style="position:relative; margin:5px;">
                            <img src="<?= htmlspecialchars($img) ?>?v=<?= time() ?>" style="max-height:60px;">
                            <input type="checkbox" name="eliminar_carrusel[]" value="<?= $idx ?>" style="position:absolute;top:0;right:0;">
                            <small style="display:block;text-align:center;">Eliminar</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label for="imagenes_carrusel" class="form-label mt-2">Agregar nuevas imágenes (PNG/JPG/JPEG):</label>
                <input type="file" class="form-control" id="imagenes_carrusel" name="imagenes_carrusel[]" accept="image/png, image/jpeg" multiple>
            </div>

            <!-- Imágenes institucionales -->
            <div class="col-md-12 mb-3">
                <label class="form-label">Imágenes institucionales actuales:</label>
                <div class="d-flex flex-wrap">
                    <?php
                    if (!is_array($imagenes_institucionales)) $imagenes_institucionales = [];
                    foreach ($imagenes_institucionales as $idx => $img): ?>
                        <div style="position:relative; margin:5px;">
                            <img src="<?= htmlspecialchars($img) ?>?v=<?= time() ?>" style="max-height:60px; border-radius:8px;">
                            <input type="checkbox" name="eliminar_institucional[]" value="<?= $idx ?>" style="position:absolute;top:0;right:0;">
                            <small style="display:block;text-align:center;">Eliminar</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label for="imagenes_institucionales" class="form-label mt-2">Agregar nuevas imágenes (PNG/JPG/JPEG):</label>
                <input type="file" class="form-control" id="imagenes_institucionales" name="imagenes_institucionales[]" accept="image/png, image/jpeg" multiple>
            </div>

            <!-- Servicios (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="servicios" class="form-label">Servicios (formato JSON: [{"titulo":"...","descripcion":"..."}])</label>
                <textarea class="form-control" id="servicios" name="servicios" rows="3"><?= htmlspecialchars(json_encode($servicios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Testimonios (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="testimonios" class="form-label">Testimonios (formato JSON: [{"texto":"...","autor":"..."}])</label>
                <textarea class="form-control" id="testimonios" name="testimonios" rows="3"><?= htmlspecialchars(json_encode($testimonios, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Redes sociales (JSON) -->
            <div class="col-md-12 mb-3">
                <label for="redes_sociales" class="form-label">Redes sociales (formato JSON: [{"nombre":"Facebook","url":"..."},...])</label>
                <textarea class="form-control" id="redes_sociales" name="redes_sociales" rows="2"><?= htmlspecialchars(json_encode($redes_sociales, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
            </div>
            <!-- Menú personalizable -->
            <div class="col-md-3 mb-3">
                <label for="menu_inicio" class="form-label">Texto menú Inicio</label>
                <input type="text" class="form-control" id="menu_inicio" name="menu_inicio"
                       value="<?= htmlspecialchars($menu_inicio) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_servicios" class="form-label">Texto menú Servicios</label>
                <input type="text" class="form-control" id="menu_servicios" name="menu_servicios"
                       value="<?= htmlspecialchars($menu_servicios) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_testimonios" class="form-label">Texto menú Testimonios</label>
                <input type="text" class="form-control" id="menu_testimonios" name="menu_testimonios"
                       value="<?= htmlspecialchars($menu_testimonios) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="menu_contacto" class="form-label">Texto menú Contacto</label>
                <input type="text" class="form-control" id="menu_contacto" name="menu_contacto"
                       value="<?= htmlspecialchars($menu_contacto) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
<script>
(function () {
    const palette = {
        color_principal: '#294f7a',
        color_secundario: '#e8f4fb',
        color_footer: '#1d3552',
        color_botones: '#3cc0cf',
        color_texto: '#24344a'
    };

    const btn = document.getElementById('btnAplicarPaletaLogo');
    if (!btn) return;

    btn.addEventListener('click', function () {
        Object.keys(palette).forEach(function (field) {
            const input = document.getElementById(field);
            if (input) {
                input.value = palette[field];
            }
        });
    });
})();

(function () {
    const builder = document.getElementById('ubicacionesBuilder');
    const textarea = document.getElementById('ubicaciones_json');
    const addBtn = document.getElementById('btnAddUbicacion');
    const syncBtn = document.getElementById('btnSyncUbicaciones');
    if (!builder || !textarea || !addBtn || !syncBtn) return;

    function createItemRow(item) {
        const wrap = document.createElement('div');
        wrap.className = 'border rounded p-2 mb-2';
        wrap.innerHTML = '' +
            '<div class="row g-2">' +
            '  <div class="col-md-3"><label class="form-label">Nombre sede</label><input type="text" class="form-control ub-nombre" value=""></div>' +
            '  <div class="col-md-3"><label class="form-label">Direccion</label><input type="text" class="form-control ub-direccion" value=""></div>' +
            '  <div class="col-md-2"><label class="form-label">Telefonos</label><input type="text" class="form-control ub-telefonos" value="" placeholder="519XXXXXXXX,519YYYYYYYY"></div>' +
            '  <div class="col-md-3"><label class="form-label">Mapa Google (iframe/src)</label><input type="text" class="form-control ub-mapa" value=""></div>' +
            '  <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 ub-del">X</button></div>' +
            '</div>';

        wrap.querySelector('.ub-nombre').value = (item && item.nombre) ? String(item.nombre) : '';
        wrap.querySelector('.ub-direccion').value = (item && item.direccion) ? String(item.direccion) : '';
        const phones = Array.isArray(item && item.telefonos)
            ? item.telefonos.map(function (x) { return String(x || '').trim(); }).filter(Boolean)
            : [];
        const fallbackCell = (item && item.celular) ? String(item.celular).trim() : '';
        if (phones.length === 0 && fallbackCell) {
            phones.push(fallbackCell);
        }
        wrap.querySelector('.ub-telefonos').value = phones.join(', ');
        wrap.querySelector('.ub-mapa').value = (item && item.maps_embed) ? String(item.maps_embed) : '';

        wrap.querySelector('.ub-del').addEventListener('click', function () {
            wrap.remove();
        });

        return wrap;
    }

    function parseTextarea() {
        try {
            const raw = JSON.parse(textarea.value || '[]');
            if (!Array.isArray(raw)) return [];
            return raw.filter(function (x) { return x && typeof x === 'object'; });
        } catch (e) {
            return [];
        }
    }

    function renderBuilder(items) {
        builder.innerHTML = '';
        if (!items.length) {
            items = [{ nombre: 'Sede principal', direccion: '', telefonos: [], maps_embed: '' }];
        }
        items.forEach(function (item) {
            builder.appendChild(createItemRow(item));
        });
    }

    function parsePhones(value) {
        return String(value || '')
            .split(',')
            .map(function (x) { return x.trim(); })
            .filter(function (x, i, arr) { return x !== '' && arr.indexOf(x) === i; });
    }

    function syncToTextarea() {
        const out = [];
        builder.querySelectorAll('.border.rounded.p-2.mb-2').forEach(function (row) {
            const nombre = (row.querySelector('.ub-nombre') || {}).value || '';
            const direccion = (row.querySelector('.ub-direccion') || {}).value || '';
            const telefonosTxt = (row.querySelector('.ub-telefonos') || {}).value || '';
            const telefonos = parsePhones(telefonosTxt);
            const maps_embed = (row.querySelector('.ub-mapa') || {}).value || '';
            if (nombre.trim() || direccion.trim() || telefonos.length > 0 || maps_embed.trim()) {
                out.push({
                    nombre: nombre.trim(),
                    direccion: direccion.trim(),
                    celular: telefonos.length > 0 ? telefonos[0] : '',
                    telefonos: telefonos,
                    maps_embed: maps_embed.trim()
                });
            }
        });
        textarea.value = JSON.stringify(out, null, 2);
    }

    addBtn.addEventListener('click', function () {
        builder.appendChild(createItemRow({ nombre: '', direccion: '', telefonos: [], maps_embed: '' }));
    });

    syncBtn.addEventListener('click', syncToTextarea);

    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            syncToTextarea();
        });
    }

    renderBuilder(parseTextarea());
})();
</script>
