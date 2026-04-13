<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$cantidad = round((float)($_POST['cantidad'] ?? 0), 2);
$observacion = trim((string)($_POST['observacion'] ?? ''));
$formToken = trim((string)($_POST['form_token'] ?? ''));
$sessionToken = trim((string)($_SESSION['inventario_transfer_form_token'] ?? ''));
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if ($formToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $formToken)) {
    $_SESSION['mensaje'] = 'El formulario de transferencia ya fue enviado o expiró. Recarga la página e intenta nuevamente.';
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}
unset($_SESSION['inventario_transfer_form_token']);

if ($itemId <= 0 || $cantidad <= 0) {
    $_SESSION['mensaje'] = 'Datos inválidos para transferencia.';
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

try {
    $stmtItem = $pdo->prepare("SELECT id, nombre, unidad_medida, activo FROM inventario_items WHERE id = ? LIMIT 1");
    $stmtItem->execute([$itemId]);
    $item = $stmtItem->fetch(\PDO::FETCH_ASSOC);

    if (!$item || (int)($item['activo'] ?? 0) !== 1) {
        $_SESSION['mensaje'] = 'Ítem no disponible para transferencia.';
        header('Location: dashboard.php?vista=inventario_interno');
        exit;
    }

    $stmtSuma = $pdo->prepare("SELECT IFNULL(SUM(cantidad_actual),0) FROM inventario_lotes WHERE item_id = ? AND cantidad_actual > 0");
    $stmtSuma->execute([$itemId]);
    $stockTotal = round((float)$stmtSuma->fetchColumn(), 2);

    $stmtColOrigen = $pdo->query("SHOW COLUMNS FROM inventario_movimientos LIKE 'origen'");
    $hasOrigenMovCol = (bool)($stmtColOrigen && $stmtColOrigen->fetch(\PDO::FETCH_ASSOC));

    if ($stockTotal < $cantidad) {
        $_SESSION['mensaje'] = 'Stock insuficiente en almacén principal. Disponible: ' . number_format($stockTotal, 2) . ' ' . ($item['unidad_medida'] ?? '');
        header('Location: dashboard.php?vista=inventario_interno');
        exit;
    }

    $pdo->beginTransaction();

    $stmtTransfer = $pdo->prepare("INSERT INTO inventario_transferencias (origen, destino, usuario_id, observacion, fecha_hora) VALUES ('almacen_principal', 'laboratorio', ?, ?, NOW())");
    $stmtTransfer->execute([
        $usuarioId > 0 ? $usuarioId : null,
        $observacion !== '' ? $observacion : null,
    ]);
    $transferenciaId = (int)$pdo->lastInsertId();

    $stmtDet = $pdo->prepare("INSERT INTO inventario_transferencias_detalle (transferencia_id, item_id, cantidad, created_at) VALUES (?, ?, ?, NOW())");
    $stmtDet->execute([$transferenciaId, $itemId, $cantidad]);

    $stmtLotes = $pdo->prepare("SELECT id, cantidad_actual FROM inventario_lotes WHERE item_id = ? AND cantidad_actual > 0 ORDER BY (fecha_vencimiento IS NULL) ASC, fecha_vencimiento ASC, id ASC");
    $stmtLotes->execute([$itemId]);
    $lotes = $stmtLotes->fetchAll(\PDO::FETCH_ASSOC);

    $restante = $cantidad;
    $stmtUpdLote = $pdo->prepare("UPDATE inventario_lotes SET cantidad_actual = cantidad_actual - ?, updated_at = NOW() WHERE id = ?");
    if ($hasOrigenMovCol) {
        $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, origen, usuario_id, fecha_hora) VALUES (?, ?, 'salida', ?, ?, 'transferencia_interna', ?, NOW())");
    } else {
        $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, usuario_id, fecha_hora) VALUES (?, ?, 'salida', ?, ?, ?, NOW())");
    }

    foreach ($lotes as $lote) {
        if ($restante <= 0) {
            break;
        }

        $actual = (float)($lote['cantidad_actual'] ?? 0);
        if ($actual <= 0) {
            continue;
        }

        $consumo = min($actual, $restante);
        $stmtUpdLote->execute([$consumo, (int)$lote['id']]);

        $obsMov = 'Transferencia interna #' . $transferenciaId . ' a laboratorio';
        if ($observacion !== '') {
            $obsMov .= ' | ' . $observacion;
        }

        $stmtMov->execute([
            $itemId,
            (int)$lote['id'],
            $consumo,
            $obsMov,
            $usuarioId > 0 ? $usuarioId : null,
        ]);

        $restante = round($restante - $consumo, 2);
    }

    if ($restante > 0) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'No se pudo completar la transferencia por stock insuficiente en lotes.';
        header('Location: dashboard.php?vista=inventario_interno');
        exit;
    }

    $pdo->commit();
    $_SESSION['mensaje'] = 'Transferencia interna registrada correctamente.';
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo registrar la transferencia: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario_interno');
exit;
