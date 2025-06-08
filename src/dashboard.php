<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
include __DIR__ . '/componentes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/componentes/sidebar.php'; ?>
    <main class="flex-grow-1" style="margin-left:250px;">
        <?php
        $vista = isset($_GET['vista']) ? $_GET['vista'] : 'clientes';

        switch ($_SESSION['rol']) {
            case 'admin':
                switch ($vista) {
                    case 'usuarios':
                        include __DIR__ . '/usuarios/tabla_usuarios.php';
                        break;
                    case 'form_usuarios':
                        include __DIR__ . '/usuarios/form_usuarios.php';
                        break;
                    case 'eliminar_usuario':
                        include __DIR__ . '/usuarios/eliminar_usuario.php';
                        break;
                    case 'editar_usuario':
                        include __DIR__ . '/usuarios/editar_usuario.php';
                        break;
                    case 'clientes':
                        include __DIR__ . '/clientes/tabla_clientes.php';
                        break;
                    case 'form_clientes':
                        include __DIR__ . '/clientes/form_clientes.php';
                        break;
                    case 'eliminar_cliente':
                        include __DIR__ . '/clientes/eliminar_cliente.php';
                        break;
                    case 'editar_cliente':
                        include __DIR__ . '/clientes/editar_cliente.php';
                        break;
                    case 'empresas':
                        include __DIR__ . '/empresas/tabla_empresas.php';
                        break;
                    case 'form_empresa':
                        include __DIR__ . '/empresas/form_empresa.php';
                        break;
                    case 'eliminar_empresa':
                        include __DIR__ . '/empresas/eliminar_empresa.php';
                        break;
                    case 'editar_empresa':
                        include __DIR__ . '/empresas/editar_empresa.php';
                        break;
                    default:
                        echo '<div class="container mt-5"><div class="alert alert-warning">Vista no encontrada.</div></div>';
                }
                break;

            case 'empresa':
                include __DIR__ . '/empresas/vistas/panel_empresa.php';
                break;

            case 'recepcionista':
                include __DIR__ . '/usuarios/vistas/panel_recepcionista.php';
                break;

            case 'laboratorista':
                include __DIR__ . '/usuarios/vistas/panel_laboratorista.php';
                break;

            case 'cliente':
                include __DIR__ . '/clientes/vistas/panel_cliente.php';
                break;

            default:
                echo '<div class="container mt-5"><div class="alert alert-warning">Rol no reconocido.</div></div>';
        }
        ?>

    </main>

</div>

<?php include __DIR__ . '/componentes/footer.php'; ?>