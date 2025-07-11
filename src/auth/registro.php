<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/empresa_config.php';

$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (empty($nombre) || empty($email) || empty($password) || empty($confirmar_password)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirmar_password) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } else {
        // Verifica si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $mensaje_error = "El correo ya está registrado.";
        } else {
            // Inserta el cliente
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, password, estado, fecha_registro) VALUES (?, ?, ?, 'activo', NOW())");
            if ($stmt->execute([$nombre, $email, $hash])) {
                $mensaje_exito = "Registro exitoso. Ahora puedes iniciar sesión.";
            } else {
                $mensaje_error = "Error al registrar. Intenta de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - <?= htmlspecialchars($config['nombre']) ?> LABORATORIO CLÍNICO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS general de autenticación -->
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
    <!-- Formulario de registro -->
    <div class="login-box mt-5 shadow">
        <img src="../<?= htmlspecialchars($config['logo']) ?>?ver=<?= time() ?>" alt="<?= htmlspecialchars($config['nombre']) ?>" class="logo-img mb-2">
        <h4 class="text-center mb-3">Registro de Cliente</h4>
        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php elseif (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirmar_password" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
            </div>
            <button type="submit" class="btn btn-success w-100 mb-2">Registrarse</button>
            <div class="d-flex justify-content-between">
                <a href="login.php" class="small">¿Ya tienes cuenta?</a>
                <a href="recuperar.php" class="small">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>
    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
