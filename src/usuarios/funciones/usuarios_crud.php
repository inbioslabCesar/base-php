<?php
// Incluye la conexión correctamente desde dos carpetas arriba
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';
// Función para contar usuarios (total y filtrados)
function usuarios_count($search = '') {
    global $pdo;
    if ($search !== '') {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ? OR usuario LIKE ? OR rol LIKE ? OR email LIKE ?";
        $stmt = $pdo->prepare($sql);
        $searchLike = "%$search%";
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
        return (int)$stmt->fetchColumn();
    } else {
        $sql = "SELECT COUNT(*) FROM usuarios";
        return (int)$pdo->query($sql)->fetchColumn();
    }
}

// Listar usuarios paginados y ordenados
function usuarios_listar($orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','apellido','usuario','rol','email','estado','fecha_creacion']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM usuarios ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar usuarios paginados y ordenados
function usuarios_buscar($search, $orderBy = 'id', $orderDir = 'asc', $start = 0, $length = 10) {
    global $pdo;
    $orderBy = in_array($orderBy, ['id','nombre','apellido','usuario','rol','email','estado','fecha_creacion']) ? $orderBy : 'id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
    $sql = "SELECT * FROM usuarios WHERE nombre LIKE :search OR apellido LIKE :search OR usuario LIKE :search OR rol LIKE :search OR email LIKE :search ORDER BY $orderBy $orderDir LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $searchLike = "%$search%";
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// ...existing code...

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
if (php_sapi_name() !== 'cli' && isset($_POST['registrar_usuario']) && !isset($_GET['action'])) {
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
if (php_sapi_name() !== 'cli' && isset($_POST['actualizar_usuario']) && !isset($_GET['action'])) {
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
if (php_sapi_name() !== 'cli' && isset($_GET['eliminar_usuario']) && !isset($_GET['action'])) {
    $id = $_GET['eliminar_usuario'];
    $sql = "DELETE FROM usuarios WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "dashboard.php?vista=usuarios&success=3");

    exit;
}