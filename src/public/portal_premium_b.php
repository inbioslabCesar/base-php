<?php
$headline = !empty($frase_promocion) ? (string)$frase_promocion : ('Diagnostico avanzado para ' . (string)$nombre_empresa);
$subline = !empty($oferta_mes)
    ? (string)$oferta_mes
    : 'Portal premium con enfoque en conversion, claridad de oferta y confianza institucional.';

$promocionesData = is_array($promociones) ? $promociones : [];
$serviciosData = is_array($servicios) ? $servicios : [];
$testimoniosData = is_array($testimonios) ? $testimonios : [];
$carruselData = is_array($imagenes_carrusel) ? $imagenes_carrusel : [];
$institucionalData = is_array($imagenes_institucionales ?? null) ? $imagenes_institucionales : [];

$toPublicAsset = static function (string $path) use ($siteBasePath): string {
    $p = trim($path);
    if ($p === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $p) || strpos($p, 'data:') === 0) {
        return $p;
    }
    $normalized = str_replace('\\', '/', $p);
    $normalized = preg_replace('#^\./+#', '', $normalized);
    $normalized = preg_replace('#^\.\./+#', '', $normalized);
    $normalized = ltrim($normalized, '/');
    return ($siteBasePath === '' ? '' : $siteBasePath) . '/' . $normalized;
};

$promoImageUrl = static function ($promo) use ($toPublicAsset): string {
    $raw = trim((string)($promo['imagen'] ?? ''));
    if ($raw === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $raw) || strpos($raw, 'data:') === 0) {
        return $raw;
    }
    if (strpos($raw, '/') === false && strpos($raw, '\\') === false) {
        return rtrim((string)BASE_URL, '/\\') . '/promociones/assets/' . rawurlencode($raw);
    }
    return $toPublicAsset($raw);
};

$formatPromoDate = static function ($raw): string {
    $txt = trim((string)$raw);
    if ($txt === '') {
        return '';
    }
    $ts = strtotime($txt);
    if ($ts === false || $ts <= 0) {
        return '';
    }
    return date('d/m/Y', $ts);
};

$btnText = function_exists('ui_theme_text_for_bg') ? ui_theme_text_for_bg((string)$color_botones) : '#ffffff';
$primaryText = function_exists('ui_theme_text_for_bg') ? ui_theme_text_for_bg((string)$color_principal) : '#ffffff';
$footerText = function_exists('ui_theme_text_for_bg') ? ui_theme_text_for_bg((string)$color_footer) : '#ffffff';

$serviceCards = [];
foreach ($serviciosData as $srv) {
    if (!is_array($srv)) {
        continue;
    }
    $serviceCards[] = [
        'titulo' => (string)($srv['titulo'] ?? 'Servicio'),
        'descripcion' => (string)($srv['descripcion'] ?? 'Descripcion de servicio'),
    ];
    if (count($serviceCards) >= 8) {
        break;
    }
}
if (empty($serviceCards)) {
    $serviceCards = [
        ['titulo' => 'Chequeos Preventivos', 'descripcion' => 'Paneles completos para control de salud.'],
        ['titulo' => 'Pruebas Especializadas', 'descripcion' => 'Resultados robustos con seguimiento profesional.'],
        ['titulo' => 'Atencion Integral', 'descripcion' => 'Acompanamiento desde toma de muestra hasta informe.'],
    ];
}

$normalizePhoneDigits = static function (string $value): string {
    return preg_replace('/\D+/', '', trim($value)) ?? '';
};

$extractBranchPhones = static function (array $ub) use ($normalizePhoneDigits): array {
    $phones = [];

    if (!empty($ub['telefonos']) && is_array($ub['telefonos'])) {
        foreach ($ub['telefonos'] as $rawPhone) {
            $raw = trim((string)$rawPhone);
            if ($raw === '') {
                continue;
            }
            $norm = $normalizePhoneDigits($raw);
            if ($norm === '' || isset($phones[$norm])) {
                continue;
            }
            $phones[$norm] = $raw;
        }
    }

    $legacyCell = trim((string)($ub['celular'] ?? ''));
    if ($legacyCell !== '') {
        $normLegacy = $normalizePhoneDigits($legacyCell);
        if ($normLegacy !== '' && !isset($phones[$normLegacy])) {
            $phones = [$normLegacy => $legacyCell] + $phones;
        }
    }

    return array_values($phones);
};

$buildWhatsAppLink = static function (string $rawPhone, string $sedeNombre) use ($normalizePhoneDigits, $nombre_empresa): string {
    $digits = $normalizePhoneDigits($rawPhone);
    if (strlen($digits) < 7) {
        return '';
    }
    $sedeMsg = trim($sedeNombre) !== '' ? trim($sedeNombre) : 'sede';
    $msg = 'Hola, deseo información de la sede ' . $sedeMsg . ' de ' . (string)$nombre_empresa . '.';
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($msg);
};

$ubicacionesData = [];
if (!empty($config_empresa['ubicaciones_json'])) {
    $tmpUb = json_decode((string)$config_empresa['ubicaciones_json'], true);
    if (is_array($tmpUb)) {
        foreach ($tmpUb as $ub) {
            if (!is_array($ub)) {
                continue;
            }
            $phonesUb = $extractBranchPhones($ub);
            $ubicacionesData[] = [
                'nombre' => trim((string)($ub['nombre'] ?? '')),
                'direccion' => trim((string)($ub['direccion'] ?? '')),
                'celular' => !empty($phonesUb) ? (string)$phonesUb[0] : trim((string)($ub['celular'] ?? '')),
                'telefonos' => $phonesUb,
                'maps_embed' => trim((string)($ub['maps_embed'] ?? '')),
            ];
        }
    }
}
if (empty($ubicacionesData) && (!empty($config_empresa['direccion']) || !empty($config_empresa['maps_embed']))) {
    $phonesFallback = [];
    $legacyCell = trim((string)($config_empresa['celular'] ?? ''));
    if ($legacyCell !== '') {
        $phonesFallback[] = $legacyCell;
    }
    $ubicacionesData[] = [
        'nombre' => 'Sede principal',
        'direccion' => (string)($config_empresa['direccion'] ?? ''),
        'celular' => $legacyCell,
        'telefonos' => $phonesFallback,
        'maps_embed' => (string)($config_empresa['maps_embed'] ?? ''),
    ];
}

$contactoDirecciones = [];
foreach ($ubicacionesData as $ub) {
    if (!is_array($ub)) {
        continue;
    }
    $nombreUb = trim((string)($ub['nombre'] ?? ''));
    $direccionUb = trim((string)($ub['direccion'] ?? ''));
    $celularUb = trim((string)($ub['celular'] ?? ''));
    if ($direccionUb === '') {
        continue;
    }
    $contactoDirecciones[] = [
        'nombre' => $nombreUb,
        'direccion' => $direccionUb,
        'celular' => $celularUb,
        'telefonos' => is_array($ub['telefonos'] ?? null) ? $ub['telefonos'] : (!empty($celularUb) ? [$celularUb] : []),
    ];
}
if (empty($contactoDirecciones) && !empty($config_empresa['direccion'])) {
    $legacyCell = trim((string)($config_empresa['celular'] ?? ''));
    $contactoDirecciones[] = [
        'nombre' => 'Sede principal',
        'direccion' => trim((string)$config_empresa['direccion']),
        'celular' => $legacyCell,
        'telefonos' => $legacyCell !== '' ? [$legacyCell] : [],
    ];
}

$whatsAppHref = '';
$sedePrincipal = null;
foreach ($ubicacionesData as $ub) {
    if (!is_array($ub)) {
        continue;
    }
    $nombreUb = strtolower(trim((string)($ub['nombre'] ?? '')));
    if ($nombreUb === 'sede principal' || strpos($nombreUb, 'principal') !== false) {
        $sedePrincipal = $ub;
        break;
    }
}
if ($sedePrincipal === null && !empty($ubicacionesData[0]) && is_array($ubicacionesData[0])) {
    $sedePrincipal = $ubicacionesData[0];
}
if (is_array($sedePrincipal)) {
    $phonesPrincipal = $extractBranchPhones($sedePrincipal);
    if (!empty($phonesPrincipal[0])) {
        $waOperativo = $buildWhatsAppLink((string)$phonesPrincipal[0], (string)($sedePrincipal['nombre'] ?? 'Sede principal'));
        if ($waOperativo !== '') {
            $whatsAppHref = $waOperativo;
        }
    }
}
if ($whatsAppHref === '') {
    $whatsappNumero = preg_replace('/\D+/', '', (string)($config_empresa['celular'] ?? ''));
    if ($whatsappNumero !== '') {
        $whatsAppHref = 'https://wa.me/' . $whatsappNumero;
    }
}

$waFloatingButtons = [];
$sucursalSecuencia = 1;
foreach ($ubicacionesData as $ub) {
    if (!is_array($ub)) {
        continue;
    }
    $phonesUb = $extractBranchPhones($ub);
    if (empty($phonesUb[0])) {
        continue;
    }
    $nombreUbRaw = trim((string)($ub['nombre'] ?? ''));
    $nombreUbNorm = strtolower($nombreUbRaw);

    if ($nombreUbNorm === 'sede principal' || strpos($nombreUbNorm, 'principal') !== false) {
        $label = 'Central';
    } elseif ($nombreUbRaw !== '') {
        $label = $nombreUbRaw;
    } else {
        $label = 'Sucursal ' . $sucursalSecuencia;
        $sucursalSecuencia++;
    }

    $waLink = $buildWhatsAppLink((string)$phonesUb[0], $nombreUbRaw !== '' ? $nombreUbRaw : $label);
    if ($waLink === '') {
        continue;
    }

    $waFloatingButtons[] = [
        'label' => $label,
        'href' => $waLink,
    ];
}

if (empty($waFloatingButtons) && $whatsAppHref !== '') {
    $waFloatingButtons[] = [
        'label' => 'Central',
        'href' => $whatsAppHref,
    ];
}

$pwaBasePath = isset($siteBasePath) ? rtrim((string)$siteBasePath, '/\\') : '';
if ($pwaBasePath === '.' || $pwaBasePath === '/') {
    $pwaBasePath = '';
}
$pwaManifestHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/manifest.php';
$pwaServiceWorkerHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/sw.js';
$pwaScope = $pwaBasePath === '' ? '/' : ($pwaBasePath . '/');
$pwaRegisterScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-register.js';
$pwaInstallScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-install.js';

$waButtonsCount = count($waFloatingButtons);
$waExtraOffset = max(0, $waButtonsCount - 2) * 8;
$waStackBottomDesktop = min(300, 156 + $waExtraOffset);
$waStackBottomMobile = min(280, 148 + $waExtraOffset);

$socialLinks = [];
if (!empty($redes_sociales) && is_array($redes_sociales)) {
    foreach ($redes_sociales as $red) {
        if (!is_array($red)) {
            continue;
        }
        $nombre = trim((string)($red['nombre'] ?? ''));
        $url = trim((string)($red['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        if (!preg_match('~^https?://~i', $url)) {
            if (preg_match('/^\+?\d{7,15}$/', preg_replace('/\s+/', '', $url))) {
                $url = 'https://wa.me/' . preg_replace('/\D+/', '', $url);
            } else {
                $url = 'https://' . ltrim($url, '/');
            }
        }
        $key = strtolower($nombre !== '' ? $nombre : parse_url($url, PHP_URL_HOST));
        $icon = 'bi-globe2';
        if (strpos($key, 'facebook') !== false) $icon = 'bi-facebook';
        elseif (strpos($key, 'instagram') !== false) $icon = 'bi-instagram';
        elseif (strpos($key, 'tiktok') !== false) $icon = 'bi-tiktok';
        elseif (strpos($key, 'youtube') !== false) $icon = 'bi-youtube';
        elseif (strpos($key, 'linkedin') !== false) $icon = 'bi-linkedin';
        elseif (strpos($key, 'telegram') !== false) $icon = 'bi-telegram';
        elseif (strpos($key, 'x.com') !== false || strpos($key, 'twitter') !== false) $icon = 'bi-twitter-x';
        elseif (strpos($key, 'whatsapp') !== false || strpos($url, 'wa.me') !== false) $icon = 'bi-whatsapp';

        $socialLinks[] = [
            'nombre' => $nombre !== '' ? $nombre : 'Red social',
            'url' => $url,
            'icon' => $icon,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="icon" href="<?= htmlspecialchars($faviconDynamicHref, ENT_QUOTES, 'UTF-8') ?>" type="image/png" sizes="48x48">
    <link rel="icon" href="<?= htmlspecialchars($logoFaviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" href="<?= htmlspecialchars($faviconIcoHref, ENT_QUOTES, 'UTF-8') ?>" sizes="any" type="image/x-icon">
    <link rel="shortcut icon" href="<?= htmlspecialchars($faviconIcoHref, ENT_QUOTES, 'UTF-8') ?>" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($logoFaviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($nombre_empresa) ?> | Portal Premium B</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_PE">
    <meta property="og:title" content="<?= htmlspecialchars($shareTitle ?? ($nombre_empresa . ' | Portal Premium B'), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($shareDescription ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($shareImage ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($shareTitle ?? ($nombre_empresa . ' | Portal Premium B'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($shareDescription ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($shareImage ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="manifest" href="<?= htmlspecialchars($pwaManifestHref, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --pb-primary: <?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>;
            --pb-secondary: <?= htmlspecialchars($color_secundario, ENT_QUOTES, 'UTF-8') ?>;
            --pb-footer: <?= htmlspecialchars($color_footer, ENT_QUOTES, 'UTF-8') ?>;
            --pb-button: <?= htmlspecialchars($color_botones, ENT_QUOTES, 'UTF-8') ?>;
            --pb-text: <?= htmlspecialchars($color_texto, ENT_QUOTES, 'UTF-8') ?>;
            --pb-button-text: <?= htmlspecialchars($btnText, ENT_QUOTES, 'UTF-8') ?>;
            --pb-primary-text: <?= htmlspecialchars($primaryText, ENT_QUOTES, 'UTF-8') ?>;
            --pb-footer-text: <?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?>;
        }

        body {
            background: var(--pb-secondary);
            color: var(--pb-text);
            font-size: <?= htmlspecialchars($tamano_letra, ENT_QUOTES, 'UTF-8') ?>;
        }

        .top-premium {
            position: relative;
            background: linear-gradient(115deg, var(--pb-primary) 0%, var(--pb-footer) 75%);
            color: var(--pb-primary-text);
            overflow: hidden;
        }

        .top-premium::before,
        .top-premium::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            opacity: 0.22;
            pointer-events: none;
        }

        .top-premium::before {
            width: 420px;
            height: 420px;
            right: -130px;
            top: -180px;
            background: radial-gradient(circle, #ffffff 0%, transparent 70%);
        }

        .top-premium::after {
            width: 330px;
            height: 330px;
            left: -120px;
            bottom: -170px;
            background: radial-gradient(circle, #ffffff 0%, transparent 70%);
        }

        .premium-nav {
            background: transparent !important;
        }

        .premium-nav .nav-link,
        .premium-nav .navbar-brand {
            color: var(--pb-primary-text) !important;
            font-weight: 600;
        }

        .brand-logo-premium-b {
            height: 76px;
            width: auto;
            border-radius: 12px;
            background: #ffffff;
            padding: 3px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.14);
        }

        .hero-wrap {
            padding: 2rem 0 3.2rem;
        }

        .hero-card {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: 18px;
            padding: 1.4rem;
            backdrop-filter: blur(7px);
        }

        .hero-carousel,
        .hero-carousel .carousel-item,
        .hero-carousel img {
            width: 100%;
            border-radius: 16px;
        }

        .hero-carousel img {
            height: 300px;
            object-fit: cover;
            display: block;
            box-shadow: 0 10px 24px rgba(0,0,0,0.16);
        }

        .hero-title {
            font-size: clamp(1.9rem, 3.6vw, 3.1rem);
            font-weight: 800;
            line-height: 1.04;
            letter-spacing: -0.02em;
        }

        .btn-pb {
            background: var(--pb-button);
            color: var(--pb-button-text);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: .7rem 1.2rem;
        }

        .btn-pb-cotizar {
            background: #ffffff;
            color: var(--pb-primary);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 999px;
            font-weight: 700;
            padding: .48rem .95rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            transition: all .2s ease;
        }

        .btn-pb-cotizar:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.75);
            transform: translateY(-1px);
            box-shadow: 0 11px 20px rgba(0, 0, 0, 0.22);
        }

        .btn-pb:hover {
            filter: brightness(0.92);
            color: var(--pb-button-text);
        }

        .kpi-strip {
            margin-top: -26px;
            position: relative;
            z-index: 2;
        }

        .kpi-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.06);
            padding: .95rem 1rem;
            box-shadow: 0 10px 24px rgba(0,0,0,0.08);
            text-align: center;
        }

        .kpi-num {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--pb-primary);
        }

        .floating-service {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            padding: 1rem;
            height: 100%;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }

        .floating-service h6 {
            margin-bottom: .4rem;
            font-weight: 700;
        }

        .promo-image {
            width: 100%;
            height: 185px;
            object-fit: contain;
            border-radius: 10px;
            margin-bottom: .65rem;
            background: #ffffff;
            padding: 6px;
            border: 1px solid rgba(0,0,0,0.06);
        }

        .promo-band {
            border-radius: 16px;
            background: linear-gradient(110deg, rgba(255,255,255,0.88), rgba(255,255,255,0.62));
            border: 1px solid rgba(0,0,0,0.06);
            padding: 1.2rem;
        }

        .promo-carousel .carousel-inner {
            padding: 2px;
        }

        .promo-carousel .carousel-control-prev,
        .promo-carousel .carousel-control-next {
            width: 44px;
            height: 44px;
            top: calc(50% - 22px);
            background: rgba(15, 23, 42, 0.55);
            border-radius: 999px;
        }

        .promo-carousel .carousel-control-prev {
            left: -16px;
        }

        .promo-carousel .carousel-control-next {
            right: -16px;
        }

        .promo-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .location-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .location-map {
            width: 100%;
            height: 230px;
            border: 0;
            display: block;
            background: #eef2fb;
        }

        .institucional-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .institucional-strip img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
            background: #eef2fb;
        }

        .social-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            padding: 0.52rem 0.84rem;
            border-radius: 999px;
            border: 1px solid rgba(0,0,0,0.1);
            background: #ffffff;
            color: var(--pb-text);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .social-link i {
            font-size: 1.05rem;
        }

        .social-link:hover {
            border-color: var(--pb-button);
            color: var(--pb-button);
        }

        .wa-inline-link {
            color: #128c7e;
            font-weight: 600;
            text-decoration: none;
        }

        .wa-inline-link:hover {
            color: #0d6f62;
            text-decoration: underline;
        }

        footer {
            margin-top: 2.3rem;
            background: var(--pb-footer);
            color: var(--pb-footer-text);
        }

        .cart-fab {
            position: fixed;
            right: 20px;
            bottom: 86px;
            z-index: 10010;
            min-width: 56px;
            height: 56px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 22px rgba(0,0,0,.24);
            background: var(--pb-button);
            color: var(--pb-button-text);
            border: 0;
            font-weight: 700;
            padding: 0 14px;
            text-decoration: none;
        }

        .cart-fab .badge {
            margin-left: 8px;
            background: #0f172a;
            color: #ffffff;
        }

        .wa-branch-stack {
            position: fixed;
            right: 20px;
            bottom: var(--wa-stack-bottom, 156px);
            z-index: 10000;
            display: flex;
            flex-direction: column-reverse;
            gap: 10px;
        }

        .wa-branch-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .wa-branch-label {
            background: rgba(15, 23, 42, 0.88);
            color: #ffffff;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wa-branch-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #25d366;
            color: #ffffff;
            box-shadow: 0 8px 22px rgba(0,0,0,.24);
            font-size: 1.4rem;
        }

        .wa-branch-btn:hover .wa-branch-icon {
            background: #1fa855;
        }

        @media (max-width: 767.98px) {
            .kpi-strip {
                margin-top: -12px;
            }
            .hero-wrap {
                padding: 1.2rem 0 2rem;
            }
            .brand-logo-premium-b {
                height: 62px;
            }
            .hero-carousel img {
                height: 220px;
            }
            .institucional-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .wa-branch-stack {
                right: 20px;
                bottom: var(--wa-stack-bottom-mobile, 148px);
                gap: 8px;
            }
            .wa-branch-label {
                font-size: 0.72rem;
                max-width: 120px;
            }
            .wa-branch-icon {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <header class="top-premium">
        <div class="container">
            <nav class="navbar navbar-expand-lg premium-nav py-3">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="<?= BASE_URL ?><?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>" alt="Logo" class="brand-logo-premium-b">
                    <span class="ms-2"><?= htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pbNav" aria-controls="pbNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="pbNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
                        <li class="nav-item ms-lg-1 my-2 my-lg-0"><a class="btn btn-pb-cotizar" href="index.php?vista=cotizar_publico"><i class="bi bi-stars"></i> Cotizar</a></li>
                        <li class="nav-item"><a class="nav-link" href="#promociones">Promociones</a></li>
                        <li class="nav-item"><a class="nav-link" href="#ubicaciones">Ubicaciones</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn btn-pb" href="src/auth/login.php">Acceso Clientes</a></li>
                    </ul>
                </div>
            </nav>

            <div class="hero-wrap">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <div class="hero-card">
                            <h1 class="hero-title mb-2"><?= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mb-3"><?= htmlspecialchars($subline, ENT_QUOTES, 'UTF-8') ?></p>
                            <a class="btn btn-pb" href="#contacto">Solicitar atencion</a>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <?php if (!empty($carruselData[0])): ?>
                            <div id="premiumHeroCarouselB" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="3200">
                                <div class="carousel-inner">
                                    <?php foreach ($carruselData as $idx => $img): ?>
                                        <?php $imgUrl = $toPublicAsset((string)$img); ?>
                                        <?php if ($imgUrl === '') continue; ?>
                                        <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Carrusel <?= (int)$idx + 1 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#premiumHeroCarouselB" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#premiumHeroCarouselB" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="hero-card">
                                <i class="bi bi-hospital fs-2"></i>
                                <div class="mt-2">Portal Premium B activo.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="container kpi-strip">
        <div class="row g-3">
            <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-num">24h</div><small>Tiempo de respuesta</small></div></div>
            <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-num">98%</div><small>Satisfaccion</small></div></div>
            <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-num">+120</div><small>Pruebas disponibles</small></div></div>
            <div class="col-6 col-md-3"><div class="kpi-card"><div class="kpi-num">365</div><small>Atencion anual</small></div></div>
        </div>
    </section>

    <main class="container py-4">
        <section id="servicios" class="mb-4">
            <h2 class="h3 mb-3">Servicios Especializados</h2>
            <div class="row g-3">
                <?php foreach ($serviceCards as $srv): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="floating-service">
                            <h6><?= htmlspecialchars($srv['titulo'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <p class="mb-0 text-muted"><?= htmlspecialchars($srv['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="analisis-frecuentes" class="mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="h3 mb-0">Análisis más frecuentes</h2>
                <a class="btn btn-sm btn-outline-primary" href="index.php?vista=cotizar_publico">Ver todos los análisis clínicos</a>
            </div>
            <div class="row g-3">
                <?php if (!empty($analisisFrecuentesPublicos)): ?>
                    <?php foreach ($analisisFrecuentesPublicos as $analisis): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <a class="text-decoration-none" href="index.php?vista=cotizar_publico&examen_id=<?= (int)($analisis['id'] ?? 0) ?>">
                                <article class="floating-service">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <h6 class="mb-1"><?= htmlspecialchars((string)($analisis['nombre'] ?? 'Examen clínico'), ENT_QUOTES, 'UTF-8') ?></h6>
                                        <i class="bi bi-arrow-up-right text-primary"></i>
                                    </div>
                                </article>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="floating-service">Aún no hay análisis destacados. Usa el cotizador completo.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <section id="promociones" class="mb-4">
            <div class="promo-band">
                <h2 class="h3 mb-3">Promociones del Mes</h2>
                <?php if (!empty($promocionesData)): ?>
                    <?php if (count($promocionesData) >= 2): ?>
                        <?php $promoSlidesDesktop = array_chunk($promocionesData, 2); ?>
                        <?php $promoSlidesMobile = array_chunk($promocionesData, 1); ?>

                        <div class="d-none d-md-block">
                            <div id="premiumPromoCarouselB" class="carousel slide promo-carousel" data-bs-ride="carousel" data-bs-interval="5500">
                                <div class="carousel-inner">
                                    <?php foreach ($promoSlidesDesktop as $slideIdx => $promoSlide): ?>
                                        <div class="carousel-item <?= $slideIdx === 0 ? 'active' : '' ?>">
                                            <div class="row g-3">
                                                <?php foreach ($promoSlide as $promo): ?>
                                                    <div class="col-12 col-md-6">
                                                        <div class="floating-service">
                                                            <?php if (!empty($promo['imagen'])): ?>
                                                                <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promocion" class="promo-image" loading="lazy" onerror="this.style.display='none';">
                                                            <?php endif; ?>
                                                            <h6 class="mb-1"><?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?></h6>
                                                            <p class="mb-2 text-muted"><?= htmlspecialchars((string)($promo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                                            <?php if (isset($promo['precio_promocional']) && (float)$promo['precio_promocional'] > 0): ?>
                                                                <div class="mb-1"><span class="badge text-bg-warning">Precio: S/ <?= number_format((float)$promo['precio_promocional'], 2) ?></span></div>
                                                            <?php endif; ?>
                                                            <?php
                                                                $fi = $formatPromoDate($promo['fecha_inicio'] ?? '');
                                                                $ff = $formatPromoDate($promo['fecha_fin'] ?? '');
                                                            ?>
                                                            <?php if ($fi !== '' || $ff !== ''): ?>
                                                                <div class="mb-2"><span class="badge text-bg-info">Vigencia: <?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?></span></div>
                                                            <?php endif; ?>
                                                            <div class="promo-actions">
                                                                <?php if (!empty($promo['id'])): ?>
                                                                    <a class="btn btn-sm btn-pb" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
                                                                <?php endif; ?>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-outline-success"
                                                                    data-add-promo="1"
                                                                    data-promo-id="<?= (int)($promo['id'] ?? 0) ?>"
                                                                    data-promo-titulo="<?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-promo-precio="<?= isset($promo['precio_promocional']) ? (float)$promo['precio_promocional'] : 0 ?>"
                                                                    data-promo-vigencia="<?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?>"
                                                                >Agregar al carrito</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#premiumPromoCarouselB" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#premiumPromoCarouselB" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        </div>

                        <div class="d-md-none">
                            <div id="premiumPromoCarouselBMobile" class="carousel slide promo-carousel" data-bs-ride="carousel" data-bs-interval="5500">
                                <div class="carousel-inner">
                                    <?php foreach ($promoSlidesMobile as $slideIdx => $promoSlide): ?>
                                        <?php $promo = $promoSlide[0]; ?>
                                        <div class="carousel-item <?= $slideIdx === 0 ? 'active' : '' ?>">
                                            <div class="floating-service">
                                                <?php if (!empty($promo['imagen'])): ?>
                                                    <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promocion" class="promo-image" loading="lazy" onerror="this.style.display='none';">
                                                <?php endif; ?>
                                                <h6 class="mb-1"><?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?></h6>
                                                <p class="mb-2 text-muted"><?= htmlspecialchars((string)($promo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                                <?php if (isset($promo['precio_promocional']) && (float)$promo['precio_promocional'] > 0): ?>
                                                    <div class="mb-1"><span class="badge text-bg-warning">Precio: S/ <?= number_format((float)$promo['precio_promocional'], 2) ?></span></div>
                                                <?php endif; ?>
                                                <?php
                                                    $fi = $formatPromoDate($promo['fecha_inicio'] ?? '');
                                                    $ff = $formatPromoDate($promo['fecha_fin'] ?? '');
                                                ?>
                                                <?php if ($fi !== '' || $ff !== ''): ?>
                                                    <div class="mb-2"><span class="badge text-bg-info">Vigencia: <?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?></span></div>
                                                <?php endif; ?>
                                                <div class="promo-actions">
                                                    <?php if (!empty($promo['id'])): ?>
                                                        <a class="btn btn-sm btn-pb" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
                                                    <?php endif; ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        data-add-promo="1"
                                                        data-promo-id="<?= (int)($promo['id'] ?? 0) ?>"
                                                        data-promo-titulo="<?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-promo-precio="<?= isset($promo['precio_promocional']) ? (float)$promo['precio_promocional'] : 0 ?>"
                                                        data-promo-vigencia="<?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?>"
                                                    >Agregar al carrito</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#premiumPromoCarouselBMobile" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#premiumPromoCarouselBMobile" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($promocionesData as $promo): ?>
                                <div class="col-12 col-md-4">
                                    <div class="floating-service">
                                        <?php if (!empty($promo['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promocion" class="promo-image" loading="lazy" onerror="this.style.display='none';">
                                        <?php endif; ?>
                                        <h6 class="mb-1"><?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?></h6>
                                        <p class="mb-2 text-muted"><?= htmlspecialchars((string)($promo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (isset($promo['precio_promocional']) && (float)$promo['precio_promocional'] > 0): ?>
                                            <div class="mb-1"><span class="badge text-bg-warning">Precio: S/ <?= number_format((float)$promo['precio_promocional'], 2) ?></span></div>
                                        <?php endif; ?>
                                        <?php
                                            $fi = $formatPromoDate($promo['fecha_inicio'] ?? '');
                                            $ff = $formatPromoDate($promo['fecha_fin'] ?? '');
                                        ?>
                                        <?php if ($fi !== '' || $ff !== ''): ?>
                                            <div class="mb-2"><span class="badge text-bg-info">Vigencia: <?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?></span></div>
                                        <?php endif; ?>
                                        <div class="promo-actions">
                                            <?php if (!empty($promo['id'])): ?>
                                                <a class="btn btn-sm btn-pb" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
                                            <?php endif; ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                data-add-promo="1"
                                                data-promo-id="<?= (int)($promo['id'] ?? 0) ?>"
                                                data-promo-titulo="<?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-promo-precio="<?= isset($promo['precio_promocional']) ? (float)$promo['precio_promocional'] : 0 ?>"
                                                data-promo-vigencia="<?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?>"
                                            >Agregar al carrito</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-12"><p class="mb-0 text-muted">No hay promociones activas en este momento.</p></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($institucionalData)): ?>
        <section id="institucional" class="mb-4">
            <h2 class="h3 mb-3">Conocenos</h2>
            <div class="institucional-strip">
                <?php foreach ($institucionalData as $imgIns): ?>
                    <?php $insUrl = $toPublicAsset((string)$imgIns); ?>
                    <?php if ($insUrl === '') continue; ?>
                    <img src="<?= htmlspecialchars($insUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Institucional" loading="lazy" onerror="this.style.display='none';">
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="contacto" class="mb-2">
            <div class="floating-service">
                <h2 class="h4 mb-2"><?= htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!empty($contactoDirecciones)): ?>
                    <?php foreach ($contactoDirecciones as $idxDir => $dir): ?>
                        <p class="mb-1">
                            <?php if (!empty($dir['nombre'])): ?>
                                <strong><?= htmlspecialchars((string)$dir['nombre'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                            <?php endif; ?>
                            <?= htmlspecialchars((string)$dir['direccion'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <?php if (!empty($dir['telefonos']) && is_array($dir['telefonos'])): ?>
                            <?php foreach ($dir['telefonos'] as $tel): ?>
                                <?php $waLink = $buildWhatsAppLink((string)$tel, (string)($dir['nombre'] ?? '')); ?>
                                <p class="mb-1 small text-muted">
                                    <strong>Telefono:</strong> <?= htmlspecialchars((string)$tel, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($waLink !== ''): ?>
                                        | <a class="wa-inline-link" href="<?= htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p class="mb-1">Telefono: <?= htmlspecialchars((string)($config_empresa['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0">Celular general: <?= htmlspecialchars((string)($config_empresa['celular'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>

        <?php if (!empty($socialLinks)): ?>
        <section id="redes" class="mb-4">
            <h2 class="h3 mb-3">Redes Sociales</h2>
            <div class="social-strip">
                <?php foreach ($socialLinks as $social): ?>
                    <a class="social-link" href="<?= htmlspecialchars((string)$social['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                        <i class="bi <?= htmlspecialchars((string)$social['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= htmlspecialchars((string)$social['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="ubicaciones" class="mb-4">
            <h2 class="h3 mb-3">Ubicaciones y Sucursales</h2>
            <div class="row g-3">
                <?php if (!empty($ubicacionesData)): ?>
                    <?php foreach ($ubicacionesData as $ub): ?>
                        <div class="col-12 col-lg-6">
                            <article class="location-card h-100">
                                <div class="p-3">
                                    <h6 class="mb-1"><?= htmlspecialchars((string)($ub['nombre'] ?: 'Sucursal'), ENT_QUOTES, 'UTF-8') ?></h6>
                                    <p class="mb-0 text-muted"><?= htmlspecialchars((string)($ub['direccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if (!empty($ub['telefonos']) && is_array($ub['telefonos'])): ?>
                                        <?php foreach ($ub['telefonos'] as $tel): ?>
                                            <?php $waUb = $buildWhatsAppLink((string)$tel, (string)($ub['nombre'] ?? '')); ?>
                                            <p class="mb-0 small text-muted">
                                                Telefono: <?= htmlspecialchars((string)$tel, ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($waUb !== ''): ?>
                                                    | <a class="wa-inline-link" href="<?= htmlspecialchars($waUb, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                                                <?php endif; ?>
                                            </p>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($ub['maps_embed'])): ?>
                                    <iframe class="location-map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= htmlspecialchars((string)$ub['maps_embed'], ENT_QUOTES, 'UTF-8') ?>"></iframe>
                                <?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="floating-service">No hay ubicaciones configuradas.</div></div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="py-4">
        <div class="container d-flex justify-content-between flex-wrap gap-2">
            <span>© <?= date('Y') ?> <?= htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </footer>

    <button type="button" class="cart-fab" data-bs-toggle="modal" data-bs-target="#promoCartModalB" aria-label="Abrir carrito de promociones">
        <i class="bi bi-cart3"></i>
        <span class="badge" id="promoCartCountB">0</span>
    </button>

    <div class="modal fade" id="promoCartModalB" tabindex="-1" aria-labelledby="promoCartModalBLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promoCartModalBLabel">Carrito de cotización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="promoCartEmptyB" class="alert alert-light border">Aun no agregaste items para cotizar.</div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="promoCartTableB">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Tipo</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-end mb-3">
                        <div class="small text-muted">Subtotal: <span id="promoCartSubtotalB">S/ 0.00</span></div>
                        <div class="small text-success" id="promoCartDiscountWrapB" style="display:none;">Descuento web: <span id="promoCartDiscountB">-S/ 0.00</span></div>
                        <strong>Total referencial: <span id="promoCartTotalB">S/ 0.00</span></strong>
                    </div>

                    <h6 class="mb-2">Datos para cotización</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre del paciente</label>
                            <input type="text" class="form-control" id="cotNombreB" placeholder="Nombres y apellidos">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Teléfono de contacto</label>
                            <input type="text" class="form-control" id="cotTelefonoB" placeholder="9XXXXXXXX">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">¿Dónde deseas la toma de muestra?</label>
                            <select class="form-select" id="cotModalidadB">
                                <option value="laboratorio">En laboratorio</option>
                                <option value="domicilio">A domicilio</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha deseada de atención</label>
                            <input type="date" class="form-control" id="cotFechaB">
                            <small class="text-muted" id="cotFechaHintB">Selecciona la fecha en la que deseas asistir al laboratorio.</small>
                        </div>
                        <div class="col-12" id="cotDireccionWrapB" style="display:none;">
                            <label class="form-label">Dirección para toma a domicilio</label>
                            <input type="text" class="form-control" id="cotDireccionB" placeholder="Dirección exacta y referencia">
                        </div>
                        <div class="col-12">
                            <label class="form-label">¿Tienes alguna duda o indicación para el laboratorio?</label>
                            <textarea class="form-control" id="cotObsB" rows="2" placeholder="Ej: ¿Debo ir en ayunas?, ¿puedo tomar agua?, horario ideal, otra consulta"></textarea>
                            <small class="text-muted">Escribe aquí cualquier consulta para el laboratorio antes de agendar.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="promoCartClearB">Vaciar carrito</button>
                    <button type="button" class="btn btn-pb" id="promoQuoteWhatsappB">Cotizar por WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($waFloatingButtons)): ?>
        <div class="wa-branch-stack" style="--wa-stack-bottom: <?= (int)$waStackBottomDesktop ?>px; --wa-stack-bottom-mobile: <?= (int)$waStackBottomMobile ?>px;" aria-label="Canales de WhatsApp por sede">
            <?php foreach ($waFloatingButtons as $waBtn): ?>
                <a class="wa-branch-btn" href="<?= htmlspecialchars((string)$waBtn['href'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp <?= htmlspecialchars((string)$waBtn['label'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="wa-branch-label"><?= htmlspecialchars((string)$waBtn['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="wa-branch-icon"><i class="bi bi-whatsapp"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const CART_KEY = 'quote_items_v1';
            const LEGACY_PROMO_KEY = 'promo_cart_v1';
            const LEGACY_EXAM_KEY = 'exam_cart_v1';
            const FORM_DRAFT_KEY = 'quote_contact_draft_v1';
            const WA_BASE = <?= json_encode($whatsAppHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const EMPRESA = <?= json_encode((string)$nombre_empresa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const DISCOUNT_ACTIVE = <?= !empty($promoWebActiva) ? 'true' : 'false' ?>;
            const DISCOUNT_PERCENT = <?= json_encode((float)($promoWebPorcentaje ?? 0), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const DISCOUNT_APPLY = <?= !empty($promoWebAplicarCarrito) ? 'true' : 'false' ?>;

            const countEl = document.getElementById('promoCartCountB');
            const table = document.getElementById('promoCartTableB');
            const tbody = table ? table.querySelector('tbody') : null;
            const emptyEl = document.getElementById('promoCartEmptyB');
            const subtotalEl = document.getElementById('promoCartSubtotalB');
            const discountWrapEl = document.getElementById('promoCartDiscountWrapB');
            const discountEl = document.getElementById('promoCartDiscountB');
            const totalEl = document.getElementById('promoCartTotalB');

            const modalidadEl = document.getElementById('cotModalidadB');
            const direccionWrapEl = document.getElementById('cotDireccionWrapB');
            const fechaHintEl = document.getElementById('cotFechaHintB');
            const nombreEl = document.getElementById('cotNombreB');
            const telefonoEl = document.getElementById('cotTelefonoB');
            const fechaEl = document.getElementById('cotFechaB');
            const direccionEl = document.getElementById('cotDireccionB');
            const obsEl = document.getElementById('cotObsB');

            function getCart() {
                try {
                    const raw = localStorage.getItem(CART_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return normalizeCart(Array.isArray(parsed) ? parsed : []);
                } catch (e) {
                    return [];
                }
            }

            function setCart(items) {
                localStorage.setItem(CART_KEY, JSON.stringify(normalizeCart(items)));
            }

            function normalizeCart(items) {
                const out = [];
                const seen = new Set();
                (Array.isArray(items) ? items : []).forEach((it) => {
                    const rawType = String((it && it.type) || '').toLowerCase();
                    const type = rawType === 'promocion' || rawType === 'promo' ? 'promocion' : 'examen';
                    const key = String((it && it.key) || '').trim() || (type + ':' + String((it && it.id) || '').trim());
                    if (!key || seen.has(key)) {
                        return;
                    }
                    seen.add(key);
                    out.push({
                        key: key,
                        type: type,
                        id: String((it && it.id) || ''),
                        title: String((it && it.title) || (type === 'promocion' ? 'Promocion' : 'Examen')),
                        price: Number((it && it.price) || 0),
                        qty: Math.max(1, Number((it && it.qty) || 1)),
                        vigencia: String((it && it.vigencia) || ''),
                        codigo: String((it && it.codigo) || ''),
                        tiempo_respuesta: String((it && it.tiempo_respuesta) || ''),
                        tipo_tubo: String((it && it.tipo_tubo) || '')
                    });
                });
                return out;
            }

            function migrateLegacyCart() {
                const existing = getCart();
                if (existing.length > 0) {
                    return;
                }

                let merged = [];
                try {
                    const legacyPromoRaw = localStorage.getItem(LEGACY_PROMO_KEY);
                    const legacyPromo = legacyPromoRaw ? JSON.parse(legacyPromoRaw) : [];
                    if (Array.isArray(legacyPromo)) {
                        merged = merged.concat(legacyPromo.map((it) => ({
                            key: 'promocion:' + String((it && it.id) || ''),
                            type: 'promocion',
                            id: String((it && it.id) || ''),
                            title: String((it && it.title) || 'Promocion'),
                            price: Number((it && it.price) || 0),
                            qty: Math.max(1, Number((it && it.qty) || 1)),
                            vigencia: String((it && it.vigencia) || '')
                        })));
                    }
                } catch (e) {}

                try {
                    const legacyExamRaw = localStorage.getItem(LEGACY_EXAM_KEY);
                    const legacyExam = legacyExamRaw ? JSON.parse(legacyExamRaw) : [];
                    if (Array.isArray(legacyExam)) {
                        merged = merged.concat(legacyExam.map((it) => ({
                            key: 'examen:' + String((it && it.id) || ''),
                            type: 'examen',
                            id: String((it && it.id) || ''),
                            title: String((it && it.title) || 'Examen'),
                            price: Number((it && it.price) || 0),
                            qty: 1,
                            codigo: String((it && it.codigo) || ''),
                            tiempo_respuesta: String((it && it.tiempo_respuesta) || ''),
                            tipo_tubo: String((it && it.tipo_tubo) || '')
                        })));
                    }
                } catch (e) {}

                if (merged.length) {
                    setCart(merged);
                }
            }

            function getDraft() {
                try {
                    const raw = localStorage.getItem(FORM_DRAFT_KEY);
                    const parsed = raw ? JSON.parse(raw) : {};
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (e) {
                    return {};
                }
            }

            function saveDraft(data) {
                const current = getDraft();
                const next = Object.assign({}, current, data || {});
                localStorage.setItem(FORM_DRAFT_KEY, JSON.stringify(next));
            }

            function readFormData() {
                return {
                    nombre: (nombreEl || {}).value || '',
                    telefono: (telefonoEl || {}).value || '',
                    modalidad: (modalidadEl || {}).value || 'laboratorio',
                    fecha: (fechaEl || {}).value || '',
                    direccion: (direccionEl || {}).value || '',
                    obs: (obsEl || {}).value || ''
                };
            }

            function applyDraft() {
                const draft = getDraft();
                if (nombreEl && draft.nombre) nombreEl.value = draft.nombre;
                if (telefonoEl && draft.telefono) telefonoEl.value = draft.telefono;
                if (modalidadEl && draft.modalidad) modalidadEl.value = draft.modalidad;
                if (fechaEl && draft.fecha) fechaEl.value = draft.fecha;
                if (direccionEl && draft.direccion) direccionEl.value = draft.direccion;
                if (obsEl && draft.obs) obsEl.value = draft.obs;
            }

            function formatMoney(value) {
                return 'S/ ' + (Number(value) || 0).toFixed(2);
            }

            function computeDiscount(subtotal) {
                if (!(DISCOUNT_ACTIVE && DISCOUNT_APPLY && DISCOUNT_PERCENT > 0)) {
                    return 0;
                }
                const raw = Number(subtotal || 0) * (Number(DISCOUNT_PERCENT) / 100);
                return Math.max(0, Number(raw.toFixed(2)));
            }

            function render() {
                const items = getCart();
                const qty = items.reduce((acc, it) => acc + (Number(it.qty) || 0), 0);
                if (countEl) countEl.textContent = String(qty);

                if (!tbody || !emptyEl || !totalEl) {
                    return;
                }

                tbody.innerHTML = '';
                let total = 0;
                items.forEach((it) => {
                    const price = Number(it.price) || 0;
                    const rowSubtotal = price * (Number(it.qty) || 0);
                    total += rowSubtotal;
                    const tr = document.createElement('tr');
                    const isPromo = it.type === 'promocion';
                    const tipoLabel = isPromo ? 'Promoción' : 'Examen';
                    const tipoChip = '<span class="badge ' + (isPromo ? 'text-bg-warning' : 'text-bg-info') + '">' + tipoLabel + '</span>';
                    tr.innerHTML = '' +
                        '<td>' + escapeHtml(it.title || 'Item') + '</td>' +
                        '<td>' + tipoChip + '</td>' +
                        '<td>' + (price > 0 ? formatMoney(price) : 'Por confirmar') + '</td>' +
                        '<td>' + String(Number(it.qty) || 0) + '</td>' +
                        '<td>' + formatMoney(rowSubtotal) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-key="' + escapeHtml(String(it.key || '')) + '">Quitar</button></td>';
                    tbody.appendChild(tr);
                });

                emptyEl.style.display = items.length ? 'none' : 'block';
                table.style.display = items.length ? '' : 'none';
                const discount = computeDiscount(total);
                const finalTotal = Math.max(0, total - discount);

                if (subtotalEl) {
                    subtotalEl.textContent = formatMoney(total);
                }
                if (discountWrapEl && discountEl) {
                    if (discount > 0) {
                        discountWrapEl.style.display = '';
                        discountEl.textContent = '-' + formatMoney(discount);
                    } else {
                        discountWrapEl.style.display = 'none';
                        discountEl.textContent = '-' + formatMoney(0);
                    }
                }
                totalEl.textContent = formatMoney(finalTotal);
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function addPromo(payload) {
                const items = getCart();
                const promoId = String(payload.id || '');
                if (!promoId) return;
                const promoKey = 'promocion:' + promoId;
                const idx = items.findIndex((it) => String(it.key) === promoKey);
                if (idx >= 0) {
                    items[idx].qty = (Number(items[idx].qty) || 0) + 1;
                } else {
                    items.push({
                        key: promoKey,
                        type: 'promocion',
                        id: promoId,
                        title: payload.title || 'Promocion',
                        price: Number(payload.price) || 0,
                        vigencia: payload.vigencia || '',
                        qty: 1
                    });
                }
                setCart(items);
                render();
            }

            document.querySelectorAll('[data-add-promo="1"]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    addPromo({
                        id: this.getAttribute('data-promo-id') || '',
                        title: this.getAttribute('data-promo-titulo') || 'Promocion',
                        price: this.getAttribute('data-promo-precio') || '0',
                        vigencia: this.getAttribute('data-promo-vigencia') || ''
                    });
                });
            });

            if (tbody) {
                tbody.addEventListener('click', function (ev) {
                    const target = ev.target;
                    if (!(target instanceof HTMLElement)) return;
                    const removeKey = target.getAttribute('data-remove-key');
                    if (!removeKey) return;
                    const next = getCart().filter((it) => String(it.key) !== String(removeKey));
                    setCart(next);
                    render();
                });
            }

            const clearBtn = document.getElementById('promoCartClearB');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    setCart([]);
                    render();
                });
            }

            if (modalidadEl && direccionWrapEl) {
                const toggleDireccion = function () {
                    direccionWrapEl.style.display = modalidadEl.value === 'domicilio' ? '' : 'none';
                    if (fechaHintEl) {
                        fechaHintEl.textContent = modalidadEl.value === 'domicilio'
                            ? 'Selecciona la fecha en la que deseas la toma de muestra a domicilio.'
                            : 'Selecciona la fecha en la que deseas asistir al laboratorio.';
                    }
                };
                modalidadEl.addEventListener('change', toggleDireccion);
                toggleDireccion();
            }

            [nombreEl, telefonoEl, modalidadEl, fechaEl, direccionEl, obsEl].forEach((el) => {
                if (!el) return;
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function () {
                    saveDraft(readFormData());
                });
            });

            const quoteBtn = document.getElementById('promoQuoteWhatsappB');
            if (quoteBtn) {
                quoteBtn.addEventListener('click', function () {
                    const items = getCart();
                    if (!items.length) {
                        alert('Agrega al menos una promocion al carrito.');
                        return;
                    }
                    if (!WA_BASE) {
                        alert('No hay WhatsApp configurado en el sistema.');
                        return;
                    }

                    const formData = readFormData();
                    const nombre = formData.nombre;
                    const telefono = formData.telefono;
                    const modalidad = formData.modalidad;
                    const fecha = formData.fecha;
                    const direccion = formData.direccion;
                    const obs = formData.obs;
                    saveDraft(formData);

                    let total = 0;
                    const promoItems = items.filter((it) => it.type === 'promocion');
                    const examItems = items.filter((it) => it.type !== 'promocion');

                    const promoLines = promoItems.map((it, idx) => {
                        const price = Number(it.price) || 0;
                        const rowSubtotal = price * (Number(it.qty) || 0);
                        total += rowSubtotal;
                        let line = (idx + 1) + '. ' + (it.title || 'Promocion') + ' x' + (Number(it.qty) || 0);
                        line += price > 0 ? (' - ' + formatMoney(rowSubtotal)) : ' - Precio por confirmar';
                        if (it.vigencia) {
                            line += ' | Vigencia: ' + it.vigencia;
                        }
                        return line;
                    });

                    const examLines = examItems.map((it, idx) => {
                        const price = Number(it.price) || 0;
                        const rowSubtotal = price * (Number(it.qty) || 1);
                        total += rowSubtotal;
                        const extras = [];
                        if (it.codigo) extras.push('Codigo: ' + it.codigo);
                        if (it.tiempo_respuesta) extras.push('Tiempo: ' + it.tiempo_respuesta);
                        if (it.tipo_tubo) extras.push('Tubo: ' + it.tipo_tubo);
                        let line = (idx + 1) + '. ' + (it.title || 'Examen') + ' - ' + formatMoney(rowSubtotal);
                        if (extras.length) {
                            line += ' | ' + extras.join(' | ');
                        }
                        return line;
                    });

                    const parts = [
                        'Hola, deseo cotizar en ' + EMPRESA + ':',
                        '',
                    ];

                    if (promoLines.length) {
                        parts.push('Promociones:', promoLines.join('\n'), '');
                    }
                    if (examLines.length) {
                        parts.push('Examenes:', examLines.join('\n'), '');
                    }

                    const discount = computeDiscount(total);
                    const finalTotal = Math.max(0, total - discount);

                    parts.push(
                        'Subtotal referencial: ' + formatMoney(total),
                        (discount > 0 ? 'Descuento web (' + String(Number(DISCOUNT_PERCENT)) + '%): -' + formatMoney(discount) : 'Descuento web: No aplica'),
                        'Total referencial: ' + formatMoney(finalTotal),
                        '',
                        'Datos para coordinar la atención:',
                        'Paciente: ' + (nombre || 'No indicado'),
                        'Teléfono: ' + (telefono || 'No indicado'),
                        'Modalidad: ' + (modalidad === 'domicilio' ? 'A domicilio' : 'En laboratorio'),
                        'Fecha deseada: ' + (fecha || 'No indicada')
                    );

                    if (modalidad === 'domicilio') {
                        parts.push('Dirección: ' + (direccion || 'No indicada'));
                    }
                    if (obs) {
                        parts.push('Duda o indicación: ' + obs);
                    }
                    parts.push('', 'Por favor, me brindan referencia para agendar.');

                    const message = parts.join('\n');
                    let link = '';
                    try {
                        const u = new URL(WA_BASE, window.location.href);
                        u.searchParams.set('text', message);
                        link = u.toString();
                    } catch (e) {
                        link = WA_BASE + (WA_BASE.indexOf('?') >= 0 ? '&' : '?') + 'text=' + encodeURIComponent(message);
                    }
                    window.open(link, '_blank');
                });
            }

            migrateLegacyCart();
            applyDraft();
            render();
        })();
    </script>
    <script>
        window.APP_PWA = {
            manifestUrl: <?= json_encode($pwaManifestHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            swUrl: <?= json_encode($pwaServiceWorkerHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            scope: <?= json_encode($pwaScope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
    <script src="<?= htmlspecialchars($pwaRegisterScriptHref, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($pwaInstallScriptHref, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
