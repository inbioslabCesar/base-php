<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../funciones/servicios_schema.php';

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger">No se pudo inicializar el modulo de servicios.</div></div>';
    return;
}

$servicioId = (int)($_SESSION['servicio_id'] ?? 0);
if ($servicioId <= 0 || strtolower(trim((string)($_SESSION['rol'] ?? ''))) !== 'servicio') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

$stmtServ = $pdo->prepare("SELECT nombre, codigo FROM servicios WHERE id = ? LIMIT 1");
$stmtServ->execute([$servicioId]);
$servicio = $stmtServ->fetch(PDO::FETCH_ASSOC) ?: ['nombre' => 'Servicio', 'codigo' => ''];

$stmtCountCli = $pdo->prepare("SELECT COUNT(DISTINCT c.id_cliente) FROM cotizaciones c WHERE c.servicio_id = ?");
$stmtCountCli->execute([$servicioId]);
$totalPacientes = (int)$stmtCountCli->fetchColumn();

$stmtCountRes = $pdo->prepare("SELECT COUNT(re.id)
    FROM resultados_examenes re
    INNER JOIN cotizaciones c ON c.id = re.id_cotizacion
    WHERE c.servicio_id = ?");
$stmtCountRes->execute([$servicioId]);
$totalResultados = (int)$stmtCountRes->fetchColumn();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-1">Portal de Servicio</h4>
            <p class="mb-0 text-muted">
                <?= htmlspecialchars((string)$servicio['nombre']) ?>
                <?php if (!empty($servicio['codigo'])): ?>
                    <span class="badge bg-secondary ms-2"><?= htmlspecialchars((string)$servicio['codigo']) ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-primary h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-people fs-2 text-primary me-3"></i>
                    <div>
                        <div class="text-muted">Pacientes asociados</div>
                        <div class="fs-4 fw-semibold"><?= $totalPacientes ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-file-medical fs-2 text-success me-3"></i>
                    <div>
                        <div class="text-muted">Resultados vinculados</div>
                        <div class="fs-4 fw-semibold"><?= $totalResultados ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6 d-grid">
            <a href="dashboard.php?vista=servicio_clientes" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-person-lines-fill me-2"></i>Ver listado de pacientes
            </a>
        </div>
        <div class="col-md-6 d-grid">
            <a href="dashboard.php?vista=servicio_resultados" class="btn btn-outline-success btn-lg">
                <i class="bi bi-file-earmark-pdf me-2"></i>Ver y descargar resultados
            </a>
        </div>
    </div>
</div>
