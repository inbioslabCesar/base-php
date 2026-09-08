<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/operacion_context.php';
require_once __DIR__ . '/../usuarios/funciones/laboratorio_turnos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

$cotizacionId = (int)($_POST['cotizacion_id'] ?? 0);
$idResultado = (int)($_POST['id_resultado'] ?? 0);
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

if ($idResultado <= 0) {
    $_SESSION['mensaje'] = 'Selecciona una sección válida para validar.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: ' . $redirect);
    exit;
}

try {
    $operacionContext = function_exists('app_operacion_context') ? app_operacion_context($pdo) : ['es_sis' => false];
    $esModoSis = !empty($operacionContext['es_sis']);

    if ($rol === 'laboratorista' && $usuarioId > 0 && $esModoSis) {
        laboratorio_turnos_asegurar_esquema($pdo);
        $turnoAbierto = laboratorio_turno_abierto_por_usuario($pdo, $usuarioId);
        if (empty($turnoAbierto)) {
            $_SESSION['mensaje'] = 'Debes abrir tu turno antes de validar resultados.';
            $_SESSION['mensaje_tipo'] = 'warning';
            header('Location: ' . $redirect);
            exit;
        }
    }

    $hasEstadoValidacion = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'estado_validacion'")->fetch(PDO::FETCH_ASSOC);
    $hasFechaValidacion = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'fecha_validacion_en'")->fetch(PDO::FETCH_ASSOC);
    $hasValidadoPor = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'validado_por'")->fetch(PDO::FETCH_ASSOC);
    $hasIdTurno = (bool)$pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'id_turno'")->fetch(PDO::FETCH_ASSOC);
    $hasTablaTurnos = (bool)$pdo->query("SHOW TABLES LIKE 'laboratorio_turnos'")->fetchColumn();
    $hasCotFechaValidFinal = (bool)$pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'fecha_validacion_final'")->fetch(PDO::FETCH_ASSOC);

    if (!$hasEstadoValidacion || !$hasFechaValidacion || !$hasValidadoPor) {
        $_SESSION['mensaje'] = 'Faltan columnas de validación. Ejecuta primero la migración SQL de fechas clínicas.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    $selectTurnoUsuario = ($hasIdTurno && $hasTablaTurnos)
        ? ", lt.usuario_id AS turno_usuario_id"
        : ", NULL AS turno_usuario_id";
    $joinTurno = ($hasIdTurno && $hasTablaTurnos)
        ? "LEFT JOIN laboratorio_turnos lt ON lt.id = re.id_turno"
        : "";

    $stmtExamen = $pdo->prepare("SELECT re.id, re.id_cotizacion, re.estado, re.id_laboratorista, re.resultados{$selectTurnoUsuario}
        FROM resultados_examenes re
        {$joinTurno}
        WHERE re.id = ?
        LIMIT 1");
    $stmtExamen->execute([$idResultado]);
    $examen = $stmtExamen->fetch(PDO::FETCH_ASSOC);

    if (!$examen) {
        $_SESSION['mensaje'] = 'No se encontró la sección seleccionada.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    $cotizacionResultado = (int)($examen['id_cotizacion'] ?? 0);
    if ($cotizacionResultado <= 0 || $cotizacionResultado !== $cotizacionId) {
        $_SESSION['mensaje'] = 'La sección no pertenece a la cotización actual.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    if ((string)($examen['estado'] ?? '') !== 'completado') {
        $_SESSION['mensaje'] = 'Solo puedes validar una sección cuando esté completada al 100%.';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: ' . $redirect);
        exit;
    }

    if ($rol === 'laboratorista') {
        $idLaboratoristaExamen = (int)($examen['id_laboratorista'] ?? 0);
        $idTurnoUsuario = (int)($examen['turno_usuario_id'] ?? 0);
        $esPropio = ($usuarioId > 0) && ($idLaboratoristaExamen === $usuarioId || $idTurnoUsuario === $usuarioId);
        if (!$esPropio) {
            $_SESSION['mensaje'] = 'Solo puedes validar secciones registradas en tu turno/responsabilidad.';
            $_SESSION['mensaje_tipo'] = 'warning';
            header('Location: ' . $redirect);
            exit;
        }
    }

    $pdo->beginTransaction();

    $stmtUpd = $pdo->prepare("UPDATE resultados_examenes
        SET estado_validacion = 'validado',
            fecha_validacion_en = NOW(),
            validado_por = :usuario
        WHERE id = :id_resultado
          AND id_cotizacion = :cotizacion_id
          AND estado = 'completado'");
    $stmtUpd->execute([
        'usuario' => $usuarioId > 0 ? $usuarioId : null,
        'id_resultado' => $idResultado,
        'cotizacion_id' => $cotizacionId,
    ]);

    if ($hasCotFechaValidFinal) {
        $stmtRows = $pdo->prepare("SELECT resultados, estado_validacion, estado FROM resultados_examenes WHERE id_cotizacion = ?");
        $stmtRows->execute([$cotizacionId]);
        $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

        $imprimibles = 0;
        $validados = 0;
        foreach ($rows as $row) {
            if ((string)($row['estado'] ?? '') === 'anulado') {
                continue;
            }

            $resultados = [];
            if (!empty($row['resultados'])) {
                $dec = json_decode((string)$row['resultados'], true);
                if (is_array($dec)) {
                    $resultados = $dec;
                }
            }
            $imprimir = !isset($resultados['imprimir_examen']) || (int)$resultados['imprimir_examen'] === 1;
            if (!$imprimir) {
                continue;
            }

            $imprimibles++;
            if ((string)($row['estado_validacion'] ?? '') === 'validado') {
                $validados++;
            }
        }

        if ($imprimibles > 0 && $imprimibles === $validados) {
            $stmtCot = $pdo->prepare("UPDATE cotizaciones SET fecha_validacion_final = NOW() WHERE id = ?");
            $stmtCot->execute([$cotizacionId]);
        } else {
            $stmtCot = $pdo->prepare("UPDATE cotizaciones SET fecha_validacion_final = NULL WHERE id = ?");
            $stmtCot->execute([$cotizacionId]);
        }
    }

    $pdo->commit();

    $_SESSION['mensaje'] = 'Sección validada correctamente.';
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
