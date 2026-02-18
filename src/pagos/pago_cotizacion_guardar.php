<?php
require_once __DIR__ . '/../conexion/conexion.php';

$idCotizacion = $_POST['id'] ?? null;
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$cajaAbiertaId = null;

// Política flexible: sin caja abierta NO se pueden registrar pagos
try {
    $stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
    $stmtTbl->execute();
    $tieneTablaCajas = ((int)$stmtTbl->fetchColumn() > 0);

    if (!$tieneTablaCajas) {
        header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=no_caja_tablas");
        exit;
    }

    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtCaja->execute();
    $cajaAbiertaId = $stmtCaja->fetchColumn();

    if (!$cajaAbiertaId) {
        header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=no_caja");
        exit;
    }
} catch (\Throwable $e) {
    header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=no_caja");
    exit;
}

$nuevo_abono = round(floatval($_POST['monto_abonado'] ?? 0), 2);
$metodo = $_POST['metodo'] ?? '';
$fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');

// Consulta la cotización
$stmt = $pdo->prepare("SELECT total, estado_pago FROM cotizaciones WHERE id = ?");
$stmt->execute([$idCotizacion]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    header("Location: dashboard.php?vista=cotizaciones&error=1");
    exit;
}

if (isset($cotizacion['estado_pago']) && strtolower((string)$cotizacion['estado_pago']) === 'anulada') {
    header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=anulada");
    exit;
}

// Calcular total abonado y saldo pendiente
$stmtPagos = $pdo->prepare("SELECT SUM(monto) AS total_pagado FROM pagos WHERE id_cotizacion = ?");
$stmtPagos->execute([$idCotizacion]);
$totalPagado = round(floatval($stmtPagos->fetchColumn()), 2);
$saldo = round(floatval($cotizacion['total']) - $totalPagado, 2);

// Validar y registrar pago
// Regla: "descarga_anticipada" permite abono 0, pero nunca mayor al saldo.
// Para el resto de métodos, el abono debe ser > 0 y <= saldo.
if (
    ($metodo !== 'descarga_anticipada' && ($nuevo_abono <= 0 || $nuevo_abono > $saldo)) ||
    ($metodo === 'descarga_anticipada' && ($nuevo_abono < 0 || $nuevo_abono > $saldo))
) {
    header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=error&intento=" . urlencode($nuevo_abono) . "&saldo=" . urlencode($saldo));
    exit;
} else {
    $stmtPago = $pdo->prepare("INSERT INTO pagos (id_cotizacion, monto, metodo_pago, fecha) VALUES (?, ?, ?, ?)");
    $stmtPago->execute([$idCotizacion, $nuevo_abono, $metodo, $fecha_pago]);
    $idPago = (int)$pdo->lastInsertId();

    try {
        $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
        $stmtMovTable->execute();
        $existsMov = (int)$stmtMovTable->fetchColumn() > 0;

        if ($existsMov && $nuevo_abono > 0 && $cajaAbiertaId) {
            $afectaEfectivo = (strtolower(trim((string)$metodo)) === 'efectivo') ? 1 : 0;
            $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, referencia_tipo, referencia_id, descripcion, usuario_id, fecha_hora) VALUES (?, 'ingreso', 'pago', ?, ?, ?, 'pago_individual', ?, ?, ?, NOW())");
            $stmtMov->execute([
                (int)$cajaAbiertaId,
                $metodo,
                $nuevo_abono,
                $afectaEfectivo,
                $idPago,
                'Pago cotización #' . (int)$idCotizacion,
                $usuarioId > 0 ? $usuarioId : null,
            ]);
        }
    } catch (\Throwable $e) {
        $dir = __DIR__ . '/../tmp/facturacion/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        file_put_contents($dir . '/hook_errors.log', json_encode(['time' => date('c'), 'cotizacion_id' => (int)$idCotizacion, 'error' => 'Movimiento caja: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    // Hook de facturación: emitir comprobante si el saldo queda en cero
    try {
        $saldoDespues = round($saldo - $nuevo_abono, 2);
        if ($saldoDespues <= 0) {
            // Respeta el flag por cotización: permitir solo ticket sin emitir CPE
            $stmtFlag = $pdo->prepare("SELECT emitir_comprobante FROM cotizaciones WHERE id = ?");
            $stmtFlag->execute([$idCotizacion]);
            $emitir = (int)($stmtFlag->fetchColumn() ?? 1);

            require_once __DIR__ . '/../facturacion/FacturacionAuthService.php';
            require_once __DIR__ . '/../facturacion/FacturacionService.php';
            $auth = new FacturacionAuthService();
            $svc = new FacturacionService($pdo, $auth);
            if ($emitir === 1) {
                $svc->emitirComprobante((int)$idCotizacion, []);
            }
            // Actualizar estado de pago a 'pagado'
            $stmtUpd = $pdo->prepare("UPDATE cotizaciones SET estado_pago = 'pagado' WHERE id = ?");
            $stmtUpd->execute([$idCotizacion]);
        } else {
            // Si existe algún pago, dejar en 'abonado'; de lo contrario, 'pendiente'
            $stmtPagosCheck = $pdo->prepare("SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = ?");
            $stmtPagosCheck->execute([$idCotizacion]);
            $pagadoAcum = round(floatval($stmtPagosCheck->fetchColumn()), 2);
            $nuevoEstado = ($pagadoAcum > 0) ? 'abonado' : 'pendiente';
            $stmtUpd = $pdo->prepare("UPDATE cotizaciones SET estado_pago = ? WHERE id = ?");
            $stmtUpd->execute([$nuevoEstado, $idCotizacion]);
        }
    } catch (\Throwable $e) {
        $dir = __DIR__ . '/../tmp/facturacion/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        file_put_contents($dir . '/hook_errors.log', json_encode(['time' => date('c'), 'cotizacion_id' => (int)$idCotizacion, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
    header("Location: dashboard.php?vista=cotizaciones&pagook=1");
    exit;
}
?>
