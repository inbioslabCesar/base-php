<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['mensaje'] = 'Servicio no valido.';
    header('Location: dashboard.php?vista=servicios');
    exit;
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo preparar la tabla de servicios.';
    header('Location: dashboard.php?vista=servicios');
    exit;
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$codigo = servicios_normalizar_codigo((string)($_POST['codigo'] ?? ''));
$responsable = trim((string)($_POST['responsable'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$estado = strtolower(trim((string)($_POST['estado'] ?? 'activo')));
$estado = in_array($estado, ['activo', 'inactivo'], true) ? $estado : 'activo';
$profesionalesIds = isset($_POST['profesionales_ids']) && is_array($_POST['profesionales_ids']) ? $_POST['profesionales_ids'] : [];

if ($nombre === '' || $codigo === '' || $email === '') {
    $_SESSION['mensaje'] = 'Completa los campos obligatorios del servicio.';
    header('Location: dashboard.php?vista=form_servicio&id=' . $id);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = 'El email del servicio no es valido.';
    header('Location: dashboard.php?vista=form_servicio&id=' . $id);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE codigo = ? AND id != ?");
    $stmt->execute([$codigo, $id]);
    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = 'Ya existe otro servicio con ese codigo.';
        header('Location: dashboard.php?vista=form_servicio&id=' . $id);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = 'Ya existe otro servicio con ese email.';
        header('Location: dashboard.php?vista=form_servicio&id=' . $id);
        exit;
    }

    $sql = "UPDATE servicios SET nombre = ?, codigo = ?, responsable = ?, telefono = ?, email = ?, estado = ?";
    $params = [
        $nombre,
        $codigo,
        $responsable !== '' ? $responsable : null,
        $telefono !== '' ? $telefono : null,
        $email,
        $estado
    ];

    if ($password !== '') {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $stmtDelProf = $pdo->prepare("DELETE FROM servicio_profesional WHERE servicio_id = ?");
    $stmtDelProf->execute([$id]);

    $profesionalesNormalizados = [];
    foreach ($profesionalesIds as $pid) {
        $pidInt = (int)$pid;
        if ($pidInt > 0) {
            $profesionalesNormalizados[$pidInt] = true;
        }
    }

    if (!empty($profesionalesNormalizados)) {
        $stmtInsProf = $pdo->prepare("INSERT IGNORE INTO servicio_profesional (servicio_id, profesional_id) VALUES (?, ?)");
        foreach (array_keys($profesionalesNormalizados) as $profesionalId) {
            $stmtInsProf->execute([$id, $profesionalId]);
        }
    }

    $pdo->commit();
    $_SESSION['mensaje'] = 'Servicio actualizado correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo actualizar el servicio.';
}

header('Location: dashboard.php?vista=servicios');
exit;
