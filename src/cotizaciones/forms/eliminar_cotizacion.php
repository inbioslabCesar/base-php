<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';

// Validar que el usuario sea admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Redirige o muestra mensaje de acceso denegado
    header("Location: dashboard.php?vista=cotizaciones&msg=sin_permiso");
    exit;
}

$id = $_POST['id'] ?? ($_GET['id'] ?? null);
if ($id) {
    $idCotizacion = (int)$id;
    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
    $motivoAnulacion = trim((string)($_POST['motivo'] ?? ($_GET['motivo'] ?? '')));

    if ($motivoAnulacion === '') {
        header("Location: dashboard.php?vista=cotizaciones&msg=motivo_requerido");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtCot = $pdo->prepare("SELECT id, estado_pago FROM cotizaciones WHERE id = ? FOR UPDATE");
        $stmtCot->execute([$idCotizacion]);
        $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

        if (!$cotizacion) {
            $pdo->rollBack();
            header("Location: dashboard.php?vista=cotizaciones&msg=error");
            exit;
        }

        if (isset($cotizacion['estado_pago']) && strtolower((string)$cotizacion['estado_pago']) === 'anulada') {
            $pdo->rollBack();
            header("Location: dashboard.php?vista=cotizaciones&msg=ya_anulada");
            exit;
        }

        $stmtPagado = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = ?");
        $stmtPagado->execute([$idCotizacion]);
        $totalPagado = round((float)$stmtPagado->fetchColumn(), 2);

        if ($totalPagado > 0) {
            $stmtReversoPago = $pdo->prepare("INSERT INTO pagos (id_cotizacion, monto, metodo_pago, fecha) VALUES (?, ?, 'anulacion', NOW())");
            $stmtReversoPago->execute([$idCotizacion, -1 * $totalPagado]);
            $idPagoReverso = (int)$pdo->lastInsertId();

            $stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
            $stmtTbl->execute();
            $tieneTablaCajas = ((int)$stmtTbl->fetchColumn() > 0);

            $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
            $stmtMovTable->execute();
            $tieneMovimientosCaja = ((int)$stmtMovTable->fetchColumn() > 0);

            if ($tieneTablaCajas && $tieneMovimientosCaja) {
                $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
                $stmtCaja->execute();
                $cajaAbiertaId = $stmtCaja->fetchColumn();

                if ($cajaAbiertaId) {
                    $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, referencia_tipo, referencia_id, descripcion, usuario_id, fecha_hora) VALUES (?, 'egreso', 'anulacion_cotizacion', 'anulacion', ?, 0, 'anulacion_cotizacion', ?, ?, ?, NOW())");
                    $stmtMov->execute([
                        (int)$cajaAbiertaId,
                        $totalPagado,
                        $idPagoReverso,
                        'Reverso por anulación de cotización #' . $idCotizacion,
                        $usuarioId > 0 ? $usuarioId : null,
                    ]);
                }
            }
        }

        $stmtCol = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'estado_pago' LIMIT 1");
        $stmtCol->execute();
        $columnType = (string)($stmtCol->fetchColumn() ?? '');
        if ($columnType !== '') {
            preg_match_all("/'([^']*)'/", $columnType, $matches);
            $enumValues = $matches[1] ?? [];
            if (!in_array('anulada', $enumValues, true) && !empty($enumValues)) {
                $enumValues[] = 'anulada';
                $enumValues = array_values(array_unique($enumValues));
                $enumSql = "'" . implode("','", array_map(function ($value) {
                    return str_replace("'", "\\'", (string)$value);
                }, $enumValues)) . "'";
                $sqlAlter = "ALTER TABLE cotizaciones MODIFY COLUMN estado_pago ENUM(" . $enumSql . ") DEFAULT 'pendiente'";
                $pdo->exec($sqlAlter);
            }
        }

        $stmtColsAudit = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME IN ('anulada_at','anulada_por','anulado_motivo')");
        $stmtColsAudit->execute();
        $auditCols = $stmtColsAudit->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('anulada_at', $auditCols, true)) {
            $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN anulada_at DATETIME NULL");
        }
        if (!in_array('anulada_por', $auditCols, true)) {
            $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN anulada_por INT NULL");
        }
        if (!in_array('anulado_motivo', $auditCols, true)) {
            $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN anulado_motivo VARCHAR(255) NULL");
        }

        $stmtAnular = $pdo->prepare("UPDATE cotizaciones SET estado_pago = 'anulada', emitir_comprobante = 0, anulada_at = NOW(), anulada_por = ?, anulado_motivo = ? WHERE id = ?");
        $stmtAnular->execute([
            $usuarioId > 0 ? $usuarioId : null,
            mb_substr($motivoAnulacion, 0, 255, 'UTF-8'),
            $idCotizacion,
        ]);

        $stmtRes = $pdo->prepare("UPDATE resultados_examenes SET estado = 'anulado' WHERE id_cotizacion = ? AND (estado IS NULL OR estado <> 'anulado')");
        $stmtRes->execute([$idCotizacion]);

        $stmtInvTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventario_consumos_examen'");
        $stmtInvTbl->execute();
        $tieneConsumosInv = ((int)$stmtInvTbl->fetchColumn() > 0);

        if ($tieneConsumosInv) {
            $stmtRevertirConsumo = $pdo->prepare("UPDATE inventario_consumos_examen SET estado = 'revertido', observacion = CONCAT(IFNULL(observacion,''), CASE WHEN IFNULL(observacion,'') = '' THEN '' ELSE ' | ' END, 'Revertido por anulación de cotización') WHERE id_cotizacion = ? AND estado = 'aplicado'");
            $stmtRevertirConsumo->execute([$idCotizacion]);
        }

        $pdo->commit();
        header("Location: dashboard.php?vista=cotizaciones&msg=anulado");
        exit;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $dir = __DIR__ . '/../../../tmp/facturacion/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        file_put_contents(
            $dir . '/hook_errors.log',
            json_encode([
                'time' => date('c'),
                'cotizacion_id' => $idCotizacion,
                'error' => 'Anulación cotización: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );

        header("Location: dashboard.php?vista=cotizaciones&msg=error");
        exit;
    }
} else {
    header("Location: dashboard.php?vista=cotizaciones&msg=error");
    exit;
}
?>
