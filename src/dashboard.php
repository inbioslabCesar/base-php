<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        //usuarios funciones que devuelve header()
        case 'crear_usuario':
            include __DIR__ . '/usuarios/crear_usuario.php';
            break;
        case 'eliminar_usuario':
            include __DIR__ . '/usuarios/eliminar_usuario.php';
            break;
        case 'editar_usuario':
            include __DIR__ . '/usuarios/editar_usuario.php';
            break;
        //clientes funciones que devuelve header()
        case 'crear_cliente':
            include __DIR__ . '/clientes/crear.php';
            break;
        case 'eliminar_cliente':
            include __DIR__ . '/clientes/eliminar.php';
            break;
        case 'editar_cliente':
            include __DIR__ . '/clientes/editar.php';
            break;
        //empresas funciones que devuelve header()
        case 'crear_empresa':
            include __DIR__ . '/empresas/crear_empresa.php';
            break;
        case 'eliminar_empresa':
            include __DIR__ . '/empresas/eliminar_empresa.php';
            break;
        case 'editar_empresa':
            include __DIR__ . '/empresas/editar_empresa.php';
            break;
        //convenios funciones que devuelve header()
        case 'crear_convenio':
            include __DIR__ . '/convenios/crear_convenio.php';
            break;
        case 'eliminar_convenio':
            include __DIR__ . '/convenios/eliminar_convenio.php';
            break;
        case 'editar_convenio':
            include __DIR__ . '/convenios/editar_convenio.php';
            break;
            // //examenes funciones que devuelve header()
            case 'crear_examen':
                include __DIR__ . '/examenes/crear_examen.php';
                break;
            case 'eliminar_examen':
                include __DIR__ . '/examenes/eliminar_examen.php';
                break;
            case 'editar_examen':
                include __DIR__ . '/examenes/editar_examen.php';
                break;
    }
}
include __DIR__ . '/componentes/header.php';
include __DIR__ . '/componentes/sidebar.php';
?>

<main class="flex-grow-1" style="margin-left:250px;">
    <?php // Mostrar mensajes de éxito o error
    if (isset($_SESSION['mensaje'])) {
        echo '<div class="alert alert-info">' . $_SESSION['mensaje'] . '</div>';
        unset($_SESSION['mensaje']); // Elimina el mensaje para que solo se muestre una vez
    }

    $vista = isset($_GET['vista']) ? $_GET['vista'] : 'admin';

    switch ($_SESSION['rol']) {
        case 'admin':
            switch ($vista) {
                //admin vistas
                case 'admin':
                    include __DIR__ . '/usuarios/vistas/panel_admin.php';
                    break;

                //usuarios vistas
                case 'usuarios':
                    include __DIR__ . '/usuarios/usuarios.php';
                    break;
                case 'form_usuario':
                    include __DIR__ . '/usuarios/form_usuario.php';
                    break;

                //clientes vistas
                case 'clientes':
                    include __DIR__ . '/clientes/clientes.php';
                    break;
                case 'form_cliente':
                    include __DIR__ . '/clientes/form_cliente.php';
                    break;

                //empresas vistas
                case 'empresas':
                    include __DIR__ . '/empresas/empresas.php';
                    break;
                case 'form_empresa':
                    include __DIR__ . '/empresas/form_empresa.php';
                    break;

                //convenio CRUD vistas
                case 'convenios':
                    include __DIR__ . '/convenios/convenios.php';
                    break;
                case 'form_convenio':
                    include __DIR__ . '/convenios/form_convenio.php';
                    break;

                //examenes vistas
                case 'examenes':
                    include __DIR__ . '/examenes/examenes.php';
                    break;
                    case 'form_examen':
                    include __DIR__ . '/examenes/form_examen.php';
                    break;

                // case 'detalle_examen':
                //     include __DIR__ . '/examenes/vistas/detalle_examenes.php';
                //     break;
                default:
                    echo '<div class="container mt-5"><div class="alert alert-warning">Vista no encontrada.</div></div>';
            }
            break;
        //vista empresas
        case 'empresa':
            include __DIR__ . '/empresas/vistas/panel_empresa.php';
            break;
        //vista recepcionista
        case 'recepcionista':
            include __DIR__ . '/usuarios/vistas/panel_recepcionista.php';
            break;
        //vista laboratorista
        case 'laboratorista':
            include __DIR__ . '/usuarios/vistas/panel_laboratorista.php';
            break;
        //vista cliente
        case 'cliente':
            include __DIR__ . '/clientes/vistas/panel_cliente.php';
            break;

        default:
            echo '<div class="container mt-5"><div class="alert alert-warning">Rol no reconocido.</div></div>';
    }
    ?>

</main>

<?php include __DIR__ . '/componentes/footer.php'; ?>