<?php require_once __DIR__ . '/../clases/Crud.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$mensaje = '';
$usuario = $nombre = $apellido = $email = $password = $rol = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $crud = new Crud($pdo, 'usuarios');
    // Validar únicos: usuario y email 
    if ($crud->existeUnico(['usuario' => $usuario, 'email' => $email])) {
        $mensaje = 'El usuario o email ya existen.';
    } else {
        if ($usuario && $nombre && $apellido && $email && $password && $rol) {
            $datos = ['usuario' => $usuario, 'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'rol' => $rol];
            if ($crud->insertar($datos)) {
                $mensaje = 'Usuario registrado correctamente.';
                $usuario = $nombre = $apellido = $email = $password = $rol = '';
            } else {
                $mensaje = 'Error al registrar usuario.';
            }
        } else {
            $mensaje = 'Completa todos los campos obligatorios.';
        }
    }
} ?> <h2>Registrar Usuario</h2> <?php if ($mensaje): ?> <p style="color: green;"><?php echo htmlspecialchars($mensaje); ?></p> <?php endif; ?> <form method="post" action=""> <label>Usuario:</label> <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>" required><br><br> <label>Nombre:</label> <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required><br><br> <label>Apellido:</label> <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required><br><br> <label>Email:</label> <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br> <label>Contraseña:</label> <input type="password" name="password" required><br><br> <label>Rol:</label> <input type="text" name="rol" value="<?php echo htmlspecialchars($rol); ?>" required><br><br> <button type="submit">Registrar</button> <a href="<?php echo BASE_URL; ?>dashboard.php?vista=tabla_usuarios"><button type="button">Regresar a la tabla</button></a> </form>