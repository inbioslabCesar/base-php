<?php session_start();
require_once __DIR__ . '/../clases/Auth.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // 1. Intentar login en la tabla USUARIOS 
    $authUsuario = new Auth($pdo, 'usuarios');
    $usuario = $authUsuario->login($email, $password);
    if ($usuario) {
        $_SESSION['usuario'] = $usuario['usuario'];
        // o 
        $_SESSION['email'] = $usuario['email']; 
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];
        header('Location: ../dashboard.php');
        exit();
    }
    // 2. Intentar login en la tabla EMPRESAS 
    $authEmpresa = new Auth($pdo, 'empresas');
    $empresa = $authEmpresa->login($email, $password);
    if ($empresa) {
        $_SESSION['usuario'] = $empresa['email'];
        $_SESSION['nombre'] = $empresa['razon_social'];
        $_SESSION['rol'] = 'empresa';
        header('Location: ../dashboard.php');
        exit();
    }
    // 3. Intentar login en la tabla CLIENTES 
    $authCliente = new Auth($pdo, 'clientes');
    $cliente = $authCliente->login($email, $password);
    if ($cliente) {
        $_SESSION['usuario'] = $cliente['email'];
        $_SESSION['nombre'] = $cliente['nombre'];
        $_SESSION['rol'] = 'cliente';
        header('Location: ../dashboard.php');
        exit();
    }
    // Si no se encontró usuario/empresa/cliente 
    $mensaje = 'Usuario o contraseña incorrectos';
}
// Si hay mensaje de sesión (por ejemplo, redirección desde auth.php) 
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
} ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INBIOSLAB LABORATORIO CLÍNICO - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tu CSS personalizado -->
    <link rel="stylesheet" href="../styles/auth.css">

</head>
<body>
    <!-- Fondo de burbujas -->
    <div class="bubbles">
        <?php for ($i = 0; $i < 18; $i++): ?>
            <div class="bubble"
                style="
                    left: <?= rand(0, 98) ?>vw;
                    width: <?= rand(30, 80) ?>px;
                    height: <?= rand(30, 80) ?>px;
                    animation-delay: <?= rand(0, 18) ?>s;
                    background: rgba(<?= rand(180,255) ?>,<?= rand(180,255) ?>,255,0.07);
                ">
            </div>
        <?php endfor; ?>
    </div>
    <!-- Login form -->
    <div class="login-box mt-5 shadow">
        <img src="../images/inbioslab-logo.png" alt="INBIOSLAB" class="logo-img mb-2">
        <h4 class="text-center mb-3">INBIOSLAB LABORATORIO CLÍNICO</h4>
        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">Iniciar sesión</button>
            <div class="d-flex justify-content-between">
                <a href="registro.php" class="small">Registrarse</a>
                <a href="recuperar.php" class="small">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>
    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
