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

	<?php
	$tablaCodigosSisOk = app_database_has_table($pdo, 'sis_codigos_prestacionales');
	$codigosSis = $tablaCodigosSisOk ? sis_prestacional_catalogo($pdo, false) : [];
	?>
	<hr class="my-4">
	<h5>Codigos prestacionales SIS (curso de vida MINSA)</h5>
	<p class="text-muted">Catalogo global del sistema; solo se aplica a atenciones SIS. Los rangos se expresan en anios cumplidos: "hasta 11" equivale a 11 anios, 11 meses, 29 dias. El historico de cotizaciones conserva el codigo con el que se registro.</p>

	<?php if (!$tablaCodigosSisOk): ?>
		<div class="alert alert-warning">Falta aplicar <code>sql/2026_10_18_codigos_prestacionales_sis/migracion.sql</code> para gestionar los codigos.</div>
	<?php else: ?>
		<form method="POST" enctype="multipart/form-data" action="<?= htmlspecialchars(BASE_URL) ?>dashboard.php?action=config_sis_codigos_guardar" autocomplete="off">
			<input type="hidden" name="empresa_cfg_id" value="<?= (int)$empresaActualId ?>">
			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr>
							<th>Codigo</th>
							<th>Nombre</th>
							<th>Descripcion MINSA</th>
							<th>Desde (anios)</th>
							<th>Hasta (anios)</th>
							<th>Color</th>
							<th>Icono (PNG, WEBP o PDF, max. 1 MB)</th>
							<th>Activo</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($codigosSis as $cs): ?>
							<?php
							$csId = (int)$cs['id'];
							$esEdad = ($cs['tipo_regla'] ?? '') === 'EDAD';
							$desdeAnios = $esEdad ? intdiv((int)($cs['edad_min_meses'] ?? 0), 12) : '';
							$hastaAnios = ($esEdad && $cs['edad_max_meses'] !== null) ? intdiv(((int)$cs['edad_max_meses']) + 1, 12) - 1 : '';
							$rutaIcono = (string)($cs['icono_ruta'] ?? '');
							$esPdf = $rutaIcono !== '' && strtolower(pathinfo($rutaIcono, PATHINFO_EXTENSION)) === 'pdf';
							?>
							<tr>
								<td class="fw-bold"><?= htmlspecialchars((string)$cs['codigo']) ?></td>
								<td><input type="text" class="form-control form-control-sm" name="cod[<?= $csId ?>][nombre]" maxlength="80" required value="<?= htmlspecialchars((string)$cs['nombre']) ?>"></td>
								<td><input type="text" class="form-control form-control-sm" name="cod[<?= $csId ?>][descripcion_minsa]" maxlength="255" value="<?= htmlspecialchars((string)($cs['descripcion_minsa'] ?? '')) ?>"></td>
								<?php if ($esEdad): ?>
									<td><input type="number" class="form-control form-control-sm" style="width:90px" name="cod[<?= $csId ?>][desde]" min="0" step="1" required value="<?= $desdeAnios ?>"></td>
									<td><input type="number" class="form-control form-control-sm" style="width:90px" name="cod[<?= $csId ?>][hasta]" min="0" step="1" placeholder="sin limite" value="<?= $hastaAnios ?>"></td>
								<?php else: ?>
									<td colspan="2" class="text-muted small">Se asigna al marcar gestante</td>
								<?php endif; ?>
								<td><input type="color" class="form-control form-control-color" name="cod[<?= $csId ?>][color]" value="<?= htmlspecialchars((string)(($cs['color'] ?? '') ?: '#6c757d')) ?>"></td>
								<td>
									<div class="d-flex align-items-center gap-2">
										<?= sis_prestacional_icono_html($cs, 32) ?>
										<div>
											<input type="file" class="form-control form-control-sm" name="icono_<?= $csId ?>" accept=".png,.webp,.pdf,image/png,image/webp,application/pdf">
											<?php if ($rutaIcono !== ''): ?>
												<div class="small mt-1">
													<?php if ($esPdf): ?>
														<a href="<?= htmlspecialchars(BASE_URL . '../' . $rutaIcono) ?>" target="_blank" rel="noopener">Ver PDF</a>
														<span class="text-muted">(en pantalla se usa el icono por defecto)</span>
													<?php endif; ?>
													<label class="ms-2"><input type="checkbox" name="quitar_icono[<?= $csId ?>]" value="1"> Quitar</label>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</td>
								<td><input class="form-check-input" type="checkbox" name="cod[<?= $csId ?>][activo]" value="1" <?= !empty($cs['activo']) ? 'checked' : '' ?>></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<button type="submit" class="btn btn-dark">Guardar codigos prestacionales</button>
		</form>
	<?php endif; ?>
</div>
