<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
require_once __DIR__ . '/../usuarios/funciones/usuarios_privilegios.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>No se pudo preparar la tabla de servicios.</div></div>";
    return;
}

$stmt = $pdo->query("SELECT * FROM servicios ORDER BY id DESC");
$servicios = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$puedeGestionarServicios = in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['admin', 'servicio'], true) || usuarios_tiene_privilegio(isset($_SESSION['privilegios']) && is_array($_SESSION['privilegios']) ? $_SESSION['privilegios'] : [], 'menu_servicios');
$rolActualServicios = strtolower((string)($_SESSION['rol'] ?? ''));
$volverPanelServicios = match ($rolActualServicios) {
    'servicio' => 'dashboard.php?vista=servicio',
    'admin' => 'dashboard.php?vista=admin',
    'recepcionista' => 'dashboard.php?vista=recepcionista',
    'laboratorista' => 'dashboard.php?vista=laboratorista',
    default => 'dashboard.php',
};
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Servicios</h3>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($volverPanelServicios, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al panel
            </a>
            <?php if ($puedeGestionarServicios): ?>
                <a href="dashboard.php?vista=profesionales_solicitantes" class="btn btn-outline-primary">Profesionales solicitantes</a>
            <?php endif; ?>
            <?php if ($puedeGestionarServicios): ?>
                <a href="dashboard.php?vista=form_servicio" class="btn btn-success">Registrar Servicio</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars((string)$_SESSION['mensaje']) ?></div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Codigo</th>
                    <th>Responsable</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$servicios): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay servicios registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servicios as $servicio): ?>
                        <?php $activo = strtolower((string)($servicio['estado'] ?? 'activo')) === 'activo'; ?>
                        <tr>
                            <td><?= (int)$servicio['id'] ?></td>
                            <td><?= htmlspecialchars((string)$servicio['nombre']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars((string)$servicio['codigo']) ?></span></td>
                            <td><?= htmlspecialchars((string)($servicio['responsable'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($servicio['telefono'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)$servicio['email']) ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($puedeGestionarServicios): ?>
                                    <a href="dashboard.php?vista=form_servicio&id=<?= (int)$servicio['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="dashboard.php?action=toggle_servicio&id=<?= (int)$servicio['id'] ?>&estado=<?= $activo ? 'inactivo' : 'activo' ?>" class="btn btn-<?= $activo ? 'outline-warning' : 'outline-success' ?> btn-sm">
                                        <?= $activo ? 'Inactivar' : 'Activar' ?>
                                    </a>
                                    <a href="dashboard.php?action=eliminar_servicio&id=<?= (int)$servicio['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este servicio? Esta accion no se puede deshacer.')">Eliminar</a>
                                <?php else: ?>
                                    <span class="text-muted small">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
