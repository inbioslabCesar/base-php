<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario');
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$tipo = trim((string)($_POST['tipo'] ?? ''));
$cantidad = round((float)($_POST['cantidad'] ?? 0), 2);
$cantidadPresentacionRaw = trim((string)($_POST['cantidad_presentacion'] ?? ''));
$cantidadPresentacion = $cantidadPresentacionRaw === '' ? 0.0 : (float)$cantidadPresentacionRaw;
$observacion = trim((string)($_POST['observacion'] ?? ''));
$loteCodigo = trim((string)($_POST['lote_codigo'] ?? ''));
$fechaVencimiento = trim((string)($_POST['fecha_vencimiento'] ?? ''));
$formToken = trim((string)($_POST['form_token'] ?? ''));
$sessionToken = trim((string)($_SESSION['inventario_mov_form_token'] ?? ''));
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if ($formToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $formToken)) {
    $_SESSION['mensaje'] = 'El formulario ya fue enviado o expiró. Recarga la página e intenta nuevamente.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}
unset($_SESSION['inventario_mov_form_token']);

$tiposEntrada = ['entrada', 'ajuste_pos'];
$tiposSalida = ['salida', 'ajuste_neg', 'merma', 'vencido'];
$tiposValidos = array_merge($tiposEntrada, $tiposSalida);

$observacionLower = function_exists('mb_strtolower')
    ? mb_strtolower($observacion, 'UTF-8')
    : strtolower($observacion);
$esSalidaLaboratorio = in_array($tipo, $tiposSalida, true)
    && strpos($observacionLower, 'laboratorio') !== false;

if ($itemId <= 0 || !in_array($tipo, $tiposValidos, true) || ($cantidad <= 0 && $cantidadPresentacion <= 0)) {
    $_SESSION['mensaje'] = 'Datos inválidos para registrar movimiento.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

if ($cantidadPresentacion < 0) {
    $_SESSION['mensaje'] = 'Cantidad por presentación inválida.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

if ($fechaVencimiento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento)) {
    $_SESSION['mensaje'] = 'Formato de fecha de vencimiento inválido.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

$fechaVencimientoVal = $fechaVencimiento === '' ? null : $fechaVencimiento;

try {
    $requiredTables = ['inventario_items', 'inventario_lotes', 'inventario_movimientos'];
    $tablesReady = true;
    $stmtTbl = $pdo->prepare("SHOW TABLES LIKE ?");
    foreach ($requiredTables as $tblName) {
        $stmtTbl->execute([$tblName]);
        if (!$stmtTbl->fetchColumn()) {
            $tablesReady = false;
            break;
        }
    }

    if (!$tablesReady) {
        $_SESSION['mensaje'] = 'Faltan tablas de inventario. Ejecuta sql/agregar_tablas_inventario.sql.';
        header('Location: dashboard.php?vista=inventario');
        exit;
    }

    $stmtColFactor = $pdo->query("SHOW COLUMNS FROM inventario_items LIKE 'factor_presentacion'");
    $hasFactorPresentacionCol = (bool)($stmtColFactor && $stmtColFactor->fetch(\PDO::FETCH_ASSOC));
    $stmtColOrigen = $pdo->query("SHOW COLUMNS FROM inventario_movimientos LIKE 'origen'");
    $hasOrigenMovCol = (bool)($stmtColOrigen && $stmtColOrigen->fetch(\PDO::FETCH_ASSOC));

    $stmtTblInterno = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('inventario_transferencias','inventario_transferencias_detalle')");
    $stmtTblInterno->execute();
    $tablasInternoReady = ((int)$stmtTblInterno->fetchColumn() === 2);

    $stmtItem = $pdo->prepare(
        "SELECT id, nombre, unidad_medida, activo, " .
        ($hasFactorPresentacionCol ? "factor_presentacion" : "1 AS factor_presentacion") .
        " FROM inventario_items WHERE id = ? LIMIT 1"
    );
    $stmtItem->execute([$itemId]);
    $item = $stmtItem->fetch(\PDO::FETCH_ASSOC);

    if (!$item || (int)($item['activo'] ?? 0) !== 1) {
        $_SESSION['mensaje'] = 'Ítem no disponible para movimientos.';
        header('Location: dashboard.php?vista=inventario');
        exit;
    }

    $factorPresentacion = (float)($item['factor_presentacion'] ?? 1);
    if ($factorPresentacion <= 0) {
        $factorPresentacion = 1;
    }

    if ($cantidadPresentacion > 0) {
        $cantidad = round($cantidadPresentacion * $factorPresentacion, 2);
        $notaConversion = 'Conversión automática: ' .
            rtrim(rtrim(number_format($cantidadPresentacion, 4, '.', ''), '0'), '.') .
            ' presentación x factor ' .
            rtrim(rtrim(number_format($factorPresentacion, 4, '.', ''), '0'), '.') .
            ' = ' .
            rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.') .
            ' ' .
            (string)($item['unidad_medida'] ?? 'unid');
        $observacion = $observacion !== '' ? ($observacion . ' | ' . $notaConversion) : $notaConversion;
    }

    if ($cantidad <= 0) {
        $_SESSION['mensaje'] = 'Cantidad inválida para registrar movimiento.';
        header('Location: dashboard.php?vista=inventario');
        exit;
    }

    $pdo->beginTransaction();

    if (in_array($tipo, $tiposEntrada, true)) {
        if ($loteCodigo === '') {
            $loteCodigo = 'L-' . date('Ymd-His');
        }

        $stmtLote = $pdo->prepare("SELECT id FROM inventario_lotes WHERE item_id = ? AND lote_codigo = ? AND ((fecha_vencimiento IS NULL AND ? IS NULL) OR fecha_vencimiento = ?) ORDER BY id DESC LIMIT 1");
        $stmtLote->execute([$itemId, $loteCodigo, $fechaVencimientoVal, $fechaVencimientoVal]);
        $loteId = (int)$stmtLote->fetchColumn();

        if ($loteId > 0) {
            $stmtUpd = $pdo->prepare("UPDATE inventario_lotes SET cantidad_inicial = cantidad_inicial + ?, cantidad_actual = cantidad_actual + ?, updated_at = NOW() WHERE id = ?");
            $stmtUpd->execute([$cantidad, $cantidad, $loteId]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO inventario_lotes (item_id, lote_codigo, fecha_vencimiento, cantidad_inicial, cantidad_actual, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmtIns->execute([$itemId, $loteCodigo, $fechaVencimientoVal, $cantidad, $cantidad]);
            $loteId = (int)$pdo->lastInsertId();
        }

        if ($hasOrigenMovCol) {
            $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, origen, usuario_id, fecha_hora) VALUES (?, ?, ?, ?, ?, 'inventario', ?, NOW())");
        } else {
            $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, usuario_id, fecha_hora) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        }
        $stmtMov->execute([
            $itemId,
            $loteId > 0 ? $loteId : null,
            $tipo,
            $cantidad,
            $observacion !== '' ? $observacion : null,
            $usuarioId > 0 ? $usuarioId : null,
        ]);
    } else {
        $stmtSuma = $pdo->prepare("SELECT IFNULL(SUM(cantidad_actual),0) FROM inventario_lotes WHERE item_id = ? AND cantidad_actual > 0");
        $stmtSuma->execute([$itemId]);
        $stockTotal = round((float)$stmtSuma->fetchColumn(), 2);

        if ($stockTotal < $cantidad) {
            $pdo->rollBack();
            $_SESSION['mensaje'] = 'Stock insuficiente. Disponible: ' . number_format($stockTotal, 2);
            header('Location: dashboard.php?vista=inventario');
            exit;
        }

        $stmtLotes = $pdo->prepare("SELECT id, cantidad_actual FROM inventario_lotes WHERE item_id = ? AND cantidad_actual > 0 ORDER BY (fecha_vencimiento IS NULL) ASC, fecha_vencimiento ASC, id ASC");
        $stmtLotes->execute([$itemId]);
        $lotes = $stmtLotes->fetchAll(\PDO::FETCH_ASSOC);

        $aplicarComoTransferenciaInterna = $esSalidaLaboratorio && $tablasInternoReady;
        $transferenciaId = null;
        if ($aplicarComoTransferenciaInterna) {
            $obsTransfer = $observacion !== ''
                ? ('Auto-conversión desde Inventario: ' . $observacion)
                : 'Auto-conversión desde Inventario: salida a laboratorio';
            $stmtTransfer = $pdo->prepare("INSERT INTO inventario_transferencias (origen, destino, usuario_id, observacion, fecha_hora) VALUES ('almacen_principal', 'laboratorio', ?, ?, NOW())");
            $stmtTransfer->execute([
                $usuarioId > 0 ? $usuarioId : null,
                $obsTransfer,
            ]);
            $transferenciaId = (int)$pdo->lastInsertId();

            $stmtTransferDet = $pdo->prepare("INSERT INTO inventario_transferencias_detalle (transferencia_id, item_id, cantidad, created_at) VALUES (?, ?, ?, NOW())");
            $stmtTransferDet->execute([$transferenciaId, $itemId, $cantidad]);
        }

        $restante = $cantidad;
        $stmtUpdLote = $pdo->prepare("UPDATE inventario_lotes SET cantidad_actual = cantidad_actual - ?, updated_at = NOW() WHERE id = ?");
        if ($hasOrigenMovCol) {
            $origenMov = ($aplicarComoTransferenciaInterna && $transferenciaId) ? 'transferencia_interna' : 'inventario';
            $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, origen, usuario_id, fecha_hora) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        } else {
            $stmtMov = $pdo->prepare("INSERT INTO inventario_movimientos (item_id, lote_id, tipo, cantidad, observacion, usuario_id, fecha_hora) VALUES (?, ?, ?, ?, ?, ?, NOW())");
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

            $obsMov = $observacion !== '' ? $observacion : null;
            if ($aplicarComoTransferenciaInterna && $transferenciaId) {
                $obsMov = 'Transferencia interna #' . $transferenciaId . ' a laboratorio';
                if ($observacion !== '') {
                    $obsMov .= ' | ' . $observacion;
                }
            }

            if ($hasOrigenMovCol) {
                $stmtMov->execute([
                    $itemId,
                    (int)$lote['id'],
                    $tipo,
                    $consumo,
                    $obsMov,
                    $origenMov,
                    $usuarioId > 0 ? $usuarioId : null,
                ]);
            } else {
                $stmtMov->execute([
                    $itemId,
                    (int)$lote['id'],
                    $tipo,
                    $consumo,
                    $obsMov,
                    $usuarioId > 0 ? $usuarioId : null,
                ]);
            }

            $restante = round($restante - $consumo, 2);
        }
    }

    $pdo->commit();
    if ($esSalidaLaboratorio && $tablasInternoReady) {
        $_SESSION['mensaje'] = 'Movimiento registrado y convertido automáticamente en transferencia interna a laboratorio.';
    } else {
        $_SESSION['mensaje'] = 'Movimiento registrado correctamente.';
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'No se pudo registrar el movimiento: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario');
exit;
