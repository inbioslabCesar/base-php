<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../clases/Auth.php';

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

        header('Location: ../dashboard.php?vista=empresa');
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
    <title>Iniciar Sesión | INBIOSLAB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap y estilos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Estilos personalizados y animaciones aquí */
        body {
            background: #f5f6fa;
        }
        .login-container {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 16px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="mb-4 text-center">INBIOSLAB | Iniciar Sesión</h2>
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
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
        <div class="mt-3 text-center">
            <a href="registro.php">¿No tienes cuenta? Regístrate</a> <br>
            <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>
