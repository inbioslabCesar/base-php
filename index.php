
<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/conexion/conexion.php';

// Redirección canónica por empresa (opcional)
// Define CANONICAL_HOST en src/config/empresas/<empresa>.php, ej.: 'jeycolab.com' o 'www.inbioslabstore.com'
// No aplica en entornos locales
$hostActual = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = in_array($hostActual, ['localhost', '127.0.0.1'], true);
$canonicalHost = defined('CANONICAL_HOST') ? constant('CANONICAL_HOST') : null;
if ($canonicalHost && !$isLocalHost && strcasecmp($hostActual, $canonicalHost) !== 0) {
    $esHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    $scheme = $esHttps ? 'https' : 'http';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: ' . $scheme . '://' . $canonicalHost . $uri, true, 301);
    exit;
}

// Consulta de promociones solo para clientes y todos
$stmtPromo = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (tipo_publico = 'clientes' OR tipo_publico = 'todos') AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin OR vigente = 1) ORDER BY fecha_inicio DESC");
$promociones = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$config_empresa = $stmt->fetch(PDO::FETCH_ASSOC);

$nombre_empresa    = $config_empresa['nombre'] ?? 'Laboratorio Ejemplo';
$color_principal   = $config_empresa['color_principal'] ?? '#0d6efd';
$color_secundario  = $config_empresa['color_secundario'] ?? '#f8f9fa';

$color_footer      = $config_empresa['color_footer'] ?? '#343a40';
$color_botones     = $config_empresa['color_botones'] ?? '#198754'; // Nuevo campo
$color_texto       = $config_empresa['color_texto'] ?? '#212529';   // Nuevo campo
$tamano_letra      = $config_empresa['tamano_letra'] ?? '1rem';     // Nuevo campo
$logo              = !empty($config_empresa['logo']) ? $config_empresa['logo'] : '../uploads/empresa/logo_empresa.png';
if (preg_match('/^data:image\//i', (string)$logo)) {
    $logo = '../uploads/empresa/logo_empresa.png';
}
$frase_promocion   = $config_empresa['frase_promocion'] ?? '';
$oferta_mes        = $config_empresa['oferta_mes'] ?? '';
$imagenes_carrusel = [];
if (!empty($config_empresa['imagenes_carrusel'])) {
    $tmp = json_decode($config_empresa['imagenes_carrusel'], true);
    if (is_array($tmp)) $imagenes_carrusel = $tmp;
}
$imagenes_institucionales = [];
if (!empty($config_empresa['imagenes_institucionales'])) {
    $tmp = json_decode($config_empresa['imagenes_institucionales'], true);
    if (is_array($tmp)) $imagenes_institucionales = $tmp;
}
$servicios         = [];
if (!empty($config_empresa['servicios'])) {
    $tmp = json_decode($config_empresa['servicios'], true);
    if (is_array($tmp)) $servicios = $tmp;
}
$testimonios       = [];
if (!empty($config_empresa['testimonios'])) {
    $tmp = json_decode($config_empresa['testimonios'], true);
    if (is_array($tmp)) $testimonios = $tmp;
}
$redes_sociales    = [];
if (!empty($config_empresa['redes_sociales'])) {
    $tmp = json_decode($config_empresa['redes_sociales'], true);
    if (is_array($tmp)) $redes_sociales = $tmp;
}
$menu_inicio       = $config_empresa['menu_inicio'] ?? 'Inicio';
$menu_servicios    = $config_empresa['menu_servicios'] ?? 'Servicios';
$menu_testimonios  = $config_empresa['menu_testimonios'] ?? 'Testimonios';
$menu_contacto     = $config_empresa['menu_contacto'] ?? 'Contacto';

$logoRel = ltrim((string)$logo, '/');
$logoRel = preg_replace('#^\.\./+#', '', $logoRel);
// BASE_URL suele terminar en /src/; para recursos publicos usamos la base del sitio.
$siteBasePath = rtrim((string)dirname(rtrim((string)BASE_URL, '/')), '/\\');
if ($siteBasePath === '.' || $siteBasePath === '') {
    $siteBasePath = '';
}
$logoPublicPath = ($siteBasePath === '' ? '' : $siteBasePath) . '/' . $logoRel;

$logoAbsPath = __DIR__ . '/' . $logoRel;
if (!file_exists($logoAbsPath)) {
    $logoAbsPath = __DIR__ . '/src/' . $logoRel;
}
$logoVersion = file_exists($logoAbsPath) ? filemtime($logoAbsPath) : time();
$logoFaviconHref = $logoPublicPath . '?v=' . $logoVersion;

$faviconIcoHref = ($siteBasePath === '' ? '' : $siteBasePath) . '/favicon.ico';
$faviconDynamicHref = ($siteBasePath === '' ? '' : $siteBasePath) . '/src/favicon.php?v=' . $logoVersion;

// Detección robusta de esquema/host (útil detrás de proxies/CDN).
$esHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
$hostHeader = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocolo = (!$isLocalHost) ? 'https' : ($esHttps ? 'https' : 'http');
$dominio   = $protocolo . '://' . $hostHeader;
$canonical = $dominio . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <!-- Fuerza upgrade a HTTPS en clientes modernos -->
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <!-- Favicon dinámico por empresa (Google suele priorizar este recurso) -->
    <link rel="icon" href="<?= htmlspecialchars($faviconDynamicHref, ENT_QUOTES, 'UTF-8') ?>" type="image/png" sizes="48x48">
    <!-- Logo configurado como fallback -->
    <link rel="icon" href="<?= htmlspecialchars($logoFaviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Favicon .ico de respaldo con ruta correcta del proyecto -->
    <link rel="icon" href="<?= htmlspecialchars($faviconIcoHref, ENT_QUOTES, 'UTF-8') ?>" sizes="any" type="image/x-icon">
    <!-- Favicon .ico para máxima compatibilidad -->
    <link rel="shortcut icon" href="<?= htmlspecialchars($faviconIcoHref, ENT_QUOTES, 'UTF-8') ?>" type="image/x-icon">
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars($logoFaviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($nombre_empresa) ?> | Laboratorio Clínico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: <?= htmlspecialchars($color_secundario) ?>;
            color: <?= htmlspecialchars($color_texto) ?>;
            font-size: <?= htmlspecialchars($tamano_letra) ?>;
        }

        .navbar,
        .btn-primary {
            background: <?= htmlspecialchars($color_principal) ?> !important;
        }

        .navbar-toggler {
            border-color: <?= htmlspecialchars($color_secundario) ?> !important;
            /* Cambia por tu color */
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,<?=
                                                                    rawurlencode(
                                                                        "<svg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'>
                <path stroke='" . $color_secundario . "' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/>
            </svg>"
                                                                    )
                                                                    ?>");
        }

        .btn-custom {
            background: <?= htmlspecialchars($color_botones) ?> !important;
            border: none;
        }

        .btn-custom:hover {
            filter: brightness(0.9);
        }

        .navbar-brand,
        .btn-primary,
        .btn-custom {
            color: #fff !important;
        }

        footer {
            background: <?= htmlspecialchars($color_footer) ?> !important;
            color: #fff;
        }

        .carousel-inner img {
            object-fit: cover;
            width: 100%;
            height: 700px;
        }


        .logo-navbar {
           height: 150px; /* o el tamaño que prefieras */
           width: auto;
        }

        .institucional-img {
            max-width: 180px;
            border-radius: 10px;
            margin: 10px;
        }

        .servicios-slider .card {
            min-width: 300px;
            margin: 0 10px;
        }

        .servicios-slider {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
        }

        .servicios-slider::-webkit-scrollbar {
            display: none;
        }

        .card-body {
            background: <?= htmlspecialchars($color_botones) ?> !important;
            color: white;

        }

        .card-title {
            color: <?= htmlspecialchars($color_secundario) ?>;
        }

        .card-testimonio {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px #0001;
            padding: 1.5rem;
            margin: 0.5rem 0;
        }

        .bi {
            vertical-align: middle;
        }

        /* Botón flotante WhatsApp */
        .whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 999;
            background: #25d366;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px #0002;
            font-size: 2.2em;
            transition: background 0.3s;
        }

        .whatsapp-float:hover {
            background: #128c7e;
            color: #fff;
        }

        .carousel-inner img,
        .carousel-item img {
            margin: 0 auto;
            display: block;
            border-radius: 20px;
            box-shadow: 0 4px 24px #0002;
            border: 4px solid #fff;
            /* O usa tu color corporativo */
            max-width: 90%;
            max-height: 400px;
            object-fit: cover;
        }

        @media (max-width: 768px) {

            .carousel-inner img,
            .carousel-item img {
                max-height: 220px;
            }
        }

        .promo-section-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #f6f7f8ff;
            margin-bottom: 2rem;
            letter-spacing: 1px;
        }

        .promo-img-container,
        .promo-desc-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 220px;
            border-radius: 20px;
            box-shadow: 0 4px 24px #0002;
            background: #fff;
            padding: 0;
        }

        .promo-img-container img {
            border-radius: 20px;
            border: 4px solid #fff;
            max-width: 90%;
            max-height: 200px;
            object-fit: cover;
            box-shadow: 0 2px 12px #0001;
        }

        .promo-desc-container {
            border: 2px solid #e3e3e3;
            background: #f8f9fa;
            min-height: 220px;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 24px #0002;
        }

        @media (max-width: 768px) {

            .promo-img-container,
            .promo-desc-container {
                min-height: unset;
                max-height: unset;
                padding: 10px;
            }

            .promo-img-container img {
                max-width: 100%;
                max-height: 180px;
            }
        }

        .btn-primary {
            background-color: #0069d9 !important;
            color: #fff !important;
            font-weight: 600;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3 !important;
            color: #fff !important;
        }

        /* Título de la sección de ubicación */
        .titulo-ubicacion {
            color: #fff;
        }

        /* Ubicación (home) */
        .ubicacion-section {
            padding: 42px 0;
            background: linear-gradient(120deg,
                    <?= htmlspecialchars($color_principal) ?> 0%,
                    <?= htmlspecialchars($color_secundario) ?> 55%,
                    rgba(255, 255, 255, 0.65) 100%);
        }

        .ubicacion-card {
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 22px;
            padding: 28px;
            backdrop-filter: blur(8px);
        }

        .ubicacion-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #101828;
            letter-spacing: -0.02em;
        }

        .ubicacion-subtitle {
            color: #475467;
        }

        .ubicacion-link {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
        }

        .ubicacion-link:hover {
            text-decoration: underline;
        }

        .ubicacion-controls {
            height: 100%;
        }

        .ubicacion-btn {
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, 0.10);
        }

        .ubicacion-hint {
            color: #667085;
            font-size: 0.95rem;
            min-height: 1.2em;
        }

        .ubicacion-place {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 18px;
            padding: 16px;
        }

        .ubicacion-place-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(29, 78, 216, 0.10);
            color: #1d4ed8;
            font-size: 1.4rem;
        }

        .ubicacion-place-name {
            font-weight: 800;
            color: #101828;
            line-height: 1.1;
        }

        .ubicacion-place-sub {
            color: #475467;
            font-size: 0.95rem;
        }

        .ubicacion-map-wrap {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            overflow: hidden;
            height: 100%;
            min-height: 420px;
        }

        .ubicacion-map-wrap iframe {
            display: block;
        }

        @media (max-width: 768px) {
            .ubicacion-card {
                padding: 18px;
            }

            .ubicacion-title {
                font-size: 1.7rem;
            }

            .ubicacion-map-wrap {
                min-height: 320px;
            }
        }
    </style>
    <script type="application/ld+json">
        <?php
        $logo_url = $dominio . $logoPublicPath;
        $json_ld = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $nombre_empresa,
            "url" => $dominio,
            "logo" => $logo_url
        ];
        echo json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        ?>
    </script>

</head>

<body>
    <?php include 'nav.php'; // Menú de navegación 
    ?>

    <!-- Botón flotante de WhatsApp -->

    <main>
        <?php
        // Enrutamiento de vistas
        if (isset($_GET['vista'])) {
            if ($_GET['vista'] === 'detalle_promocion_publico' && isset($_GET['id'])) {
                include 'src/promociones/detalle_promocion_publico.php';
            } elseif ($_GET['vista'] === 'otra_vista') {
                include 'src/otra_vista.php';
            } else {
                include 'home.php';
            }
        } else {
            include 'home.php';
        }
        ?>
    </main>
    <!-- PIE DE PÁGINA Y SCRIPTS -->
    <footer class="mt-5 py-4 text-center">
        © <?= date('Y') ?> <?= htmlspecialchars($nombre_empresa) ?>. Todos los derechos reservados.
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Si tienes otros scripts, agrégalos aquí -->
</body>

</html>