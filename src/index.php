<?php
require_once __DIR__ . '/conexion/conexion.php';

// Consulta la configuración de la empresa
$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$config_empresa = $stmt->fetch(PDO::FETCH_ASSOC);

// Asigna valores seguros
$nombre_empresa = $config_empresa['nombre'] ?? 'Laboratorio Ejemplo';
$color_principal = $config_empresa['color_principal'] ?? '#0d6efd';
$color_secundario = $config_empresa['color_secundario'] ?? '#f8f9fa';
$logo = (!empty($config_empresa['logo']))
    ? htmlspecialchars($config_empresa['logo'])
    : 'https://upload.wikimedia.org/wikipedia/commons/6/6b/Logo_universo.png';



// Imágenes del slider (si tienes en BD, cámbialo; aquí se usan externas por defecto)
$slider_imgs = [
    'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=800&q=80'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($nombre_empresa) ?> | Laboratorio Clínico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: <?= htmlspecialchars($color_secundario) ?>;
        }
        .navbar, .btn-primary {
            background: <?= htmlspecialchars($color_principal) ?> !important;
        }
        .navbar-brand, .btn-primary {
            color: #fff !important;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:<?= htmlspecialchars($color_principal) ?>;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
           <img src="<?= $logo ?>" alt="Logo" style="max-width:200px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLab"
            aria-controls="navbarLab" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarLab">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="#inicio">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#servicios">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonios">Testimonios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contacto">Contacto</a>
                </li>
                <li class="nav-item">
                    <a href="auth/login.php" class="btn btn-light ms-3">Acceso Clientes</a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- Slider dinámico -->
<div id="sliderEmpresa" class="carousel slide mt-3" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php foreach($slider_imgs as $i => $img): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= $img ?>" class="d-block w-100" style="max-height:350px;object-fit:cover;" alt="Slider <?= $i+1 ?>">
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
<!-- Sección de información de la empresa -->
<div class="container mt-5">
    <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
            <h2 class="mb-3" style="color:<?= htmlspecialchars($color_principal) ?>;">
                Bienvenido a <?= htmlspecialchars($nombre_empresa) ?>
            </h2>
            <p>
                Dirección: <?= htmlspecialchars($config_empresa['direccion'] ?? 'No disponible') ?><br>
                Teléfono: <?= htmlspecialchars($config_empresa['telefono'] ?? 'No disponible') ?><br>
                Celular: <?= htmlspecialchars($config_empresa['celular'] ?? 'No disponible') ?><br>
                Email: <?= htmlspecialchars($config_empresa['email'] ?? 'No disponible') ?><br>
                RUC: <?= htmlspecialchars($config_empresa['ruc'] ?? 'No disponible') ?>
            </p>
            <a href="login.php" class="btn btn-primary mt-2">Acceso para clientes y médicos</a>
        </div>
        <div class="col-md-6 text-center">
            <img src="<?= $logo ?>" alt="Logo" style="max-width:200px;">
        </div>
    </div>
</div>
<!-- INICIO -->
<section id="inicio" class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h1 class="mb-3" style="color:<?= htmlspecialchars($color_principal) ?>;">
                    Bienvenido a <?= htmlspecialchars($nombre_empresa) ?>
                </h1>
                <p>
                    Somos un laboratorio clínico comprometido con la calidad y la atención personalizada.<br>
                    Consulta tus resultados en línea y accede a nuestros servicios de vanguardia.
                </p>
                <a href="#servicios" class="btn btn-primary mt-2">Ver Servicios</a>
            </div>
            <div class="col-md-6 text-center">
                <img src="<?= $logo ?>" alt="Logo" style="max-width:250px;">
            </div>
        </div>
    </div>
</section>

<!-- SERVICIOS -->
<section id="servicios" class="py-5 bg-light">
    <div class="container">
        <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;">Nuestros Servicios</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Análisis Clínicos</h5>
                        <p class="card-text">Hemogramas, perfiles bioquímicos, pruebas hormonales y más.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Pruebas Especializadas</h5>
                        <p class="card-text">Marcadores tumorales, estudios de coagulación, pruebas inmunológicas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Atención Personalizada</h5>
                        <p class="card-text">Asesoría médica y entrega de resultados en línea de forma segura.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIOS -->
<section id="testimonios" class="py-5">
    <div class="container">
        <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;">Testimonios</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <blockquote class="blockquote">
                    <p class="mb-0">"Excelente atención y resultados confiables. Recomiendo el laboratorio."</p>
                    <footer class="blockquote-footer">Paciente 1</footer>
                </blockquote>
            </div>
            <div class="col-md-6 mb-4">
                <blockquote class="blockquote">
                    <p class="mb-0">"Muy profesionales y rápidos, todo el proceso fue sencillo y seguro."</p>
                    <footer class="blockquote-footer">Paciente 2</footer>
                </blockquote>
            </div>
        </div>
    </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="py-5 bg-light">
    <div class="container">
        <h2 class="mb-4 text-center" style="color:<?= htmlspecialchars($color_principal) ?>;">Contacto</h2>
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
                        <label for="mensaje" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="mensaje" rows="3" placeholder="Escribe tu mensaje"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar mensaje</button>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Dirección:</h5>
                <p><?= htmlspecialchars($config_empresa['direccion'] ?? 'No disponible') ?></p>
                <h5>Teléfono:</h5>
                <p><?= htmlspecialchars($config_empresa['telefono'] ?? 'No disponible') ?></p>
                <h5>Email:</h5>
                <p><?= htmlspecialchars($config_empresa['email'] ?? 'No disponible') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- PIE DE PÁGINA -->
<footer class="mt-5 py-4 text-center" style="background:<?= htmlspecialchars($color_principal) ?>;color:#fff;">
    © <?= date('Y') ?> <?= htmlspecialchars($nombre_empresa) ?>. Todos los derechos reservados.
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
