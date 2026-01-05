<?php
require_once __DIR__ . '/../conexion/conexion.php';

$idCotizacion = $_POST['id'] ?? null;
$nuevo_abono = round(floatval($_POST['monto_abonado'] ?? 0), 2);
$metodo = $_POST['metodo'] ?? '';
$fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');

// Consulta la cotización
$stmt = $pdo->prepare("SELECT total FROM cotizaciones WHERE id = ?");
$stmt->execute([$idCotizacion]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    header("Location: dashboard.php?vista=cotizaciones&error=1");
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
    } catch (Throwable $e) {
        $dir = __DIR__ . '/../tmp/facturacion/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        file_put_contents($dir . '/hook_errors.log', json_encode(['time' => date('c'), 'cotizacion_id' => (int)$idCotizacion, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
    header("Location: dashboard.php?vista=cotizaciones&pagook=1");
    exit;
}
?>
