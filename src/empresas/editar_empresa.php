<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    echo "Empresa no encontrada.";
    exit;
}

$mensaje = '';
// Inicializar variables con valores actuales
$ruc = $empresa['ruc'] ?? '';
$razon_social = $empresa['razon_social'] ?? '';
$nombre_comercial = $empresa['nombre_comercial'] ?? '';
$direccion = $empresa['direccion'] ?? '';
$telefono = $empresa['telefono'] ?? '';
$email = $empresa['email'] ?? '';
$representante = $empresa['representante'] ?? '';
$convenio = $empresa['convenio'] ?? '';
$estado = $empresa['estado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruc = $_POST['ruc'];
    $razon_social = $_POST['razon_social'];
    $nombre_comercial = $_POST['nombre_comercial'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $representante = $_POST['representante'];
    $convenio = $_POST['convenio'];
    $estado = $_POST['estado'];

    // Validar único RUC y email
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE (ruc = ? OR email = ?) AND id != ?");
    $stmt->execute([$ruc, $email, $id]);
    if ($stmt->fetch()) {
        $mensaje = "El RUC o el Email ya existe en otra empresa.";
    } else {
        // Actualizar contraseña solo si se ingresó una nueva
        $updatePassword = "";
        $params = [
            $ruc, $razon_social, $nombre_comercial, $direccion, $telefono, $email,
            $representante, $convenio, $estado, $id
        ];
        if (!empty($_POST['password'])) {
            $nuevoHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $updatePassword = ", password = ?";
            $params = [
                $ruc, $razon_social, $nombre_comercial, $direccion, $telefono, $email,
                $representante, $convenio, $estado, $nuevoHash, $id
            ];
        }

        $sql = "UPDATE empresas SET ruc = ?, razon_social = ?, nombre_comercial = ?, direccion = ?, telefono = ?, email = ?, representante = ?, convenio = ?, estado = ? $updatePassword WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mensaje = "Empresa actualizada correctamente.";
        } else {
            $mensaje = "Error al actualizar la empresa.";
        }
    }
}
?>

<h2>Editar Empresa</h2>
<?php if ($mensaje): ?>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<form method="POST">
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

    <label>Contraseña (dejar en blanco para no cambiar):</label>
    <input type="password" name="password"><br>

    <label>Convenio:</label>
    <input type="text" name="convenio" value="<?= htmlspecialchars($convenio) ?>"><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="activo" <?= $estado == "activo" ? "selected" : "" ?>>Activo</option>
        <option value="inactivo" <?= $estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
    </select><br>

    <button type="submit">Actualizar</button>
    <a href="<?= BASE_URL ?>dashboard.php?vista=empresas" style="display:inline-block;padding:8px 16px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
</form>
