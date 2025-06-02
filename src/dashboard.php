<?php session_start();
require_once __DIR__ . '/config/config.php';
if (!isset($_SESSION['usuario'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión primero.';
    header('Location: auth/login.php');
    exit();
}
$rol = $_SESSION['rol'] ?? '';
include __DIR__ . '/componentes/header.php';
include __DIR__ . '/componentes/navbar.php'; ?> <div style="display: flex;"> <?php include __DIR__ . '/componentes/sidebar.php'; ?> <div class="main" style="flex: 1; padding: 20px;">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? ''); ?> (<?php echo htmlspecialchars($rol); ?>)</h2> <?php 
        if ($rol === 'cliente') {
                    // Solo bienvenida para clientes 
                    echo "<p>Bienvenido a INBIOSLAB. No tienes acceso a esta sección.</p>";
                } else {
                    // Admin, empresa, etc. ven el CRUD 
                    if (isset($_GET['vista'])) {
                        switch ($_GET['vista']) {
                            case 'tabla_clientes':
                                include __DIR__ . '/clientes/tabla_clientes.php';
                                break;
                            case 'form_clientes':
                                include __DIR__ . '/clientes/form_clientes.php';
                                break;
                            case 'editar_cliente':
                                if (isset($_GET['id'])) {
                                    include __DIR__ . '/clientes/editar_cliente.php';
                                }
                                break;
                            case 'eliminar_cliente':
                                if (isset($_GET['id'])) {
                                    include __DIR__ . '/clientes/eliminar_cliente.php';
                                }
                                break; // ...otros módulos... 
                            default:
                                echo "<p>Vista no encontrada.</p>";
                                break;
                        }
                    } else {
                        include __DIR__ . '/clientes/tabla_clientes.php';
                    }
                } ?>
    </div>
</div> <?php include __DIR__ . '/componentes/footer.php'; ?>