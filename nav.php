 <?php $logoBgNavbar = isset($logo_fondo_navbar) ? (string)$logo_fondo_navbar : '#ffffff'; ?>
 <nav class="navbar navbar-expand-lg navbar-dark">
     <div class="container">
         <a class="navbar-brand d-flex align-items-center" href="#">
             <span class="logo-navbar-shell" style="background:<?= htmlspecialchars($logoBgNavbar, ENT_QUOTES, 'UTF-8') ?>;">
                 <img src="<?= BASE_URL ?><?= htmlspecialchars($logo) ?>?ver=<?= time() ?>" alt="Logo Empresa" class="logo-navbar">
             </span>
         </a>
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLab"
             aria-controls="navbarLab" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon"></span>
         </button>
         <div class="collapse navbar-collapse" id="navbarLab">
             <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                 <li class="nav-item">
                     <a class="nav-link active" href="index.php" style="color:<?= htmlspecialchars($color_navbar_texto ?? $color_texto) ?>;">
                         <?= htmlspecialchars($menu_inicio) ?>
                     </a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#servicios" style="color:<?= htmlspecialchars($color_navbar_texto ?? $color_texto) ?>;">
                         <?= htmlspecialchars($menu_servicios) ?>
                     </a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#testimonios" style="color:<?= htmlspecialchars($color_navbar_texto ?? $color_texto) ?>;">
                         <?= htmlspecialchars($menu_testimonios) ?>
                     </a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#contacto" style="color:<?= htmlspecialchars($color_navbar_texto ?? $color_texto) ?>;">
                         <?= htmlspecialchars($menu_contacto) ?>
                     </a>
                 </li>
                 <li class="nav-item">
                     <a href="src/auth/login.php"
                         class="btn ms-3"
                         style="background:<?= htmlspecialchars($color_botones) ?>; color:<?= htmlspecialchars($color_boton_texto ?? '#fff') ?>; border:none;">
                         Acceso Clientes
                     </a>
                 </li>

             </ul>
         </div>
     </div>
 </nav>