<?php require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/config.php';
$mensaje = '';
$codigo_cliente = $nombre = $apellido = $edad = $email = $password = $telefono = $direccion = $dni = $sexo = $procedencia = $referencia = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_cliente = $_POST['codigo_cliente'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $edad = $_POST['edad'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $procedencia = $_POST['procedencia'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    // Validar campos únicos: código_cliente, email, dni 
    $sql = "SELECT COUNT(*) FROM clientes WHERE codigo_cliente = :codigo_cliente OR email = :email OR dni = :dni";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':codigo_cliente', $codigo_cliente, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $stmt->execute();
    $existe = $stmt->fetchColumn();
    if ($existe > 0) {
        $mensaje = 'El código de cliente, email o DNI ya existen. Usa valores diferentes.';
    } else {
        if ($codigo_cliente && $nombre && $apellido && $edad && $email && $password && $dni && ($sexo === 'masculino' || $sexo === 'femenino' || $sexo === 'otro')) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO clientes (codigo_cliente, nombre, apellido, edad, email, password, telefono, direccion, dni, sexo, procedencia, referencia) VALUES (:codigo_cliente, :nombre, :apellido, :edad, :email, :password, :telefono, :direccion, :dni, :sexo, :procedencia, :referencia)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':codigo_cliente', $codigo_cliente, PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':edad', $edad, PDO::PARAM_INT);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
            $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
            $stmt->bindParam(':sexo', $sexo, PDO::PARAM_STR);
            $stmt->bindParam(':procedencia', $procedencia, PDO::PARAM_STR);
            $stmt->bindParam(':referencia', $referencia, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $mensaje = 'Cliente agregado correctamente.';
                $codigo_cliente = $nombre = $apellido = $edad = $email = $password = $telefono = $direccion = $dni = $sexo = $procedencia = $referencia = '';
            } else {
                $mensaje = 'Error al agregar el cliente.';
            }
        } else {
            $mensaje = 'Completa todos los campos obligatorios y selecciona un sexo válido.';
        }
    }
} ?> <h2>Agregar Cliente</h2> <?php if ($mensaje): ?> <p style="color: green;"><?php echo htmlspecialchars($mensaje); ?></p> <?php endif; ?> <form method="post" action=""> <label>Código Cliente:</label> <input type="text" name="codigo_cliente" id="codigo_cliente" value="<?php echo htmlspecialchars($codigo_cliente); ?>" required> <button type="button" onclick="generarCodigoCliente()" style="margin-left:8px;">Generar</button> <br><br> <label>Nombre:</label> <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required><br><br> <label>Apellido:</label> <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required><br><br> <label>Edad:</label> <input type="number" name="edad" value="<?php echo htmlspecialchars($edad); ?>" min="0" max="120" required><br><br> <label>Email:</label> <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br> <label>Contraseña:</label> <input type="password" name="password" required><br><br> <label>Teléfono:</label> <input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>"><br><br> <label>Dirección:</label> <input type="text" name="direccion" value="<?php echo htmlspecialchars($direccion); ?>"><br><br> <label>DNI:</label> <input type="text" name="dni" value="<?php echo htmlspecialchars($dni); ?>" required><br><br> <label>Sexo:</label> <select name="sexo" required>
        <option value="">Seleccione</option>
        <option value="masculino" <?php if ($sexo === 'masculino') echo 'selected'; ?>>Masculino</option>
        <option value="femenino" <?php if ($sexo === 'femenino') echo 'selected'; ?>>Femenino</option>
        <option value="otro" <?php if ($sexo === 'otro') echo 'selected'; ?>>Otro</option>
    </select><br><br> <label>Procedencia:</label> <input type="text" name="procedencia" value="<?php echo htmlspecialchars($procedencia); ?>"><br><br> <label>Referencia:</label> <input type="text" name="referencia" value="<?php echo htmlspecialchars($referencia); ?>"><br><br> <button type="submit">Guardar</button> <a href="<?= BASE_URL ?>dashboard.php?vista=clientes" style="display:inline-block;padding:8px 16px;background:#343a40;color:#fff;text-decoration:none;border-radius:4px;">Regresar a la tabla</a>
 </form>
<script>
    function generarCodigoCliente() {
    const now = new Date();
    const year = now.getFullYear().toString().slice(-2); // últimos 2 dígitos del año
    const month = ("0" + (now.getMonth() + 1)).slice(-2); // mes con 2 dígitos
    const day = ("0" + now.getDate()).slice(-2); // día con 2 dígitos
    const random = Math.floor(1000 + Math.random() * 9000); // 4 dígitos aleatorios
    const codigo = `LAB-${year}${month}${day}-${random}`;
    document.getElementById('codigo_cliente').value = codigo;
}

</script>