<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
$msg = $_GET['msg'] ?? '';
$egreso = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM egresos WHERE id = ?");
    $stmt->execute([$id]);
    $egreso = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="container mt-4">
    <h3 class="mb-4">Editar Egreso</h3>
    <?php if ($msg === "error"): ?>
        <div class="alert alert-danger">Completa todos los campos correctamente.</div>
    <?php endif; ?>
    <?php if ($egreso): ?>
        <form method="post" action="dashboard.php?action=egresos_actualizar" class="card p-3 shadow-sm">
            <input type="hidden" name="id" value="<?= htmlspecialchars($egreso['id']) ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Monto (S/)</label>
                    <input type="number" step="0.01" min="0.01" name="monto" class="form-control" value="<?= htmlspecialchars($egreso['monto']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($egreso['descripcion']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d', strtotime($egreso['fecha'])) ?>" required>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
        <a href="dashboard.php?vista=egresos" class="btn btn-secondary mt-3">Volver</a>
    <?php else: ?>
        <div class="alert alert-danger">Egreso no encontrado.</div>
    <?php endif; ?>
</div>
