<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=referenciados_liquidacion&msg=metodo_invalido');
    exit;
}

$idDetalle = (int)($_POST['id_detalle'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if ($idDetalle <= 0) {
    header('Location: dashboard.php?vista=referenciados_liquidacion&msg=detalle_invalido');
    exit;
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

$requires = [
    hasColumn($pdo, 'cotizaciones_detalle', 'es_referenciado'),
    hasColumn($pdo, 'cotizaciones_detalle', 'estado_liquidacion'),
    hasColumn($pdo, 'cotizaciones_detalle', 'fecha_liquidacion'),
    hasColumn($pdo, 'cotizaciones_detalle', 'liquidado_por'),
    hasColumn($pdo, 'cotizaciones_detalle', 'egreso_laboratorio_id'),
    hasColumn($pdo, 'cotizaciones_detalle', 'egreso_logistica_id'),
    hasColumn($pdo, 'egresos', 'categoria'),
    hasColumn($pdo, 'egresos', 'subcategoria'),
    hasColumn($pdo, 'egresos', 'id_cotizacion'),
    hasColumn($pdo, 'egresos', 'id_cotizacion_detalle'),
    hasColumn($pdo, 'egresos', 'origen_auto'),
];

if (in_array(false, $requires, true)) {
    header('Location: dashboard.php?vista=referenciados_liquidacion&msg=ejecutar_migracion_tercerizados');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT cd.*, c.codigo AS codigo_cotizacion, c.fecha AS fecha_cotizacion FROM cotizaciones_detalle cd INNER JOIN cotizaciones c ON c.id = cd.id_cotizacion WHERE cd.id = ? FOR UPDATE");
    $stmt->execute([$idDetalle]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        $pdo->rollBack();
        header('Location: dashboard.php?vista=referenciados_liquidacion&msg=detalle_no_encontrado');
        exit;
    }

    if ((int)($detalle['es_referenciado'] ?? 0) !== 1) {
        $pdo->rollBack();
        header('Location: dashboard.php?vista=referenciados_liquidacion&msg=no_referenciado');
        exit;
    }

    if (($detalle['estado_liquidacion'] ?? '') === 'liquidado') {
        $pdo->rollBack();
        header('Location: dashboard.php?vista=referenciados_liquidacion&msg=ya_liquidado');
        exit;
    }

    $costoLab = max(0, (float)($detalle['costo_laboratorio_referenciado'] ?? 0));
    $costoLog = max(0, (float)($detalle['costo_logistica_extra'] ?? 0));
    $labNombre = trim((string)($detalle['laboratorio_referenciado_nombre'] ?? ''));
    $idCotizacion = (int)($detalle['id_cotizacion'] ?? 0);
    $codigo = (string)($detalle['codigo_cotizacion'] ?? ('COT-' . $idCotizacion));
    $nombreExamen = (string)($detalle['nombre_examen'] ?? 'Examen referenciado');

    if ($costoLab <= 0 && $costoLog <= 0) {
        $pdo->rollBack();
        header('Location: dashboard.php?vista=referenciados_liquidacion&msg=sin_costos_referenciados');
        exit;
    }

    $fechaPago = date('Y-m-d H:i:s');
    $egresoLabId = null;
    $egresoLogId = null;

    if ($costoLab > 0) {
        $desc = "Liquidación laboratorio referenciado {$codigo} - {$nombreExamen}";
        $stmtInsLab = $pdo->prepare("INSERT INTO egresos (monto, descripcion, categoria, subcategoria, id_cotizacion, id_cotizacion_detalle, origen_auto, fecha) VALUES (?, ?, 'referenciado_laboratorio', ?, ?, ?, 1, ?)");
        $stmtInsLab->execute([
            $costoLab,
            $desc,
            $labNombre !== '' ? $labNombre : null,
            $idCotizacion,
            $idDetalle,
            $fechaPago,
        ]);
        $egresoLabId = (int)$pdo->lastInsertId();
    }

    if ($costoLog > 0) {
        $desc = "Liquidación logística referenciado {$codigo} - {$nombreExamen}";
        $stmtInsLog = $pdo->prepare("INSERT INTO egresos (monto, descripcion, categoria, subcategoria, id_cotizacion, id_cotizacion_detalle, origen_auto, fecha) VALUES (?, ?, 'referenciado_logistica', ?, ?, ?, 1, ?)");
        $stmtInsLog->execute([
            $costoLog,
            $desc,
            $labNombre !== '' ? $labNombre : null,
            $idCotizacion,
            $idDetalle,
            $fechaPago,
        ]);
        $egresoLogId = (int)$pdo->lastInsertId();
    }

    $stmtMovTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMovTbl->execute();
    $hasMov = ((int)$stmtMovTbl->fetchColumn() > 0);

    if ($hasMov) {
        $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
        $stmtCaja->execute();
        $cajaAbiertaId = (int)$stmtCaja->fetchColumn();

        if ($cajaAbiertaId > 0) {
            if ($costoLab > 0 && $egresoLabId) {
                $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, referencia_tipo, referencia_id, descripcion, usuario_id, fecha_hora) VALUES (?, 'egreso', 'egreso_manual', 'efectivo', ?, 1, 'egreso', ?, ?, ?, NOW())");
                $stmtMov->execute([$cajaAbiertaId, $costoLab, $egresoLabId, "Liquidación laboratorio {$codigo}", $usuarioId > 0 ? $usuarioId : null]);
            }

            if ($costoLog > 0 && $egresoLogId) {
                $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, referencia_tipo, referencia_id, descripcion, usuario_id, fecha_hora) VALUES (?, 'egreso', 'egreso_manual', 'efectivo', ?, 1, 'egreso', ?, ?, ?, NOW())");
                $stmtMov->execute([$cajaAbiertaId, $costoLog, $egresoLogId, "Liquidación logística {$codigo}", $usuarioId > 0 ? $usuarioId : null]);
            }
        }
    }

    $stmtUpd = $pdo->prepare("UPDATE cotizaciones_detalle SET estado_liquidacion = 'liquidado', fecha_liquidacion = NOW(), liquidado_por = ?, egreso_laboratorio_id = ?, egreso_logistica_id = ? WHERE id = ?");
    $stmtUpd->execute([
        $usuarioId > 0 ? $usuarioId : null,
        $egresoLabId,
        $egresoLogId,
        $idDetalle,
    ]);

    $pdo->commit();
    header('Location: dashboard.php?vista=referenciados_liquidacion&ok=liquidado');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: dashboard.php?vista=referenciados_liquidacion&msg=error_liquidacion');
    exit;
}
