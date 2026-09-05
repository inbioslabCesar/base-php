<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';

// Consulta de promociones SOLO para convenios
$stmt = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (tipo_publico = 'convenios' OR tipo_publico = 'todos') AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin OR vigente = 1) ORDER BY fecha_inicio DESC
");
$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
.panel-shell {
    max-width: 1120px;
}

.panel-promo-carousel {
    background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid #dce8f8;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 12px 26px rgba(29, 63, 110, 0.10);
}

.panel-promo-slide {
    background: #ffffff;
    border: 1px solid #e5eef8;
    border-radius: 14px;
    padding: 14px;
}

.panel-promo-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #dfe8f6;
}

.panel-promo-content {
    background: #f3f8ff;
    border: 1px solid #dce9fc;
    border-radius: 12px;
    padding: 14px;
}

.panel-promo-content h4 {
    font-size: 1.35rem;
    margin-bottom: 0.45rem;
}

.panel-promo-content .desc {
    color: #28435d;
    line-height: 1.35;
    margin-bottom: 0.65rem;
}

.panel-promo-content .meta {
    color: #56718d;
    font-size: 0.92rem;
}

.panel-promo-carousel .carousel-control-prev,
.panel-promo-carousel .carousel-control-next {
    width: 42px;
    height: 42px;
    top: 50%;
    transform: translateY(-50%);
    border-radius: 50%;
    background: rgba(33, 84, 145, 0.12);
}

.panel-promo-carousel .carousel-control-prev { left: -10px; }
.panel-promo-carousel .carousel-control-next { right: -10px; }

.panel-actions-card {
    border: 1px solid #dce8f8;
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(26, 61, 105, 0.08);
}

.panel-actions-title {
    font-weight: 700;
    color: #1f3f60;
    margin-bottom: 0.85rem;
}

.panel-action-btn {
    min-height: 54px;
    border-radius: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

@media (max-width: 767.98px) {
    .panel-promo-image {
        height: 170px;
    }

    .panel-promo-carousel .carousel-control-prev,
    .panel-promo-carousel .carousel-control-next {
        width: 34px;
        height: 34px;
    }
}
</style>

<div class="container my-5 panel-shell">

    <!-- Carrusel de promociones para convenios -->
    <?php if ($promociones): ?>
        <div id="promoCarousel" class="carousel slide mb-4 panel-promo-carousel" data-bs-ride="carousel" data-bs-interval="5600">
            <div class="carousel-indicators">
                <?php foreach ($promociones as $i => $promo): ?>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($promociones as $i => $promo): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="panel-promo-slide">
                        <div class="row justify-content-center align-items-center g-3">
                            <div class="col-12 col-lg-5">
                                <?php if (!empty($promo['imagen']) && file_exists(__DIR__ . "/../../promociones/assets/" . $promo['imagen'])): ?>
                                    <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="panel-promo-image" alt="Promo">
                                <?php else: ?>
                                    <div class="panel-promo-image d-flex align-items-center justify-content-center bg-secondary text-white">Sin imagen</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-lg-7 d-flex flex-column justify-content-center">
                                <div class="panel-promo-content">
                                    <h4 class="text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                    <?php
                                    $descripcion_corta = mb_strimwidth($promo['descripcion'], 0, 180, '...');
                                    ?>
                                    <p class="desc"><?= nl2br(htmlspecialchars($descripcion_corta)) ?></p>
                                    <?php if ($promo['precio_promocional'] > 0): ?>
                                        <span class="badge bg-warning text-dark fs-6">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                    <?php endif; ?>
                                    <?php if ($promo['descuento'] > 0): ?>
                                        <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                                    <?php endif; ?>
                                    <div class="meta mt-2 mb-2">
                                        <i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                                        <?php if ($promo['vigente']): ?>
                                            <span class="badge bg-primary ms-2">Promoción vigente</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">No vigente</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="dashboard.php?vista=detalle_promocion&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Panel de acciones principales para convenios -->
    <div class="card panel-actions-card p-4">
        <h6 class="panel-actions-title">Accesos rápidos</h6>
        <div class="row g-3">
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=buscar_cliente" class="btn btn-primary panel-action-btn">
                    <i class="bi bi-search"></i> Buscar cliente
                </a>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=cotizaciones_convenios" class="btn btn-success panel-action-btn">
                    <i class="bi bi-file-earmark-text"></i> Ver cotizaciones
                </a>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=clientes_convenio" class="btn btn-info panel-action-btn">
                    <i class="bi bi-people"></i> Ver clientes de convenio
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$cotizadorContextType = 'convenio';
echo '<div class="container mb-5 panel-shell">';
require __DIR__ . '/../../gestion/components/cotizador_rapido_panel.php';
echo '</div>';
?>

<!-- Bootstrap JS para el carrusel -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">