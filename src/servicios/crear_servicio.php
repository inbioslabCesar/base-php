<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
$profesionalesIds = isset($_POST['profesionales_ids']) && is_array($_POST['profesionales_ids']) ? $_POST['profesionales_ids'] : [];

if ($nombre === '' || $codigo === '' || $email === '' || $password === '') {
    $_SESSION['mensaje'] = 'Completa los campos obligatorios del servicio.';
    header('Location: dashboard.php?vista=form_servicio');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = 'El email del servicio no es valido.';
    header('Location: dashboard.php?vista=form_servicio');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE codigo = ?");
    $stmt->execute([$codigo]);
    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = 'Ya existe un servicio con ese codigo.';
        header('Location: dashboard.php?vista=form_servicio');
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE email = ?");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['mensaje'] = 'Ya existe un servicio con ese email.';
        header('Location: dashboard.php?vista=form_servicio');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO servicios (nombre, codigo, responsable, telefono, email, password, estado) VALUES (?, ?, ?, ?, ?, ?, 'activo')");
    $stmt->execute([
        $nombre,
        $codigo,
        $responsable !== '' ? $responsable : null,
        $telefono !== '' ? $telefono : null,
        $email,
        password_hash($password, PASSWORD_DEFAULT)
    ]);

    $servicioId = (int)$pdo->lastInsertId();
    $profesionalesNormalizados = [];
    foreach ($profesionalesIds as $pid) {
        $pidInt = (int)$pid;
        if ($pidInt > 0) {
            $profesionalesNormalizados[$pidInt] = true;
        }
    }

    if ($servicioId > 0 && !empty($profesionalesNormalizados)) {
        $stmtInsProf = $pdo->prepare("INSERT IGNORE INTO servicio_profesional (servicio_id, profesional_id) VALUES (?, ?)");
        foreach (array_keys($profesionalesNormalizados) as $profesionalId) {
            $stmtInsProf->execute([$servicioId, $profesionalId]);
        }
    }

    $pdo->commit();

    $_SESSION['mensaje'] = 'Servicio registrado correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo registrar el servicio.';
}

header('Location: dashboard.php?vista=servicios');
exit;
