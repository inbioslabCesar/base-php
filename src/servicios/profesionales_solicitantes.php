<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>No se pudo preparar el modulo de profesionales solicitantes.</div></div>";
    return;
}

$stmt = $pdo->query("SELECT * FROM profesionales_solicitantes ORDER BY estado ASC, nombres ASC, apellidos ASC");
$profesionales = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
$volverUrl = match ($rolActual) {
    'servicio' => 'dashboard.php?vista=servicio',
    'admin' => 'dashboard.php?vista=servicios',
    'recepcionista' => 'dashboard.php?vista=recepcionista',
    'laboratorista' => 'dashboard.php?vista=laboratorista',
    default => 'dashboard.php?vista=servicios',
};
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Profesionales Solicitantes</h3>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($volverUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="dashboard.php?vista=form_profesional_solicitante" class="btn btn-success">Registrar Profesional</a>
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
                    <th>Profesional</th>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Registro</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$profesionales): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay profesionales solicitantes registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($profesionales as $prof): ?>
                        <?php
                        $nombre = trim((string)($prof['nombres'] ?? '') . ' ' . (string)($prof['apellidos'] ?? ''));
                        $documento = trim((string)($prof['numero_documento'] ?? ''));
                        $registro = trim((string)($prof['registro_profesional'] ?? ''));
                        $contacto = trim((string)($prof['telefono'] ?? ''));
                        if (!empty($prof['email'])) {
                            $contacto .= ($contacto !== '' ? ' | ' : '') . (string)$prof['email'];
                        }
                        $activo = strtolower((string)($prof['estado'] ?? 'activo')) === 'activo';
                        ?>
                        <tr>
                            <td><?= (int)$prof['id'] ?></td>
                            <td><?= htmlspecialchars($nombre) ?></td>
                            <td><?= htmlspecialchars((string)($prof['tipo_profesional'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($documento !== '' ? $documento : '-') ?></td>
                            <td><?= htmlspecialchars($registro !== '' ? $registro : '-') ?></td>
                            <td><?= htmlspecialchars($contacto !== '' ? $contacto : '-') ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="dashboard.php?vista=form_profesional_solicitante&id=<?= (int)$prof['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="dashboard.php?action=eliminar_profesional_solicitante&id=<?= (int)$prof['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este profesional solicitante?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
