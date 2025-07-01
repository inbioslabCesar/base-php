<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Auth.php';
require_once __DIR__ . '/empresa_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. USUARIOS (admin, recepcionista, laboratorista, etc.)
    $auth = new Auth($pdo, 'usuarios');
    $usuario = $auth->login($email, $password);
    if ($usuario) {
        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = strtolower($usuario['rol']);

        switch ($_SESSION['rol']) {
            case 'admin':
                header('Location: ../dashboard.php?vista=admin');
                break;
            case 'recepcionista':
                header('Location: ../dashboard.php?vista=recepcionista');
                break;
            case 'laboratorista':
                header('Location: ../dashboard.php?vista=laboratorista');
                break;
            default:
                header('Location: ../dashboard.php');
                break;
        }
        exit;
    }

    // 2. EMPRESAS
    $auth = new Auth($pdo, 'empresas');
    $empresa = $auth->login($email, $password);
    if ($empresa) {
        $_SESSION['usuario'] = $empresa['nombre_comercial'];
        $_SESSION['email'] = $empresa['email'];
        $_SESSION['empresa_id'] = $empresa['id'];
        $_SESSION['rol'] = 'empresa';

        header('Location: dashboard.php?vista=empresa');
        exit;
    }

    // 3. CLIENTES
    $auth = new Auth($pdo, 'clientes');
    $cliente = $auth->login($email, $password);
    if ($cliente) {
        $_SESSION['usuario'] = $cliente['nombre'];
        $_SESSION['email'] = $cliente['email'];
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['rol'] = 'cliente';

        header('Location: ../dashboard.php?vista=cliente');
        exit;
    }

    // 4. CONVENIOS
    $auth = new Auth($pdo, 'convenios');
    $convenio = $auth->login($email, $password);
    if ($convenio) {
        $_SESSION['usuario'] = $convenio['nombre'];
        $_SESSION['email'] = $convenio['email'];
        $_SESSION['convenio_id'] = $convenio['id'];
        $_SESSION['rol'] = 'convenio';

        header('Location: ../dashboard.php?vista=convenio');
        exit;
    }

    // Ninguno autenticó
    $_SESSION['mensaje'] = "Credenciales incorrectas o usuario no encontrado.";
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión | <?= htmlspecialchars($config['nombre']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS general de autenticación -->
    <link rel="stylesheet" href="../styles/auth.css">
    <style>
        .login-box {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 16px rgba(0,0,0,0.08);
            position: relative;
            z-index: 2;
        }
        .logo-img {
            width: 120px;
            display: block;
            margin: 0 auto 12px auto;
        }
    </style>
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

    <div class="login-box mt-5 shadow">
        <!-- Logo y nombre dinámicos -->
        <img src="../<?= htmlspecialchars($config['logo']) ?>" alt="<?= htmlspecialchars($config['nombre']) ?>" class="logo-img mb-2">
        <h4 class="text-center mb-3">Iniciar Sesión</h4>
        <?php if (!empty($_SESSION['mensaje'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['mensaje']) ?></div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">Ingresar</button>
            <div class="d-flex justify-content-between">
                <a href="registro.php" class="small">¿No tienes cuenta? Regístrate</a>
                <a href="recuperar.php" class="small">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>