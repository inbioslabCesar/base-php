<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/config.php';

// Consulta de promociones SOLO para empresas
$stmt = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (tipo_publico = 'empresas' OR tipo_publico = 'todos') AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin OR vigente = 1) ORDER BY fecha_inicio DESC
");
$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container my-5">
    <!-- Carrusel de promociones para empresas -->
    <?php if ($promociones): ?>
        <div id="promoCarousel" class="carousel slide mb-4 shadow-sm rounded" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($promociones as $i => $promo): ?>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($promociones as $i => $promo): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="row justify-content-center align-items-center bg-light rounded p-3">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <?php if (!empty($promo['imagen']) && file_exists(__DIR__ . "/../../promociones/assets/" . $promo['imagen'])): ?>
                                    <img src="<?= BASE_URL . 'promociones/assets/' . htmlspecialchars($promo['imagen']) ?>" class="d-block w-100 rounded" alt="Promo">
                                <?php else: ?>
                                    <div class="bg-secondary text-white text-center py-5 rounded">Sin imagen</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7 d-flex flex-column justify-content-center">
                                <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h4 class="card-title text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                        <?php
                                        $descripcion_corta = mb_strimwidth($promo['descripcion'], 0, 200, '...');
                                        ?>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($descripcion_corta)) ?></p>

                                        <?php if ($promo['precio_promocional'] > 0): ?>
                                            <span class="badge bg-warning text-dark fs-5 mb-2">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                        <?php endif; ?>
                                        <?php if ($promo['descuento'] > 0): ?>
                                            <span class="badge bg-success ms-2">Descuento: <?= $promo['descuento'] ?>%</span>
                                        <?php endif; ?>
                                        <div class="text-muted mb-1">
                                            <i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?>
                                            <?php if ($promo['vigente']): ?>
                                                <span class="badge bg-primary ms-2">¡Promoción vigente!</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-2">No vigente</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="dashboard.php?vista=detalle_promocion&id=<?= $promo['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">
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

    <!-- Panel de acciones principales para empresas -->
    <div class="card shadow p-4">
        <div class="row g-3">
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=buscar_cliente" class="btn btn-primary btn-lg mb-2">
                    <i class="bi bi-search"></i> Buscar cliente
                </a>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=cotizaciones_empresas" class="btn btn-success btn-lg mb-2">
                    <i class="bi bi-file-earmark-text"></i> Ver cotizaciones
                </a>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <a href="dashboard.php?vista=clientes_empresa" class="btn btn-info btn-lg mb-2">
                    <i class="bi bi-people"></i> Ver clientes de empresas
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS para el carrusel -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">