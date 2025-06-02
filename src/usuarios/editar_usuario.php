<?php require_once __DIR__ . '/../clases/Crud.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$id = $_GET['id'] ?? null;
$mensaje = '';
$crud = new Crud($pdo, 'usuarios');
if (!$id) {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=tabla_usuarios');
    exit();
}
// Obtener datos actuales del usuario 
$usuarioData = $crud->obtenerPorId($id);
if (!$usuarioData) {
    header('Location: ' . BASE_URL . 'dashboard.php?vista=tabla_usuarios');
    exit();
}
$usuario = $usuarioData['usuario'];
$nombre = $usuarioData['nombre'];
$apellido = $usuarioData['apellido'];
$email = $usuarioData['email'];
$rol = $usuarioData['rol'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioNuevo = $_POST['usuario'] ?? '';
    $nombreNuevo = $_POST['nombre'] ?? '';
    $apellidoNuevo = $_POST['apellido'] ?? '';
    $emailNuevo = $_POST['email'] ?? '';
    $rolNuevo = $_POST['rol'] ?? '';
    // Validar únicos (excluyendo el propio usuario) 
    $sql = "SELECT COUNT(*) FROM usuarios WHERE (usuario = :usuario OR email = :email) AND id != :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario', $usuarioNuevo, PDO::PARAM_STR);
    $stmt->bindParam(':email', $emailNuevo, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $mensaje = 'El usuario o email ya existen para otro registro.';
    } else {
        if ($usuarioNuevo && $nombreNuevo && $apellidoNuevo && $emailNuevo && $rolNuevo) {
            $datos = ['usuario' => $usuarioNuevo, 'nombre' => $nombreNuevo, 'apellido' => $apellidoNuevo, 'email' => $emailNuevo, 'rol' => $rolNuevo];
            if ($crud->actualizar($id, $datos)) {
                $mensaje = 'Usuario actualizado correctamente.';
                $usuario = $usuarioNuevo;
                $nombre = $nombreNuevo;
                $apellido = $apellidoNuevo;
                $email = $emailNuevo;
                $rol = $rolNuevo;
            } else {
                $mensaje = 'Error al actualizar usuario.';
            }
        } else {
            $mensaje = 'Completa todos los campos obligatorios.';
        }
    }
} ?> <h2>Editar Usuario</h2> <?php if ($mensaje): ?> <p style="color: green;"><?php echo htmlspecialchars($mensaje); ?></p> <?php endif; ?> <form method="post" action=""> <label>Usuario:</label> <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>" required><br><br> <label>Nombre:</label> <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required><br><br> <label>Apellido:</label> <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required><br><br> <label>Email:</label> <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br> <label>Rol:</label> <input type="text" name="rol" value="<?php echo htmlspecialchars($rol); ?>" required><br><br> <button type="submit">Actualizar</button> <a href="<?php echo BASE_URL; ?>dashboard.php?vista=tabla_usuarios"><button type="button">Regresar a la tabla</button></a> </form>