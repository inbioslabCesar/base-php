<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../conexion/conexion.php';

$cliente_id = $_GET['cliente_id'] ?? null;

if ($cliente_id) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->execute([':id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cliente):
?>
<div class="container mt-4">
    <h4>Cotización para <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></h4>
    <form method="POST" action="dashboard.php?action=guardar_cotizacion">
        <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
        <div class="mb-3">
            <label for="detalle" class="form-label">Detalle de la cotización</label>
            <textarea name="detalle" id="detalle" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label for="monto" class="form-label">Monto</label>
            <input type="number" name="monto" id="monto" class="form-control" required step="0.01" min="0">
        </div>
        <button type="submit" class="btn btn-success">Guardar cotización</button>
        <a href="dashboard.php?vista=buscar_cliente" class="btn btn-secondary">Volver</a>
    </form>
</div>
<?php
    else:
        echo "<div class='alert alert-danger'>Cliente no encontrado.</div>";
    endif;
} else {
    echo "<div class='alert alert-danger'>No se especificó cliente.</div>";
}
?>
