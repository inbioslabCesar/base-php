<?php
// Vista: Estadística SIS por código prestacional (conteos, sin precios). Solo modo SIS.
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/estadisticas_sis_datos.php';

$ctxSis = function_exists('app_operacion_context') ? app_operacion_context($pdo) : [];
if (empty($ctxSis['es_sis'])) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>La estadística SIS solo está disponible en modo operativo SIS.</div></div>";
    return;
}

[$desde, $hasta] = sis_estadistica_rango($_GET['desde'] ?? null, $_GET['hasta'] ?? null);
$resumen = sis_estadistica_resumen($pdo, $desde, $hasta);
$qs = http_build_query(['desde' => $desde, 'hasta' => $hasta]);
$exportBase = htmlspecialchars(BASE_URL) . 'dashboard.php?action=estadisticas_sis_export&' . $qs;
?>
<style>
.sis-card { border: 0; border-radius: 16px; box-shadow: 0 6px 18px rgba(15, 23, 42, .08); border-top: 5px solid var(--sis-color, #6c757d); height: 100%; }
.sis-card .sis-card-icon { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; background: color-mix(in srgb, var(--sis-color, #6c757d) 14%, #fff); }
.sis-card .sis-card-num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.sis-card .sis-card-lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
</style>
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h3 class="mb-0"><i class="bi bi-shield-check"></i> Estadística SIS por código prestacional</h3>
        <?php if ($resumen['disponible']): ?>
            <div class="d-flex gap-2">
                <a class="btn btn-success" href="<?= $exportBase ?>&format=xls"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                <a class="btn btn-danger" href="<?= $exportBase ?>&format=pdf"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            </div>
        <?php endif; ?>
    </div>

    <form method="GET" action="dashboard.php" class="row g-2 align-items-end mb-4">
        <input type="hidden" name="vista" value="estadisticas_sis">
        <div class="col-6 col-md-3">
            <label for="sisDesde" class="form-label mb-0">Desde</label>
            <input type="date" id="sisDesde" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label for="sisHasta" class="form-label mb-0">Hasta</label>
            <input type="date" id="sisHasta" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Ver</button>
        </div>
    </form>

    <?php if (!$resumen['disponible']): ?>
        <div class="alert alert-warning">
            Falta aplicar <code>sql/2026_10_18_codigos_prestacionales_sis/migracion.sql</code> para generar este reporte.
        </div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card sis-card" style="--sis-color:#0d6efd"><div class="card-body">
                    <div class="sis-card-lbl">Atenciones SIS</div>
                    <div class="sis-card-num"><?= (int)$resumen['total_atenciones'] ?></div>
                </div></div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card sis-card" style="--sis-color:#198754"><div class="card-body">
                    <div class="sis-card-lbl">Pacientes únicos</div>
                    <div class="sis-card-num"><?= (int)$resumen['total_pacientes'] ?></div>
                </div></div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card sis-card" style="--sis-color:#fd7e14"><div class="card-body">
                    <div class="sis-card-lbl">Exámenes</div>
                    <div class="sis-card-num"><?= (int)$resumen['total_examenes'] ?></div>
                </div></div>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($resumen['filas'] as $f): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card sis-card" style="--sis-color:<?= htmlspecialchars((string)(($f['color'] ?? '') ?: '#6c757d')) ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="sis-card-icon"><?= sis_prestacional_icono_html($f, 30) ?></div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars((string)$f['codigo']) ?> · <?= htmlspecialchars((string)$f['nombre']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($f['descripcion_minsa'] ?? '')) ?></div>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-4"><div class="sis-card-num"><?= (int)$f['atenciones'] ?></div><div class="sis-card-lbl">Atenciones</div></div>
                                <div class="col-4"><div class="sis-card-num"><?= (int)$f['pacientes'] ?></div><div class="sis-card-lbl">Pacientes</div></div>
                                <div class="col-4"><div class="sis-card-num"><?= (int)$f['examenes'] ?></div><div class="sis-card-lbl">Exámenes</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-muted small mt-3">
            Excluye cotizaciones anuladas. "Pacientes" por código cuenta pacientes distintos dentro de cada código;
            el total de pacientes únicos puede ser menor que la suma si un paciente cambió de código en el rango.
        </p>
    <?php endif; ?>
</div>
