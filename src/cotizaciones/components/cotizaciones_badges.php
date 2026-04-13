<?php
// Helpers para mostrar badges de estado de pago y examen
require_once __DIR__ . '/../../config/currency.php';

function badgePago($total, $pagado, ?array $currency = null) {
    $saldo = $total - $pagado;
    $currency = $currency ?: currency_default_config();
    if ($saldo <= 0) {
        $badgeClassPago = 'bg-success';
        $iconPago = 'bi-check-circle-fill';
        $textoPago = 'Pagado';
    } elseif ($pagado > 0) {
        $badgeClassPago = 'bg-warning text-dark';
        $iconPago = 'bi-hourglass-split';
        $textoPago = 'Parcial: ' . money_format_local((float)$saldo, $currency);
    } else {
        $badgeClassPago = 'bg-danger';
        $iconPago = 'bi-x-circle-fill';
        $textoPago = 'Pendiente: ' . money_format_local((float)$saldo, $currency);
    }
    return "<span class='badge $badgeClassPago'><i class='bi $iconPago'></i> $textoPago</span>";
}

function badgeExamen($examenes) {
    $pendientes = array_filter($examenes, function ($ex) {
        return $ex['estado'] === 'pendiente';
    });
    if ($pendientes) {
        return "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente</span>";
    } else {
        return "<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado</span>";
    }
}
