<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE id = ?");
$stmt->execute([$id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="container mt-5">
    <?php if ($promo): ?>
        <div class="card shadow-lg mx-auto" style="max-width: 600px; border-radius: 18px;">
            <?php if ($promo['imagen']): ?>
                <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="card-img-top img-fluid rounded-top" alt="Promo">
            <?php endif; ?>
            <div class="card-body" style="background: #fff; border-radius: 0 0 18px 18px;">
                <h2 class="card-title mb-3" style="color: #1a1a1a; font-weight: bold; letter-spacing: 1px;">
                    <?= htmlspecialchars($promo['titulo']) ?>
                </h2>
                <p class="card-text" style="color: #222; font-size: 1.13em;">
                    <?= nl2br(htmlspecialchars($promo['descripcion'])) ?>
                </p>
                <?php if ($promo['precio_promocional'] > 0): ?>
                    <span class="badge bg-warning text-dark fs-5 mb-2">Precio: S/ <?= number_format($promo['precio_promocional'], 2) ?></span>
                <?php endif; ?>
                <?php if ($promo['descuento'] > 0): ?>
                    <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                <?php endif; ?>
                <div class="mb-2 mt-2">
                    <span class="badge bg-info text-dark">
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
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> Volver al inicio
                </a>

            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger mt-5">Promoción no encontrada.</div>
        <a href="index.php" class="btn btn-primary mt-2" style="color: #fff; font-weight: 600;">
            <i class="bi bi-arrow-left"></i> Volver al inicio
        </a>
    <?php endif; ?>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">