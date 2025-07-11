<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/empresa_config.php';

$mensaje = '';
$alerta_tipo = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $mensaje = 'Por favor ingresa tu correo electrónico.';
        $alerta_tipo = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare("UPDATE clientes SET reset_token = ?, reset_expira = ? WHERE id = ?");
            $stmt->execute([$token, $expira, $cliente['id']]);
            $enlace_recuperacion = BASE_URL . "auth/restablecer.php?token=$token";
            $mensaje = '
                <div>
                    <strong>¡Enlace de recuperación generado!</strong><br>
                    <a href="' . $enlace_recuperacion . '" class="btn btn-primary mt-2" target="_blank">
                        Recuperar contraseña
                    </a>
                    <div class="mt-2 small text-muted" style="word-break:break-all;">' . $enlace_recuperacion . '</div>
                </div>';
            $alerta_tipo = 'success';
        } else {
            $mensaje = 'Correo no encontrado.';
            $alerta_tipo = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - <?= htmlspecialchars($config['nombre']) ?> LABORATORIO CLÍNICO</title>
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
        <img src="../<?= htmlspecialchars($config['logo']) ?>?ver=<?= time() ?>" alt="<?= htmlspecialchars($config['nombre']) ?>" class="logo-img mb-2">
        <h4 class="text-center mb-3">Recuperar Contraseña</h4>
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $alerta_tipo ?>"><?= $mensaje ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <button type="submit" class="btn btn-warning w-100 mb-2">Recuperar contraseña</button>
            <div class="text-center">
                <a href="login.php" class="small">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
