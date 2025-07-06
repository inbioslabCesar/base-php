<?php
require_once __DIR__ . '/src/conexion/conexion.php';

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

        .btn-custom {
            background: <?= htmlspecialchars($color_botones) ?> !important;
            color: #fff !important;
            border: none;
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
            height: 340px;
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
                <img src="/base-php/src/<?= htmlspecialchars($logo) ?>?ver=<?= time() ?>" alt="Logo Empresa" class="logo-navbar">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLab"
                aria-controls="navbarLab" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarLab">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="#inicio"><?= htmlspecialchars($menu_inicio) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#servicios"><?= htmlspecialchars($menu_servicios) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonios"><?= htmlspecialchars($menu_testimonios) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto"><?= htmlspecialchars($menu_contacto) ?></a></li>
                    <li class="nav-item"><a href="src/auth/login.php" class="btn btn-light ms-3">Acceso Clientes</a></li>
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
            <h5>Conócenos</h5>
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($imagenes_institucionales as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="institucional-img" alt="Imagen institucional">
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- INICIO -->
    <section id="inicio" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7 mb-4 mb-md-0">
                    <h1 class="mb-3" style="color:<?= htmlspecialchars($color_principal) ?>;">
                        Bienvenido a <?= htmlspecialchars($nombre_empresa) ?>
                    </h1>
                    <p>
                        <?= htmlspecialchars($frase_promocion ?: 'Somos un laboratorio clínico comprometido con la calidad y la atención personalizada.') ?>
                    </p>
                    <?php if (!empty($oferta_mes)): ?>
                        <div class="alert alert-success mt-2"><?= htmlspecialchars($oferta_mes) ?></div>
                    <?php endif; ?>
                    <a href="#servicios" class="btn btn-custom mt-2"><?= htmlspecialchars($menu_servicios) ?></a>
                </div>
                <div class="col-md-5 text-center">
                    <?php if (!empty($imagenes_institucionales[0])): ?>
                        <img src="<?= htmlspecialchars($imagenes_institucionales[0]) ?>" alt="Imagen institucional" style="max-width:250px;border-radius:10px;">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- SERVICIOS EN CARDS -->
    <section id="servicios" class="py-5 bg-light">
        <div class="container">
            <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;"><?= htmlspecialchars($menu_servicios) ?></h2>
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
            <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;"><?= htmlspecialchars($menu_contacto) ?></h2>
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

    <!-- PIE DE PÁGINA -->
    <footer class="mt-5 py-4 text-center">
        © <?= date('Y') ?> <?= htmlspecialchars($nombre_empresa) ?>. Todos los derechos reservados.
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>

</html>