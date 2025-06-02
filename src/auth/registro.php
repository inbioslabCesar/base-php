<?php require_once '../conexion/conexion.php';
$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (!empty($nombre) && !empty($email) && !empty($password) && !empty($password2)) {
        if ($password !== $password2) {
            $error = "Las contraseñas no coinciden.";
        } else {
            try {
                // Verificar si el email ya existe 
                $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "El email ya está registrado.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, password, fecha_registro) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$nombre, $email, $hash]);
                    header('Location: login.php?registro=ok');
                    exit();
                }
            } catch (PDOException $e) {
                $error = "Error al registrar: " . $e->getMessage();
            }
        }
    } else {
        $error = "Completa todos los campos.";
    }
} ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Cliente</title>
</head>

<body>
    <h2>Registro de Cliente</h2> <?php if ($error): ?> <div style="color:red;"><?= $error ?></div> <?php endif; ?> <form method="POST" action=""> <label for="nombre">Nombre:</label> <input type="text" id="nombre" name="nombre" required> <br> <label for="email">Email:</label> <input type="email" id="email" name="email" required> <br> <label for="password">Contraseña:</label> <input type="password" id="password" name="password" required> <br> <label for="password2">Repite la Contraseña:</label> <input type="password" id="password2" name="password2" required> <br> <button type="submit">Registrarse</button> </form> <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
</body>

</html>