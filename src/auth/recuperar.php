<?php require_once '../conexion/conexion.php';
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            // Guarda el token y su expiración 
            $stmt = $pdo->prepare("UPDATE clientes SET reset_token = ?, reset_expira = ? WHERE email = ?");
            $stmt->execute([$token, $expira, $email]);
            // Enlace de recuperación (ajusta la URL a tu entorno) 
            $enlace = "http://localhost/base-php/src/auth/restablecer.php?token=$token";
            // Aquí deberías enviar el enlace por email al usuario. 
            // Para pruebas, simplemente muestra el enlace: 
            $mensaje = "Recibe tu enlace de recuperación: <a href='$enlace'>$enlace</a>";
        } else {
            $mensaje = "Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.";
        }
    } else {
        $mensaje = "Por favor, ingresa tu correo electrónico.";
    }
} ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
</head>

<body>
    <h2>Recuperar Contraseña</h2> <?php if ($mensaje): ?> <div style="color:blue;"><?= $mensaje ?></div> <?php endif; ?> <form method="POST" action=""> <label for="email">Correo electrónico:</label> <input type="email" id="email" name="email" required> <br> <button type="submit">Recuperar</button> </form> <a href="login.php">Volver al login</a>
</body>

</html>