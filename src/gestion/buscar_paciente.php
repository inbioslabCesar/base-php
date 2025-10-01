<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$busqueda = trim($_GET['busqueda_paciente'] ?? '');
$resultados = [];
$mensaje = '';

if ($busqueda !== '') {
    $sql = "SELECT *, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM clientes WHERE 
        dni LIKE ? OR 
        codigo_cliente LIKE ? OR 
        nombre LIKE ? OR 
        apellido LIKE ? OR 
        CONCAT(nombre, ' ', apellido) LIKE ?
        LIMIT 20";
    $param = "%$busqueda%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$param, $param, $param, $param, $param]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container mt-4">
    <h4 class="mb-3"><i class="bi bi-search me-2"></i>Resultado de búsqueda de paciente</h4>
    <?php if ($busqueda === ''): ?>
        <div class="alert alert-info">Ingrese un dato para buscar.</div>
    <?php elseif ($resultados): ?>
        <div class="alert alert-success">Paciente encontrado:</div>
        <?php foreach ($resultados as $paciente): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <?= htmlspecialchars($paciente['nombre']) ?> <?= htmlspecialchars($paciente['apellido']) ?>
                        <span class="badge bg-primary ms-2">#<?= htmlspecialchars($paciente['codigo_cliente']) ?></span>
                    </h5>
                    <p class="mb-1">DNI: <strong><?= htmlspecialchars($paciente['dni']) ?></strong></p>
                    <a href="dashboard.php?vista=form_cotizacion&id=<?= $paciente['id'] ?>" class="btn btn-success">
                        <i class="bi bi-file-earmark-plus"></i> Cotizar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-warning">No se encontró el paciente. ¿Desea registrarlo?</div>
        <a href="dashboard.php?vista=form_cliente&dni=<?= urlencode($busqueda) ?>" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Registrar paciente
        </a>
    <?php endif; ?>
</div>
