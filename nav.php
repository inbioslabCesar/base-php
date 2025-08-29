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
                     <a class="nav-link active" href="index.php" style="color:<?= htmlspecialchars($color_texto) ?>;">
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