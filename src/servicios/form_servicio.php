<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';

servicios_asegurar_tabla($pdo);

$esEdicion = isset($_GET['id']);
$servicio = [
    'nombre' => '',
    'codigo' => '',
    'responsable' => '',
    'telefono' => '',
    'email' => '',
    'estado' => 'activo'
];

$profesionalesDisponibles = [];
$profesionalesSeleccionados = [];

$rolActualServicioForm = strtolower((string)($_SESSION['rol'] ?? ''));
$volverServicios = match ($rolActualServicioForm) {
    'servicio' => 'dashboard.php?vista=servicio',
    'admin' => 'dashboard.php?vista=servicios',
    'recepcionista' => 'dashboard.php?vista=recepcionista',
    'laboratorista' => 'dashboard.php?vista=laboratorista',
    default => 'dashboard.php?vista=servicios',
};

if ($esEdicion) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$servicio) {
        $_SESSION['mensaje'] = 'Servicio no encontrado.';
        header('Location: dashboard.php?vista=servicios');
        exit;
    }

    $stmtProfSel = $pdo->prepare("SELECT profesional_id FROM servicio_profesional WHERE servicio_id = ?");
    $stmtProfSel->execute([$id]);
    $profesionalesSeleccionados = array_map('intval', $stmtProfSel->fetchAll(PDO::FETCH_COLUMN));
}

$stmtProfesionales = $pdo->query("SELECT id, nombres, apellidos, tipo_profesional, registro_profesional, estado FROM profesionales_solicitantes ORDER BY estado ASC, nombres ASC, apellidos ASC");
$profesionalesDisponibles = $stmtProfesionales ? $stmtProfesionales->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0"><?= $esEdicion ? 'Editar Servicio' : 'Registrar Servicio' ?></h3>
        <a href="<?= htmlspecialchars($volverServicios, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_servicio&id=' . (int)$_GET['id'] : 'crear_servicio' ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required value="<?= htmlspecialchars((string)$servicio['nombre']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="codigo" class="form-label">Codigo *</label>
                <input type="text" class="form-control" id="codigo" name="codigo" required value="<?= htmlspecialchars((string)$servicio['codigo']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="responsable" class="form-label">Responsable</label>
                <input type="text" class="form-control" id="responsable" name="responsable" value="<?= htmlspecialchars((string)($servicio['responsable'] ?? '')) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="telefono" class="form-label">Telefono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars((string)($servicio['telefono'] ?? '')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Usuario (email) *</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars((string)$servicio['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label"><?= $esEdicion ? 'Nueva contraseña (opcional)' : 'Contraseña *' ?></label>
                <input type="text" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
            </div>
        </div>

        <?php if ($esEdicion): ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="activo" <?= (strtolower((string)$servicio['estado']) === 'activo') ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= (strtolower((string)$servicio['estado']) === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="profesionales_ids" class="form-label">Profesionales solicitantes asociados</label>
            <select class="form-select" id="profesionales_ids" name="profesionales_ids[]" multiple size="8">
                <?php foreach ($profesionalesDisponibles as $prof): ?>
                    <?php
                    $profId = (int)($prof['id'] ?? 0);
                    $seleccionadoProf = in_array($profId, $profesionalesSeleccionados, true) ? 'selected' : '';
                    $estadoProf = strtolower((string)($prof['estado'] ?? 'activo'));
                    $nombreProf = trim((string)($prof['nombres'] ?? '') . ' ' . (string)($prof['apellidos'] ?? ''));
                    $tipoProf = trim((string)($prof['tipo_profesional'] ?? ''));
                    $registroProf = trim((string)($prof['registro_profesional'] ?? ''));
                    ?>
                    <option value="<?= $profId ?>" <?= $seleccionadoProf ?> <?= $estadoProf !== 'activo' ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($nombreProf . ($tipoProf !== '' ? ' - ' . $tipoProf : '') . ($registroProf !== '' ? ' (' . $registroProf . ')' : '')) ?>
                        <?= $estadoProf !== 'activo' ? ' [inactivo]' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Estos profesionales aparecerán al cotizar cuando se seleccione este servicio.</small>
        </div>

        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
        <a href="dashboard.php?vista=servicios" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
