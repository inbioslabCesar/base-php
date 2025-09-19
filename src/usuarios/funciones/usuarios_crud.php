<?php
// Incluye la conexión correctamente desde dos carpetas arriba
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';

// FUNCIONES CRUD
function obtenerTodosLosUsuarios() {
    global $pdo;
    $sql = "SELECT id, nombre, apellido, dni, email, telefono, rol, estado FROM usuarios";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerUsuarioPorId($id) {
    global $pdo;
    $sql = "SELECT * FROM usuarios WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// REGISTRO DE USUARIO
if (isset($_POST['registrar_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $sexo = $_POST['sexo'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $profesion = trim($_POST['profesion']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errores = [];
    if ($password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }

    $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? OR dni = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $dni]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o DNI ya está registrado.";
    }

    if (count($errores) === 0) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, apellido, dni, sexo, fecha_nacimiento, email, telefono, direccion, profesion, rol, estado, password, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $nombre, $apellido, $dni, $sexo, $fecha_nacimiento, $email, $telefono,
            $direccion, $profesion, $rol, $estado, $password_hash
        ]);

        if ($result) {
            header("Location:" . BASE_URL . "dashboard.php?vista=usuarios&success=1");
            exit;
        } else {
            $errores[] = "Error al registrar usuario. Intenta nuevamente.";
        }
    }

    if (count($errores) > 0) {
        $_SESSION['errores_usuario'] = $errores;
        header("Location:" . BASE_URL . "dashboard.php?vista=form_usuarios");
        exit;
    }
}

// ACTUALIZAR USUARIO
if (isset($_POST['actualizar_usuario'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $sexo = $_POST['sexo'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $profesion = trim($_POST['profesion']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errores = [];
    if ($password && $password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }

    $sql = "SELECT COUNT(*) FROM usuarios WHERE (email = ? OR dni = ?) AND id != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $dni, $id]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o DNI ya está registrado por otro usuario.";
    }

    if (count($errores) === 0) {
        $campos = "nombre=?, apellido=?, dni=?, sexo=?, fecha_nacimiento=?, email=?, telefono=?, direccion=?, profesion=?, rol=?, estado=?";
        $params = [$nombre, $apellido, $dni, $sexo, $fecha_nacimiento, $email, $telefono, $direccion, $profesion, $rol, $estado];
        if ($password) {
            $campos .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $params[] = $id;
        $sql = "UPDATE usuarios SET $campos WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            header("Location: " . BASE_URL . "dashboard.php?vista=usuarios&success=1");


            exit;
        } else {
            $errores[] = "Error al actualizar usuario.";
        }
    }
    if (count($errores) > 0) {
        $_SESSION['errores_usuario'] = $errores;
        header("Location:" . BASE_URL . "dashboard.php?vista=editar_usuario&id=" . $id);
        
        exit;
    }
}

// ELIMINAR USUARIO
if (isset($_GET['eliminar_usuario'])) {
    $id = $_GET['eliminar_usuario'];
    $sql = "DELETE FROM usuarios WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "dashboard.php?vista=usuarios&success=3");

    exit;
}