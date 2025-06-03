<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener datos del cliente
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit;
}

// Inicializar variables con valores actuales o vacíos
$codigo_cliente = $cliente['codigo_cliente'] ?? '';
$nombre = $cliente['nombre'] ?? '';
$apellido = $cliente['apellido'] ?? '';
$edad = $cliente['edad'] ?? '';
$email = $cliente['email'] ?? '';
$telefono = $cliente['telefono'] ?? '';
$direccion = $cliente['direccion'] ?? '';
$dni = $cliente['dni'] ?? '';
$sexo = $cliente['sexo'] ?? '';
$procedencia = $cliente['procedencia'] ?? '';
$referencia = $cliente['referencia'] ?? '';
$estado = $cliente['estado'] ?? '';
$mensaje = "";

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $codigo_cliente = $_POST['codigo_cliente'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $edad = $_POST['edad'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $dni = $_POST['dni'];
    // Validar sexo según los valores permitidos
    $sexo = isset($_POST['sexo']) && in_array($_POST['sexo'], ['Masculino', 'Femenino', 'Otro']) ? $_POST['sexo'] : null;
    $procedencia = $_POST['procedencia'];
    $referencia = $_POST['referencia'];
    $estado = $_POST['estado'];

    // Validación de campos únicos
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE (codigo_cliente = ? OR email = ? OR dni = ?) AND id != ?");
    $stmt->execute([$codigo_cliente, $email, $dni, $id]);
    if ($stmt->fetch()) {
        $mensaje = "El código, email o DNI ya existe en otro cliente.";
    } else {
        // Actualizar contraseña solo si se ingresó una nueva
        $updatePassword = "";
        $params = [
            $codigo_cliente, $nombre, $apellido, $edad, $email,
            $telefono, $direccion, $dni, $sexo, $procedencia, $referencia, $estado, $id
        ];
        if (!empty($_POST['password'])) {
            $nuevoHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $updatePassword = ", password = ?";
            $params = [
                $codigo_cliente, $nombre, $apellido, $edad, $email,
                $telefono, $direccion, $dni, $sexo, $procedencia, $referencia, $estado, $nuevoHash, $id
            ];
        }

        $sql = "UPDATE clientes SET codigo_cliente = ?, nombre = ?, apellido = ?, edad = ?, email = ?, telefono = ?, direccion = ?, dni = ?, sexo = ?, procedencia = ?, referencia = ?, estado = ? $updatePassword WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mensaje = "Cliente actualizado correctamente.";
        } else {
            $mensaje = "Error al actualizar el cliente.";
        }
    }
}
?>

<h2>Editar Cliente</h2>
<?php if ($mensaje): ?>
    <div style="color:<?= strpos($mensaje, 'correctamente') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<form method="POST">
    <label>Código Cliente:</label>
    <input type="text" name="codigo_cliente" id="codigo_cliente" value="<?= htmlspecialchars($codigo_cliente) ?>" required>
    <button type="button" onclick="generarCodigoCliente()">Generar</button><br>

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required><br>

    <label>Edad:</label>
    <input type="number" name="edad" value="<?= htmlspecialchars($edad) ?>"><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br>

    <label>Contraseña (dejar en blanco para no cambiar):</label>
    <input type="password" name="password"><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>"><br>

    <label>Dirección:</label>
    <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>"><br>

    <label>DNI:</label>
    <input type="text" name="dni" value="<?= htmlspecialchars($dni) ?>"><br>

    <label>Sexo:</label>
    <select name="sexo">
        <option value="">Seleccione</option>
        <option value="Masculino" <?= $sexo == "Masculino" ? "selected" : "" ?>>Masculino</option>
        <option value="Femenino" <?= $sexo == "Femenino" ? "selected" : "" ?>>Femenino</option>
        <option value="Otro" <?= $sexo == "Otro" ? "selected" : "" ?>>Otro</option>
    </select><br>

    <label>Procedencia:</label>
    <input type="text" name="procedencia" value="<?= htmlspecialchars($procedencia) ?>"><br>

    <label>Referencia:</label>
    <input type="text" name="referencia" value="<?= htmlspecialchars($referencia) ?>"><br>

    <label>Estado:</label>
    <select name="estado">
        <option value="activo" <?= $estado == "activo" ? "selected" : "" ?>>Activo</option>
        <option value="inactivo" <?= $estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
    </select><br>

    <button type="submit">Actualizar</button>
    <a href="<?= BASE_URL ?>dashboard.php?vista=clientes" style="display:inline-block;padding:8px 16px;background:#343a40;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
</form>

<script>
function generarCodigoCliente() {
    const now = new Date();
    const year = now.getFullYear().toString().slice(-2);
    const month = ("0" + (now.getMonth() + 1)).slice(-2);
    const day = ("0" + now.getDate()).slice(-2);
    const random = Math.floor(1000 + Math.random() * 9000);
    const codigo = `LAB-${year}${month}${day}-${random}`;
    document.getElementById('codigo_cliente').value = codigo;
}
</script>
