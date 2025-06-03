<aside style="width:200px; position:fixed; left:0; top:0; height:100vh; background:#343a40; color:#fff; padding-top:20px;">
    <nav>
        <ul style="list-style:none; padding:0; margin:0;">
            <?php if ($_SESSION['rol'] == 'admin'): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=clientes" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Clientes</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Usuarios</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Empresas</a>
                </li>
            <?php elseif ($_SESSION['rol'] == 'recepcionista'): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=cotizar" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Cotizar Exámenes</a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=crear_cliente" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Registrar Cliente</a>
                </li>
                <!-- Agrega más enlaces para recepcionista aquí -->
            <?php elseif ($_SESSION['rol'] == 'operador' || $_SESSION['rol'] == 'quimico'): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=reportes" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Reportes de Resultados</a>
                </li>
                <!-- Agrega más enlaces para operador/químico aquí -->
            <?php elseif ($_SESSION['rol'] == 'empresa'): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=panel_empresa" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Panel Empresa</a>
                </li>
                <!-- Más enlaces para empresa -->
            <?php elseif ($_SESSION['rol'] == 'cliente'): ?>
                <li>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=panel_cliente" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Mi Panel</a>
                </li>
                <!-- Más enlaces para cliente -->
            <?php endif; ?>
            <li>
                <a href="<?= BASE_URL ?>auth/logout.php" style="color:#fff; text-decoration:none; display:block; padding:12px 24px;">Cerrar sesión</a>
            </li>
        </ul>
    </nav>
</aside>
