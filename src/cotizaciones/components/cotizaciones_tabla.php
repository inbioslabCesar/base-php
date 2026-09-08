<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/currency.php';
require_once __DIR__ . '/../../usuarios/funciones/usuarios_privilegios.php';
$modoCotizaciones = strtolower(trim((string)($modoCotizaciones ?? 'activas')));
$soloAnuladas = ($modoCotizaciones === 'anuladas');
$rolActualCot = strtolower(trim((string)($_SESSION['rol'] ?? '')));
$puedeAnularCot = ($rolActualCot === 'admin');
$privilegiosCot = usuarios_privilegios_usuario_actual($pdo);
$puedeCompararResultados = ($rolActualCot === 'admin') || !empty($privilegiosCot['resultados_comparar']) || !empty($privilegiosCot['resultados_ver']);
$puedeEditarResultados = ($rolActualCot === 'admin') || !empty($privilegiosCot['resultados_editar']);
$puedeEditarCotizaciones = ($rolActualCot === 'admin') || !empty($privilegiosCot['cotizaciones_editar']);
$esModoSisCot = !empty($esModoSis ?? false);
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(\PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(\PDO::FETCH_ASSOC);
$currencyCfg = currency_get_config($pdo);
?>
<?php if ($esModoSisCot): ?>
<style>
    #tablaCotizaciones th.col-total,
    #tablaCotizaciones td.col-total,
    #tablaCotizaciones th.col-estado-pago,
    #tablaCotizaciones td.col-estado-pago {
        display: none !important;
    }
</style>
<?php endif; ?>
<style>
.btn-cotizacion-accion {
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
    min-width: 34px;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    padding: 0.375rem 0.5rem;
}
.cotizaciones-filters {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(44,62,80,0.07);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 2rem;
}
.cotizaciones-filters .form-label {
    color: #1565c0;
    font-weight: 600;
    font-size: 1rem;
}
.cotizaciones-filters .form-control, .cotizaciones-filters .form-select {
    border-radius: 10px;
    border: 1.5px solid #90caf9;
    font-size: 1rem;
}
.cotizaciones-filter-actions {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.cotizaciones-main-actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
}
.cotizaciones-main-actions .btn {
    min-width: 160px;
}
.cotizaciones-slow-network {
    min-width: 260px;
    max-width: 420px;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
}
.cotizaciones-slow-network .form-check-input {
    margin: 0.2rem 0 0 0;
    float: none;
    flex: 0 0 auto;
}
.cotizaciones-slow-network-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.25;
}
.cotizaciones-slow-network .form-check-label {
    color: #1f3f60;
    margin-bottom: 0.1rem;
}
.cotizaciones-slow-network.is-active {
    border-color: #5b9af0 !important;
    background: linear-gradient(135deg, #edf5ff 0%, #dbeafe 100%) !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
}
.cotizaciones-slow-network.is-active .form-check-label {
    color: #0b4da2;
}
.cotizaciones-slow-network.is-active .small {
    color: #335f93 !important;
}
.alerta-resumen-cotizaciones {
    display: flex;
    gap: 1.25rem;
    justify-content: center;
    align-items: center;
    margin: 1rem 0 1.25rem;
    flex-wrap: wrap;
}
.alerta-resumen-cotizaciones.has-vencidos {
    background: linear-gradient(90deg, rgba(220,53,69,.08), rgba(253,126,20,.06));
    border: 1px solid rgba(220,53,69,.22);
    border-radius: 12px;
    padding: .55rem .85rem;
}
.alerta-circle {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 2px solid rgba(0,0,0,0.12);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.alerta-circle:hover { transform: translateY(-2px); }
.alerta-circle.active { outline: 3px solid rgba(13,110,253,.25); }
.alerta-circle.red { background: #dc3545; }
.alerta-circle.orange { background: #fd7e14; }
.alerta-circle.green { background: #198754; }
.alerta-circle.pulse-red {
    animation: alertaPulseRed 1.1s ease-in-out infinite;
}
@keyframes alertaPulseRed {
    0% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    50% {
        transform: scale(1.12);
        box-shadow: 0 0 0 10px rgba(220,53,69,.18);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
}
.alerta-caption {
    font-size: .8rem;
    color: #4b5563;
    text-align: center;
    margin-top: .25rem;
}
.alerta-hint {
    margin-top: .2rem;
    font-size: .72rem;
    font-weight: 700;
    color: #dc3545;
    text-align: center;
    opacity: 0;
    transition: opacity .2s ease;
}
.alerta-hint.show {
    opacity: 1;
}
@media (max-width: 768px) {
    .cotizaciones-filter-actions {
        align-items: stretch;
    }
    .cotizaciones-main-actions {
        width: 100%;
        margin-left: 0;
    }
    .cotizaciones-main-actions .btn {
        width: 100%;
        min-width: 0;
    }
    .cotizaciones-slow-network {
        width: 100%;
        max-width: none;
    }
    .table-responsive { display: none; }
    .cards-container { display: block; }
    .mobile-pagination-cotizaciones {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 1.5rem 0 2rem 0;
        width: 100%;
    }
    .cotizacion-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: 1px solid #e3e6f0;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.3s ease-out;
    }
    .cotizacion-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    .cotizacion-codigo {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .cotizacion-nombre {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    .info-item {
        margin-bottom: 0.5rem;
    }
    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 500;
        margin-right: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }
    .info-value {
        font-size: 0.95rem;
        color: #2c3e50;
        font-weight: 500;
        display: inline-block;
    }
    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
        border: none;
        display: inline-block;
    }
    .cotizacion-selector-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .cotizacion-acciones-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .cotizacion-acciones-row .btn-cotizacion-accion {
        margin-right: 0;
        margin-bottom: 0;
    }
    .cotizacion-acciones-row .badge {
        margin: 0;
    }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<div class="cotizaciones-filters">
    <div class="row">
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🔍 DNI</label>
            <input type="text" id="filtroDni" class="form-control" placeholder="Buscar por DNI">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🏢 Empresa</label>
            <select id="filtroEmpresa" class="form-select">
                <option value="">Seleccionar empresa...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🤝 Convenio</label>
            <select id="filtroConvenio" class="form-select">
                <option value="">Seleccionar convenio...</option>
                <?php foreach ($convenios as $conv): ?>
                    <option value="<?= $conv['id'] ?>"><?= htmlspecialchars($conv['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">👨‍🔬 Usuario resultados</label>
            <select id="filtroUsuarioResultados" class="form-select">
                <option value="">Todos</option>
                <option value="unico">Único</option>
                <option value="multiple">Múltiples</option>
                <option value="sin_asignar">Sin asignar</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha desde</label>
            <input type="date" id="filtroFechaDesde" class="form-control">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha hasta</label>
            <input type="date" id="filtroFechaHasta" class="form-control">
        </div>
        <div class="col-12 mb-2">
            <div class="cotizaciones-filter-actions">
                <div class="cotizaciones-slow-network w-100 bg-white rounded px-3 py-2 border">
                    <input class="form-check-input" type="checkbox" id="modoRedLentaToggle">
                    <div class="cotizaciones-slow-network-copy">
                        <label class="form-check-label fw-semibold" for="modoRedLentaToggle">Modo red lenta</label>
                        <div class="small text-muted">Reduce auto-recargas para conexiones inestables.</div>
                    </div>
                </div>
                <div class="cotizaciones-main-actions">
                    <button id="btnLimpiarFiltros" class="btn btn-outline-secondary" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
                    <button id="btnActualizarAhora" class="btn btn-primary" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="alerta-resumen-cotizaciones" id="alertaResumenCotizaciones">
    <div>
        <button type="button" class="alerta-circle red" id="alertaFiltroVencido" data-alerta="vencido" title="Filtrar vencidos">0</button>
        <div class="alerta-caption">Vencidos</div>
        <div class="alerta-hint" id="alertaVencidosHint">¡Atención!</div>
    </div>
    <div>
        <button type="button" class="alerta-circle orange" id="alertaFiltroPorVencer" data-alerta="por_vencer" title="Filtrar por vencer">0</button>
        <div class="alerta-caption">Por vencer</div>
    </div>
    <div>
        <button type="button" class="alerta-circle green" id="alertaFiltroEnTiempo" data-alerta="en_tiempo" title="Filtrar en tiempo">0</button>
        <div class="alerta-caption">En tiempo</div>
    </div>
</div>
<div>
        <div class="table-responsive">
            <table id="tablaCotizaciones" class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllCotizaciones" title="Seleccionar todo"></th>
                        <th>Código Cliente</th>
                        <th>Paciente</th>
                        <th>DNI</th>
                        <th>Fecha</th>
                        <?php if (!$esModoSisCot): ?>
                        <th>Referencia</th>
                        <?php endif; ?>
                        <?php if (!$esModoSisCot): ?>
                        <th>Total</th>
                        <th>Estado Pago</th>
                        <?php endif; ?>
                        <th>Estado Examen</th>
                        <th>Usuario resultados</th>
                        <th>Servicio</th>
                        <th>Rol Creador</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- El contenido de la tabla será llenado dinámicamente por DataTables server-side -->
                </tbody>
            </table>
                        <div class="mt-3">
                                <button id="btnPagoMasivo" class="btn btn-success" disabled data-bs-toggle="modal" data-bs-target="#modalPagoMasivo">Pago masivo (<span id="totalPagoMasivo"><?= htmlspecialchars(money_format_local(0, $currencyCfg)) ?></span>)</button>
                        </div>

                        <!-- Modal de confirmación pago masivo -->
                        <div class="modal fade" id="modalPagoMasivo" tabindex="-1" aria-labelledby="modalPagoMasivoLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalPagoMasivoLabel">Confirmar pago masivo</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>¿Desea registrar el pago masivo para <span id="cantidadSeleccionadas">0</span> cotizaciones seleccionadas?</p>
                                        <p>Totales a pagar: <strong id="modalTotalPago"><?= htmlspecialchars(money_format_local(0, $currencyCfg)) ?></strong></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-success" id="confirmarPagoMasivo">Confirmar pago</button>
                                    </div>
                                </div>
                            </div>
                        </div>
        </div>
    </div>
    <form id="formAnularCotizacion" method="post" action="dashboard.php?action=eliminar_cotizacion" style="display:none;">
        <input type="hidden" name="id" id="anularCotizacionId" value="">
        <input type="hidden" name="motivo" id="anularCotizacionMotivo" value="">
    </form>
    <!-- Buscador y cards para móvil -->
<div class="mb-3 d-block d-md-none">
    <div class="input-group">
        <input type="text" id="buscadorCotizacionesMovil" class="form-control" placeholder="Buscar por paciente, código, referencia...">
        <button class="btn btn-primary" type="button" id="btnClearCotizacionesMovil"><i class="bi bi-x"></i></button>
    </div>
</div>
<div class="d-block d-md-none mb-3" id="totalesCotizacionesMovil">
    <div class="card p-3 bg-info bg-opacity-10 border-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span class="fw-bold">Total a pagar: <span class="text-success" id="totalPagoMasivoMovil"><?= htmlspecialchars(money_format_local(0, $currencyCfg)) ?></span></span>
            <button id="btnPagoMasivoMovil" class="btn btn-success mt-2 mt-md-0" disabled data-bs-toggle="modal" data-bs-target="#modalPagoMasivoMovil">Pago masivo (<span id="cantidadSeleccionadasMovil">0</span>)</button>
        </div>
    </div>
</div>

    <!-- jQuery (requerido por DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
// Referencias a elementos DOM usados en el flujo
const btnPagoMasivo = document.getElementById('btnPagoMasivo');
const totalPagoMasivo = document.getElementById('totalPagoMasivo');
const modalTotalPago = document.getElementById('modalTotalPago');
const cantidadSeleccionadas = document.getElementById('cantidadSeleccionadas');
const confirmarPagoMasivo = document.getElementById('confirmarPagoMasivo');
const selectAll = document.getElementById('selectAllCotizaciones');
const puedeAnularCotizacion = <?= $puedeAnularCot ? 'true' : 'false' ?>;
const puedeCompararResultados = <?= $puedeCompararResultados ? 'true' : 'false' ?>;
const puedeEditarResultados = <?= $puedeEditarResultados ? 'true' : 'false' ?>;
const puedeEditarCotizaciones = <?= $puedeEditarCotizaciones ? 'true' : 'false' ?>;
const modoCotizaciones = '<?= $soloAnuladas ? 'anuladas' : 'activas' ?>';
const soloAnuladas = <?= $soloAnuladas ? 'true' : 'false' ?>;
const esModoSisCot = <?= $esModoSisCot ? 'true' : 'false' ?>;
let filtroAlertaEstado = '';
const formatMoneySafe = (value) => (typeof window.formatMoney === 'function')
    ? window.formatMoney(value)
    : `S/ ${Number(value || 0).toFixed(2)}`;

function normalizarRolCreador(rol) {
    const raw = (rol || '').toString().trim();
    if (!raw) return 'Sin rol';
    return raw.charAt(0).toUpperCase() + raw.slice(1).toLowerCase();
}

function resolverCanalCaptura(row) {
    const rol = (row.rol_creador || '').toString().toLowerCase().trim();
    if (rol === 'convenio') return { label: 'Canal: Portal Convenio', badge: 'bg-info text-dark' };
    if (rol === 'empresa') return { label: 'Canal: Portal Empresa', badge: 'bg-success' };
    if (rol === 'cliente') return { label: 'Canal: Portal Cliente', badge: 'bg-secondary' };
    if (rol === 'recepcionista' || rol === 'admin' || rol === 'laboratorista') return { label: 'Canal: Central', badge: 'bg-dark' };
    return { label: `Canal: ${normalizarRolCreador(rol)}`, badge: 'bg-secondary' };
}

function resolverContextoCotizacion(row) {
    if (parseInt(row.id_empresa || 0, 10) > 0) {
        return {
            label: 'Empresa',
            badge: 'bg-success'
        };
    }
    if (parseInt(row.id_convenio || 0, 10) > 0) {
        return {
            label: 'Convenio',
            badge: 'bg-info text-dark'
        };
    }
    const rol = (row.rol_creador || '').toString().toLowerCase().trim();
    if (rol === 'cliente') {
        return { label: 'Cliente', badge: 'bg-secondary' };
    }
    return { label: 'Particular', badge: 'bg-secondary' };
}

function renderRolCreadorConOrigen(row) {
    const rol = normalizarRolCreador(row.rol_creador || '');
    const rolRaw = (row.rol_creador || '').toString().toLowerCase().trim();
    if (rolRaw === 'admin' || rolRaw === 'recepcionista' || rolRaw === 'laboratorista') {
        return `<div>${rol}</div>`;
    }
    const contexto = resolverContextoCotizacion(row);
    return `<div>${rol}</div><div class="mt-1"><span class='badge ${contexto.badge}'>${contexto.label}</span></div>`;
}
const MAX_SELECT_ALL_LENGTH = 5000;
let resumenAlertasXhr = null;
let resumenAlertasTimer = null;
let filtrosReloadTimer = null;
const SLOW_NETWORK_KEY = 'cotizaciones_slow_network_mode';
let isSlowNetworkMode = false;

function readSlowNetworkMode() {
    return localStorage.getItem(SLOW_NETWORK_KEY) === '1';
}

function writeSlowNetworkMode(enabled) {
    localStorage.setItem(SLOW_NETWORK_KEY, enabled ? '1' : '0');
}

function setSlowModeUi(enabled) {
    const btnActualizarAhora = document.getElementById('btnActualizarAhora');
    const toggle = document.getElementById('modoRedLentaToggle');
    const slowNetworkBox = document.querySelector('.cotizaciones-slow-network');
    if (toggle) {
        toggle.checked = enabled;
    }
    if (slowNetworkBox) {
        slowNetworkBox.classList.toggle('is-active', enabled);
    }
    if (btnActualizarAhora) {
        btnActualizarAhora.classList.toggle('btn-warning', enabled);
        btnActualizarAhora.classList.toggle('btn-primary', !enabled);
        btnActualizarAhora.innerHTML = enabled
            ? '<i class="bi bi-wifi-off"></i> Actualizar manual'
            : '<i class="bi bi-arrow-clockwise"></i> Actualizar';
    }
}

function buildPdfDownloadUrl(cotizacionId) {
    return `resultados/descarga-pdf.php?cotizacion_id=${encodeURIComponent(cotizacionId)}&_ts=${Date.now()}`;
}

function renderEstadoExamenBadge(row) {
    const estado = row.estado_examen;
    const porcentaje = row.porcentaje_examen;
    let base = "<span class='badge bg-secondary'>Sin datos</span>";

    if (estado === 'completado_100' || estado === 'pendiente_100') {
        base = "<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado 100%</span>";
    } else if (estado === 'pendiente_0') {
        base = "<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente 0%</span>";
    } else if (estado && estado.startsWith('pendiente_')) {
        base = `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente ${porcentaje}%</span>`;
    }

    const alertaTotal = parseInt(row.alerta_total || 0, 10);
    if (!alertaTotal) {
        return base;
    }

    if (row.alerta_estado === 'vencido') {
        return `${base}<div class='mt-1'><span class='badge bg-danger'><i class='bi bi-bell-fill'></i> Vencido (${row.alerta_vencido})</span></div>`;
    }
    if (row.alerta_estado === 'por_vencer') {
        return `${base}<div class='mt-1'><span class='badge bg-warning text-dark'><i class='bi bi-bell'></i> Por vencer (${row.alerta_por_vencer})</span></div>`;
    }
    return `${base}<div class='mt-1'><span class='badge bg-success'><i class='bi bi-bell'></i> En tiempo (${row.alerta_en_tiempo})</span></div>`;
}

function actualizarResumenAlertas() {
    if (resumenAlertasXhr && typeof resumenAlertasXhr.abort === 'function') {
        resumenAlertasXhr.abort();
    }
    resumenAlertasXhr = $.ajax({
        url: 'dashboard.php?action=cotizaciones_api',
        type: 'GET',
        dataType: 'json',
        data: {
            resumen_alertas: 1,
            modo: modoCotizaciones,
            filtro_dni: $('#filtroDni').val(),
            filtro_empresa: $('#filtroEmpresa').val(),
            filtro_convenio: $('#filtroConvenio').val(),
            filtro_usuario_resultados: $('#filtroUsuarioResultados').val(),
            filtro_fecha_desde: $('#filtroFechaDesde').val(),
            filtro_fecha_hasta: $('#filtroFechaHasta').val(),
            filtro_alerta: filtroAlertaEstado
        },
        success: function(resp) {
            const vencido = parseInt(resp.vencido || 0, 10);
            const porVencer = parseInt(resp.por_vencer || 0, 10);
            const enTiempo = parseInt(resp.en_tiempo || 0, 10);

            const $btnVencido = $('#alertaFiltroVencido');
            $btnVencido.text(vencido);
            $btnVencido.toggleClass('pulse-red', vencido > 0);
            $('#alertaVencidosHint').toggleClass('show', vencido > 0);
            $('#alertaResumenCotizaciones').toggleClass('has-vencidos', vencido > 0);

            $('#alertaFiltroPorVencer').text(porVencer);
            $('#alertaFiltroEnTiempo').text(enTiempo);
        },
        complete: function() {
            resumenAlertasXhr = null;
        }
    });
}

function scheduleActualizarResumenAlertas(delay = 250) {
    if (isSlowNetworkMode) {
        return;
    }
    if (resumenAlertasTimer) {
        clearTimeout(resumenAlertasTimer);
    }
    resumenAlertasTimer = setTimeout(function() {
        actualizarResumenAlertas();
    }, delay);
}

function actualizarEstadoBotonesAlerta() {
    $('.alerta-circle').removeClass('active');
    if (filtroAlertaEstado) {
        $(`.alerta-circle[data-alerta="${filtroAlertaEstado}"]`).addClass('active');
    }
}

function solicitarAnulacionCotizacion(id) {
    if (!puedeAnularCotizacion) return;
    const motivo = window.prompt('Ingresa el motivo de anulación:');
    if (motivo === null) return;
    const motivoTrim = (motivo || '').trim();
    if (motivoTrim === '') {
        alert('Debes ingresar un motivo para continuar.');
        return;
    }
    const confirmar = window.confirm('¿Seguro que deseas anular esta cotización? Esta acción aplicará reversos de pagos y consumos.');
    if (!confirmar) return;

    const form = document.getElementById('formAnularCotizacion');
    document.getElementById('anularCotizacionId').value = id;
    document.getElementById('anularCotizacionMotivo').value = motivoTrim;
    form.submit();
}
// --- Utilidades para selección manual ---
function getSeleccionadasManual() {
    try {
        return JSON.parse(localStorage.getItem('cotizacionesManualSeleccionadasDesktop') || '[]');
    } catch (e) {
        return [];
    }
}
function setSeleccionadasManual(arr) {
    localStorage.setItem('cotizacionesManualSeleccionadasDesktop', JSON.stringify(arr));
}
function getCheckboxes() {
    return Array.from(document.querySelectorAll('.cotizacion-checkbox'));
}
function restaurarSeleccionManual() {
    const seleccionadas = getSeleccionadasManual();
    getCheckboxes().forEach(cb => {
        cb.checked = seleccionadas.includes(cb.getAttribute('data-id'));
    });
}

// --- Selección global vs manual ---
function setSeleccionGlobal(flag) {
    localStorage.setItem('cotizacionesSeleccionGlobal', flag ? '1' : '0');
}
function getSeleccionGlobal() {
    return localStorage.getItem('cotizacionesSeleccionGlobal') === '1';
}


    $(document).ready(function() {
    isSlowNetworkMode = readSlowNetworkMode();
    setSlowModeUi(isSlowNetworkMode);

    // Forzar ajuste visual de DataTables y botones de acciones al cambiar tamaño de pantalla
    let lastIsMobile = window.innerWidth <= 768;
    $(window).on('resize', function() {
        const isMobile = window.innerWidth <= 768;
        if ($.fn.dataTable.isDataTable('#tablaCotizaciones')) {
            $('#tablaCotizaciones').DataTable().columns.adjust().draw(false);
        }
        if (isMobile !== lastIsMobile && isMobile && typeof cargarCardsCotizaciones === 'function') {
            const busquedaMovil = $('#buscadorCotizacionesMovil').val() || '';
            cargarCardsCotizaciones(1, busquedaMovil);
        }
        lastIsMobile = isMobile;
    });
        const cotizacionesRestorePending = localStorage.getItem('cotizaciones_restore_pending') === '1';
        const cotizacionesRestoreStartRaw = parseInt(localStorage.getItem('cotizaciones_restore_start') || '0', 10);
        const cotizacionesRestoreStart = Number.isFinite(cotizacionesRestoreStartRaw) && cotizacionesRestoreStartRaw >= 0
            ? cotizacionesRestoreStartRaw
            : 0;

        var tabla = $('#tablaCotizaciones').DataTable({
            "serverSide": true,
            "processing": true,
            "ajax": {
                "url": "dashboard.php?action=cotizaciones_api",
                "type": "GET",
                "data": function(d) {
                    d.filtro_dni = $('#filtroDni').val();
                    d.filtro_empresa = $('#filtroEmpresa').val();
                    d.filtro_convenio = $('#filtroConvenio').val();
                    d.filtro_usuario_resultados = $('#filtroUsuarioResultados').val();
                    d.filtro_fecha_desde = $('#filtroFechaDesde').val();
                    d.filtro_fecha_hasta = $('#filtroFechaHasta').val();
                    d.filtro_alerta = filtroAlertaEstado;
                    d.modo = modoCotizaciones;
                }
            },
            "pageLength": 3,
            "displayStart": cotizacionesRestorePending ? cotizacionesRestoreStart : 0,
            "lengthMenu": [[3, 5, 10], [3, 5, 10]],
            "order": [],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            // "responsive": true,
            "columns": [
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `<input type='checkbox' class='cotizacion-checkbox' data-id='${row.id}' data-saldo='${parseFloat(row.saldo) || 0}'>`;
                    }
                },
                { "data": "codigo_cliente" },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        return `${row.nombre_cliente || ''} ${row.apellido_cliente || ''}`;
                    }
                },
                { "data": "dni" },
                { "data": "fecha" },
                <?php if (!$esModoSisCot): ?>
                {
                    "data": "referencia",
                    "render": function(data, type, row) {
                        // Color único por empresa/convenio
                        function stringToColor(str) {
                            let hash = 0;
                            for (let i = 0; i < str.length; i++) {
                                hash = str.charCodeAt(i) + ((hash << 5) - hash);
                            }
                            let color = '#';
                            for (let i = 0; i < 3; i++) {
                                let value = (hash >> (i * 8)) & 0xFF;
                                color += ('00' + value.toString(16)).substr(-2);
                            }
                            return color;
                        }
                        if (row.referencia && row.referencia !== 'Particular') {
                            const color = stringToColor(row.referencia);
                            const textColor = '#fff';
                            return `<span class='badge' style='background:${color};color:${textColor};'>${row.referencia}</span>`;
                        } else {
                            return `<span class='badge bg-secondary'>Particular</span>`;
                        }
                    }
                },
                <?php endif; ?>
                <?php if (!$esModoSisCot): ?>
                {
                    "data": "total",
                    "render": function(data) {
                        return formatMoneySafe(data);
                    }
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        // Calcular estado de pago usando total pagado y descarga anticipada
                        const total = parseFloat(row.total) || 0;
                        const pagado = parseFloat(row.total_pagado) || 0;
                        if (soloAnuladas) {
                            return `<span class='badge bg-secondary'><i class='bi bi-x-octagon'></i> Anulada</span>`;
                        } else if (row.tiene_descarga_anticipada == 1) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-clock'></i> Descarga anticipada</span>`;
                        } else if (pagado >= total && total > 0) {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
                        } else if (pagado > 0 && pagado < total) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Parcial</span>`;
                        } else {
                            return `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente</span>`;
                        }
                    }
                },
                <?php endif; ?>
                { "data": null, "render": function(row) { return renderEstadoExamenBadge(row); } },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        const tipo = (row.resultados_usuario_tipo || 'sin_asignar');
                        const nombre = (row.resultados_usuario_nombre || '').trim();
                        const total = parseInt(row.resultados_usuario_total || 0, 10);
                        if (tipo === 'unico' && nombre) {
                            return `<span class='badge bg-primary'>${nombre}</span>`;
                        }
                        if (tipo === 'multiple' && total > 1) {
                            return `<span class='badge bg-warning text-dark'>Múltiples (${total})</span>`;
                        }
                        return `<span class='text-muted'>—</span>`;
                    }
                },
                {
                    "data": "nombre_servicio",
                    "render": function(data) {
                        return data ? `<span class='badge bg-info text-dark'>${data}</span>` : '<span class="text-muted">—</span>';
                    }
                },
                { "data": null,
                    "render": function(data, type, row) {
                        return renderRolCreadorConOrigen(row);
                    }
                },
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let acciones = '';
                        acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm btn-cotizacion-accion' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
                        if (puedeCompararResultados && parseInt(row.id_cliente || 0, 10) > 0) {
                            acciones += `<a href='dashboard.php?vista=comparar_resultados_cliente&id=${row.id_cliente}' class='btn btn-secondary btn-sm btn-cotizacion-accion' title='Comparar resultados'><i class='bi bi-graph-up-arrow'></i></a>`;
                        }
                        if (!soloAnuladas && puedeEditarCotizaciones) {
                            acciones += `<a href='dashboard.php?vista=form_cotizacion&id=${row.id}&edit=1' class='btn btn-dark btn-sm btn-cotizacion-accion' title='Editar cotización'><i class='bi bi-file-earmark-medical'></i></a>`;
                        }
                        if (row.modificada == 1) {
                            acciones += `<span class='badge bg-warning text-dark ms-1' title='Cotización modificada'><i class='bi bi-pencil'></i> Modificada</span>`;
                        }
                        if (!soloAnuladas) {
                            if (puedeEditarResultados) {
                                acciones += `<a href='dashboard.php?vista=formulario&cotizacion_id=${row.id}' class='btn btn-primary btn-sm btn-cotizacion-accion' title='Editar o agregar resultados'><i class='bi bi-pencil-square'></i></a>`;
                            }
                            if (parseInt(row.es_sis || 0, 10) !== 1) {
                                acciones += `<a href='dashboard.php?vista=pago_cotizacion&id=${row.id}' class='btn btn-warning btn-sm btn-cotizacion-accion' title='Registrar pago'><i class='bi bi-cash-coin'></i></a>`;
                            } else {
                                acciones += `<span class='badge bg-success text-white ms-1' title='Atención SIS'><i class='bi bi-shield-check'></i> SIS</span>`;
                            }
                        }
                        if (!soloAnuladas && puedeAnularCotizacion) {
                            acciones += `<button type='button' class='btn btn-danger btn-sm btn-cotizacion-accion' title='Anular cotización' onclick='solicitarAnulacionCotizacion(${row.id})'><i class='bi bi-x-octagon'></i></button>`;
                        }
                        acciones += `<a href='${buildPdfDownloadUrl(row.id)}' class='btn btn-success btn-sm btn-cotizacion-accion' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
                        return acciones;
                    }
                }
            ]
        });

        if (cotizacionesRestorePending) {
            $('#tablaCotizaciones').one('draw.dt', function() {
                localStorage.removeItem('cotizaciones_restore_pending');
            });
        }

        $(document).on('click', 'a.btn-cotizacion-accion', function() {
            const href = ($(this).attr('href') || '').trim();
            const target = (($(this).attr('target') || '').trim()).toLowerCase();
            if (!href || target === '_blank') {
                return;
            }
            if (href.indexOf('dashboard.php?vista=') !== 0) {
                return;
            }

            const pageInfo = tabla.page.info();
            if (!pageInfo || typeof pageInfo.start === 'undefined') {
                return;
            }
            localStorage.setItem('cotizaciones_restore_start', String(pageInfo.start));
            localStorage.setItem('cotizaciones_restore_pending', '1');
        });

        // Recargar tabla al cambiar filtros (debounce para evitar tormenta de requests)
        const recargarTablaConResumen = function() {
            tabla.ajax.reload(null, false);
            if (isSlowNetworkMode) {
                actualizarResumenAlertas();
            } else {
                scheduleActualizarResumenAlertas();
            }
        };
        $('#filtroDni').on('keyup', function() {
            if (isSlowNetworkMode) {
                return;
            }
            if (filtrosReloadTimer) {
                clearTimeout(filtrosReloadTimer);
            }
            filtrosReloadTimer = setTimeout(recargarTablaConResumen, 300);
        });
        $('#filtroEmpresa, #filtroConvenio, #filtroUsuarioResultados, #filtroFechaDesde, #filtroFechaHasta').on('change', function() {
            if (isSlowNetworkMode) {
                return;
            }
            recargarTablaConResumen();
        });
        // Limpiar filtros
        $('#btnLimpiarFiltros').on('click', function() {
            $('#filtroDni').val('');
            $('#filtroEmpresa').val('');
            $('#filtroConvenio').val('');
            $('#filtroUsuarioResultados').val('');
            $('#filtroFechaDesde').val('');
            $('#filtroFechaHasta').val('');
            filtroAlertaEstado = '';
            actualizarEstadoBotonesAlerta();
            tabla.ajax.reload();
            if (isSlowNetworkMode) {
                actualizarResumenAlertas();
            } else {
                scheduleActualizarResumenAlertas();
            }
        });
        $('#btnBuscarCotizaciones, #btnLimpiarCotizaciones').on('click', function() {
            tabla.ajax.reload();
            if (isSlowNetworkMode) {
                actualizarResumenAlertas();
            } else {
                scheduleActualizarResumenAlertas();
            }
        });

        $('#btnActualizarAhora').on('click', function() {
            tabla.ajax.reload(null, false);
            actualizarResumenAlertas();
            if (window.innerWidth < 768 && typeof cargarCardsCotizaciones === 'function') {
                const busquedaMovil = $('#buscadorCotizacionesMovil').val() || '';
                cargarCardsCotizaciones(1, busquedaMovil);
            }
        });

        $('#modoRedLentaToggle').on('change', function() {
            isSlowNetworkMode = !!this.checked;
            writeSlowNetworkMode(isSlowNetworkMode);
            setSlowModeUi(isSlowNetworkMode);
            tabla.ajax.reload(null, false);
            actualizarResumenAlertas();
        });

        $('.alerta-circle').on('click', function() {
            const target = $(this).data('alerta');
            filtroAlertaEstado = (filtroAlertaEstado === target) ? '' : target;
            actualizarEstadoBotonesAlerta();
            if (window.innerWidth < 768 && typeof cargarCardsCotizaciones === 'function') {
                const busquedaMovil = $('#buscadorCotizacionesMovil').val() || '';
                cargarCardsCotizaciones(1, busquedaMovil);
            } else {
                tabla.ajax.reload();
            }
            if (isSlowNetworkMode) {
                actualizarResumenAlertas();
            } else {
                scheduleActualizarResumenAlertas();
            }
        });

        function actualizarTotal() {
    let total = 0;
    let count = 0;
    let algunoSeleccionado = false;
    const seleccionadas = getSeleccionadasManual();
    if (seleccionadas.length > 0) {
        // Siempre obtener los saldos de todas las seleccionadas (aunque no estén en la página actual)
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                ids: seleccionadas.join(','),
                length: seleccionadas.length,
                start: 0,
                draw: 1,
                lite: 1,
                modo: modoCotizaciones
            },
            success: function(resp) {
                let data = resp.data || [];
                total = 0;
                count = 0;
                data.forEach(row => {
                    const saldo = parseFloat(row.saldo);
                    if (saldo > 0) {
                        total += saldo;
                        count++;
                        algunoSeleccionado = true;
                    }
                });
                totalPagoMasivo.textContent = formatMoneySafe(total);
                btnPagoMasivo.disabled = !algunoSeleccionado;
                modalTotalPago.textContent = formatMoneySafe(total);
                cantidadSeleccionadas.textContent = count;
            }
        });
    } else {
        totalPagoMasivo.textContent = formatMoneySafe(0);
        btnPagoMasivo.disabled = true;
        modalTotalPago.textContent = formatMoneySafe(0);
        cantidadSeleccionadas.textContent = 0;
    }
        }

        // Evento delegado para checkboxes
        $(document).on('change', '.cotizacion-checkbox', function() {
            // Si se marca/desmarca manualmente, desactivar selección global
            setSeleccionGlobal(false);
            let seleccionadas = getSeleccionadasManual();
            const id = $(this).attr('data-id');
            if (this.checked) {
                if (!seleccionadas.includes(id)) seleccionadas.push(id);
            } else {
                seleccionadas = seleccionadas.filter(x => x !== id);
            }
            setSeleccionadasManual(seleccionadas);
            restaurarSeleccionManual();
            actualizarTotal();
        });

        // Evento para selectAll
        selectAll.addEventListener('change', function() {
        if (selectAll.checked) {
            setSeleccionGlobal(true);
            // Tomar los valores actuales de todos los filtros
            const filtroEmpresa = $('#filtroEmpresa').val();
            const filtroConvenio = $('#filtroConvenio').val();
            const filtroDni = $('#filtroDni').val();
            const filtroFechaDesde = $('#filtroFechaDesde').val();
            const filtroFechaHasta = $('#filtroFechaHasta').val();
            $.ajax({
                url: 'dashboard.php?action=cotizaciones_api',
                type: 'GET',
                data: {
                    filtro_fecha_desde: filtroFechaDesde,
                    filtro_fecha_hasta: filtroFechaHasta,
                    filtro_empresa: filtroEmpresa,
                    filtro_convenio: filtroConvenio,
                    filtro_dni: filtroDni,
                    length: MAX_SELECT_ALL_LENGTH,
                    start: 0,
                    draw: 1,
                    lite: 1,
                    modo: modoCotizaciones
                },
                success: function(resp) {
                    let data = resp.data || [];
                    // Filtrar solo cotizaciones con saldo pendiente
                    const ids = data.filter(row => parseFloat(row.saldo) > 0).map(row => row.id);
                    setSeleccionadasManual(ids);
                    restaurarSeleccionManual();
                    actualizarTotal();
                }
            });
        } else {
            setSeleccionGlobal(false);
            setSeleccionadasManual([]);
            getCheckboxes().forEach(cb => { cb.checked = false; });
            actualizarTotal();
        }
        });

        // Actualizar total cada vez que se dibuja la tabla
        $('#tablaCotizaciones').on('draw.dt', function() {
            // Mantener el estado del checkbox global si hay seleccionadas
            const seleccionadas = getSeleccionadasManual();
            selectAll.checked = seleccionadas.length > 0;
            restaurarSeleccionManual();
            actualizarTotal();
        });

        // Actualizar datos del modal al abrirlo
        btnPagoMasivo.addEventListener('click', function() {
            actualizarTotal();
        });

        // Lógica para confirmar pago masivo
        confirmarPagoMasivo.addEventListener('click', function() {
            const seleccionadas = getSeleccionadasManual();
            if (seleccionadas.length === 0) return;

            fetch('cotizaciones/api/pago_masivo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cotizaciones: seleccionadas })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pago masivo realizado correctamente',
                        showConfirmButton: false,
                        timer: 1800
                    });
                    setSeleccionadasManual([]);
                    setTimeout(() => location.reload(), 1800);
                } else {
                    if (data.redirect_url) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Caja no disponible',
                            text: data.message || 'Debes abrir caja para continuar.',
                            confirmButtonText: 'Ir a Contabilidad'
                        }).then(() => {
                            window.location.href = data.redirect_url;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al realizar el pago masivo',
                            text: data.message || '',
                        });
                    }
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión al procesar el pago masivo',
                });
            });
        });

        actualizarEstadoBotonesAlerta();
        if (isSlowNetworkMode) {
            actualizarResumenAlertas();
        } else {
            scheduleActualizarResumenAlertas(100);
        }
    });
    </script>

<div class="d-block d-md-none mb-2" id="selectAllCotizacionesMovilContainer">
    <label class="form-check-label" for="selectAllCotizacionesMovil">
        <input type="checkbox" id="selectAllCotizacionesMovil" class="form-check-input me-2"> Seleccionar todo
    </label>
</div>
<div class="cards-container" id="cardsCotizacionesAjax"></div>
<!-- Modal de confirmación pago masivo móvil -->
<div class="modal fade" id="modalPagoMasivoMovil" tabindex="-1" aria-labelledby="modalPagoMasivoMovilLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagoMasivoMovilLabel">Confirmar pago masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Desea registrar el pago masivo para <span id="cantidadSeleccionadasMovilModal">0</span> cotizaciones seleccionadas?</p>
                <p>Total a pagar: <strong id="modalTotalPagoMovil"><?= htmlspecialchars(money_format_local(0, $currencyCfg)) ?></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmarPagoMasivoMovil">Confirmar pago</button>
            </div>
        </div>
    </div>
</div>
    <script>
// --- Utilidades selección manual móvil ---
function getSeleccionadasManualMovil() {
    try {
        return JSON.parse(localStorage.getItem('cotizacionesManualSeleccionadasMovil') || '[]');
    } catch (e) { return []; }
}
function setSeleccionadasManualMovil(arr) {
    localStorage.setItem('cotizacionesManualSeleccionadasMovil', JSON.stringify(arr));
}
function actualizarTotalMovil() {
    let total = 0;
    let count = 0;
    let algunoSeleccionado = false;
    const seleccionadas = getSeleccionadasManualMovil();
    if (seleccionadas.length > 0) {
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                ids: seleccionadas.join(','),
                length: seleccionadas.length,
                start: 0,
                draw: 1,
                lite: 1,
                modo: modoCotizaciones
            },
            success: function(resp) {
                let data = resp.data || [];
                total = 0;
                count = 0;
                data.forEach(row => {
                    const saldo = parseFloat(row.saldo);
                    if (saldo > 0) {
                        total += saldo;
                        count++;
                        algunoSeleccionado = true;
                    }
                });
                document.getElementById('totalPagoMasivoMovil').textContent = formatMoneySafe(total);
                document.getElementById('btnPagoMasivoMovil').disabled = !algunoSeleccionado;
                document.getElementById('modalTotalPagoMovil').textContent = formatMoneySafe(total);
                document.getElementById('cantidadSeleccionadasMovil').textContent = count;
                document.getElementById('cantidadSeleccionadasMovilModal').textContent = count;
            }
        });
    } else {
        document.getElementById('totalPagoMasivoMovil').textContent = formatMoneySafe(0);
        document.getElementById('btnPagoMasivoMovil').disabled = true;
        document.getElementById('modalTotalPagoMovil').textContent = formatMoneySafe(0);
        document.getElementById('cantidadSeleccionadasMovil').textContent = 0;
        document.getElementById('cantidadSeleccionadasMovilModal').textContent = 0;
    }
}
function renderCotizacionCard(row) {
    // Badge referencia
    function stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        let color = '#';
        for (let i = 0; i < 3; i++) {
            let value = (hash >> (i * 8)) & 0xFF;
            color += ('00' + value.toString(16)).substr(-2);
        }
        return color;
    }
    let referenciaBadge = '';
    if (row.referencia && row.referencia !== 'Particular') {
        const color = stringToColor(row.referencia);
        referenciaBadge = `<span class='badge' style='background:${color};color:#fff;'>${row.referencia}</span>`;
    } else {
        referenciaBadge = `<span class='badge bg-secondary'>Particular</span>`;
    }
    // Estado pago
    const total = parseFloat(row.total) || 0;
    const pagado = parseFloat(row.total_pagado) || 0;
    let estadoPago = '';
    if (soloAnuladas) {
        estadoPago = `<span class='badge bg-secondary'><i class='bi bi-x-octagon'></i> Anulada</span>`;
    } else if (row.tiene_descarga_anticipada == 1) {
        estadoPago = `<span class='badge bg-warning text-dark'><i class='bi bi-clock'></i> Descarga anticipada</span>`;
    } else if (pagado >= total && total > 0) {
        estadoPago = `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
    } else if (pagado > 0 && pagado < total) {
        estadoPago = `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Parcial</span>`;
    } else {
        estadoPago = `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente</span>`;
    }
    // Estado examen con porcentaje
    let estadoExamen = renderEstadoExamenBadge(row);
    // Acciones (todas como en escritorio)
    let acciones = '';
    acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm btn-cotizacion-accion' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
    if (puedeCompararResultados && (parseInt(row.id_cliente || 0, 10)) > 0) {
        acciones += `<a href='dashboard.php?vista=comparar_resultados_cliente&id=${row.id_cliente}' class='btn btn-secondary btn-sm btn-cotizacion-accion' title='Comparar resultados'><i class='bi bi-graph-up-arrow'></i></a>`;
    }
    if (!soloAnuladas && puedeEditarCotizaciones) {
        acciones += `<a href='dashboard.php?vista=form_cotizacion&id=${row.id}&edit=1' class='btn btn-dark btn-sm btn-cotizacion-accion' title='Editar cotización'><i class='bi bi-file-earmark-medical'></i></a>`;
    }
    if (row.modificada == 1) {
        acciones += `<span class='badge bg-warning text-dark ms-1' title='Cotización modificada'><i class='bi bi-pencil'></i> Modif.</span>`;
    }
    if (!soloAnuladas) {
        if (puedeEditarResultados) {
            acciones += `<a href='dashboard.php?vista=formulario&cotizacion_id=${row.id}' class='btn btn-primary btn-sm btn-cotizacion-accion' title='Editar o agregar resultados'><i class='bi bi-pencil-square'></i></a>`;
        }
        if (parseInt(row.es_sis || 0, 10) !== 1) {
            acciones += `<a href='dashboard.php?vista=pago_cotizacion&id=${row.id}' class='btn btn-warning btn-sm btn-cotizacion-accion' title='Registrar pago'><i class='bi bi-cash-coin'></i></a>`;
        } else {
            acciones += `<span class='badge bg-success text-white ms-1' title='Atención SIS'><i class='bi bi-shield-check'></i> SIS</span>`;
        }
    }
    if (!soloAnuladas && puedeAnularCotizacion) {
        acciones += `<button type='button' class='btn btn-danger btn-sm btn-cotizacion-accion' title='Anular cotización' onclick='solicitarAnulacionCotizacion(${row.id})'><i class='bi bi-x-octagon'></i></button>`;
    }
    acciones += `<a href='${buildPdfDownloadUrl(row.id)}' class='btn btn-success btn-sm btn-cotizacion-accion' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
    // Checkbox selección
    const seleccionadas = getSeleccionadasManualMovil();
    const checked = seleccionadas.includes(row.id) ? 'checked' : '';
    return `
    <div class='cotizacion-card mb-3'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <span class='cotizacion-nombre'>${row.nombre_cliente || ''} ${row.apellido_cliente || ''}</span>
            <span class='cotizacion-codigo'>${row.codigo_cliente || ''}</span>
        </div>
        <div class='info-item'><span class='info-label'>DNI</span><span class='info-value'>${row.dni || ''}</span></div>
        <div class='info-item'><span class='info-label'>Fecha</span><span class='info-value'>${row.fecha || ''}</span></div>
        ${esModoSisCot ? '' : `<div class='info-item'><span class='info-label'>Referencia</span><span class='info-value'>${referenciaBadge}</span></div>`}
        ${esModoSisCot ? '' : `<div class='info-item'><span class='info-label'>Total</span><span class='info-value'>${formatMoneySafe(parseFloat(row.total) || 0)}</span></div>`}
        ${esModoSisCot ? '' : `<div class='info-item'><span class='info-label'>Estado Pago</span><span class='info-value'>${estadoPago}</span></div>`}
        <div class='info-item'><span class='info-label'>Estado Examen</span><span class='info-value'>${estadoExamen}</span></div>
        <div class='info-item'><span class='info-label'>Usuario resultados</span><span class='info-value'>${(() => {
            const tipo = (row.resultados_usuario_tipo || 'sin_asignar');
            const nombre = (row.resultados_usuario_nombre || '').trim();
            const total = parseInt(row.resultados_usuario_total || 0, 10);
            if (tipo === 'unico' && nombre) return `<span class='badge bg-primary'>${nombre}</span>`;
            if (tipo === 'multiple' && total > 1) return `<span class='badge bg-warning text-dark'>Múltiples (${total})</span>`;
            return `<span class='text-muted'>—</span>`;
        })()}</span></div>
        <div class='info-item'><span class='info-label'>Creado por</span><div class='info-value'>${renderRolCreadorConOrigen(row)}</div></div>
        <div class='cotizacion-selector-row'>
            <input type='checkbox' class='cotizacion-checkbox-movil' data-id='${row.id}' data-saldo='${parseFloat(row.saldo) || 0}' ${checked}>
            <label class='mb-0'>Seleccionar</label>
        </div>
        <div class='cotizacion-acciones-row'>
            ${acciones}
        </div>
    </div>`;
}
function cargarCardsCotizaciones(pagina = 1, busqueda = '') {
    const porPagina = 3;
    const params = {
        draw: 1,
        start: (pagina - 1) * porPagina,
        length: porPagina,
        search: { value: busqueda },
        filtro_dni: $('#filtroDni').val(),
        filtro_empresa: $('#filtroEmpresa').val(),
        filtro_convenio: $('#filtroConvenio').val(),
        filtro_usuario_resultados: $('#filtroUsuarioResultados').val(),
        filtro_fecha_desde: $('#filtroFechaDesde').val(),
        filtro_fecha_hasta: $('#filtroFechaHasta').val(),
        filtro_alerta: filtroAlertaEstado,
        modo: modoCotizaciones
    };
    $.ajax({
        url: 'dashboard.php?action=cotizaciones_api',
        data: params,
        dataType: 'json',
        success: function(resp) {
            const cont = document.getElementById('cardsCotizacionesAjax');
            cont.innerHTML = '';
            if (resp.data && resp.data.length > 0) {
                resp.data.forEach(row => {
                    cont.innerHTML += renderCotizacionCard(row);
                });
                renderPaginacionCotizacionesMovil(pagina, Math.ceil(resp.recordsFiltered / porPagina), busqueda);
            } else {
                cont.innerHTML = '<div class="text-center py-5">No hay cotizaciones</div>';
                renderPaginacionCotizacionesMovil(1, 1, busqueda);
            }
            actualizarTotalMovil();
            scheduleActualizarResumenAlertas();
        },
        error: function() {
            document.getElementById('cardsCotizacionesAjax').innerHTML = '<div class="alert alert-danger">Error al cargar las cotizaciones.</div>';
        }
    });
}
function renderPaginacionCotizacionesMovil(pagina, totalPaginas, busqueda) {
    let nav = document.getElementById('paginacionCotizacionesMovil');
    if (!nav) {
        nav = document.createElement('nav');
        nav.className = 'mobile-pagination-cotizaciones';
        nav.id = 'paginacionCotizacionesMovil';
        document.getElementById('cardsCotizacionesAjax').after(nav);
    }
    let html = '';
    html += `<button class='page-btn' onclick='cargarCardsCotizaciones(${pagina - 1}, ${JSON.stringify(busqueda)})' ${pagina <= 1 ? 'disabled' : ''}>&#8592;</button>`;
    for (let p = Math.max(1, pagina - 1); p <= Math.min(totalPaginas, pagina + 1); p++) {
        html += `<button class='page-btn${p === pagina ? ' active' : ''}' onclick='cargarCardsCotizaciones(${p}, ${JSON.stringify(busqueda)})'>${p}</button>`;
    }
    html += `<button class='page-btn' onclick='cargarCardsCotizaciones(${pagina + 1}, ${JSON.stringify(busqueda)})' ${pagina >= totalPaginas ? 'disabled' : ''}>&#8594;</button>`;
    nav.innerHTML = html;
}
// --- Checkbox global móvil ---
$(document).on('change', '#selectAllCotizacionesMovil', function() {
    const checked = this.checked;
    if (checked) {
        // Tomar los valores actuales de todos los filtros
        const filtroEmpresa = $('#filtroEmpresa').val();
        const filtroConvenio = $('#filtroConvenio').val();
        const filtroDni = $('#filtroDni').val();
        const filtroFechaDesde = $('#filtroFechaDesde').val();
        const filtroFechaHasta = $('#filtroFechaHasta').val();
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                filtro_fecha_desde: filtroFechaDesde,
                filtro_fecha_hasta: filtroFechaHasta,
                filtro_empresa: filtroEmpresa,
                filtro_convenio: filtroConvenio,
                filtro_usuario_resultados: $('#filtroUsuarioResultados').val(),
                filtro_dni: filtroDni,
                length: MAX_SELECT_ALL_LENGTH,
                start: 0,
                draw: 1,
                lite: 1,
                modo: modoCotizaciones
            },
            success: function(resp) {
                let data = resp.data || [];
                // Filtrar solo cotizaciones con saldo pendiente
                const ids = data.filter(row => parseFloat(row.saldo) > 0).map(row => row.id);
                // Marcar todos los checkboxes visibles
                $('.cotizacion-checkbox-movil').each(function() {
                    $(this).prop('checked', ids.includes($(this).attr('data-id')));
                });
                setSeleccionadasManualMovil(ids);
                actualizarTotalMovil();
            }
        });
    } else {
        setSeleccionadasManualMovil([]);
        $('.cotizacion-checkbox-movil').prop('checked', false);
        actualizarTotalMovil();
    }
});

(function() {
    let lastMode = window.innerWidth < 768 ? 'mobile' : 'desktop';
    let lastBusqueda = '';
    let resizeTimeout = null;
    function isMobile() { return window.innerWidth < 768; }
    function cargarSiMovilCotizaciones(force = false) {
        if (isMobile()) {
            const buscador = document.getElementById('buscadorCotizacionesMovil');
            let busqueda = buscador ? buscador.value : '';
            if (force || lastMode !== 'mobile' || lastBusqueda !== busqueda) {
                cargarCardsCotizaciones(1, busqueda);
                lastMode = 'mobile';
                lastBusqueda = busqueda;
            }
        } else {
            // Limpiar cards y paginación móvil
            const nav = document.getElementById('paginacionCotizacionesMovil');
            if (nav && nav.parentNode) nav.parentNode.removeChild(nav);
            const cont = document.getElementById('cardsCotizacionesAjax');
            if (cont) cont.innerHTML = '';
            lastMode = 'desktop';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        cargarSiMovilCotizaciones(true);
        // Buscador móvil
        const buscador = document.getElementById('buscadorCotizacionesMovil');
        const btnClear = document.getElementById('btnClearCotizacionesMovil');
        if (buscador) {
            buscador.addEventListener('input', function(e) {
                if (isSlowNetworkMode) {
                    return;
                }
                cargarSiMovilCotizaciones(true);
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', function() {
                buscador.value = '';
                cargarCardsCotizaciones(1, '');
            });
        }
        // Filtros avanzados: recargar cards al cambiar filtros
        $('#filtroDni, #filtroEmpresa, #filtroConvenio, #filtroFechaDesde, #filtroFechaHasta').on('change keyup', function() {
            if (isSlowNetworkMode) {
                return;
            }
            cargarSiMovilCotizaciones(true);
        });
        // Selección de cards
        $(document).on('change', '.cotizacion-checkbox-movil', function() {
            let seleccionadas = getSeleccionadasManualMovil();
            const id = $(this).attr('data-id');
            if (this.checked) {
                if (!seleccionadas.includes(id)) seleccionadas.push(id);
            } else {
                seleccionadas = seleccionadas.filter(x => x !== id);
            }
            setSeleccionadasManualMovil(seleccionadas);
            // Actualizar el estado del checkbox global
            const totalCheckboxes = $('.cotizacion-checkbox-movil').length;
            const checkedCheckboxes = $('.cotizacion-checkbox-movil:checked').length;
            $('#selectAllCotizacionesMovil').prop('checked', totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
            actualizarTotalMovil();
        });
        // Pago masivo móvil
        document.getElementById('btnPagoMasivoMovil').addEventListener('click', function() {
            actualizarTotalMovil();
        });
        document.getElementById('confirmarPagoMasivoMovil').addEventListener('click', function() {
            const seleccionadas = getSeleccionadasManualMovil();
            if (seleccionadas.length === 0) return;
            fetch('cotizaciones/api/pago_masivo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cotizaciones: seleccionadas })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pago masivo realizado correctamente',
                        showConfirmButton: false,
                        timer: 1800
                    });
                    setSeleccionadasManualMovil([]);
                    setTimeout(() => location.reload(), 1800);
                } else {
                    if (data.redirect_url) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Caja no disponible',
                            text: data.message || 'Debes abrir caja para continuar.',
                            confirmButtonText: 'Ir a Contabilidad'
                        }).then(() => {
                            window.location.href = data.redirect_url;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al realizar el pago masivo',
                            text: data.message || '',
                        });
                    }
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión al procesar el pago masivo',
                });
            });
        });
    });
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            cargarSiMovilCotizaciones();
        }, 150);
    });
})();
    </script>
</div>

