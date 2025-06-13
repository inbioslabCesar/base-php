<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT * FROM promociones ORDER BY fecha_inicio DESC");
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Promociones</h2>
    <a href="dashboard.php?vista=form_promocion" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Nueva Promoción
    </a>
    <?php if (!empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['mensaje']) ?></div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Precio Promo</th>
                    <th>Fechas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promos as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['imagen']): ?>
                                <img src="promociones/assets/<?= htmlspecialchars($p['imagen']) ?>" width="80" class="img-thumbnail">
                            <?php else: ?>
                                <span class="text-muted">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['titulo']) ?></td>
                        <td><?= htmlspecialchars($p['descripcion']) ?></td>
                        <td>S/ <?= htmlspecialchars(number_format($p['precio_promocional'],2)) ?></td>
                        <td><?= htmlspecialchars($p['fecha_inicio']) ?> <br> <span class="text-muted">al</span> <br> <?= htmlspecialchars($p['fecha_fin']) ?></td>
                        <td>
                            <?php if ($p['activo']): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="dashboard.php?vista=form_promocion&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="dashboard.php?action=eliminar_promocion&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar promoción?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$promos): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay promociones registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
