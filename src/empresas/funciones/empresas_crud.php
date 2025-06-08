<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../conexion/conexion.php';

function obtenerTodasLasEmpresas() {
    global $pdo;
    $sql = "SELECT * FROM empresas";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEmpresaPorId($id) {
    global $pdo;
    $sql = "SELECT * FROM empresas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Crear empresa
if (isset($_POST['registrar_empresa'])) {
    $ruc = trim($_POST['ruc']);
    $razon_social = trim($_POST['razon_social']);
    $nombre_comercial = trim($_POST['nombre_comercial']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $representante = trim($_POST['representante']);
    $password = $_POST['password'];
    $convenio = trim($_POST['convenio']);
    $estado = $_POST['estado'];

    $errores = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }
    $sql = "SELECT COUNT(*) FROM empresas WHERE email = ? OR ruc = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $ruc]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o RUC ya está registrado.";
    }

    if (count($errores) === 0) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO empresas (ruc, razon_social, nombre_comercial, direccion, telefono, email, representante, password, convenio, estado, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $ruc, $razon_social, $nombre_comercial, $direccion, $telefono, $email, $representante, $password_hash, $convenio, $estado
        ]);
        if ($result) {
            header("Location: " . BASE_URL . "dashboard.php?vista=empresas&success=1");
            exit;
        } else {
            $errores[] = "Error al registrar empresa.";
        }
    }
    if (count($errores) > 0) {
        session_start();
        $_SESSION['errores_empresa'] = $errores;
        header("Location: " . BASE_URL . "dashboard.php?vista=form_empresas");
        exit;
    }
}

// Actualizar empresa
if (isset($_POST['actualizar_empresa'])) {
    $id = $_POST['id'];
    $ruc = trim($_POST['ruc']);
    $razon_social = trim($_POST['razon_social']);
    $nombre_comercial = trim($_POST['nombre_comercial']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $representante = trim($_POST['representante']);
    $password = $_POST['password'];
    $convenio = trim($_POST['convenio']);
    $estado = $_POST['estado'];

    $errores = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo electrónico no válido.";
    }
    $sql = "SELECT COUNT(*) FROM empresas WHERE (email = ? OR ruc = ?) AND id != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $ruc, $id]);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El correo o RUC ya está registrado por otra empresa.";
    }

    if (count($errores) === 0) {
        $campos = "ruc=?, razon_social=?, nombre_comercial=?, direccion=?, telefono=?, email=?, representante=?, convenio=?, estado=?";
        $params = [$ruc, $razon_social, $nombre_comercial, $direccion, $telefono, $email, $representante, $convenio, $estado];
        if ($password) {
            $campos .= ", password=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $params[] = $id;
        $sql = "UPDATE empresas SET $campos WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            header("Location: " . BASE_URL . "dashboard.php?vista=empresas&success=2");
            exit;
        } else {
            $errores[] = "Error al actualizar empresa.";
        }
    }
    if (count($errores) > 0) {
        session_start();
        $_SESSION['errores_empresa'] = $errores;
        header("Location: " . BASE_URL . "dashboard.php?vista=editar_empresa&id=" . $id);
        exit;
    }
}

// Eliminar empresa (eliminación directa y segura)
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM empresas WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "dashboard.php?vista=empresas&success=3");
    exit;
}
?>
