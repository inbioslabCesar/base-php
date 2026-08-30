<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
	echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
	return;
}

$empresaIdGet = (int)($_GET['empresa_cfg_id'] ?? 0);
$stmtEmpresas = $pdo->query('SELECT id, nombre, dominio FROM config_empresa ORDER BY id ASC');
$empresas = $stmtEmpresas ? ($stmtEmpresas->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
$empresa = ui_theme_fetch_company_config($pdo, $empresaIdGet > 0 ? $empresaIdGet : null);
$empresa = is_array($empresa) ? $empresa : [];
$empresaActualId = (int)($empresa['id'] ?? 0);

$modo_operativo = strtoupper(trim((string)($empresa['modo_operativo'] ?? 'PARTICULAR')));
if (!in_array($modo_operativo, ['PARTICULAR', 'SIS', 'MIXTO'], true)) {
	$modo_operativo = 'PARTICULAR';
}
$portal_publico_enable = (int)($empresa['portal_publico_enable'] ?? 1) === 1;
?>

<div class="container mt-4">
	<h4>Configuracion Operativa (Engineer)</h4>
	<p class="text-muted mb-3">Vista tecnica dedicada para controlar modo operativo global y disponibilidad del portal publico.</p>

	<?php if (isset($_SESSION['msg'])): ?>
		<div class="alert alert-info"><?= htmlspecialchars((string)$_SESSION['msg']) ?></div>
		<?php unset($_SESSION['msg']); ?>
	<?php endif; ?>

	<div class="alert alert-warning">
		Cambios aqui impactan cotizaciones, pagos y portal publico para la empresa seleccionada.
	</div>

	<?php if (!empty($empresas)): ?>
		<form method="GET" action="dashboard.php" class="row g-2 mb-3 align-items-end">
			<input type="hidden" name="vista" value="config_operacion_engineer">
			<div class="col-12 col-md-6 col-lg-5">
				<label for="empresa_cfg_id" class="form-label">Empresa objetivo</label>
				<select class="form-select" name="empresa_cfg_id" id="empresa_cfg_id">
					<?php foreach ($empresas as $emp): ?>
						<?php
						$idOpt = (int)($emp['id'] ?? 0);
						$nombreOpt = trim((string)($emp['nombre'] ?? 'Empresa'));
						$dominioOpt = trim((string)($emp['dominio'] ?? ''));
						$labelOpt = $nombreOpt . ($dominioOpt !== '' ? ' - ' . $dominioOpt : '');
						?>
						<option value="<?= $idOpt ?>" <?= $idOpt === $empresaActualId ? 'selected' : '' ?>><?= htmlspecialchars($labelOpt, ENT_QUOTES, 'UTF-8') ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-auto">
				<button type="submit" class="btn btn-outline-primary">Cambiar empresa</button>
			</div>
		</form>
	<?php endif; ?>

	<form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>dashboard.php?action=config_operacion_engineer_guardar" autocomplete="off">
		<input type="hidden" name="empresa_cfg_id" value="<?= (int)$empresaActualId ?>">
		<input type="hidden" name="portal_publico_enable_present" value="1">

		<div class="row">
			<div class="col-md-4 mb-3">
				<label for="modo_operativo" class="form-label">Modo operativo</label>
				<select class="form-select" id="modo_operativo" name="modo_operativo" required>
					<option value="PARTICULAR" <?= $modo_operativo === 'PARTICULAR' ? 'selected' : '' ?>>Particular</option>
					<option value="SIS" <?= $modo_operativo === 'SIS' ? 'selected' : '' ?>>SIS</option>
					<option value="MIXTO" <?= $modo_operativo === 'MIXTO' ? 'selected' : '' ?>>Mixto</option>
				</select>
			</div>

			<div class="col-md-4 mb-3 d-flex align-items-end">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" id="portal_publico_enable" name="portal_publico_enable" value="1" <?= $portal_publico_enable ? 'checked' : '' ?>>
					<label class="form-check-label" for="portal_publico_enable">Portal publico habilitado</label>
				</div>
			</div>
		</div>

		<button type="submit" class="btn btn-dark">Guardar configuracion operativa</button>
		<a href="dashboard.php?vista=config_personalizacion_engineer&empresa_cfg_id=<?= (int)$empresaActualId ?>" class="btn btn-outline-secondary ms-2">Ir a personalizacion visual</a>
	</form>
</div>
