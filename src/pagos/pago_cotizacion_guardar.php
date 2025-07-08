<?php
require_once __DIR__ . '/../conexion/conexion.php';

$idCotizacion = $_POST['id'] ?? null;
$nuevo_abono = floatval($_POST['monto_abonado'] ?? 0);
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
$totalPagado = floatval($stmtPagos->fetchColumn());
$saldo = floatval($cotizacion['total']) - $totalPagado;

// Validar y registrar pago
if ($nuevo_abono <= 0 || $nuevo_abono > $saldo) {
    header("Location: dashboard.php?vista=pago_cotizacion&id=$idCotizacion&msg=error");
    exit;
} else {
    $stmtPago = $pdo->prepare("INSERT INTO pagos (id_cotizacion, monto, metodo_pago, fecha) VALUES (?, ?, ?, ?)");
    $stmtPago->execute([$idCotizacion, $nuevo_abono, $metodo, $fecha_pago]);
    header("Location: dashboard.php?vista=cotizaciones&pagook=1");
    exit;
}
?>
