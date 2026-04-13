<?php
require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../config/currency.php';
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$currencyCfg = currency_get_config($pdo);
?>

<div class="cotizaciones-filters mb-3">
    <div class="row">
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🔍 DNI</label>
            <input type="text" id="filtroDniAnuladas" class="form-control" placeholder="Buscar por DNI">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🏢 Empresa</label>
            <select id="filtroEmpresaAnuladas" class="form-select">
                <option value="">Seleccionar empresa...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🤝 Convenio</label>
            <select id="filtroConvenioAnuladas" class="form-select">
                <option value="">Seleccionar convenio...</option>
                <?php foreach ($convenios as $conv): ?>
                    <option value="<?= $conv['id'] ?>"><?= htmlspecialchars($conv['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha desde</label>
            <input type="date" id="filtroFechaDesdeAnuladas" class="form-control">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha hasta</label>
            <input type="date" id="filtroFechaHastaAnuladas" class="form-control">
        </div>
        <div class="col-md-2 col-sm-12 d-flex align-items-end mb-2">
            <button id="btnLimpiarFiltrosAnuladas" class="btn btn-outline-secondary w-100" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table id="tablaCotizacionesAnuladas" class="table table-modern align-middle">
        <thead>
            <tr>
                <th>Código Cliente</th>
                <th>Paciente</th>
                <th>DNI</th>
                <th>Fecha</th>
                <th>Fecha anulación</th>
                <th>Usuario anuló</th>
                <th>Motivo</th>
                <th>Total</th>
                <th>Referencia</th>
                <th>Estado Examen</th>
                <th>Rol Creador</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    const anuladasCurrencyConfig = <?= json_encode($currencyCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function formatMoneySafe(amount) {
        if (typeof window.formatMoney === 'function') {
            return window.formatMoney(amount);
        }

        const numericAmount = Number(amount || 0);
        const decimals = Number(anuladasCurrencyConfig.decimals ?? 2);
        const decimalSeparator = anuladasCurrencyConfig.decimal_separator ?? '.';
        const thousandsSeparator = anuladasCurrencyConfig.thousands_separator ?? ',';
        const symbol = anuladasCurrencyConfig.symbol ?? '$';
        const position = anuladasCurrencyConfig.position === 'suffix' ? 'suffix' : 'prefix';
        const fixed = numericAmount.toFixed(decimals);
        const parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        const formattedNumber = decimals > 0 ? parts.join(decimalSeparator) : parts[0];

        return position === 'suffix'
            ? `${formattedNumber} ${symbol}`
            : `${symbol} ${formattedNumber}`;
    }

    var tablaAnuladas = $('#tablaCotizacionesAnuladas').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: function(d) {
                d.filtro_dni = $('#filtroDniAnuladas').val();
                d.filtro_empresa = $('#filtroEmpresaAnuladas').val();
                d.filtro_convenio = $('#filtroConvenioAnuladas').val();
                d.filtro_fecha_desde = $('#filtroFechaDesdeAnuladas').val();
                d.filtro_fecha_hasta = $('#filtroFechaHastaAnuladas').val();
                d.modo = 'anuladas';
            }
        },
        pageLength: 10,
        lengthMenu: [[10, 20, 50], [10, 20, 50]],
        order: [[4, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        columns: [
            { data: 'codigo_cliente' },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.nombre_cliente || ''} ${row.apellido_cliente || ''}`;
                }
            },
            { data: 'dni' },
            { data: 'fecha' },
            {
                data: 'anulada_at',
                render: function(data) {
                    return data ? data : '<span class="text-muted">Sin dato</span>';
                }
            },
            {
                data: 'anulada_por_nombre',
                render: function(data) {
                    const txt = (data || '').trim();
                    return txt !== '' ? txt : '<span class="text-muted">Sin dato</span>';
                }
            },
            {
                data: 'anulado_motivo',
                render: function(data) {
                    const txt = (data || '').trim();
                    return txt !== '' ? txt : '<span class="text-muted">Sin motivo</span>';
                }
            },
            {
                data: 'total',
                render: function(data) {
                    return formatMoneySafe(data);
                }
            },
            {
                data: 'referencia',
                render: function(data) {
                    return data && data !== '' ? data : 'Particular';
                }
            },
            {
                data: null,
                render: function(row) {
                    const estado = row.estado_examen;
                    const porcentaje = row.porcentaje_examen;
                    if (estado === 'completado_100' || estado === 'pendiente_100') {
                        return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado 100%</span>`;
                    } else if (estado === 'pendiente_0') {
                        return `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente 0%</span>`;
                    } else if (estado && estado.startsWith('pendiente_')) {
                        return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente ${porcentaje}%</span>`;
                    }
                    return `<span class='badge bg-secondary'>Sin datos</span>`;
                }
            },
            { data: 'rol_creador' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let acciones = '';
                    acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm btn-cotizacion-accion' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
                    if (parseInt(row.id_cliente || 0, 10) > 0) {
                        acciones += `<a href='dashboard.php?vista=comparar_resultados_cliente&id=${row.id_cliente}' class='btn btn-secondary btn-sm btn-cotizacion-accion' title='Comparar resultados'><i class='bi bi-graph-up-arrow'></i></a>`;
                    }
                    acciones += `<a href='resultados/descarga-pdf.php?cotizacion_id=${row.id}' class='btn btn-success btn-sm btn-cotizacion-accion' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
                    return acciones;
                }
            }
        ]
    });

    $('#filtroDniAnuladas, #filtroEmpresaAnuladas, #filtroConvenioAnuladas, #filtroFechaDesdeAnuladas, #filtroFechaHastaAnuladas').on('change keyup', function() {
        tablaAnuladas.ajax.reload();
    });

    $('#btnLimpiarFiltrosAnuladas').on('click', function() {
        $('#filtroDniAnuladas').val('');
        $('#filtroEmpresaAnuladas').val('');
        $('#filtroConvenioAnuladas').val('');
        $('#filtroFechaDesdeAnuladas').val('');
        $('#filtroFechaHastaAnuladas').val('');
        tablaAnuladas.ajax.reload();
    });
});
</script>
