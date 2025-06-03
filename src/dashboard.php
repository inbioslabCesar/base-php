<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
include_once 'componentes/header.php';
include_once 'componentes/navbar.php';
include_once 'componentes/sidebar.php';

$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$vista = isset($_GET['vista']) ? htmlspecialchars($_GET['vista']) : '';

// Saludo y control de vistas por rol
echo '<main style="margin-left:200px; padding:20px; min-height:80vh;">';

if ($rol == 'cliente') {
    echo "<h2>Bienvenido, $nombre</h2>";
    echo "<p>Accede a cotizar exámenes, ver tus resultados y más funciones próximamente.</p>";
} elseif ($rol == 'empresa') {
    echo "<h2>Bienvenida empresa, $nombre</h2>";
    echo "<p>Aquí verás tus funciones empresariales (en desarrollo).</p>";
} elseif ($rol == 'admin') {
    echo "<h2>Bienvenido administrador, $nombre</h2>";
    switch ($vista) {
        // CRUD CLIENTES
        case 'clientes':
            include __DIR__ . '/clientes/tabla_clientes.php';
            break;
        case 'crear_cliente':
            include __DIR__ . '/clientes/form_clientes.php';
            break;
        case 'editar_cliente':
            include __DIR__ . '/clientes/editar_cliente.php';
            break;
        case 'eliminar_cliente':
            include __DIR__ . '/clientes/eliminar_cliente.php';
            break;
        // CRUD USUARIOS
        case 'usuarios':
            include __DIR__ . '/usuarios/tabla_usuarios.php';
            break;
        case 'crear_usuario':
            include __DIR__ . '/usuarios/form_usuarios.php';
            break;
        case 'editar_usuario':
            include __DIR__ . '/usuarios/editar_usuario.php';
            break;
        case 'eliminar_usuario':
            include __DIR__ . '/usuarios/eliminar_usuario.php';
            break;
        // CRUD EMPRESAS
        case 'empresas':
            include __DIR__ . '/empresas/tabla_empresas.php';
            break;
        case 'crear_empresa':
            include __DIR__ . '/empresas/form_empresa.php';
            break;
        case 'editar_empresa':
            include __DIR__ . '/empresas/editar_empresa.php';
            break;
        case 'eliminar_empresa':
            include __DIR__ . '/empresas/eliminar_empresa.php';
            break;
        default:
            echo "<p>Selecciona una opción del menú para comenzar.</p>";
            break;
    }
} elseif ($rol == 'recepcionista') {
    echo "<h2>Bienvenida recepcionista, $nombre</h2>";
    echo "<p>Aquí podrás cotizar exámenes, crear clientes y otras funciones de recepción (en desarrollo).</p>";
} elseif ($rol == 'operador' || $rol == 'quimico') {
    echo "<h2>Bienvenido operador, $nombre</h2>";
    echo "<p>Aquí podrás ver reportes de resultados y funciones de laboratorio (en desarrollo).</p>";
} else {
    echo "<h2>Bienvenido, $nombre</h2>";
}

echo '</main>';

include_once 'componentes/footer.php';
?>
