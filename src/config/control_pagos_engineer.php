<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/sistema_pagos.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
    return;
}

$tablasOk = sp_tablas_ok($pdo);
$empresaIdGet = (int)($_GET['empresa_cfg_id'] ?? 0);
$stmtEmpresas = $pdo->query('SELECT id, nombre, dominio FROM config_empresa ORDER BY id ASC');
$empresas = $stmtEmpresas ? ($stmtEmpresas->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
$empresaCfg = ui_theme_fetch_company_config($pdo, $empresaIdGet > 0 ? $empresaIdGet : null);
$empresaId = (int)($empresaCfg['id'] ?? 0);
$csrf = sp_csrf_token();

$sus = $tablasOk ? sp_obtener_suscripcion($pdo, $empresaId) : null;
$estado = ($tablasOk && $empresaId > 0) ? sp_calcular_estado($pdo, $empresaId) : ['estado' => 'SIN_CONFIG'];
$proximos = ($sus && in_array($sus['modalidad'], ['MENSUAL', 'ANUAL'], true)) ? sp_proximos_ciclos($pdo, $sus, 4) : [];

$pagos = [];
if ($sus) {
    $st = $pdo->prepare('SELECT * FROM sistema_suscripcion_pagos WHERE suscripcion_id = ? ORDER BY fecha_pago DESC, id DESC LIMIT 60');
    $st->execute([(int)$sus['id']]);
    $pagos = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$estadoInfo = [
    'SIN_CONFIG' => ['secondary', 'Sin configurar'],
    'EXENTO' => ['secondary', 'Exento (no se cobra)'],
    'OK' => ['success', 'Al día'],
    'AVISO' => ['warning', 'Próximo a vencer'],
    'URGENTE' => ['warning', 'Vence en menos de 48 horas'],
    'HOY' => ['danger', 'Vence hoy'],
    'VENCIDO' => ['danger', 'Vencido'],
    'SUSPENDIDO' => ['dark', 'Suspendido'],
];
[$badgeClase, $badgeTexto] = $estadoInfo[$estado['estado']] ?? ['secondary', $estado['estado']];
$hoy = sp_ahora()->format('Y-m-d');
$modalidad = $sus['modalidad'] ?? 'EXENTO';
$h = 'sp_h';
$urlAccion = BASE_URL . 'dashboard.php?action=control_pagos_engineer_guardar';
?>

<div class="container mt-4 mb-5">
    <h4>Control de pagos del sistema</h4>
    <p class="text-muted mb-3">Mensualidad o alojamiento anual de cada empresa. El aviso aparece arriba a la derecha a admin, recepcionista y laboratorista hasta que se registre el pago.</p>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= $h($_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <?php if (!$tablasOk): ?>
        <div class="alert alert-warning">Falta aplicar <code>sql/2026_11_21_control_pagos_sistema/migracion.sql</code> para usar el control de pagos.</div>
    <?php elseif ($empresaId <= 0): ?>
        <div class="alert alert-warning">No hay empresa configurada en <code>config_empresa</code>.</div>
    <?php else: ?>

        <?php if (count($empresas) > 1): ?>
            <form method="GET" action="dashboard.php" class="row g-2 mb-3 align-items-end">
                <input type="hidden" name="vista" value="control_pagos_engineer">
                <div class="col-12 col-md-6 col-lg-5">
                    <label for="empresa_cfg_id" class="form-label">Empresa objetivo</label>
                    <select class="form-select" name="empresa_cfg_id" id="empresa_cfg_id">
                        <?php foreach ($empresas as $emp): ?>
                            <?php $idOpt = (int)$emp['id']; ?>
                            <option value="<?= $idOpt ?>" <?= $idOpt === $empresaId ? 'selected' : '' ?>><?= $h(trim((string)$emp['nombre']) . (trim((string)$emp['dominio']) !== '' ? ' - ' . $emp['dominio'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-outline-primary">Cambiar empresa</button></div>
            </form>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <h5 class="mb-0"><?= $h($empresaCfg['nombre'] ?? 'Empresa') ?></h5>
                    <span class="badge text-bg-<?= $h($badgeClase) ?> fs-6"><?= $h($badgeTexto) ?></span>
                    <?php if (!empty($estado['mostrar'])): ?>
                        <span>Vence: <strong><?= $h($estado['venc_fecha']) ?></strong>
                            <?php if ($estado['dias_vencido'] > 0): ?>(hace <?= (int)$estado['dias_vencido'] ?> día(s))
                            <?php elseif ($estado['dias_restantes'] >= 0): ?>(en <?= (int)$estado['dias_restantes'] ?> día(s))<?php endif; ?>
                        </span>
                        <?php if (!empty($estado['bloqueo_ts'])): ?>
                            <span class="text-danger">Bloqueo: <?= $h((new DateTimeImmutable('@' . $estado['bloqueo_ts']))->setTimezone(sp_tz())->format('d/m/Y')) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($proximos)): ?>
                    <div class="mt-3 small text-muted">Próximos ciclos impagos:
                        <?php foreach ($proximos as $c): ?>
                            <span class="badge text-bg-light border"><?= $h($c['periodo']) ?> · <?= $h($c['vence']->format('d/m/Y')) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header fw-bold">Configuración del cobro</div>
                    <div class="card-body">
                        <form method="POST" action="<?= $h($urlAccion) ?>" autocomplete="off">
                            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                            <input type="hidden" name="accion" value="guardar_config">
                            <input type="hidden" name="empresa_cfg_id" value="<?= $empresaId ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="spModalidad">Modalidad</label>
                                    <select class="form-select" id="spModalidad" name="modalidad">
                                        <option value="MENSUAL" <?= $modalidad === 'MENSUAL' ? 'selected' : '' ?>>Mensual (cada mes)</option>
                                        <option value="ANUAL" <?= $modalidad === 'ANUAL' ? 'selected' : '' ?>>Anual (alojamiento)</option>
                                        <option value="EXENTO" <?= $modalidad === 'EXENTO' ? 'selected' : '' ?>>Exento (sin aviso)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="spPlan">Plan (referencia)</label>
                                    <input type="text" class="form-control" id="spPlan" name="plan" maxlength="60" value="<?= $h($sus['plan'] ?? '') ?>" placeholder="Básico / Profesional / Premium">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="spMonto">Monto por ciclo</label>
                                    <input type="number" class="form-control" id="spMonto" name="monto" min="0" step="0.01" value="<?= $h($sus['monto'] ?? '0.00') ?>">
                                </div>
                                <div class="col-md-4" id="spBloqueDia">
                                    <label class="form-label" for="spDia">Día de cobro (1-31)</label>
                                    <input type="number" class="form-control" id="spDia" name="dia_cobro" min="1" max="31" value="<?= (int)($sus['dia_cobro'] ?? 1) ?>">
                                    <div class="form-text">Si el mes es más corto vence su último día.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="spPrimer">Primer vencimiento a controlar</label>
                                    <input type="date" class="form-control" id="spPrimer" name="primer_vencimiento" value="<?= $h($sus['primer_vencimiento'] ?? '') ?>">
                                    <div class="form-text">Mensual: se toma el mes. Anual: fija el aniversario.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="spAviso">Días de aviso antes</label>
                                    <input type="number" class="form-control" id="spAviso" name="dias_aviso" min="0" max="365" value="<?= $h($sus['dias_aviso'] ?? '') ?>" placeholder="vacío = 5 (mensual) / 30 (anual)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="spGracia">Días de gracia antes de bloquear</label>
                                    <input type="number" class="form-control" id="spGracia" name="dias_gracia" min="0" max="365" value="<?= $h($sus['dias_gracia'] ?? '') ?>" placeholder="vacío = nunca se bloquea">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="spMedios">Medios de pago (se muestran al cliente)</label>
                                    <textarea class="form-control" id="spMedios" name="medios_pago" rows="3" placeholder="Yape/Plin: 999 999 999&#10;Cuenta BCP: ..."><?= $h($sus['medios_pago'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="spMsg">Mensaje adicional (opcional)</label>
                                    <input type="text" class="form-control" id="spMsg" name="mensaje_extra" maxlength="255" value="<?= $h($sus['mensaje_extra'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark mt-3">Guardar configuración</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header fw-bold">Registrar pago</div>
                    <div class="card-body">
                        <?php if (!$sus): ?>
                            <div class="alert alert-secondary mb-0">Guarde primero la configuración del cobro.</div>
                        <?php else: ?>
                            <form method="POST" action="<?= $h($urlAccion) ?>" autocomplete="off">
                                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                                <input type="hidden" name="accion" value="registrar_pago">
                                <input type="hidden" name="empresa_cfg_id" value="<?= $empresaId ?>">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" for="spConcepto">Concepto</label>
                                        <select class="form-select" id="spConcepto" name="concepto">
                                            <?php if (in_array($modalidad, ['MENSUAL', 'ANUAL'], true)): ?>
                                                <option value="CICLO"><?= $modalidad === 'ANUAL' ? 'Alojamiento anual' : 'Mensualidad' ?> (ciclos pendientes)</option>
                                            <?php endif; ?>
                                            <option value="IMPLEMENTACION">Implementación (pago único)</option>
                                            <option value="OTRO">Otro (no afecta el aviso)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="spPeriodos">Ciclos que cubre</label>
                                        <input type="number" class="form-control" id="spPeriodos" name="periodos" min="1" max="60" value="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="spPMonto">Monto por ciclo</label>
                                        <input type="number" class="form-control" id="spPMonto" name="monto" min="0" step="0.01" value="<?= $h($sus['monto']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="spFecha">Fecha de pago</label>
                                        <input type="date" class="form-control" id="spFecha" name="fecha_pago" value="<?= $h($hoy) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="spMetodo">Método</label>
                                        <select class="form-select" id="spMetodo" name="metodo">
                                            <?php foreach (['Yape', 'Plin', 'Transferencia', 'Depósito', 'Efectivo', 'Otro'] as $m): ?>
                                                <option><?= $h($m) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="spRef">Referencia / operación</label>
                                        <input type="text" class="form-control" id="spRef" name="referencia" maxlength="120">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="spNota">Nota</label>
                                        <input type="text" class="form-control" id="spNota" name="nota" maxlength="255">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success mt-3"><i class="bi bi-check-circle me-1"></i>Registrar pago</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header fw-bold">Historial de pagos</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Fecha</th><th>Concepto</th><th>Periodo</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Registró</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pagos)): ?>
                            <tr><td colspan="8" class="text-muted text-center py-3">Sin pagos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($pagos as $p): ?>
                            <tr class="<?= !empty($p['anulado']) ? 'text-decoration-line-through text-muted' : '' ?>">
                                <td><?= $h(date('d/m/Y', strtotime((string)$p['fecha_pago']))) ?></td>
                                <td><?= $h($p['concepto']) ?></td>
                                <td><?= $h($p['periodo'] ?? '—') ?></td>
                                <td><?= $h(money_format_local((float)$p['monto'])) ?></td>
                                <td><?= $h($p['metodo'] ?? '') ?></td>
                                <td><?= $h($p['referencia'] ?? '') ?></td>
                                <td class="small"><?= $h($p['registrado_por'] ?? '') ?></td>
                                <td class="text-end">
                                    <?php if (empty($p['anulado'])): ?>
                                        <form method="POST" action="<?= $h($urlAccion) ?>" class="d-inline-flex gap-1" onsubmit="return confirm('¿Anular este pago? El ciclo volverá a quedar pendiente.');">
                                            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                                            <input type="hidden" name="accion" value="anular_pago">
                                            <input type="hidden" name="empresa_cfg_id" value="<?= $empresaId ?>">
                                            <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
                                            <input type="text" name="motivo" class="form-control form-control-sm" style="width:150px" placeholder="Motivo" required maxlength="255">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Anular</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="small">Anulado: <?= $h($p['anulado_motivo'] ?? '') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        var sel = document.getElementById('spModalidad');
        var dia = document.getElementById('spBloqueDia');
        if (!sel || !dia) return;
        function sync() { dia.style.display = sel.value === 'MENSUAL' ? '' : 'none'; }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>
