<?php session_start();
require_once __DIR__ . '/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    // Consulta a la base de datos para verificar usuario y contraseña 
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario'] = $usuario;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
} ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login INBIOSLAB</title>
</head>

<body>
    <h2>Iniciar sesión</h2> <?php if ($error): ?> <p style="color:red;"><?php echo $error; ?></p> <?php endif; ?> <form method="post"> <label>Usuario: <input type="text" name="usuario" required></label><br> <label>Contraseña: <input type="password" name="password" required></label><br> <button type="submit">Entrar</button> </form> <a href="registro.php">Registrarse</a> | <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
</body>

</html>