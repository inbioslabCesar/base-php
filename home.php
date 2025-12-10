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
                                     <a href="index.php?vista=detalle_promocion_publico&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">
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

  <!-- Ubicación del Laboratorio -->
 <div class="container mt-4">
    <h4>Ubicación del Laboratorio</h4>
    <div style="width:100%;max-width:600px;margin:auto;">
        <iframe src="https://www.google.com/maps?q=INBIOSLAB+LABORATORIO+CLINICO&output=embed" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</div>

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