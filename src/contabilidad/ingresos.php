<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Filtros recibidos por GET
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$tipo_paciente = $_GET['tipo_paciente'] ?? 'todos';
$filtro_convenio = $_GET['filtro_convenio'] ?? '';
$filtro_empresa = $_GET['filtro_empresa'] ?? '';

// Consultar empresas y convenios para los selects
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$empresas = $pdo->query("SELECT id, nombre_comercial FROM empresas ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);

// Construir condiciones dinámicas para cotizaciones
$where = "WHERE DATE(c.fecha) BETWEEN ? AND ?";
$params = [$desde, $hasta];

if ($tipo_paciente == 'convenio') {
    $where .= " AND c.id_convenio IS NOT NULL";
    if ($filtro_convenio) {
        $where .= " AND c.id_convenio = ?";
        $params[] = $filtro_convenio;
    }
} elseif ($tipo_paciente == 'empresa') {
    $where .= " AND c.id_empresa IS NOT NULL";
    if ($filtro_empresa) {
        $where .= " AND c.id_empresa = ?";
        $params[] = $filtro_empresa;
    }
} elseif ($tipo_paciente == 'particular') {
    $where .= " AND c.id_convenio IS NULL AND c.id_empresa IS NULL";
}

// Consulta de cotizaciones con SUMA de pagos (puede ser 0)
$stmt = $pdo->prepare("
    SELECT 
        c.id AS id_cotizacion,
        c.codigo AS codigo_cotizacion,
        c.total AS total_cotizacion,
        c.fecha,
        c.tipo_usuario,
        c.id_convenio,
        c.id_empresa,
        conv.nombre AS nombre_convenio,
        emp.nombre_comercial AS nombre_empresa,
        cl.nombre, cl.apellido,
        (SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_cotizacion = c.id) AS total_pagado,
        (SELECT GROUP_CONCAT(DISTINCT p3.metodo_pago SEPARATOR ', ') FROM pagos p3 WHERE p3.id_cotizacion = c.id) AS metodo_pago
    FROM cotizaciones c
    JOIN clientes cl ON c.id_cliente = cl.id
    LEFT JOIN convenios conv ON c.id_convenio = conv.id
    LEFT JOIN empresas emp ON c.id_empresa = emp.id
    $where
    GROUP BY c.id, c.codigo, c.total, c.fecha, c.tipo_usuario, c.id_convenio, c.id_empresa, conv.nombre, emp.nombre_comercial, cl.nombre, cl.apellido
    ORDER BY c.fecha DESC, c.id DESC
");
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar totales
$total_adelanto = 0;
$total_deuda = 0;
foreach ($registros as $r) {
    $total_adelanto += floatval($r['total_pagado']);
    $total_deuda += floatval($r['total_cotizacion']) - floatval($r['total_pagado']);
}
?>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Fuerza color de encabezado y estados de ordenación en ingresos */
#tablaIngresos thead th {
    background-color: #4f46e5 !important; /* indigo-600 */
    color: #ffffff !important;
}
#tablaIngresos thead th.sorting,
#tablaIngresos thead th.sorting_asc,
#tablaIngresos thead th.sorting_desc {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
}
/* Encabezado con degradado para el título */
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    box-shadow: 0 4px 24px #764ba233;
}
</style>
<div class="container-fluid mt-4">
    <div class="header-section mb-3">
        <div class="p-3">
            <h3 class="mb-0 text-white text-3xl">Reporte de Deudas y Adelantos</h3>
        </div>
    </div>
    <form method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="vista" value="ingresos">
        <div class="col-auto">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label">Tipo de Paciente</label>
            <select name="tipo_paciente" class="form-select" onchange="this.form.submit()">
                <option value="todos" <?= $tipo_paciente == 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="convenio" <?= $tipo_paciente == 'convenio' ? 'selected' : '' ?>>Convenio</option>
                <option value="empresa" <?= $tipo_paciente == 'empresa' ? 'selected' : '' ?>>Empresa</option>
                <option value="particular" <?= $tipo_paciente == 'particular' ? 'selected' : '' ?>>Particular</option>
            </select>
        </div>
        <?php if ($tipo_paciente == 'convenio'): ?>
            <div class="col-auto">
                <label class="form-label">Convenio</label>
                <select name="filtro_convenio" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($convenios as $convenio): ?>
                        <option value="<?= $convenio['id'] ?>" <?= $filtro_convenio == $convenio['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($convenio['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ($tipo_paciente == 'empresa'): ?>
            <div class="col-auto">
                <label class="form-label">Empresa</label>
                <select name="filtro_empresa" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?= $empresa['id'] ?>" <?= $filtro_empresa == $empresa['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($empresa['nombre_comercial']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="dashboard.php?vista=ingresos" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
    <div class="table-responsive">
        <table id="tablaIngresos" class="table table-striped table-bordered align-middle" style="width:100%; min-width:1200px;">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-sm font-semibold">Código Cotización</th>
                    <th class="px-4 py-2 text-sm font-semibold">Fecha</th>
                    <th class="px-4 py-2 text-sm font-semibold">Mét. Pago</th>
                    <th class="px-4 py-2 text-sm font-semibold">Cliente</th>
                    <th class="px-4 py-2 text-sm font-semibold">Tipo de Paciente</th>
                    <th class="px-4 py-2 text-sm font-semibold">Referencia</th>
                    <th class="px-4 py-2 text-sm font-semibold">Total Cotización</th>
                    <th class="px-4 py-2 text-sm font-semibold">Adelanto</th>
                    <th class="px-4 py-2 text-sm font-semibold">Deuda</th>
                </tr>
            </thead>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
            </tbody>
            <tfoot>
                <tr class="table-info fw-bold">
                    <td colspan="7" class="text-end">Totales del periodo:</td>
                    <td>S/ <?= number_format($total_adelanto, 2) ?></td>
                    <td>S/ <?= number_format($total_deuda, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <!-- CSS de DataTables y Botones -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <!-- JS de jQuery, DataTables y Botones -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <!-- Inicialización de DataTables con botones de exportación -->
    <script>
        $(document).ready(function() {
            var tabla = $('#tablaIngresos').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: 'dashboard.php?action=ingresos_api',
                    type: 'GET',
                    data: function(d) {
                        d.desde = $('input[name="desde"]').val();
                        d.hasta = $('input[name="hasta"]').val();
                        d.tipo_paciente = $('select[name="tipo_paciente"]').val();
                        d.filtro_convenio = $('select[name="filtro_convenio"]').val();
                        d.filtro_empresa = $('select[name="filtro_empresa"]').val();
                    }
                },
                pageLength: 3,
                lengthMenu: [3, 5, 10],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                order: [[1, 'desc']], // Columna fecha descendente
                columns: [
                    { data: 'codigo_cotizacion' },
                    { data: 'fecha' },
                    {
                        data: 'metodo_pago',
                        render: function(data) {
                            if (!data || !data.trim()) {
                                return '<span class="badge bg-dark">Sin pago</span>';
                            }
                            // Separar métodos y asignar color
                            const colores = {
                                'efectivo': 'bg-success',
                                'tarjeta': 'bg-primary',
                                'transferencia': 'bg-info',
                                'masivo': 'bg-warning text-dark',
                                'descarga_anticipada': 'bg-secondary',
                                'cambio_total': 'bg-danger',
                                'adelanto': 'bg-purple',
                                'default': 'bg-dark'
                            };
                            return data.split(',').map(metodo => {
                                const key = metodo.trim().toLowerCase();
                                const color = colores[key] || colores['default'];
                                return `<span class="badge ${color} me-1">${metodo.trim()}</span>`;
                            }).join(' ');
                        }
                    },
                    { data: 'cliente' },
                    { data: 'tipo_paciente' },
                        {
                            data: 'referencia',
                            render: function(data) {
                                if (!data) return '';
                                // Asignar color único por cada valor de referencia
                                const colorList = [
                                    'bg-primary',
                                    'bg-success',
                                    'bg-info',
                                    'bg-warning text-dark',
                                    'bg-danger',
                                    'bg-secondary',
                                    'bg-purple',
                                    'bg-dark'
                                ];
                                // Hash simple para asignar color por valor
                                function hashColor(str) {
                                    let hash = 0;
                                    for (let i = 0; i < str.length; i++) {
                                        hash = str.charCodeAt(i) + ((hash << 5) - hash);
                                    }
                                    return colorList[Math.abs(hash) % colorList.length];
                                }
                                return data.split(',').map(ref => {
                                    const color = hashColor(ref.trim());
                                    return `<span class="badge ${color} me-1">${ref.trim()}</span>`;
                                }).join(' ');
                            }
                        },
                    { data: 'total_cotizacion' },
                    { data: 'adelanto' },
                    { data: 'deuda' }
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel"></i> Exportar a Excel',
                        className: 'btn btn-success mb-2'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="bi bi-file-earmark-pdf"></i> Exportar a PDF',
                        className: 'btn btn-danger mb-2',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: ':visible'
                        },
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 10;
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Imprimir',
                        className: 'btn btn-info mb-2'
                    }
                ]
            });
            // Filtros avanzados: recargar tabla al cambiar filtros
            $('input[name="desde"], input[name="hasta"], select[name="tipo_paciente"], select[name="filtro_convenio"], select[name="filtro_empresa"]').on('change', function() {
                tabla.ajax.reload();
            });
        });
    </script>
        <style>
        .bg-purple {
            background-color: #8e24aa !important;
            color: #fff !important;
        }
        .bg-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
        }
        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        .bg-info {
            background-color: #0dcaf0 !important;
            color: #fff !important;
        }
        .bg-success {
            background-color: #198754 !important;
            color: #fff !important;
        }
        .bg-primary {
            background-color: #0d6efd !important;
            color: #fff !important;
        }
        .bg-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
        }
        .bg-dark {
            background-color: #212529 !important;
            color: #fff !important;
        }
        @media (max-width: 768px) {
    .table-responsive { display: none; }
    .cards-container { display: block; }
    .mobile-pagination-ingresos {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 1.5rem 0 2rem 0;
        width: 100%;
    }
    .cliente-card {
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
    .cliente-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    .cliente-codigo {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .cliente-nombre {
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
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
    </style>
<!-- Buscador y cards para móvil -->
<div class="mb-3 d-block d-md-none">
    <div class="input-group">
        <input type="text" id="buscadorIngresosMovil" class="form-control" placeholder="Buscar por cliente, referencia...">
        <button class="btn btn-primary" type="button" id="btnClearIngresosMovil"><i class="bi bi-x"></i></button>
    </div>
</div>
<div class="cards-container" id="cardsIngresosAjax"></div>
<!-- Mostrar totales del periodo en móvil -->
<div class="d-block d-md-none mb-3" id="totalesIngresosMovil">
    <div class="card p-3 bg-info bg-opacity-10 border-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <span class="fw-bold">Totales del periodo:</span>
            <span class="fw-bold">Adelanto: <span class="text-success">S/ <?= number_format($total_adelanto, 2) ?></span></span>
            <span class="fw-bold">Deuda: <span class="text-danger">S/ <?= number_format($total_deuda, 2) ?></span></span>
        </div>
    </div>
</div>
<style>
@media (max-width: 768px) {
    .table-responsive { display: none; }
    .cards-container { display: block; }
    .mobile-pagination-ingresos {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 1.5rem 0 2rem 0;
        width: 100%;
    }
    .cliente-card {
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
    .cliente-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    .cliente-codigo {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .cliente-nombre {
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
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<script>
function renderIngresoCard(ingreso) {
    // Puedes personalizar el diseño del card según los campos de ingreso
    let badges = '';
    // Método de pago
    if (ingreso.metodo_pago) {
        ingreso.metodo_pago.split(',').forEach(metodo => {
            const key = metodo.trim().toLowerCase();
            const colores = {
                'efectivo': 'bg-success',
                'tarjeta': 'bg-primary',
                'transferencia': 'bg-info',
                'masivo': 'bg-warning text-dark',
                'descarga_anticipada': 'bg-secondary',
                'cambio_total': 'bg-danger',
                'adelanto': 'bg-purple',
                'default': 'bg-dark'
            };
            const color = colores[key] || colores['default'];
            badges += `<span class="badge ${color} me-1">${metodo.trim()}</span>`;
        });
    } else {
        badges += '<span class="badge bg-dark">Sin pago</span>';
    }
    // Referencia
    let refBadges = '';
    if (ingreso.referencia) {
        ingreso.referencia.split(',').forEach(ref => {
            const colorList = [
                'bg-primary','bg-success','bg-info','bg-warning text-dark','bg-danger','bg-secondary','bg-purple','bg-dark'
            ];
            function hashColor(str) {
                let hash = 0;
                for (let i = 0; i < str.length; i++) {
                    hash = str.charCodeAt(i) + ((hash << 5) - hash);
                }
                return colorList[Math.abs(hash) % colorList.length];
            }
            const color = hashColor(ref.trim());
            refBadges += `<span class="badge ${color} me-1">${ref.trim()}</span>`;
        });
    }
    return `
    <div class='cliente-card mb-3'>
        <div class='card-header d-flex justify-content-between align-items-center'>
            <h5 class='cliente-nombre mb-0'>${ingreso.cliente || ''}</h5>
            <span class='cliente-codigo'>${ingreso.codigo_cotizacion || ''}</span>
        </div>
        <div class='card-body'>
            <div class='info-item'><span class='info-label'>Fecha</span><span class='info-value'>${ingreso.fecha || ''}</span></div>
            <div class='info-item'><span class='info-label'>Mét. Pago</span><span class='info-value'>${badges}</span></div>
            <div class='info-item'><span class='info-label'>Referencia</span><span class='info-value'>${refBadges}</span></div>
            <div class='info-item'><span class='info-label'>Tipo Paciente</span><span class='info-value'>${ingreso.tipo_paciente || ''}</span></div>
            <div class='info-item'><span class='info-label'>Total Cotización</span><span class='info-value'>S/ ${ingreso.total_cotizacion || '0.00'}</span></div>
            <div class='info-item'><span class='info-label'>Adelanto</span><span class='info-value'>S/ ${ingreso.adelanto || '0.00'}</span></div>
            <div class='info-item'><span class='info-label'>Deuda</span><span class='info-value'>S/ ${ingreso.deuda || '0.00'}</span></div>
        </div>
    </div>`;
}
function cargarCardsIngresos(pagina = 1, busqueda = '') {
    const porPagina = 3;
    const params = {
        draw: 1,
        start: (pagina - 1) * porPagina,
        length: porPagina,
        search: { value: busqueda },
        desde: $('input[name="desde"]').val(),
        hasta: $('input[name="hasta"]').val(),
        tipo_paciente: $('select[name="tipo_paciente"]').val(),
        filtro_convenio: $('select[name="filtro_convenio"]').val(),
        filtro_empresa: $('select[name="filtro_empresa"]').val(),
        order: [{ column: 1, dir: 'desc' }] // columna 1 = fecha, descendente
    };
    $.ajax({
        url: 'dashboard.php?action=ingresos_api',
        data: params,
        dataType: 'json',
        success: function(resp) {
            const cont = document.getElementById('cardsIngresosAjax');
            cont.innerHTML = '';
            if (resp.data && resp.data.length > 0) {
                resp.data.forEach(ingreso => {
                    cont.innerHTML += renderIngresoCard(ingreso);
                });
                renderPaginacionIngresosMovil(pagina, Math.ceil(resp.recordsFiltered / porPagina), busqueda);
            } else {
                cont.innerHTML = '<div class="text-center py-5">No hay registros</div>';
                renderPaginacionIngresosMovil(1, 1, busqueda);
            }
        },
        error: function() {
            document.getElementById('cardsIngresosAjax').innerHTML = '<div class="alert alert-danger">Error al cargar los ingresos.</div>';
        }
    });
}
function renderPaginacionIngresosMovil(pagina, totalPaginas, busqueda) {
    let nav = document.getElementById('paginacionIngresosMovil');
    if (!nav) {
        nav = document.createElement('nav');
        nav.className = 'mobile-pagination-ingresos';
        nav.id = 'paginacionIngresosMovil';
        document.getElementById('cardsIngresosAjax').after(nav);
    }
    let html = '';
    html += `<button class='page-btn' onclick='cargarCardsIngresos(${pagina - 1}, ${JSON.stringify(busqueda)})' ${pagina <= 1 ? 'disabled' : ''}>&#8592;</button>`;
    for (let p = Math.max(1, pagina - 1); p <= Math.min(totalPaginas, pagina + 1); p++) {
        html += `<button class='page-btn${p === pagina ? ' active' : ''}' onclick='cargarCardsIngresos(${p}, ${JSON.stringify(busqueda)})'>${p}</button>`;
    }
    html += `<button class='page-btn' onclick='cargarCardsIngresos(${pagina + 1}, ${JSON.stringify(busqueda)})' ${pagina >= totalPaginas ? 'disabled' : ''}>&#8594;</button>`;
    nav.innerHTML = html;
}
(function() {
    let lastMode = window.innerWidth < 768 ? 'mobile' : 'desktop';
    let lastBusqueda = '';
    function isMobile() { return window.innerWidth < 768; }
    function cargarSiMovilIngresos(force = false) {
        if (isMobile()) {
            const buscador = document.getElementById('buscadorIngresosMovil');
            let busqueda = buscador ? buscador.value : '';
            if (force || lastMode !== 'mobile' || lastBusqueda !== busqueda) {
                cargarCardsIngresos(1, busqueda);
                lastMode = 'mobile';
                lastBusqueda = busqueda;
            }
        } else {
            // Eliminar paginación móvil si existe
            const nav = document.getElementById('paginacionIngresosMovil');
            if (nav && nav.parentNode) nav.parentNode.removeChild(nav);
            // Limpiar cards
            const cont = document.getElementById('cardsIngresosAjax');
            if (cont) cont.innerHTML = '';
            // Forzar ajuste de columnas DataTables al volver a desktop
            if ($.fn.DataTable && $('#tablaIngresos').length && $('#tablaIngresos').hasClass('dataTable')) {
                $('#tablaIngresos').DataTable().columns.adjust().responsive.recalc();
            }
            lastMode = 'desktop';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        cargarSiMovilIngresos(true);
        // Buscador móvil
        const buscador = document.getElementById('buscadorIngresosMovil');
        const btnClear = document.getElementById('btnClearIngresosMovil');
        if (buscador) {
            buscador.addEventListener('input', function(e) {
                cargarSiMovilIngresos(true);
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', function() {
                buscador.value = '';
                cargarCardsIngresos(1, '');
            });
        }
        // Filtros avanzados: recargar cards al cambiar filtros
        $('input[name="desde"], input[name="hasta"], select[name="tipo_paciente"], select[name="filtro_convenio"], select[name="filtro_empresa"]').on('change', function() {
            cargarSiMovilIngresos(true);
        });
    });
    window.addEventListener('resize', function() {
        cargarSiMovilIngresos();
    });
})();
</script>