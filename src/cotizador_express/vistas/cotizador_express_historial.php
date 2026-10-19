<?php
require_once __DIR__ . '/../funciones/cotizador_express_servicio.php';

$ctxHist = cotExpContexto($pdo);
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$cfgMonedaHist = currency_get_config($pdo);
$money = static fn(float $v): string => money_format_local($v, $cfgMonedaHist);

$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['desde'] ?? '')) ? $_GET['desde'] : date('Y-m-d', strtotime('-30 days'));
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['hasta'] ?? '')) ? $_GET['hasta'] : date('Y-m-d');
$filtroEstado = in_array($_GET['estado'] ?? '', ['vigente', 'vencida', 'convertida', 'anulada'], true) ? $_GET['estado'] : '';

$disponible = cotExpHistorialDisponible($pdo);
$filas = [];
$m = ['total' => 0, 'convertidas' => 0, 'monto' => 0.0, 'monto_conv' => 0.0, 'vencidas' => 0, 'vigentes' => 0];

if ($disponible) {
    $baseWhere = 'DATE(creado_en) BETWEEN ? AND ?';
    $stmtM = $pdo->prepare("SELECT estado, vence_en, total FROM cotizaciones_express WHERE {$baseWhere} AND estado <> 'anulada'");
    $stmtM->execute([$desde, $hasta]);
    foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $ef = cotExpEstadoEfectivo($r);
        $m['total']++;
        $m['monto'] += (float)$r['total'];
        if ($ef === 'convertida') {
            $m['convertidas']++;
            $m['monto_conv'] += (float)$r['total'];
        } elseif ($ef === 'vencida') {
            $m['vencidas']++;
        } else {
            $m['vigentes']++;
        }
    }

    $where = [$baseWhere];
    $params = [$desde, $hasta];
    if ($filtroEstado === 'vigente') {
        $where[] = "estado = 'emitida' AND vence_en >= CURDATE()";
    } elseif ($filtroEstado === 'vencida') {
        $where[] = "estado = 'emitida' AND vence_en < CURDATE()";
    } elseif ($filtroEstado !== '') {
        $where[] = 'estado = ?';
        $params[] = $filtroEstado;
    }
    $stmt = $pdo->prepare('SELECT * FROM cotizaciones_express WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 300');
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$tasa = $m['total'] > 0 ? round($m['convertidas'] * 100 / $m['total'], 1) : 0;

$badges = [
    'emitida' => ['bg-primary', 'Vigente'],
    'vencida' => ['bg-secondary', 'Vencida'],
    'convertida' => ['bg-success', 'Convertida'],
    'anulada' => ['bg-dark', 'Anulada'],
];
?>
<div class="container-fluid mt-4" id="cxHist">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Cotizaciones Express</h3>
            <small class="text-muted">Estas cotizaciones no generan deuda ni atención hasta que se convierten.</small>
        </div>
        <a href="dashboard.php?vista=cotizador_express" class="btn btn-warning fw-semibold"><i class="bi bi-lightning-charge-fill"></i> Nueva cotización</a>
    </div>

    <?php if (!$disponible): ?>
        <div class="alert alert-warning">El historial aún no está habilitado: falta ejecutar <code>sql/2026_10_14_cotizador_express/migracion.sql</code> en esta base de datos.</div>
    <?php else: ?>
        <form class="row g-2 align-items-end mb-3" method="get" action="dashboard.php">
            <input type="hidden" name="vista" value="cotizador_express_historial">
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Desde</label><input type="date" name="desde" class="form-control" value="<?= $h($desde) ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= $h($hasta) ?>"></div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (['vigente' => 'Vigente', 'vencida' => 'Vencida', 'convertida' => 'Convertida', 'anulada' => 'Anulada'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $filtroEstado === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button></div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Cotizaciones emitidas</small><div class="fs-3 fw-bold"><?= (int)$m['total'] ?></div><small class="text-muted"><?= (int)$m['vigentes'] ?> vigentes · <?= (int)$m['vencidas'] ?> vencidas</small></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Convertidas en atención</small><div class="fs-3 fw-bold text-success"><?= (int)$m['convertidas'] ?></div><small class="text-muted">Conversión: <strong><?= $h($tasa) ?>%</strong></small></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Monto cotizado</small><div class="fs-3 fw-bold"><?= $h($money($m['monto'])) ?></div></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><small class="text-muted">Monto convertido</small><div class="fs-3 fw-bold text-success"><?= $h($money($m['monto_conv'])) ?></div></div></div></div>
        </div>

        <div class="card shadow-sm mb-3 d-none" id="cxConvPanel">
            <div class="card-body position-relative">
                <h6 class="mb-2">Convertir <span id="cxConvFolio" class="fw-bold"></span> en atención: elige al paciente</h6>
                <input type="text" id="cxConvBuscar" class="form-control" placeholder="Nombre, DNI o código del paciente" autocomplete="off">
                <div id="cxConvRes" class="list-group mt-2"></div>
                <div class="mt-2 small">
                    ¿Paciente nuevo? <a href="dashboard.php?vista=form_cliente" target="_blank" rel="noopener">Regístralo aquí</a> y vuelve a buscarlo.
                    <button type="button" class="btn btn-sm btn-link" id="cxConvCerrar">Cancelar</button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Folio</th><th>Fecha</th><th>Para</th><th>Tarifa</th><th class="text-end">Total</th><th>Vence</th><th>Estado</th><th>Canales</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$filas): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Sin cotizaciones en el período.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($filas as $r):
                        $ef = cotExpEstadoEfectivo($r);
                        [$bg, $lbl] = $badges[$ef] ?? ['bg-secondary', $ef];
                        $url = cotExpUrlPublica((string)$r['token']);
                    ?>
                        <tr data-id="<?= (int)$r['id'] ?>" data-folio="<?= $h($r['folio']) ?>">
                            <td class="fw-semibold"><?= $h($r['folio']) ?></td>
                            <td><?= $h(date('d/m/Y H:i', strtotime($r['creado_en']))) ?></td>
                            <td><?= $h($r['destinatario_nombre'] ?: '—') ?></td>
                            <td><?= $h($r['tarifa_tipo'] === 'particular' ? 'Particular' : ucfirst($r['tarifa_tipo']) . ': ' . $r['tarifa_nombre']) ?></td>
                            <td class="text-end"><?= $h($money((float)$r['total'])) ?></td>
                            <td><?= $h(date('d/m/Y', strtotime($r['vence_en']))) ?></td>
                            <td><span class="badge <?= $bg ?>"><?= $h($lbl) ?></span></td>
                            <td class="small text-muted"><?= $h($r['canales'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <?php if ($ef === 'convertida' && !empty($r['convertida_cotizacion_id'])): ?>
                                    <a class="btn btn-sm btn-outline-success" title="Ver atención" href="dashboard.php?vista=detalle_cotizacion&id=<?= (int)$r['convertida_cotizacion_id'] ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                                <?php if ($ef !== 'anulada'): ?>
                                    <a class="btn btn-sm btn-outline-danger" title="Abrir PDF" target="_blank" rel="noopener" href="<?= $h($url) ?>"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary cx-copiar-url" title="Copiar enlace" data-url="<?= $h($url) ?>"><i class="bi bi-link-45deg"></i></button>
                                    <a class="btn btn-sm btn-outline-primary" title="Reutilizar en el cotizador" href="dashboard.php?vista=cotizador_express&reutilizar=<?= (int)$r['id'] ?>"><i class="bi bi-arrow-repeat"></i></a>
                                <?php endif; ?>
                                <?php if (in_array($ef, ['emitida', 'vencida'], true)): ?>
                                    <button type="button" class="btn btn-sm btn-success cx-convertir" title="Convertir en atención"><i class="bi bi-person-check"></i></button>
                                    <?php if ($ef === 'emitida'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark cx-anular" title="Anular"><i class="bi bi-x-circle"></i></button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($filas) >= 300): ?><small class="text-muted">Mostrando las 300 más recientes; acota las fechas para ver más.</small><?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($disponible): ?>
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    let convId = 0;
    let timer = null;

    document.querySelectorAll('.cx-copiar-url').forEach((b) => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.url); } catch (e) { window.prompt('Copia el enlace:', b.dataset.url); return; }
        b.classList.replace('btn-outline-secondary', 'btn-success');
        setTimeout(() => b.classList.replace('btn-success', 'btn-outline-secondary'), 1200);
    }));

    document.querySelectorAll('.cx-anular').forEach((b) => b.addEventListener('click', () => {
        const tr = b.closest('tr');
        if (!window.confirm('¿Anular la cotización ' + tr.dataset.folio + '? El enlace dejará de funcionar.')) return;
        const body = new URLSearchParams({ op: 'anular', id: tr.dataset.id });
        fetch('dashboard.php?action=cotizador_express_api', { method: 'POST', body, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((d) => { if (d.ok) location.reload(); else window.alert('No se pudo anular.'); })
            .catch(() => window.alert('No se pudo anular.'));
    }));

    document.querySelectorAll('.cx-convertir').forEach((b) => b.addEventListener('click', () => {
        const tr = b.closest('tr');
        convId = parseInt(tr.dataset.id, 10);
        $('cxConvFolio').textContent = tr.dataset.folio;
        $('cxConvPanel').classList.remove('d-none');
        $('cxConvBuscar').value = '';
        $('cxConvRes').textContent = '';
        $('cxConvBuscar').focus();
        $('cxConvPanel').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    $('cxConvCerrar').addEventListener('click', () => $('cxConvPanel').classList.add('d-none'));

    $('cxConvBuscar').addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = $('cxConvBuscar').value.trim();
            const cont = $('cxConvRes');
            cont.textContent = '';
            if (q.length < 2) return;
            fetch('dashboard.php?action=cotizador_express_api&op=buscar_paciente&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then((r) => r.json())
                .then((data) => (data.items || []).forEach((p) => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.href = 'dashboard.php?vista=form_cotizacion&id=' + p.id + '&express_id=' + convId;
                    a.textContent = p.nombre + (p.dni ? ' · ' + p.dni : '');
                    cont.appendChild(a);
                }));
        }, 300);
    });
})();
</script>
<?php endif; ?>
