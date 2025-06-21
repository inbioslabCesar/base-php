<?php
if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de cotización no proporcionado.</div>';
    exit;
}
require_once __DIR__ . '/../conexion/conexion.php';
$id = intval($_GET['id']);

// Traer datos generales de la cotización
$stmt = $pdo->prepare("SELECT c.*, cl.nombre AS nombre_cliente,cl.apellido AS cliente_apellido
                       FROM cotizaciones c 
                       LEFT JOIN clientes cl ON c.id_cliente = cl.id 
                       WHERE c.id = :id");
$stmt->execute([':id' => $id]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    echo '<div class="alert alert-warning">Cotización no encontrada.</div>';
    exit;
}

// Traer exámenes cotizados (detalle)
$stmt_detalle = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = :id_cotizacion");
$stmt_detalle->execute([':id_cotizacion' => $id]);
$examenes = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h5>Código: <?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></h5>
    <p><strong>Cliente:</strong> <?= htmlspecialchars(($cotizacion['nombre_cliente'] ?? '') . ' ' . ($cotizacion['cliente_apellido'] ?? '')) ?></p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></p>
    <p><strong>Total:</strong> S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></p>
    <p><strong>Estado:</strong> <?= htmlspecialchars($cotizacion['estado_pago'] ?? '') ?></p>
    <p><strong>Rol Creador:</strong> <?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></p>
    <p><strong>Observaciones:</strong> <?= htmlspecialchars($cotizacion['observaciones'] ?? '') ?></p>
    <?php if (!empty($cotizacion['pdf_url'])): ?>
        <a href="<?= htmlspecialchars($cotizacion['pdf_url']) ?>" target="_blank" class="btn btn-outline-danger btn-sm mt-2">
            <i class="bi bi-file-earmark-pdf"></i> Ver PDF
        </a>
    <?php endif; ?>

    <hr>
    <h6>Exámenes Cotizados</h6>
    <?php if ($examenes): ?>
        <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Nombre del Examen</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($examenes as $examen): ?>
                <tr>
                    <td><?= htmlspecialchars($examen['nombre_examen'] ?? '') ?></td>
                    <td>S/ <?= number_format($examen['precio_unitario'] ?? 0, 2) ?></td>
                    <td><?= htmlspecialchars($examen['cantidad'] ?? '') ?></td>
                    <td>S/ <?= number_format($examen['subtotal'] ?? 0, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a href="cotizaciones/descargar_cotizacion.php?id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-2" target="_blank">
    <i class="bi bi-download"></i> Descargar PDF
</a>

        </div>
    <?php else: ?>
        <p class="text-muted">No hay exámenes cotizados.</p>
    <?php endif; ?>
</div>
