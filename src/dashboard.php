<?php
session_start();
require_once __DIR__ . '/config/config.php';
if (!isset($_SESSION['rol'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
include_once __DIR__ . '/componentes/header.php';
?>

<div style="display:flex; min-height:100vh;">
    <?php include_once __DIR__ . '/componentes/sidebar.php'; ?>
    <main style="flex:1; padding:20px;">
        <?php
        $vista = $_GET['vista'] ?? 'clientes';
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
                include __DIR__ . '/usuarios/eliminar_empresa.php';
                break;
            case 'editar_empresa':
                include __DIR__ . '/empresas/editar_empresa.php';
                break;
            default:
                echo "<div class='alert alert-warning'>Vista no encontrada.</div>";
                break;
        }
        ?>
    </main>
</div>

<?php include_once __DIR__ . '/componentes/footer.php'; ?>