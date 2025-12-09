<?php
// Helpers para mostrar badges de estado de pago y examen
function badgePago($total, $pagado) {
    $saldo = $total - $pagado;
    if ($saldo <= 0) {
        $badgeClassPago = 'bg-success';
        $iconPago = 'bi-check-circle-fill';
        $textoPago = 'Pagado';
    } elseif ($pagado > 0) {
        $badgeClassPago = 'bg-warning text-dark';
        $iconPago = 'bi-hourglass-split';
        $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
    } else {
        $badgeClassPago = 'bg-danger';
        $iconPago = 'bi-x-circle-fill';
        $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
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
