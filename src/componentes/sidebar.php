<nav id="sidebarMenu" class="sidebar-custom d-lg-block p-4" style="width:270px; min-height:100vh;">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" class="nav-link">
                <i class="bi bi-people"></i> Usuarios
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" class="nav-link">
                <i class="bi bi-building"></i> Empresas
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>dashboard.php?vista=clientes" class="nav-link">
                <i class="bi bi-person"></i> Clientes
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </a>
        </li>
    </ul>
</nav>
