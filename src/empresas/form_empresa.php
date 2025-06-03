<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$mensaje = '';
$ruc = $razon_social = $nombre_comercial = $direccion = $telefono = $email = $representante = $convenio = $estado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruc = trim($_POST['ruc'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? '');
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $representante = trim($_POST['representante'] ?? '');
    $convenio = trim($_POST['convenio'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    $password = $_POST['password'] ?? '';

    if (empty($ruc) || empty($razon_social) || empty($email) || empty($password)) {
        $mensaje = "RUC, Razón Social, Email y Contraseña son obligatorios.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM empresas WHERE ruc = ? OR email = ?");
        $stmt->execute([$ruc, $email]);
        if ($stmt->fetch()) {
            $mensaje = "El RUC o el Email ya existe.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO empresas (ruc, razon_social, nombre_comercial, direccion, telefono, email, representante, password, convenio, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$ruc, $razon_social, $nombre_comercial, $direccion, $telefono, $email, $representante, $hash, $convenio, $estado])) {
                $mensaje = "Empresa creada correctamente.";
                $ruc = $razon_social = $nombre_comercial = $direccion = $telefono = $email = $representante = $convenio = $estado = '';
            } else {
                $mensaje = "Error al crear la empresa.";
            }
        }
    }
}
?>

<h2>Registrar Empresa</h2>
<?php if ($mensaje): ?>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<form method="POST" autocomplete="off">
    <label>RUC:</label>
    <input type="text" name="ruc" value="<?= htmlspecialchars($ruc) ?>" required><br>

    <label>Razón Social:</label>
    <input type="text" name="razon_social" value="<?= htmlspecialchars($razon_social) ?>" required><br>

    <label>Nombre Comercial:</label>
    <input type="text" name="nombre_comercial" value="<?= htmlspecialchars($nombre_comercial) ?>"><br>

    <label>Dirección:</label>
    <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>"><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>"><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br>

    <label>Representante:</label>
    <input type="text" name="representante" value="<?= htmlspecialchars($representante) ?>"><br>

    <label>Contraseña:</label>
    <input type="password" name="password" required><br>

    <label>Convenio:</label>
    <input type="text" name="convenio" value="<?= htmlspecialchars($convenio) ?>"><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="activo" <?= $estado == "activo" ? "selected" : "" ?>>Activo</option>
        <option value="inactivo" <?= $estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
    </select><br>

    <button type="submit">Guardar</button>
    <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" style="display:inline-block;padding:8px 16px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
</form>
