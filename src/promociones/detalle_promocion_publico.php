<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE id = ?");
$stmt->execute([$id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

$siteBasePath = rtrim((string)dirname(rtrim((string)BASE_URL, '/')), '/\\');
if ($siteBasePath === '.' || $siteBasePath === '') {
    $siteBasePath = '';
}

$promoImagePublic = '';
if (!empty($promo['imagen'])) {
    $raw = trim((string)$promo['imagen']);
    if (preg_match('~^(https?:)?//~i', $raw) || strpos($raw, 'data:') === 0) {
        $promoImagePublic = $raw;
    } elseif (strpos($raw, '/') === false && strpos($raw, '\\') === false) {
        $promoImagePublic = rtrim((string)BASE_URL, '/\\') . '/promociones/assets/' . rawurlencode($raw);
    } else {
        $normalized = str_replace('\\', '/', $raw);
        $normalized = preg_replace('#^\./+#', '', $normalized);
        $normalized = preg_replace('#^\.\./+#', '', $normalized);
        $normalized = ltrim($normalized, '/');
        $promoImagePublic = ($siteBasePath === '' ? '' : $siteBasePath) . '/' . $normalized;
    }
}

$fechaInicio = !empty($promo['fecha_inicio']) && strtotime((string)$promo['fecha_inicio'])
    ? date('d/m/Y', strtotime((string)$promo['fecha_inicio']))
    : '';
$fechaFin = !empty($promo['fecha_fin']) && strtotime((string)$promo['fecha_fin'])
    ? date('d/m/Y', strtotime((string)$promo['fecha_fin']))
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de promoción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <?php if ($promo): ?>
            <div class="card shadow-lg mx-auto" style="max-width: 700px; border-radius: 18px;">
                <?php if ($promoImagePublic !== ''): ?>
                    <img src="<?= htmlspecialchars($promoImagePublic, ENT_QUOTES, 'UTF-8') ?>" class="card-img-top img-fluid rounded-top" alt="Promo" onerror="this.style.display='none';">
                <?php endif; ?>
                <div class="card-body" style="background: #fff; border-radius: 0 0 18px 18px;">
                    <h2 class="card-title mb-3" style="color: #1a1a1a; font-weight: bold; letter-spacing: 0.4px;">
                        <?= htmlspecialchars((string)$promo['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <p class="card-text" style="color: #222; font-size: 1.06em;">
                        <?= nl2br(htmlspecialchars((string)$promo['descripcion'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                    <?php if ((float)($promo['precio_promocional'] ?? 0) > 0): ?>
                        <span class="badge bg-warning text-dark fs-6 mb-2">Precio: S/ <?= number_format((float)$promo['precio_promocional'], 2) ?></span>
                    <?php endif; ?>
                    <?php if ((float)($promo['descuento'] ?? 0) > 0): ?>
                        <span class="badge bg-success ms-2">Descuento: <?= (float)$promo['descuento'] ?>%</span>
                    <?php endif; ?>
                    <div class="mb-2 mt-2">
                        <?php if ($fechaInicio !== '' || $fechaFin !== ''): ?>
                            <span class="badge bg-info text-dark">
                                Vigencia: <?= htmlspecialchars(trim($fechaInicio . ($fechaFin !== '' ? ' al ' . $fechaFin : '')), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($promo['activo'])): ?>
                            <span class="badge bg-success ms-2">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Inactiva</span>
                        <?php endif; ?>
                        <?php if (!empty($promo['vigente'])): ?>
                            <span class="badge bg-primary ms-2">Promoción vigente</span>
                        <?php endif; ?>
                    </div>
                    <a href="../../index.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left"></i> Volver al inicio
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger mt-2">Promoción no encontrada.</div>
            <a href="../../index.php" class="btn btn-primary mt-2" style="color: #fff; font-weight: 600;">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
        <?php endif; ?>
    </div>
</body>
</html>