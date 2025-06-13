<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id_cot = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM cotizaciones_detalle WHERE id_cotizacion = ?");
$stmt->execute([$id_cot]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$detalles) {
    echo '<div class="alert alert-info">No hay detalles para esta cotización.</div>';
    exit;
}
?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Examen</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($detalles as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['nombre_examen']); ?></td>
            <td>S/. <?php echo number_format($item['precio_unitario'], 2); ?></td>
            <td><?php echo $item['cantidad']; ?></td>
            <td>S/. <?php echo number_format($item['subtotal'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
