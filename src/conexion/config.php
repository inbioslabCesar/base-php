<?php require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
$error = '';
$exito = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Validaciones básicas 
    if (!$nombre || !$apellido || !$email || !$password) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        // Verifica que el email no esté registrado 
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'El email ya está registrado.';
        } else {
            $codigo_cliente = generarCodigoCliente($conn);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO clientes (codigo_cliente, nombre, apellido, email, password) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$codigo_cliente, $nombre, $apellido, $email, $hash])) {
                $exito = 'Registro exitoso. Ahora puedes iniciar sesión.';
            } else {
                $error = 'Error al registrar el cliente.';
            }
        }
    }
} ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente</title>
</head>

<body>
    <h2>Registro de Cliente</h2> <?php if ($error): ?> <p style="color:red;"><?php echo $error; ?></p> <?php endif; ?> <?php if ($exito): ?> <p style="color:green;"><?php echo $exito; ?></p> <?php endif; ?> <form method="post"> <label>Nombre: <input type="text" name="nombre" required></label><br> <label>Apellido: <input type="text" name="apellido" required></label><br> <label>Email: <input type="email" name="email" required></label><br> <label>Contraseña: <input type="password" name="password" required></label><br> <button type="submit">Registrarse</button> </form>
</body>

</html>