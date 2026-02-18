<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

$idResultado = (int)($_POST['id_resultado'] ?? 0);
$cotizacionId = (int)($_POST['cotizacion_id'] ?? 0);
$motivo = trim((string)($_POST['motivo_repeticion'] ?? ''));
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$redirect = 'dashboard.php?vista=cotizaciones';
if ($cotizacionId > 0) {
    $redirect = 'dashboard.php?vista=formulario&cotizacion_id=' . $cotizacionId;
}

if ($idResultado <= 0) {
    $_SESSION['mensaje'] = 'No se pudo repetir la prueba: resultado no válido.';
    header('Location: ' . $redirect);
    exit;
}

if ($motivo === '') {
    $_SESSION['mensaje'] = 'Debe indicar el motivo de la repetición de prueba.';
    header('Location: ' . $redirect);
    exit;
}

try {
    $stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('inventario_examen_recetas','inventario_consumos_examen','inventario_transferencias','inventario_transferencias_detalle')");
    $stmtTbl->execute();
    $tablasOk = ((int)$stmtTbl->fetchColumn() === 4);

    if (!$tablasOk) {
        $_SESSION['mensaje'] = 'No se pudo repetir la prueba: faltan tablas de inventario interno.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmtInfo = $pdo->prepare("SELECT id, id_examen, id_cotizacion FROM resultados_examenes WHERE id = ? LIMIT 1");
    $stmtInfo->execute([$idResultado]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        $_SESSION['mensaje'] = 'No se encontró el resultado para repetir la prueba.';
        header('Location: ' . $redirect);
        exit;
    }

    $idExamen = (int)($info['id_examen'] ?? 0);
    $idCotizacionReal = (int)($info['id_cotizacion'] ?? 0);
    if ($idCotizacion <= 0) {
        $cotizacionId = $idCotizacionReal;
        $redirect = 'dashboard.php?vista=formulario&cotizacion_id=' . $cotizacionId;
    }

    if ($idExamen <= 0 || $idCotizacionReal <= 0) {
        $_SESSION['mensaje'] = 'No se pudo repetir la prueba: datos del resultado incompletos.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmtQty = $pdo->prepare("SELECT IFNULL(SUM(cantidad), 1) FROM cotizaciones_detalle WHERE id_cotizacion = ? AND id_examen = ?");
    $stmtQty->execute([$idCotizacionReal, $idExamen]);
    $factorCantidad = (float)$stmtQty->fetchColumn();
    if ($factorCantidad <= 0) {
        $factorCantidad = 1;
    }

    $stmtRecetas = $pdo->prepare("SELECT item_id, cantidad_por_prueba
        FROM inventario_examen_recetas
        WHERE id_examen = ? AND activo = 1");
    $stmtRecetas->execute([$idExamen]);
    $recetas = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recetas)) {
        $_SESSION['mensaje'] = 'No hay recetas activas para este examen. No se aplicó consumo por repetición.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmtTransferido = $pdo->prepare("SELECT IFNULL(SUM(td.cantidad),0)
        FROM inventario_transferencias_detalle td
        JOIN inventario_transferencias t ON t.id = td.transferencia_id
        WHERE td.item_id = ? AND t.destino = 'laboratorio'");
    $stmtConsumido = $pdo->prepare("SELECT IFNULL(SUM(cantidad_consumida),0)
        FROM inventario_consumos_examen
        WHERE item_id = ? AND estado = 'aplicado'");
    $stmtItem = $pdo->prepare("SELECT codigo, nombre, unidad_medida FROM inventario_items WHERE id = ? LIMIT 1");

    $pendientes = [];
    $consumosAInsertar = [];

    foreach ($recetas as $r) {
        $itemId = (int)($r['item_id'] ?? 0);
        $cantidadBase = (float)($r['cantidad_por_prueba'] ?? 0);
        if ($itemId <= 0 || $cantidadBase <= 0) {
            continue;
        }

        $cantidadNecesaria = round($cantidadBase * $factorCantidad, 4);
        if ($cantidadNecesaria <= 0) {
            continue;
        }

        $stmtTransferido->execute([$itemId]);
        $transferido = (float)$stmtTransferido->fetchColumn();

        $stmtConsumido->execute([$itemId]);
        $consumido = (float)$stmtConsumido->fetchColumn();

        $saldoInterno = round($transferido - $consumido, 4);
        if ($saldoInterno + 0.0001 < $cantidadNecesaria) {
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            $nombreItem = trim((string)($item['codigo'] ?? '') . ' ' . (string)($item['nombre'] ?? ''));
            $unidad = (string)($item['unidad_medida'] ?? 'unid');
            $pendientes[] = 'Stock insuficiente para ' . $nombreItem . ' (' . number_format($cantidadNecesaria, 4) . ' ' . $unidad . ' requeridos, ' . number_format($saldoInterno, 4) . ' disponibles).';
            continue;
        }

        $consumosAInsertar[] = [
            'item_id' => $itemId,
            'cantidad' => $cantidadNecesaria,
        ];
    }

    if (empty($consumosAInsertar)) {
        $msg = 'No se pudo aplicar consumo por repetición.';
        if (!empty($pendientes)) {
            $msg .= ' ' . implode(' ', $pendientes);
        }
        $_SESSION['mensaje'] = $msg;
        header('Location: ' . $redirect);
        exit;
    }

    if (!empty($pendientes)) {
        $_SESSION['mensaje'] = 'No se aplicó consumo de repetición porque faltó stock en algunos ítems. ' . implode(' ', $pendientes);
        header('Location: ' . $redirect);
        exit;
    }

    $pdo->beginTransaction();

    $origenEvento = 'repeticion_' . date('ymdHis') . '_' . (string)mt_rand(100, 999);
    if (strlen($origenEvento) > 30) {
        $origenEvento = substr($origenEvento, 0, 30);
    }

    $stmtInsert = $pdo->prepare("INSERT INTO inventario_consumos_examen
        (id_cotizacion, id_examen, item_id, cantidad_consumida, origen_evento, estado, usuario_id, observacion, fecha_hora)
        VALUES (?, ?, ?, ?, ?, 'aplicado', ?, ?, NOW())");

    foreach ($consumosAInsertar as $consumo) {
        $obs = 'Repetición de prueba. Resultado ID: ' . $idResultado . '. Motivo: ' . $motivo;
        $stmtInsert->execute([
            $idCotizacionReal,
            $idExamen,
            (int)$consumo['item_id'],
            (float)$consumo['cantidad'],
            $origenEvento,
            $usuarioId > 0 ? $usuarioId : null,
            $obs,
        ]);
    }

    $pdo->commit();
    $_SESSION['mensaje'] = 'Repetición registrada. Consumo adicional aplicado para este examen.';
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo registrar la repetición: ' . $e->getMessage();
}

header('Location: ' . $redirect);
exit;
