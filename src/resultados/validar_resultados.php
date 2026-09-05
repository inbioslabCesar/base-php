<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

$cotizacionId = (int)($_POST['cotizacion_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));

$redirect = 'dashboard.php?vista=cotizaciones';
if ($cotizacionId > 0) {
    $redirect = 'dashboard.php?vista=formulario&cotizacion_id=' . $cotizacionId;
}

if ($cotizacionId <= 0) {
    $_SESSION['mensaje'] = 'No se pudo validar: cotización inválida.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: ' . $redirect);
    exit;
}

if (!in_array($rol, ['admin', 'laboratorista'], true)) {
    $_SESSION['mensaje'] = 'No tienes permisos para validar resultados.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: ' . $redirect);
    exit;
}

try {
    $hasEstadoValidacion = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'estado_validacion'")->fetch(PDO::FETCH_ASSOC);
    $hasFechaValidacion = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'fecha_validacion_en'")->fetch(PDO::FETCH_ASSOC);
    $hasValidadoPor = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'validado_por'")->fetch(PDO::FETCH_ASSOC);
    $hasCotFechaValidFinal = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'fecha_validacion_final'")->fetch(PDO::FETCH_ASSOC);

    if (!$hasEstadoValidacion || !$hasFechaValidacion || !$hasValidadoPor) {
        $_SESSION['mensaje'] = 'Faltan columnas de validación. Ejecuta primero la migración SQL de fechas clínicas.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM resultados_examenes WHERE id_cotizacion = ? AND (estado IS NULL OR estado <> 'anulado')");
    $stmtCount->execute([$cotizacionId]);
    $totalExamenes = (int)$stmtCount->fetchColumn();

    if ($totalExamenes <= 0) {
        $_SESSION['mensaje'] = 'No hay exámenes para validar en esta cotización.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    $stmtCompletos = $pdo->prepare("SELECT COUNT(*) FROM resultados_examenes WHERE id_cotizacion = ? AND estado = 'completado' AND (estado IS NULL OR estado <> 'anulado')");
    $stmtCompletos->execute([$cotizacionId]);
    $totalCompletos = (int)$stmtCompletos->fetchColumn();

    if ($totalCompletos < $totalExamenes) {
        $_SESSION['mensaje'] = 'No se puede validar: el proceso aún no está al 100% en todos los exámenes.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    $pdo->beginTransaction();

    $stmtUpd = $pdo->prepare("UPDATE resultados_examenes
        SET estado_validacion = 'validado',
            fecha_validacion_en = NOW(),
            validado_por = :usuario
        WHERE id_cotizacion = :cotizacion_id
          AND estado = 'completado'");
    $stmtUpd->execute([
        'usuario' => $usuarioId > 0 ? $usuarioId : null,
        'cotizacion_id' => $cotizacionId,
    ]);

    if ($hasCotFechaValidFinal) {
        $stmtCot = $pdo->prepare("UPDATE cotizaciones SET fecha_validacion_final = NOW() WHERE id = ?");
        $stmtCot->execute([$cotizacionId]);
    }

    $pdo->commit();

    $_SESSION['mensaje'] = 'Resultados validados correctamente.';
    $_SESSION['mensaje_tipo'] = 'success';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo validar resultados: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

header('Location: ' . $redirect);
exit;
