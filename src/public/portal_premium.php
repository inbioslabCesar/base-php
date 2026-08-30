<?php
$heroTitle = !empty($frase_promocion) ? (string)$frase_promocion : ('Bienvenido a ' . (string)$nombre_empresa);
$heroSubtitle = !empty($oferta_mes)
    ? (string)$oferta_mes
    : 'Resultados confiables, procesos modernos y atencion humana en cada etapa de tu diagnostico.';

$serviciosData = is_array($servicios) ? $servicios : [];
$testimoniosData = is_array($testimonios) ? $testimonios : [];
$promocionesData = is_array($promociones) ? $promociones : [];
$imgCarrusel = is_array($imagenes_carrusel) ? $imagenes_carrusel : [];
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

    // Caso historico: solo nombre de archivo guardado en promociones.imagen
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

$btnTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_botones)
    : '#ffffff';
$navTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_principal)
    : '#ffffff';
$footerTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_footer)
    : '#ffffff';

$previewServices = [];
foreach ($serviciosData as $srv) {
    if (!is_array($srv)) {
        continue;
    }
    $previewServices[] = [
        'titulo' => (string)($srv['titulo'] ?? 'Servicio Especializado'),
        'descripcion' => (string)($srv['descripcion'] ?? 'Atencion integral con protocolos de laboratorio.'),
    ];
    if (count($previewServices) >= 6) {
        break;
    }
}
if (empty($previewServices)) {
    $previewServices = [
        ['titulo' => 'Analisis Clinicos', 'descripcion' => 'Paneles de rutina y pruebas de alta precision.'],
        ['titulo' => 'Perfil Hormonal', 'descripcion' => 'Indicadores clave para evaluaciones medicas completas.'],
        ['titulo' => 'Microbiologia', 'descripcion' => 'Procesamiento y lectura con criterios estandarizados.'],
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

$waButtonsCount = count($waFloatingButtons);
$waExtraOffset = max(0, $waButtonsCount - 2) * 8;
$waStackBottomDesktop = min(300, 156 + $waExtraOffset);
$waStackBottomMobile = min(280, 148 + $waExtraOffset);

$pwaBasePath = isset($siteBasePath) ? rtrim((string)$siteBasePath, '/\\') : '';
if ($pwaBasePath === '.' || $pwaBasePath === '/') {
    $pwaBasePath = '';
}
$pwaManifestHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/manifest.php';
$pwaServiceWorkerHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/sw.js';
$pwaScope = $pwaBasePath === '' ? '/' : ($pwaBasePath . '/');
$pwaRegisterScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-register.js';
$pwaInstallScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-install.js';

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
    <title><?= htmlspecialchars($nombre_empresa) ?> | Portal Premium</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="manifest" href="<?= htmlspecialchars($pwaManifestHref, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --p-primary: <?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>;
            --p-secondary: <?= htmlspecialchars($color_secundario, ENT_QUOTES, 'UTF-8') ?>;
            --p-footer: <?= htmlspecialchars($color_footer, ENT_QUOTES, 'UTF-8') ?>;
            --p-button: <?= htmlspecialchars($color_botones, ENT_QUOTES, 'UTF-8') ?>;
            --p-text: <?= htmlspecialchars($color_texto, ENT_QUOTES, 'UTF-8') ?>;
            --p-btn-text: <?= htmlspecialchars($btnTextContrast, ENT_QUOTES, 'UTF-8') ?>;
            --p-nav-text: <?= htmlspecialchars($navTextContrast, ENT_QUOTES, 'UTF-8') ?>;
            --p-footer-text: <?= htmlspecialchars($footerTextContrast, ENT_QUOTES, 'UTF-8') ?>;
        }

        body {
            background: linear-gradient(160deg, var(--p-secondary) 0%, #ffffff 42%, var(--p-secondary) 100%);
            color: var(--p-text);
            font-size: <?= htmlspecialchars($tamano_letra, ENT_QUOTES, 'UTF-8') ?>;
        }

        .navbar {
            background: var(--p-primary) !important;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .navbar .nav-link,
        .navbar .navbar-brand {
            color: var(--p-nav-text) !important;
        }

        .logo-navbar-shell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            padding: 4px 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .logo-navbar {
            height: 72px;
            width: auto;
            max-height: 72px;
            object-fit: contain;
            display: block;
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 3.8rem 0 2.8rem;
            background: radial-gradient(circle at 12% 15%, rgba(255,255,255,0.28), transparent 35%),
                        radial-gradient(circle at 90% 90%, rgba(255,255,255,0.14), transparent 30%),
                        linear-gradient(130deg, var(--p-primary), var(--p-footer));
            color: var(--p-nav-text);
        }

        .hero-panel {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 1.6rem;
        }

        .hero-carousel,
        .hero-carousel .carousel-item,
        .hero-carousel img {
            width: 100%;
            border-radius: 18px;
        }

        .hero-carousel img {
            height: 300px;
            object-fit: cover;
            display: block;
            box-shadow: 0 10px 24px rgba(0,0,0,0.18);
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.05;
            margin-bottom: 0.8rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .hero p {
            font-size: 1.05rem;
            opacity: 0.96;
        }

        .btn-premium {
            background: var(--p-button);
            color: var(--p-btn-text);
            border: none;
            border-radius: 999px;
            padding: 0.68rem 1.25rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .btn-premium:hover {
            filter: brightness(0.92);
            color: var(--p-btn-text);
        }

        .section-title {
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1.2rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .service-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            transition: transform .22s ease, box-shadow .22s ease;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.09);
        }

        .institucional-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .institucional-grid img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.06);
            background: #eef3f7;
        }

        .service-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--p-secondary);
            color: var(--p-primary);
            margin-bottom: 0.6rem;
            font-size: 1.2rem;
        }

        .promo-card {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 8px 22px rgba(0,0,0,0.05);
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

        .promo-media {
            width: 100%;
            height: 190px;
            object-fit: contain;
            display: block;
            background: #ffffff;
            padding: 6px;
            border-top: 1px solid rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }

        .testimonial-quote {
            background: #fff;
            border-left: 4px solid var(--p-button);
            border-radius: 14px;
            padding: 1rem;
            box-shadow: 0 8px 22px rgba(0,0,0,0.05);
            height: 100%;
        }

        .cta-wrap {
            border-radius: 20px;
            background: linear-gradient(140deg, rgba(255,255,255,0.85), rgba(255,255,255,0.6));
            border: 1px solid rgba(0,0,0,0.06);
            padding: 1.4rem;
        }

        .map-card {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(0,0,0,0.05);
        }

        .map-frame {
            width: 100%;
            height: 220px;
            border: 0;
            display: block;
            background: #eef3f7;
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
            color: var(--p-text);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .social-link i {
            font-size: 1.05rem;
        }

        .social-link:hover {
            border-color: var(--p-button);
            color: var(--p-button);
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

        .promo-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            background: var(--p-button);
            color: var(--p-btn-text);
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

        footer {
            background: var(--p-footer);
            color: var(--p-footer-text);
            margin-top: 2.5rem;
        }

        @media (max-width: 991.98px) {
            .services-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .hero {
                padding: 2.6rem 0 2rem;
            }
            .logo-navbar {
                height: 54px;
                max-height: 54px;
            }
            .logo-navbar-shell {
                padding: 3px 8px;
                border-radius: 12px;
            }
            .services-grid {
                grid-template-columns: 1fr;
            }
            .promo-media {
                height: 170px;
            }
            .hero-carousel img {
                height: 220px;
            }
            .institucional-grid {
                grid-template-columns: 1fr;
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
    <?php include __DIR__ . '/../../nav.php'; ?>

    <section class="hero" id="inicio">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-7">
                    <div class="hero-panel">
                        <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mb-3"><?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                        <a class="btn btn-premium" href="#contacto">Agendar consulta</a>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <?php if (!empty($imgCarrusel[0])): ?>
                        <div id="premiumHeroCarouselA" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="3200">
                            <div class="carousel-inner">
                                <?php foreach ($imgCarrusel as $idx => $img): ?>
                                    <?php $imgUrl = $toPublicAsset((string)$img); ?>
                                    <?php if ($imgUrl === '') continue; ?>
                                    <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Carrusel <?= (int)$idx + 1 ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#premiumHeroCarouselA" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#premiumHeroCarouselA" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Siguiente</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="hero-panel">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-heart-pulse-fill fs-2"></i>
                                <div>
                                    <strong>Portal Premium Activo</strong>
                                    <div>Experiencia visual mejorada para pacientes.</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-4" id="servicios">
        <h2 class="section-title">Servicios Destacados</h2>
        <div class="services-grid">
            <?php foreach ($previewServices as $i => $srv): ?>
                <article class="service-card">
                    <div class="service-icon"><i class="bi bi-activity"></i></div>
                    <h6 class="mb-1"><?= htmlspecialchars($srv['titulo'], ENT_QUOTES, 'UTF-8') ?></h6>
                    <p class="mb-0 text-muted"><?= htmlspecialchars($srv['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($institucionalData)): ?>
    <section class="container py-2" id="institucional">
        <h2 class="section-title">Conocenos</h2>
        <div class="institucional-grid">
            <?php foreach ($institucionalData as $imgIns): ?>
                <?php $insUrl = $toPublicAsset((string)$imgIns); ?>
                <?php if ($insUrl === '') continue; ?>
                <img src="<?= htmlspecialchars($insUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Institucional" loading="lazy" onerror="this.style.display='none';">
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="container py-2">
        <h2 class="section-title">Promociones</h2>
        <?php if (!empty($promocionesData)): ?>
            <?php if (count($promocionesData) >= 2): ?>
                <?php $promoSlidesDesktop = array_chunk($promocionesData, 2); ?>
                <?php $promoSlidesMobile = array_chunk($promocionesData, 1); ?>

                <div class="d-none d-md-block">
                    <div id="premiumPromoCarouselA" class="carousel slide promo-carousel" data-bs-ride="carousel" data-bs-interval="5500">
                        <div class="carousel-inner">
                            <?php foreach ($promoSlidesDesktop as $slideIdx => $promoSlide): ?>
                                <div class="carousel-item <?= $slideIdx === 0 ? 'active' : '' ?>">
                                    <div class="row g-3">
                                        <?php foreach ($promoSlide as $promo): ?>
                                            <div class="col-12 col-md-6">
                                                <article class="promo-card h-100">
                                                    <?php if (!empty($promo['imagen'])): ?>
                                                        <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promo" class="promo-media" loading="lazy" onerror="this.style.display='none';">
                                                    <?php else: ?>
                                                        <div class="promo-media d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-stars fs-2"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="p-3">
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
                                                                <a class="btn btn-sm btn-premium" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
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
                                                </article>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#premiumPromoCarouselA" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#premiumPromoCarouselA" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    </div>
                </div>

                <div class="d-md-none">
                    <div id="premiumPromoCarouselAMobile" class="carousel slide promo-carousel" data-bs-ride="carousel" data-bs-interval="5500">
                        <div class="carousel-inner">
                            <?php foreach ($promoSlidesMobile as $slideIdx => $promoSlide): ?>
                                <?php $promo = $promoSlide[0]; ?>
                                <div class="carousel-item <?= $slideIdx === 0 ? 'active' : '' ?>">
                                    <article class="promo-card h-100">
                                        <?php if (!empty($promo['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promo" class="promo-media" loading="lazy" onerror="this.style.display='none';">
                                        <?php else: ?>
                                            <div class="promo-media d-flex align-items-center justify-content-center">
                                                <i class="bi bi-stars fs-2"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="p-3">
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
                                                    <a class="btn btn-sm btn-premium" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
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
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#premiumPromoCarouselAMobile" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#premiumPromoCarouselAMobile" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($promocionesData as $promo): ?>
                        <div class="col-12 col-md-4">
                            <article class="promo-card h-100">
                                <?php if (!empty($promo['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($promoImageUrl($promo), ENT_QUOTES, 'UTF-8') ?>" alt="Promo" class="promo-media" loading="lazy" onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div class="promo-media d-flex align-items-center justify-content-center">
                                        <i class="bi bi-stars fs-2"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="p-3">
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
                                            <a class="btn btn-sm btn-premium" href="index.php?vista=detalle_promocion_publico&id=<?= (int)$promo['id'] ?>">Ver detalle</a>
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
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="row g-3">
                <div class="col-12">
                    <div class="cta-wrap">No hay promociones activas por el momento.</div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="container py-4" id="testimonios">
        <h2 class="section-title">Testimonios</h2>
        <div class="row g-3">
            <?php if (!empty($testimoniosData)): ?>
                <?php foreach (array_slice($testimoniosData, 0, 3) as $t): ?>
                    <div class="col-12 col-md-4">
                        <div class="testimonial-quote">
                            <p class="mb-2">"<?= htmlspecialchars((string)($t['texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"</p>
                            <strong><?= htmlspecialchars((string)($t['autor'] ?? 'Paciente'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="cta-wrap">Aun no hay testimonios publicados.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="container py-4" id="contacto">
        <div class="cta-wrap">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-8">
                    <h3 class="mb-1"><?= htmlspecialchars((string)$nombre_empresa, ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php if (!empty($contactoDirecciones)): ?>
                        <?php foreach ($contactoDirecciones as $dir): ?>
                            <p class="mb-0">
                                <?php if (!empty($dir['nombre'])): ?>
                                    <strong><?= htmlspecialchars((string)$dir['nombre'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                                <?php endif; ?>
                                <?= htmlspecialchars((string)$dir['direccion'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if (!empty($dir['telefonos']) && is_array($dir['telefonos'])): ?>
                                <?php foreach ($dir['telefonos'] as $tel): ?>
                                    <?php $waLink = $buildWhatsAppLink((string)$tel, (string)($dir['nombre'] ?? '')); ?>
                                    <p class="mb-0 small text-muted">
                                        <strong>Telefono:</strong> <?= htmlspecialchars((string)$tel, ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($waLink !== ''): ?>
                                            | <a class="wa-inline-link" href="<?= htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                                        <?php endif; ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <small class="text-muted">Telefono: <?= htmlspecialchars((string)($config_empresa['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | Celular general: <?= htmlspecialchars((string)($config_empresa['celular'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <a class="btn btn-premium" href="src/auth/login.php">Acceso Clientes</a>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($socialLinks)): ?>
    <section class="container py-2" id="redes">
        <h2 class="section-title">Redes Sociales</h2>
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

    <section class="container py-2" id="ubicaciones">
        <h2 class="section-title">Ubicaciones</h2>
        <div class="row g-3">
            <?php if (!empty($ubicacionesData)): ?>
                <?php foreach ($ubicacionesData as $ub): ?>
                    <div class="col-12 col-lg-6">
                        <article class="map-card h-100">
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
                                <iframe class="map-frame" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= htmlspecialchars((string)$ub['maps_embed'], ENT_QUOTES, 'UTF-8') ?>"></iframe>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><div class="cta-wrap">No hay ubicaciones configuradas.</div></div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="py-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <span>© <?= date('Y') ?> <?= htmlspecialchars((string)$nombre_empresa, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </footer>

    <button type="button" class="cart-fab" data-bs-toggle="modal" data-bs-target="#promoCartModalA" aria-label="Abrir carrito de promociones">
        <i class="bi bi-cart3"></i>
        <span class="badge" id="promoCartCountA">0</span>
    </button>

    <div class="modal fade" id="promoCartModalA" tabindex="-1" aria-labelledby="promoCartModalALabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promoCartModalALabel">Carrito de promociones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="promoCartEmptyA" class="alert alert-light border">Aun no agregaste promociones.</div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="promoCartTableA">
                            <thead>
                                <tr>
                                    <th>Promocion</th>
                                    <th>Vigencia</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <strong>Total: <span id="promoCartTotalA">S/ 0.00</span></strong>
                    </div>

                    <h6 class="mb-2">Datos para cotizacion</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre del paciente</label>
                            <input type="text" class="form-control" id="cotNombreA" placeholder="Nombres y apellidos">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Telefono de contacto</label>
                            <input type="text" class="form-control" id="cotTelefonoA" placeholder="9XXXXXXXX">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Modalidad de toma</label>
                            <select class="form-select" id="cotModalidadA">
                                <option value="laboratorio">En laboratorio</option>
                                <option value="domicilio">A domicilio</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha tentativa</label>
                            <input type="date" class="form-control" id="cotFechaA">
                        </div>
                        <div class="col-12" id="cotDireccionWrapA" style="display:none;">
                            <label class="form-label">Direccion para toma a domicilio</label>
                            <input type="text" class="form-control" id="cotDireccionA" placeholder="Direccion exacta y referencia">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" id="cotObsA" rows="2" placeholder="Ej: ayuno, horario preferido, referencia adicional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="promoCartClearA">Vaciar carrito</button>
                    <button type="button" class="btn btn-premium" id="promoQuoteWhatsappA">Cotizar por WhatsApp</button>
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
            const CART_KEY = 'promo_cart_v1';
            const WA_BASE = <?= json_encode($whatsAppHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const EMPRESA = <?= json_encode((string)$nombre_empresa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            const countEl = document.getElementById('promoCartCountA');
            const table = document.getElementById('promoCartTableA');
            const tbody = table ? table.querySelector('tbody') : null;
            const emptyEl = document.getElementById('promoCartEmptyA');
            const totalEl = document.getElementById('promoCartTotalA');

            const modalidadEl = document.getElementById('cotModalidadA');
            const direccionWrapEl = document.getElementById('cotDireccionWrapA');

            function getCart() {
                try {
                    const raw = localStorage.getItem(CART_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    return [];
                }
            }

            function setCart(items) {
                localStorage.setItem(CART_KEY, JSON.stringify(items));
            }

            function formatMoney(value) {
                return 'S/ ' + (Number(value) || 0).toFixed(2);
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
                    tr.innerHTML = '' +
                        '<td>' + escapeHtml(it.title || 'Promocion') + '</td>' +
                        '<td>' + escapeHtml(it.vigencia || '-') + '</td>' +
                        '<td>' + (price > 0 ? formatMoney(price) : 'Por confirmar') + '</td>' +
                        '<td>' + String(Number(it.qty) || 0) + '</td>' +
                        '<td>' + formatMoney(rowSubtotal) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-id="' + String(it.id || '') + '">Quitar</button></td>';
                    tbody.appendChild(tr);
                });

                emptyEl.style.display = items.length ? 'none' : 'block';
                table.style.display = items.length ? '' : 'none';
                totalEl.textContent = formatMoney(total);
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
                const idx = items.findIndex((it) => String(it.id) === promoId);
                if (idx >= 0) {
                    items[idx].qty = (Number(items[idx].qty) || 0) + 1;
                } else {
                    items.push({
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
                    const removeId = target.getAttribute('data-remove-id');
                    if (!removeId) return;
                    const next = getCart().filter((it) => String(it.id) !== String(removeId));
                    setCart(next);
                    render();
                });
            }

            const clearBtn = document.getElementById('promoCartClearA');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    setCart([]);
                    render();
                });
            }

            if (modalidadEl && direccionWrapEl) {
                const toggleDireccion = function () {
                    direccionWrapEl.style.display = modalidadEl.value === 'domicilio' ? '' : 'none';
                };
                modalidadEl.addEventListener('change', toggleDireccion);
                toggleDireccion();
            }

            const quoteBtn = document.getElementById('promoQuoteWhatsappA');
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

                    const nombre = (document.getElementById('cotNombreA') || {}).value || '';
                    const telefono = (document.getElementById('cotTelefonoA') || {}).value || '';
                    const modalidad = (document.getElementById('cotModalidadA') || {}).value || 'laboratorio';
                    const fecha = (document.getElementById('cotFechaA') || {}).value || '';
                    const direccion = (document.getElementById('cotDireccionA') || {}).value || '';
                    const obs = (document.getElementById('cotObsA') || {}).value || '';

                    let total = 0;
                    const lines = items.map((it, idx) => {
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

                    const parts = [
                        'Hola, deseo cotizar las siguientes promociones en ' + EMPRESA + ':',
                        '',
                        'Promociones:',
                        lines.join('\n'),
                        '',
                        'Total referencial: ' + formatMoney(total),
                        '',
                        'Datos para agendar toma de muestra:',
                        'Paciente: ' + (nombre || 'No indicado'),
                        'Telefono: ' + (telefono || 'No indicado'),
                        'Modalidad: ' + (modalidad === 'domicilio' ? 'A domicilio' : 'En laboratorio'),
                        'Fecha tentativa: ' + (fecha || 'No indicada')
                    ];

                    if (modalidad === 'domicilio') {
                        parts.push('Direccion: ' + (direccion || 'No indicada'));
                    }
                    if (obs) {
                        parts.push('Observaciones: ' + obs);
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
