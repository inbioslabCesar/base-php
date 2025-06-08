<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../conexion/conexion.php';

function obtenerTodosLosClientes() {
    global $pdo;
    $sql = "SELECT * FROM clientes";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerClientePorId($id) {
    global $pdo;
    $sql = "SELECT * FROM clientes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Crear cliente
if (isset($_POST['registrar_cliente'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $edad = trim($_POST['edad']);
    $edad = ($edad === '' ? null : $edad);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $dni = trim($_POST['dni']);
    $sexo = $_POST['sexo'];
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $fecha_nacimiento = ($fecha_nacimiento === '' ? null : $fecha_nacimiento);
    $referencia = trim($_POST['referencia']);
    $procedencia = trim($_POST['procedencia']);
    $promociones = trim($_POST['promociones']);
    $estado = $_POST['estado'];
    $codigo_cliente = trim($_POST['codigo_cliente']);

    $errores = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }
    $sql = "SELECT COUNT(*) FROM clientes WHERE email = ? OR dni = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $dni]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o DNI ya está registrado.";
    }

    if (count($errores) === 0) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO clientes (codigo_cliente, nombre, apellido, edad, email, password, telefono, direccion, dni, sexo, fecha_nacimiento, referencia, procedencia, promociones, estado, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $codigo_cliente, $nombre, $apellido, $edad, $email, $password_hash, $telefono, $direccion, $dni, $sexo, $fecha_nacimiento, $referencia, $procedencia, $promociones, $estado
        ]);
        if ($result) {
            header("Location: " . BASE_URL . "dashboard.php?vista=clientes&success=1");
            exit;
        } else {
            $errores[] = "Error al registrar cliente.";
        }
    }
    if (count($errores) > 0) {
        session_start();
        $_SESSION['errores_cliente'] = $errores;
        header("Location: " . BASE_URL . "dashboard.php?vista=form_clientes");
        exit;
    }
}

// Actualizar cliente
if (isset($_POST['actualizar_cliente'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $edad = trim($_POST['edad']);
    $edad = ($edad === '' ? null : $edad);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $dni = trim($_POST['dni']);
    $sexo = $_POST['sexo'];
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $fecha_nacimiento = ($fecha_nacimiento === '' ? null : $fecha_nacimiento);
    $referencia = trim($_POST['referencia']);
    $procedencia = trim($_POST['procedencia']);
    $promociones = trim($_POST['promociones']);
    $estado = $_POST['estado'];
    $password = $_POST['password'];
    $codigo_cliente = trim($_POST['codigo_cliente']);

    $errores = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }
    $sql = "SELECT COUNT(*) FROM clientes WHERE (email = ? OR dni = ?) AND id != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $dni, $id]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o DNI ya está registrado por otro cliente.";
    }

    if (count($errores) === 0) {
        $campos = "codigo_cliente=?, nombre=?, apellido=?, edad=?, email=?, telefono=?, direccion=?, dni=?, sexo=?, fecha_nacimiento=?, referencia=?, procedencia=?, promociones=?, estado=?";
        $params = [$codigo_cliente, $nombre, $apellido, $edad, $email, $telefono, $direccion, $dni, $sexo, $fecha_nacimiento, $referencia, $procedencia, $promociones, $estado];
        if ($password) {
            $campos .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $params[] = $id;
        $sql = "UPDATE clientes SET $campos WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            header("Location: " . BASE_URL . "dashboard.php?vista=clientes&success=2");
            exit;
        } else {
            $errores[] = "Error al actualizar cliente.";
        }
    }
    if (count($errores) > 0) {
        session_start();
        $_SESSION['errores_cliente'] = $errores;
        header("Location: " . BASE_URL . "dashboard.php?vista=editar_cliente&id=" . $id);
        exit;
    }
}

// Eliminar cliente
if (isset($_GET['eliminar_cliente'])) {
    $id = $_GET['eliminar_cliente'];
    $sql = "DELETE FROM clientes WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "dashboard.php?vista=clientes&success=3");
    exit;
}
?>
