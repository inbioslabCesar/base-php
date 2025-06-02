<?php session_start();
require_once __DIR__ . '/../clases/Auth.php';
require_once __DIR__ . '/../conexion/conexion.php';
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
    <title>Iniciar Sesión - INBIOSLAB</title>
</head>

<body>
    <h2>Iniciar sesión</h2> <?php if ($mensaje): ?> <p style="color:red;"><?php echo htmlspecialchars($mensaje); ?></p> <?php endif; ?> <form action="" method="POST"> <label for="email">Email:</label> <input type="email" name="email" required><br><br> <label for="password">Contraseña:</label> <input type="password" name="password" required><br><br> <button type="submit">Ingresar</button> </form>
    <p> <a href="registro.php">Registrarse</a> | <a href="recuperar.php">¿Olvidaste tu contraseña?</a> </p>
</body>

</html>