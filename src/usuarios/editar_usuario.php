<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

$mensaje = '';
$nombre = $usuario['nombre'] ?? '';
$apellido = $usuario['apellido'] ?? '';
$dni = $usuario['dni'] ?? '';
$sexo = $usuario['sexo'] ?? '';
$email = $usuario['email'] ?? '';
$telefono = $usuario['telefono'] ?? '';
$direccion = $usuario['direccion'] ?? '';
$profesion = $usuario['profesion'] ?? '';
$rol = $usuario['rol'] ?? '';
$estado = $usuario['estado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $sexo = $_POST['sexo'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $profesion = $_POST['profesion'];
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];

    // Validar único email
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $mensaje = "El correo ya existe en otro usuario.";
    } elseif (!in_array($rol, ['admin', 'recepcionista', 'laboratorista'])) {
        $mensaje = "Rol inválido.";
    } elseif (!in_array($sexo, ['masculino', 'femenino', 'otro'])) {
        $mensaje = "Sexo inválido.";
    } else {
        // Actualizar contraseña solo si se ingresó una nueva
        $updatePassword = "";
        $params = [
            $nombre, $apellido, $dni, $sexo, $email, $telefono, $direccion, $profesion, $rol, $estado, $id
        ];
        if (!empty($_POST['password'])) {
            $nuevoHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $updatePassword = ", password = ?";
            $params = [
                $nombre, $apellido, $dni, $sexo, $email, $telefono, $direccion, $profesion, $rol, $estado, $nuevoHash, $id
            ];
        }

        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, sexo = ?, email = ?, telefono = ?, direccion = ?, profesion = ?, rol = ?, estado = ? $updatePassword WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mensaje = "Usuario actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar el usuario.";
        }
    }
}
?>

<h2>Editar Usuario</h2>
<?php if ($mensaje): ?>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<form method="POST">
    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required><br>

    <label>DNI:</label>
    <input type="text" name="dni" value="<?= htmlspecialchars($dni) ?>"><br>

    <label>Sexo:</label>
    <select name="sexo" required>
        <option value="">Seleccione</option>
        <option value="masculino" <?= $sexo == "masculino" ? "selected" : "" ?>>Masculino</option>
        <option value="femenino" <?= $sexo == "femenino" ? "selected" : "" ?>>Femenino</option>
        <option value="otro" <?= $sexo == "otro" ? "selected" : "" ?>>Otro</option>
    </select><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>"><br>

    <label>Dirección:</label>
    <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>"><br>

    <label>Profesión:</label>
    <input type="text" name="profesion" value="<?= htmlspecialchars($profesion) ?>"><br>

    <label>Contraseña (dejar en blanco para no cambiar):</label>
    <input type="password" name="password"><br>

    <label>Rol:</label>
    <select name="rol" required>
        <option value="">Seleccione</option>
        <option value="admin" <?= $rol == "admin" ? "selected" : "" ?>>Administrador</option>
        <option value="recepcionista" <?= $rol == "recepcionista" ? "selected" : "" ?>>Recepcionista</option>
        <option value="laboratorista" <?= $rol == "laboratorista" ? "selected" : "" ?>>Laboratorista</option>
    </select><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="activo" <?= $estado == "activo" ? "selected" : "" ?>>Activo</option>
        <option value="inactivo" <?= $estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
    </select><br>

    <button type="submit">Actualizar</button>
    <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" style="display:inline-block;padding:8px 16px;background:#343a40;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
</form>
