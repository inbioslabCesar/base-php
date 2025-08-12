<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE id = ?");
$stmt->execute([$id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

// Determina el panel de regreso según el rol
$panel = 'cliente';
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'convenio') {
        $panel = 'convenio';
    } elseif ($_SESSION['rol'] === 'empresa') {
        $panel = 'empresa';
    }
}
?>

<div class="container mt-4">
    <?php if ($promo): ?>
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <?php if ($promo['imagen']): ?>
                <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="card-img-top img-fluid rounded" alt="Promo">
            <?php endif; ?>
            <div class="card-body">
                <h3 class="card-title"><?= htmlspecialchars($promo['titulo']) ?></h3>
                <p class="card-text"><?= nl2br(htmlspecialchars($promo['descripcion'])) ?></p>
                <?php if ($promo['precio_promocional'] > 0): ?>
                    <span class="badge bg-warning text-dark fs-5 mb-2">Precio: S/ <?= number_format($promo['precio_promocional'], 2) ?></span>
                <?php endif; ?>
                <?php if ($promo['descuento'] > 0): ?>
                    <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                <?php endif; ?>
                <div class="mb-2">
                    <span class="badge bg-info">
                        Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                    </span>
                    <?php if ($promo['activo']): ?>
                        <span class="badge bg-success ms-2">Activa</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Inactiva</span>
                    <?php endif; ?>
                    <?php if ($promo['vigente']): ?>
                        <span class="badge bg-primary ms-2">¡Promoción vigente!</span>
                    <?php endif; ?>
                </div>
                <a href="dashboard.php?vista=<?= $panel ?>" class="btn btn-outline-secondary mt-2">
                    <i class="bi bi-arrow-left"></i> Volver al panel <?= ucfirst($panel) ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Promoción no encontrada.</div>
        <a href="dashboard.php?vista=<?= $panel ?>" class="btn btn-outline-secondary mt-2">
            <i class="bi bi-arrow-left"></i> Volver al panel <?= ucfirst($panel) ?>
        </a>
    <?php endif; ?>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
