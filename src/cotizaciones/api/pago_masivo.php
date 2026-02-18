<?php
// src/cotizaciones/pago_masivo.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../conexion/conexion.php'; // Incluye $pdo
require_once __DIR__ . '/../funciones/cotizaciones_utils.php'; // Función utilitaria
require_once __DIR__ . '/../../facturacion/FacturacionAuthService.php';
require_once __DIR__ . '/../../facturacion/FacturacionService.php';

// Solo aceptar POST y JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['cotizaciones']) || !is_array($input['cotizaciones'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$cotizaciones = $input['cotizaciones'];
if (empty($cotizaciones)) {
    echo json_encode(['success' => false, 'message' => 'No hay cotizaciones seleccionadas']);
    exit;
}

// Política flexible: sin caja abierta NO se pueden registrar pagos
try {
    $stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cajas'");
    $stmtTbl->execute();
    $tieneTablaCajas = ((int)$stmtTbl->fetchColumn() > 0);

    if (!$tieneTablaCajas) {
        echo json_encode([
            'success' => false,
            'code' => 'NO_CAJA_TABLAS',
            'message' => 'Falta crear las tablas de caja. Ejecuta sql/agregar_tablas_caja.sql (y si ya existían, sql/actualizar_caja_robusta.sql).',
            'redirect_url' => 'dashboard.php?vista=contabilidad'
        ]);
        exit;
    }

    $stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_hora_apertura DESC LIMIT 1");
    $stmtCaja->execute();
    $cajaAbiertaId = $stmtCaja->fetchColumn();

    if (!$cajaAbiertaId) {
        echo json_encode([
            'success' => false,
            'code' => 'NO_CAJA_ABIERTA',
            'message' => 'No hay caja abierta. Abre caja en Contabilidad para registrar pagos.',
            'redirect_url' => 'dashboard.php?vista=contabilidad'
        ]);
        exit;
    }
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'code' => 'NO_CAJA_ABIERTA',
        'message' => 'No hay caja abierta. Abre caja en Contabilidad para registrar pagos.',
        'redirect_url' => 'dashboard.php?vista=contabilidad'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    $pagosRegistrados = 0;
    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

    $stmtMovTable = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMovTable->execute();
    $existsMov = (int)$stmtMovTable->fetchColumn() > 0;

    // Reusar servicios (evita reinstanciar por cada cotización)
    $auth = new FacturacionAuthService();
    $svc = new FacturacionService($pdo, $auth);
    foreach ($cotizaciones as $idCotizacion) {
        $stmtEstado = $pdo->prepare("SELECT estado_pago FROM cotizaciones WHERE id = ? LIMIT 1");
        $stmtEstado->execute([$idCotizacion]);
        $estadoPago = strtolower((string)($stmtEstado->fetchColumn() ?? ''));
        if ($estadoPago === 'anulada') {
            continue;
        }

        $saldo = obtenerSaldoCotizacion($pdo, $idCotizacion);
        if ($saldo <= 0) continue; // Ya pagado
        // Registrar pago por el saldo pendiente
        $stmtPago = $pdo->prepare('INSERT INTO pagos (id_cotizacion, monto, fecha, metodo_pago) VALUES (?, ?, NOW(), ?)');
        $stmtPago->execute([$idCotizacion, $saldo, 'masivo']);
        $idPago = (int)$pdo->lastInsertId();
        $pagosRegistrados++;

        if ($existsMov) {
            $stmtMov = $pdo->prepare("INSERT INTO caja_movimientos (caja_id, tipo, origen, metodo_pago, monto, afecta_efectivo, referencia_tipo, referencia_id, descripcion, usuario_id, fecha_hora) VALUES (?, 'ingreso', 'pago', 'masivo', ?, 0, 'pago_masivo', ?, ?, ?, NOW())");
            $stmtMov->execute([
                (int)$cajaAbiertaId,
                $saldo,
                $idPago,
                'Pago masivo cotización #' . (int)$idCotizacion,
                $usuarioId > 0 ? $usuarioId : null,
            ]);
        }

        // Actualizar estado de pago a 'pagado' para la cotización liquidada
        $stmtUpd = $pdo->prepare("UPDATE cotizaciones SET estado_pago = 'pagado' WHERE id = ?");
        $stmtUpd->execute([$idCotizacion]);
        try {
            // Respeta el flag por cotización: permitir solo ticket sin emitir CPE
            $stmtFlag = $pdo->prepare("SELECT emitir_comprobante FROM cotizaciones WHERE id = ?");
            $stmtFlag->execute([(int)$idCotizacion]);
            $emitir = (int)($stmtFlag->fetchColumn() ?? 1);
            if ($emitir === 1) {
                $svc->emitirComprobante((int)$idCotizacion, []);
            }
        } catch (\Throwable $e) {
            $dir = __DIR__ . '/../../tmp/facturacion/logs';
            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            file_put_contents($dir . '/hook_errors.log', json_encode(['time' => date('c'), 'cotizacion_id' => (int)$idCotizacion, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'pagos' => $pagosRegistrados]);
} catch (\Throwable $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
