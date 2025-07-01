<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/empresa_config.php';

$mensaje = '';
$alerta_tipo = 'info';
$token = $_GET['token'] ?? '';
$mostrar_formulario = false;

if (!$token) {
    $mensaje = 'Token no válido.';
    $alerta_tipo = 'danger';
} else {
    // Buscar cliente por token y verificar expiración
    $stmt = $pdo->prepare("SELECT id, reset_expira FROM clientes WHERE reset_token = ?");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $mensaje = 'El enlace de recuperación es inválido o ha expirado.';
        $alerta_tipo = 'danger';
    } elseif (strtotime($cliente['reset_expira']) < time()) {
        $mensaje = 'El enlace ha expirado. Solicita uno nuevo.';
        $alerta_tipo = 'danger';
    } else {
        $mostrar_formulario = true;

        // Procesar restablecimiento
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmar = $_POST['confirmar_password'] ?? '';
            if (empty($password) || empty($confirmar)) {
                $mensaje = 'Completa ambos campos.';
                $alerta_tipo = 'danger';
            } elseif ($password !== $confirmar) {
                $mensaje = 'Las contraseñas no coinciden.';
                $alerta_tipo = 'danger';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET password = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
                if ($stmt->execute([$hash, $cliente['id']])) {
                    $mensaje = '¡Contraseña restablecida correctamente! Ahora puedes iniciar sesión.';
                    $alerta_tipo = 'success';
                    $mostrar_formulario = false;
                } else {
                    $mensaje = 'Error al actualizar la contraseña. Intenta de nuevo.';
                    $alerta_tipo = 'danger';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - <?= htmlspecialchars($config['nombre']) ?> LABORATORIO CLÍNICO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <div class="login-box mt-5 shadow">
        <img src="../<?= htmlspecialchars($config['logo']) ?>" alt="<?= htmlspecialchars($config['nombre']) ?>" class="logo-img mb-2">
        <h4 class="text-center mb-3">Restablecer Contraseña</h4>
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $alerta_tipo ?>"><?= $mensaje ?></div>
        <?php endif; ?>
        <?php if ($mostrar_formulario): ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required autofocus>
            </div>
            <div class="mb-3">
                <label for="confirmar_password" class="form-label">Confirmar nueva contraseña</label>
                <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
            </div>
            <button type="submit" class="btn btn-success w-100 mb-2">Restablecer</button>
            <div class="text-center">
                <a href="login.php" class="small">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
