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
    ORDER BY c.fecha DESC
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
<div class="container mt-4">
    <h3 class="mb-4">Reporte de Deudas y Adelantos</h3>
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
        <table id="tablaIngresos" class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Código Cotización</th>
                    <th>Fecha</th>
                    <th>Mét. Pago</th>
                    <th>Cliente</th>
                    <th>Tipo de Paciente</th>
                    <th>Referencia</th>
                    <th>Total Cotización</th>
                    <th>Adelanto</th>
                    <th>Deuda</th>
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
                columns: [
                    { data: 'codigo_cotizacion' },
                    { data: 'fecha' },
                    { data: 'metodo_pago' },
                    { data: 'cliente' },
                    { data: 'tipo_paciente' },
                    { data: 'referencia' },
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