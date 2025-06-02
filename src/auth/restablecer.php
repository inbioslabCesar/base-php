<?php require_once '../conexion/conexion.php';
$token = $_GET['token'] ?? '';
$error = '';
$success = '';
if ($token) {
    $stmt = $pdo->prepare("SELECT id, reset_expira FROM clientes WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && strtotime($user['reset_expira']) > time()) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            if ($password && $password === $password2) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET password = ?, reset_token = NULL, reset_expira = NULL WHERE reset_token = ?");
                $stmt->execute([$hash, $token]);
                $success = "Contraseña restablecida con éxito. <a href='login.php'>Inicia sesión</a>";
            } else {
                $error = "Las contraseñas no coinciden.";
            }
        }
    } else {
        $error = "El enlace de recuperación es inválido o ha expirado.";
    }
} else {
    $error = "Token no válido.";
} ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
</head>

<body>
    <h2>Restablecer Contraseña</h2> <?php if ($error): ?> <div style="color:red;"><?= $error ?></div> <?php endif; ?> <?php if ($success): ?> <div style="color:green;"><?= $success ?></div> <?php elseif (!$error): ?> <form method="POST" action=""> <label for="password">Nueva contraseña:</label> <input type="password" id="password" name="password" required> <br> <label for="password2">Repite la nueva contraseña:</label> <input type="password" id="password2" name="password2" required> <br> <button type="submit">Restablecer</button> </form> <?php endif; ?>
</body>

</html>