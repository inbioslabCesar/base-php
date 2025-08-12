<?php
require_once __DIR__ . '/src/conexion/conexion.php';
require_once __DIR__ . '/src/config/config.php';

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
$logo              = !empty($config_empresa['logo']) ? $config_empresa['logo'] : 'images/empresa/logo_empresa.png';
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="./src/images/inbioslab-logo.svg" />
    <title><?= htmlspecialchars($nombre_empresa) ?> | Laboratorio Clínico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            max-width: 100px;
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
    </style>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "MEDDITECH",
            "url": "https://www.medditech.es",
            "logo": "https://www.medditech.es/src/images/empresa/logo_empresa.png"
        }
    </script>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="<?= BASE_URL ?><?= htmlspecialchars($logo) ?>?ver=<?= time() ?>" alt="Logo Empresa" class="logo-navbar">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLab"
                aria-controls="navbarLab" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarLab">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#inicio" style="color:<?= htmlspecialchars($color_texto) ?>;">
                            <?= htmlspecialchars($menu_inicio) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#servicios" style="color:<?= htmlspecialchars($color_texto) ?>;">
                            <?= htmlspecialchars($menu_servicios) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonios" style="color:<?= htmlspecialchars($color_texto) ?>;">
                            <?= htmlspecialchars($menu_testimonios) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto" style="color:<?= htmlspecialchars($color_texto) ?>;">
                            <?= htmlspecialchars($menu_contacto) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="src/auth/login.php"
                            class="btn ms-3"
                            style="background:<?= htmlspecialchars($color_secundario) ?>; color:#fff; border:none;">
                            Acceso Clientes
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <!-- Carrusel dinámico y amigable -->
    <?php if (count($imagenes_carrusel) > 0): ?>
        <div id="sliderEmpresa" class="carousel slide mt-3" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($imagenes_carrusel as $i => $img): ?>
                    <button type="button" data-bs-target="#sliderEmpresa" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                        aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($imagenes_carrusel as $i => $img): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <img src="src/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Slider <?= $i + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#sliderEmpresa" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#sliderEmpresa" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Galería de imágenes institucionales -->
    <?php if (count($imagenes_institucionales) > 0): ?>
        <section class="container my-4 text-center">
            <h5 style="color:<?= htmlspecialchars($color_principal) ?>;">Conócenos</h5>
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($imagenes_institucionales as $img): ?>
                    <img src="src/<?= htmlspecialchars($img) ?>" class="institucional-img" alt="Imagen institucional">
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <!-- Carrusel de Promociones (solo clientes y todos) -->
    <?php if ($promociones): ?>
        <div class="container my-5">
            <h2 class="promo-section-title" style="color:<?= htmlspecialchars($color_principal) ?>;">
                <i class="bi bi-stars"></i> Promociones Especiales para Ti
            </h2>
            <div id="promoCarousel" class="carousel slide mb-4 shadow-sm rounded" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($promociones as $i => $promo): ?>
                        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($promociones as $i => $promo): ?>
                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                            <div class="row justify-content-center align-items-center bg-light rounded p-3 flex-column flex-md-row">
                                <div class="col-md-5 mb-3 mb-md-0 promo-img-container order-1 order-md-1">
                                    <?php if (!empty($promo['imagen']) && file_exists(__DIR__ . "/src/promociones/assets/" . $promo['imagen'])): ?>
                                        <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" alt="Promo">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white text-center py-5 rounded w-100">Sin imagen</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-7 promo-desc-container order-2 order-md-2">
                                    <div>
                                        <h4 class="card-title text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                        <?php
                                        $descripcion_corta = mb_strimwidth($promo['descripcion'], 0, 100, '...');
                                        ?>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($descripcion_corta)) ?></p>
                                        <?php if ($promo['precio_promocional'] > 0): ?>
                                            <span class="badge bg-warning text-dark fs-5 mb-2">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                        <?php endif; ?>
                                        <?php if ($promo['descuento'] > 0): ?>
                                            <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                                        <?php endif; ?>
                                        <div class="text-muted mb-1">
                                            <i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                                            <?php if ($promo['vigente']): ?>
                                                <span class="badge bg-primary ms-2">¡Promoción vigente!</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-2">No vigente</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="src/dashboard.php?vista=detalle_promocion&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">
                                            Ver detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    <?php endif; ?>


    <!-- INICIO -->
    <section id="inicio" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7 mb-4 mb-md-0">
                    <h1 class="mb-3" style="color:<?= htmlspecialchars($color_principal) ?>;">
                        Bienvenido a <?= htmlspecialchars($nombre_empresa) ?>
                    </h1>
                    <p style="color:<?= htmlspecialchars($color_principal) ?>;">
                        <?= htmlspecialchars($frase_promocion ?: 'Somos un laboratorio clínico comprometido con la calidad y la atención personalizada.') ?>
                    </p>
                    <?php if (!empty($oferta_mes)): ?>
                        <div class="alert alert-success mt-2"><?= htmlspecialchars($oferta_mes) ?></div>
                    <?php endif; ?>
                    <a href="#servicios" class="btn btn-custom mt-2"><?= htmlspecialchars($menu_servicios) ?></a>
                </div>
                <div class="col-md-5 text-center">
                    <?php if (!empty($imagenes_institucionales[0])): ?>
                        <img src="src/<?= htmlspecialchars($imagenes_institucionales[0]) ?>" alt="Imagen institucional" style="max-width:250px;border-radius:10px;">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- SERVICIOS EN CARDS -->
    <section id="servicios" class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_secundario) ?>;"><?= htmlspecialchars($menu_servicios) ?></h2>
            <div class="row">
                <?php foreach ($servicios as $servicio): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($servicio['titulo']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($servicio['descripcion']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SLIDER DE SERVICIOS (solo muestra 3 a la vez, auto-scroll con JS) -->
    <section class="py-5">
        <div class="container">
            <h3 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;">Servicios destacados</h3>
            <div class="servicios-slider" id="serviciosSlider">
                <?php foreach ($servicios as $servicio): ?>
                    <div class="card shadow-sm mx-2" style="min-width: 300px; max-width: 320px;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($servicio['titulo']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($servicio['descripcion']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <script>
        // Auto-scroll slider de servicios (muestra 3, avanza cada 3s)
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('serviciosSlider');
            let scrollAmount = 0;
            let cardWidth = 320; // igual al max-width de las cards
            let visibleCards = 3;
            setInterval(() => {
                if (slider.scrollWidth - slider.clientWidth - scrollAmount <= 10) {
                    scrollAmount = 0;
                } else {
                    scrollAmount += cardWidth;
                }
                slider.scrollTo({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }, 3000);
        });
    </script>
    <!-- TESTIMONIOS EN CARDS -->
    <section id="testimonios" class="py-5">
        <div class="container">
            <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;"><?= htmlspecialchars($menu_testimonios) ?></h2>
            <div class="row">
                <?php foreach ($testimonios as $testimonio): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card-testimonio">
                            <p class="mb-2" style="font-style:italic;">"<?= htmlspecialchars($testimonio['texto']) ?>"</p>
                            <div class="text-end text-muted">— <?= htmlspecialchars($testimonio['autor']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CONTACTO -->
    <section id="contacto" class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_secundario) ?>;"><?= htmlspecialchars($menu_contacto) ?></h2>
            <div class="row">
                <div class="col-md-6">
                    <form>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" placeholder="Tu nombre">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" placeholder="tu@email.com">
                        </div>
                        <div class="mb-3">
                            <label for="celular" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="celular" placeholder="Tu celular">
                        </div>
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje" rows="3" placeholder="Escribe tu mensaje"></textarea>
                        </div>
                        <button type="submit" class="btn btn-custom">Enviar mensaje</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h5>Dirección:</h5>
                    <p><?= htmlspecialchars($config_empresa['direccion'] ?? 'No disponible') ?></p>
                    <h5>Teléfono:</h5>
                    <p><?= htmlspecialchars($config_empresa['telefono'] ?? 'No disponible') ?></p>
                    <h5>Celular:</h5>
                    <p><?= htmlspecialchars($config_empresa['celular'] ?? 'No disponible') ?></p>
                    <h5>Email:</h5>
                    <p><?= htmlspecialchars($config_empresa['email'] ?? 'No disponible') ?></p>
                    <?php if (!empty($redes_sociales) && is_array($redes_sociales)): ?>
                        <div class="mt-3">
                            <span>Síguenos:</span>
                            <?php
                            function icono_red($nombre)
                            {
                                switch (strtolower($nombre)) {
                                    case 'facebook':
                                        return '<i class="bi bi-facebook"></i>';
                                    case 'instagram':
                                        return '<i class="bi bi-instagram"></i>';
                                    case 'whatsapp':
                                        return '<i class="bi bi-whatsapp"></i>';
                                    default:
                                        return '<i class="bi bi-link-45deg"></i>';
                                }
                            }
                            foreach ($redes_sociales as $red): ?>
                                <a href="<?= htmlspecialchars($red['url']) ?>" target="_blank" style="margin:0 8px; font-size:1.5em;">
                                    <?= icono_red($red['nombre']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- Botón flotante de WhatsApp -->
     
    <?php
$whatsapp_numero = null;
foreach ($redes_sociales as $red) {
    if (strtolower($red['nombre']) === 'whatsapp') {
        $whatsapp_numero = preg_replace('/\D/', '', $red['url']); // Extrae solo números
        break;
    }
}
?>
<?php if ($whatsapp_numero): ?>
    <a href="https://wa.me/<?= $whatsapp_numero ?>" class="whatsapp-float" target="_blank">
        <i class="bi bi-whatsapp"></i>
    </a>
<?php endif; ?>


    <!-- PIE DE PÁGINA Y SCRIPTS -->
    <footer class="mt-5 py-4 text-center">
        © <?= date('Y') ?> <?= htmlspecialchars($nombre_empresa) ?>. Todos los derechos reservados.
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Si tienes otros scripts, agrégalos aquí -->
</body>

</html>