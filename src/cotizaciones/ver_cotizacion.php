<?php
file_put_contents('debug_detalle.txt', print_r($_GET, true)); // Esto crea un archivo con los datos recibidos

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
file_put_contents('debug_detalle.txt', print_r($_GET, true)); // Esto crea un archivo con los datos recibidos

require_once __DIR__ . '/../conexion/conexion.php';

$id_cotizacion = $_GET['id'] ?? null;
$id_cliente = $_SESSION['id_cliente'] ?? null;

if (!$id_cotizacion || !$id_cliente) {
    echo "<div class='alert alert-danger'>Cotización o cliente no válido.</div>";
    exit;
}

// Obtener cabecera
$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ? AND id_cliente = ?");
$stmt->execute([$id_cotizacion, $id_cliente]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    echo "<div class='alert alert-danger'>Cotización no encontrada.</div>";
    exit;
}

// Obtener detalles
$stmt = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = ?");
$stmt->execute([$id_cotizacion]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<div class="container mt-4">
    <h2>Detalle de Cotización</h2>
    <div class="mb-3">
        <strong>Código:</strong> <?= htmlspecialchars($cotizacion['codigo']) ?><br>
        <strong>Fecha:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($cotizacion['fecha']))) ?><br>
        <strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($cotizacion['observaciones'] ?? '')) ?><br>
        <strong>Estado de pago:</strong>
        <?php if ($cotizacion['estado_pago'] === 'pagado'): ?>
            <span class="badge bg-success">Pagado</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">Pendiente</span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Examen</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $det): ?>
                <tr>
                    <td><?= htmlspecialchars($det['nombre_examen']) ?></td>
                    <td>S/ <?= number_format($det['precio_unitario'], 2) ?></td>
                    <td><?= htmlspecialchars($det['cantidad']) ?></td>
                    <td>S/ <?= number_format($det['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                    <td><strong>S/ <?= number_format($cotizacion['total'], 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="mb-3">
        <a href="cotizaciones.php" class="btn btn-secondary">Volver al Historial</a>
        <?php if ($cotizacion['estado_pago'] === 'pagado'): ?>
            <a href="descargar_pdf.php?id=<?= $cotizacion['id'] ?>" class="btn btn-success">Descargar PDF</a>
        <?php else: ?>
            <button class="btn btn-secondary" disabled title="Debe estar pagada">Descargar PDF</button>
        <?php endif; ?>
    </div>
</div>
