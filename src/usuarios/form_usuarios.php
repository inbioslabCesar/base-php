<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$mensaje = '';
$nombre = $apellido = $dni = $sexo = $email = $telefono = $direccion = $profesion = $rol = $estado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $dni       = trim($_POST['dni'] ?? '');
    $sexo      = $_POST['sexo'] ?? '';
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $profesion = trim($_POST['profesion'] ?? '');
    $rol       = $_POST['rol'] ?? '';
    $estado    = $_POST['estado'] ?? 'activo';
    $password  = $_POST['password'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($rol) || empty($sexo)) {
        $mensaje = "Todos los campos obligatorios deben estar completos.";
    } elseif (!in_array($rol, ['admin', 'recepcionista', 'laboratorista'])) {
        $mensaje = "Rol inválido.";
    } elseif (!in_array($sexo, ['masculino', 'femenino', 'otro'])) {
        $mensaje = "Sexo inválido.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $mensaje = "El correo ya existe.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (password, nombre, apellido, dni, sexo, email, telefono, direccion, profesion, rol, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$hash, $nombre, $apellido, $dni, $sexo, $email, $telefono, $direccion, $profesion, $rol, $estado])) {
                $mensaje = "Usuario creado correctamente.";
                $nombre = $apellido = $dni = $sexo = $email = $telefono = $direccion = $profesion = $rol = $estado = '';
            } else {
                $mensaje = "Error al crear el usuario.";
            }
        }
    }
}
?>

<h2>Registrar Usuario</h2>
<?php if ($mensaje): ?>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<form method="POST" autocomplete="off">
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

    <label>Contraseña:</label>
    <input type="password" name="password" required><br>

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

    <button type="submit">Guardar</button>
    <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" style="display:inline-block;padding:8px 16px;background:#343a40;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
</form>
