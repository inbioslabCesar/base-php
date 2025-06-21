<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../conexion/conexion.php';

// Datos del cliente
$nombre_cliente = $_SESSION['usuario'] ?? 'Cliente';

// 1. Promociones activas para el carrusel
$stmt = $pdo->query("SELECT * FROM promociones WHERE activo = 1 AND (CURDATE() BETWEEN fecha_inicio AND fecha_fin) ORDER BY fecha_inicio DESC");
$promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Cotizaciones pendientes y canceladas
$id_cliente = $_SESSION['cliente_id'] ?? 0;
$stmt = $pdo->prepare("SELECT estado_pago, COUNT(*) as total, IFNULL(SUM(total),0) as suma FROM cotizaciones WHERE id_cliente = ? GROUP BY estado_pago");
$stmt->execute([$id_cliente]);
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pendientes = 0;
$total_deuda = 0;
$canceladas = 0;
//echo '<pre>'; print_r($estados); echo '</pre>';

foreach ($estados as $e) {
    if (strtolower($e['estado_pago']) === 'pendiente') {
        $pendientes = $e['total'];
        $total_deuda = $e['suma'];
    }
}
?>

<div class="container mt-4">

    <!-- Carrusel de promociones -->
    <?php if ($promociones): ?>
        <div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($promociones as $i => $promo): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <?php if ($promo['imagen']): ?>
                                    <img src="../../promociones/assets/<?= htmlspecialchars($promo['imagen']) ?>" class="d-block w-100 rounded" alt="Promo">
                                <?php else: ?>
                                    <div class="bg-secondary text-white text-center py-5 rounded">Sin imagen</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7 d-flex flex-column justify-content-center">
                                <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                                    <div class="card-body">
                                        <h4 class="card-title text-primary"><?= htmlspecialchars($promo['titulo']) ?></h4>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($promo['descripcion'])) ?></p>
                                        <?php if ($promo['precio_promocional'] > 0): ?>
                                            <span class="badge bg-warning text-dark fs-5 mb-2">¡Solo S/ <?= number_format($promo['precio_promocional'], 2) ?>!</span>
                                        <?php endif; ?>
                                        <div class="text-muted mb-1"><i class="bi bi-calendar-event"></i> Vigente: <?= htmlspecialchars($promo['fecha_inicio']) ?> al <?= htmlspecialchars($promo['fecha_fin']) ?></div>
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

    <!-- Cards de resumen -->
    <div class="row mb-4">
        <div class="col-md-6 mb-2">
            <div class="card border-warning bg-warning bg-opacity-10 h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-hourglass-split display-5 text-warning me-3"></i>
                    <div>
                        <h6 class="card-title mb-1">Cotizaciones Pendientes</h6>
                        <p class="mb-0"><?= $pendientes ?> en proceso</p>
                        <p class="mb-0"><strong>Total a pagar:</strong> S/ <?= number_format($total_deuda, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($canceladas > 0): ?>
            <div class="col-md-6 mb-2">
                <div class="card border-success bg-success bg-opacity-10 h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-check-circle display-5 text-success me-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Cotizaciones Canceladas</h6>
                            <p class="mb-0"><?= $canceladas ?> pagadas</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bienvenida y botón rápido -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div>
                <h3 class="mb-2">¡Bienvenido, <?= htmlspecialchars($nombre_cliente) ?>!</h3>
                <p class="mb-0">Desde aquí puedes gestionar tus cotizaciones y acceder a promociones exclusivas.</p>
            </div>
            <a href="dashboard.php?vista=cotizaciones_clientes" class="btn btn-primary btn-lg mt-3 mt-md-0">
                <i class="bi bi-file-earmark-text"></i> Mis Cotizaciones
            </a>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">