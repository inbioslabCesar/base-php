<?php
require_once __DIR__ . '/../conexion/conexion.php';

$idCotizacion = $_GET['id'] ?? null;
$cotizacion = null;
$msg = $_GET['msg'] ?? '';

if ($idCotizacion) {
    $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $stmt->execute([$idCotizacion]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
}

$totalPagado = 0;
$saldo = 0;
if ($cotizacion) {
    $stmtPagos = $pdo->prepare("SELECT SUM(monto) AS total_pagado FROM pagos WHERE id_cotizacion = ?");
    $stmtPagos->execute([$idCotizacion]);
    $totalPagado = floatval($stmtPagos->fetchColumn());
    $saldo = floatval($cotizacion['total']) - $totalPagado;
}
?>
<div class="container mt-4">
    <h4>Registrar Pago para Cotización #<?= htmlspecialchars($idCotizacion) ?></h4>
    <?php if ($msg == "error"): ?>
        <div class="alert alert-danger">El monto debe ser positivo y no mayor al saldo pendiente.</div>
    <?php endif; ?>
    <?php if ($cotizacion): ?>
        <form method="post" action="dashboard.php?action=pago_cotizacion_guardar" class="card p-4 shadow-sm mb-4">
            <input type="hidden" name="id" value="<?= htmlspecialchars($idCotizacion) ?>">
            <div class="mb-3">
                <label class="form-label">Monto total de la cotización</label>
                <input type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($cotizacion['total']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Monto abonado acumulado</label>
                <input type="number" step="0.01" class="form-control" value="<?= number_format($totalPagado, 2) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label text-danger">Saldo pendiente</label>
                <input type="number" step="0.01" class="form-control" value="<?= number_format($saldo, 2) ?>" disabled>
            </div>
            <?php if ($saldo > 0): ?>
                <div class="mb-3">
                    <label class="form-label">Nuevo abono (a cuenta)</label>
                    <input type="number" step="0.01" name="monto_abonado" class="form-control" min="0.01" max="<?= $saldo ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Método de pago</label>
                    <select name="metodo" class="form-control" required>
                        <option value="">Selecciona...</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="yape">Yape</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha de pago</label>
                    <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="submit" class="btn btn-success">Registrar Pago</button>
                <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary">Volver</a>
            <?php else: ?>
                <div class="alert alert-success">Esta cotización ya está completamente pagada.</div>
                <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary">Volver</a>
            <?php endif; ?>
        </form>
        <div class="alert alert-info">
            <strong>Nota:</strong> El cliente solo podrá descargar sus resultados cuando el estado de pago sea "pagado".
        </div>
        <?php
        // Historial de pagos
        $stmtHistorial = $pdo->prepare("SELECT monto, metodo_pago, fecha FROM pagos WHERE id_cotizacion = ? ORDER BY fecha DESC");
        $stmtHistorial->execute([$idCotizacion]);
        $historialPagos = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
        if ($historialPagos):
        ?>
        <div class="mt-4">
            <h5>Historial de Pagos</h5>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historialPagos as $pago): ?>
                        <tr>
                            <td>S/ <?= number_format($pago['monto'], 2) ?></td>
                            <td><?= ucfirst($pago['metodo_pago']) ?></td>
                            <td><?= date('d/m/Y', strtotime($pago['fecha'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-danger">Cotización no encontrada.</div>
    <?php endif; ?>
</div>
