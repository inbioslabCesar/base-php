<?php require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$id = $_GET['id'] ?? null;
$mensaje = '';
if (!$id) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}
// Obtener datos del cliente 
$sql = "SELECT * FROM clientes WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}
$codigo_cliente = $cliente['codigo_cliente'] ?? '';
$nombre = $cliente['nombre'] ?? '';
$apellido = $cliente['apellido'] ?? '';
$email = $cliente['email'] ?? '';
$telefono = $cliente['telefono'] ?? '';
$direccion = $cliente['direccion'] ?? '';
$dni = $cliente['dni'] ?? '';
$sexo = $cliente['sexo'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_cliente = $_POST['codigo_cliente'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $sql = "SELECT COUNT(*) FROM clientes WHERE (codigo_cliente = :codigo_cliente OR email = :email OR dni = :dni) AND id != :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo_cliente', $codigo_cliente, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $existe = $stmt->fetchColumn();
    if ($existe > 0) {
        $mensaje = 'El código, email o DNI ya existen para otro cliente.';
    } else {
        if ($codigo_cliente && $nombre && $apellido && $email && $dni && ($sexo === 'masculino' || $sexo === 'femenino' || $sexo === 'otro')) {
            $sql = "UPDATE clientes SET codigo_cliente = :codigo_cliente, nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono, direccion = :direccion, dni = :dni, sexo = :sexo WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':codigo_cliente', $codigo_cliente, PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
            $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
            $stmt->bindParam(':sexo', $sexo, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $mensaje = 'Cliente actualizado correctamente.';
            } else {
                $mensaje = 'Error al actualizar el cliente.';
            }
        } else {
            $mensaje = 'Completa todos los campos obligatorios y selecciona un sexo válido.';
        }
    }
} ?> <h2>Editar Cliente</h2> <?php if ($mensaje): ?> <p style="color: green;"><?php echo htmlspecialchars($mensaje); ?></p> <?php endif; ?> <form method="post" action=""> <label>Código Cliente:</label> <input type="text" name="codigo_cliente" value="<?php echo htmlspecialchars($codigo_cliente ?? ''); ?>" required><br><br> <label>Nombre:</label> <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required><br><br> <label>Apellido:</label> <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido ?? ''); ?>" required><br><br> <label>Email:</label> <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required><br><br> <label>Teléfono:</label> <input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono ?? ''); ?>"><br><br> <label>Dirección:</label> <input type="text" name="direccion" value="<?php echo htmlspecialchars($direccion ?? ''); ?>"><br><br> <label>DNI:</label> <input type="text" name="dni" value="<?php echo htmlspecialchars($dni ?? ''); ?>" required><br><br> <label>Sexo:</label> <select name="sexo" required>
        <option value="">Seleccione</option>
        <option value="masculino" <?php if (($sexo ?? '') === 'masculino') echo 'selected'; ?>>Masculino</option>
        <option value="femenino" <?php if (($sexo ?? '') === 'femenino') echo 'selected'; ?>>Femenino</option>
        <option value="otro" <?php if (($sexo ?? '') === 'otro') echo 'selected'; ?>>Otro</option>
    </select><br><br> <button type="submit">Actualizar</button> <a href="<?php echo BASE_URL; ?>dashboard.php"><button type="button">Regresar a la tabla</button></a> </form>