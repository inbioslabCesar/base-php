<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

servicios_asegurar_tabla($pdo);

$esEdicion = isset($_GET['id']);
$profesional = [
    'nombres' => '',
    'apellidos' => '',
    'tipo_profesional' => '',
    'numero_documento' => '',
    'registro_profesional' => '',
    'telefono' => '',
    'email' => '',
    'estado' => 'activo',
];
$serviciosDisponibles = [];
$serviciosSeleccionados = [];

if ($esEdicion) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM profesionales_solicitantes WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $profesional = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$profesional) {
        $_SESSION['mensaje'] = 'Profesional solicitante no encontrado.';
        header('Location: dashboard.php?vista=profesionales_solicitantes');
        exit;
    }

    $stmtRel = $pdo->prepare("SELECT servicio_id FROM servicio_profesional WHERE profesional_id = ?");
    $stmtRel->execute([$id]);
    $serviciosSeleccionados = array_map('intval', $stmtRel->fetchAll(PDO::FETCH_COLUMN));
}

$stmtServicios = $pdo->query("SELECT id, nombre, codigo, estado FROM servicios ORDER BY nombre");
$serviciosDisponibles = $stmtServicios ? $stmtServicios->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0"><?= $esEdicion ? 'Editar profesional solicitante' : 'Registrar profesional solicitante' ?></h3>
        <a href="dashboard.php?vista=profesionales_solicitantes" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_profesional_solicitante&id=' . (int)$_GET['id'] : 'crear_profesional_solicitante' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="nombres" class="form-label">Nombres *</label>
                <input type="text" class="form-control" id="nombres" name="nombres" required value="<?= htmlspecialchars((string)$profesional['nombres']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="apellidos" class="form-label">Apellidos</label>
                <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= htmlspecialchars((string)($profesional['apellidos'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="tipo_profesional" class="form-label">Tipo profesional *</label>
                <input type="text" class="form-control" id="tipo_profesional" name="tipo_profesional" required placeholder="Ej: Médico, Obstetra, Enfermera, Psicólogo" value="<?= htmlspecialchars((string)($profesional['tipo_profesional'] ?? '')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="numero_documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="numero_documento" name="numero_documento" value="<?= htmlspecialchars((string)($profesional['numero_documento'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="registro_profesional" class="form-label">Registro/Colegiatura</label>
                <input type="text" class="form-control" id="registro_profesional" name="registro_profesional" value="<?= htmlspecialchars((string)($profesional['registro_profesional'] ?? '')) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars((string)($profesional['telefono'] ?? '')) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string)($profesional['email'] ?? '')) ?>">
            </div>
            <?php if ($esEdicion): ?>
                <div class="col-md-3 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="activo" <?= strtolower((string)($profesional['estado'] ?? 'activo')) === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= strtolower((string)($profesional['estado'] ?? 'activo')) === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="servicios_ids" class="form-label">Servicios asociados *</label>
            <select class="form-select" id="servicios_ids" name="servicios_ids[]" multiple size="8" required>
                <?php foreach ($serviciosDisponibles as $srv): ?>
                    <?php
                    $srvId = (int)($srv['id'] ?? 0);
                    $seleccionado = in_array($srvId, $serviciosSeleccionados, true) ? 'selected' : '';
                    $estadoSrv = strtolower((string)($srv['estado'] ?? 'activo'));
                    ?>
                    <option value="<?= $srvId ?>" <?= $seleccionado ?> <?= $estadoSrv !== 'activo' ? 'disabled' : '' ?>>
                        <?= htmlspecialchars((string)$srv['nombre'] . (!empty($srv['codigo']) ? ' (' . (string)$srv['codigo'] . ')' : '')) ?>
                        <?= $estadoSrv !== 'activo' ? ' [inactivo]' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Usa Ctrl o Cmd para seleccionar varios servicios.</small>
        </div>

        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
        <a href="dashboard.php?vista=profesionales_solicitantes" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
