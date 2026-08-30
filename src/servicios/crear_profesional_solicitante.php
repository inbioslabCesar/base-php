<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo preparar la estructura para profesionales solicitantes.';
    header('Location: dashboard.php?vista=profesionales_solicitantes');
    exit;
}

$nombres = trim((string)($_POST['nombres'] ?? ''));
$apellidos = trim((string)($_POST['apellidos'] ?? ''));
$tipoProfesional = trim((string)($_POST['tipo_profesional'] ?? ''));
$numeroDocumento = trim((string)($_POST['numero_documento'] ?? ''));
$registroProfesional = trim((string)($_POST['registro_profesional'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$serviciosIds = isset($_POST['servicios_ids']) && is_array($_POST['servicios_ids']) ? $_POST['servicios_ids'] : [];

if ($nombres === '' || $tipoProfesional === '') {
    $_SESSION['mensaje'] = 'Completa los campos obligatorios del profesional solicitante.';
    header('Location: dashboard.php?vista=form_profesional_solicitante');
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = 'El email del profesional solicitante no es válido.';
    header('Location: dashboard.php?vista=form_profesional_solicitante');
    exit;
}

$serviciosNorm = [];
foreach ($serviciosIds as $sid) {
    $sidInt = (int)$sid;
    if ($sidInt > 0) {
        $serviciosNorm[$sidInt] = true;
    }
}
if (empty($serviciosNorm)) {
    $_SESSION['mensaje'] = 'Debes asociar al menos un servicio al profesional solicitante.';
    header('Location: dashboard.php?vista=form_profesional_solicitante');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO profesionales_solicitantes (nombres, apellidos, tipo_profesional, numero_documento, registro_profesional, telefono, email, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')");
    $stmt->execute([
        $nombres,
        $apellidos !== '' ? $apellidos : null,
        $tipoProfesional,
        $numeroDocumento !== '' ? $numeroDocumento : null,
        $registroProfesional !== '' ? $registroProfesional : null,
        $telefono !== '' ? $telefono : null,
        $email !== '' ? $email : null,
    ]);

    $profesionalId = (int)$pdo->lastInsertId();
    if ($profesionalId > 0) {
        $stmtRel = $pdo->prepare("INSERT IGNORE INTO servicio_profesional (servicio_id, profesional_id) VALUES (?, ?)");
        foreach (array_keys($serviciosNorm) as $servicioId) {
            $stmtRel->execute([$servicioId, $profesionalId]);
        }
    }

    $pdo->commit();
    $_SESSION['mensaje'] = 'Profesional solicitante registrado correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo registrar el profesional solicitante.';
}

header('Location: dashboard.php?vista=profesionales_solicitantes');
exit;
