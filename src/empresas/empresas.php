<?php
require_once __DIR__ . '/../conexion/conexion.php';

// Obtener todas las empresas
$stmt = $pdo->query("SELECT * FROM empresas");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<!-- Incluye CSS de Bootstrap y DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Fuerza el color de fondo del encabezado de la tabla de Empresas */
#tabla-empresas thead th {
    background-color: #4f46e5 !important; /* indigo-600 */
    color: #ffffff !important;
}

#tabla-empresas thead th.sorting,
#tabla-empresas thead th.sorting_asc,
#tabla-empresas thead th.sorting_desc {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
}
</style>


<div class="container-fluid mt-4">
    <!-- Encabezado con degradado para el título -->
    <style>
    .header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        box-shadow: 0 4px 24px #764ba233;
    }
    </style>
    <div class="header-section mb-3">
        <div class="p-3">
            <h3 class="mb-0 text-white text-3xl">Lista de Empresas</h3>
        </div>
    </div>
    <a href="dashboard.php?vista=form_empresa" class="btn btn-primary mb-3">Agregar Empresa</a>
    <!-- Buscador y cards para móvil -->
    <div class="mb-3 d-block d-md-none">
        <div class="input-group">
            <input type="text" id="buscadorEmpresaMovil" class="form-control" placeholder="Buscar por razón social, comercial, RUC...">
            <button class="btn btn-primary" type="button" onclick="document.getElementById('buscadorEmpresaMovil').value = ''; filtrarCardsEmpresas('');"><i class="bi bi-x"></i></button>
        </div>
    </div>
    <div class="d-block d-md-none">
        <?php
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $por_pagina = isset($_GET['por_pagina']) ? max(3, (int)$_GET['por_pagina']) : 3;
        $total_empresas = count($empresas);
        $total_paginas = ceil($total_empresas / $por_pagina);
        ?>
        <form method="get" class="mb-2 d-flex justify-content-end align-items-center gap-2">
            <label for="por_pagina_movil_emp" class="form-label mb-0">Mostrar:</label>
            <select name="por_pagina" id="por_pagina_movil_emp" class="form-select form-select-sm" style="width: auto;">
                <option value="3"<?= $por_pagina==3?' selected':'' ?>>3</option>
                <option value="5"<?= $por_pagina==5?' selected':'' ?>>5</option>
                <option value="10"<?= $por_pagina==10?' selected':'' ?>>10</option>
            </select>
        </form>
        <?php if ($empresas): ?>
            <?php foreach ($empresas as $empresa): ?>
                <div class="card mb-3 shadow-sm empresa-card" data-razon="<?= htmlspecialchars($empresa['razon_social']) ?>" data-comercial="<?= htmlspecialchars($empresa['nombre_comercial']) ?>" data-ruc="<?= htmlspecialchars($empresa['ruc']) ?>">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building me-2"></i><?= htmlspecialchars($empresa['razon_social']) ?></span>
                        <span class="badge bg-secondary">#<?= htmlspecialchars($empresa['id']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><strong>RUC:</strong> <?= htmlspecialchars($empresa['ruc']) ?></div>
                            <div class="col-6"><strong>Comercial:</strong> <?= htmlspecialchars($empresa['nombre_comercial']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12"><strong>Dirección:</strong> <?= htmlspecialchars($empresa['direccion']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Teléfono:</strong> <?= htmlspecialchars($empresa['telefono']) ?></div>
                            <div class="col-6"><strong>Email:</strong> <?= htmlspecialchars($empresa['email']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Representante:</strong> <?= htmlspecialchars($empresa['representante']) ?></div>
                            <div class="col-6"><strong>Convenio:</strong> <?= htmlspecialchars($empresa['convenio']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Descuento:</strong> <?= htmlspecialchars($empresa['descuento']) ?> %</div>
                            <div class="col-6"><strong>Estado:</strong> <?= htmlspecialchars($empresa['estado']) ?></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="dashboard.php?vista=form_empresa&id=<?= $empresa['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="dashboard.php?action=eliminar_empresa&id=<?= $empresa['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta empresa?');">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <nav class="mobile-pagination d-flex justify-content-center align-items-center gap-2 mb-3" id="paginacionEmpresaMovil">
                <button class="btn btn-outline-primary btn-sm" id="btnAnteriorEmpresa" type="button">&#8592;</button>
                <span id="paginacionEmpresaPaginas"></span>
                <button class="btn btn-outline-primary btn-sm" id="btnSiguienteEmpresa" type="button">&#8594;</button>
            </nav>
        <?php else: ?>
            <div class="alert alert-info">No hay empresas registradas.</div>
        <?php endif; ?>
    </div>
    <!-- Tabla para desktop -->
    <div class="table-responsive d-none d-md-block">
        <table id="tabla-empresas" class="table table-bordered table-striped" style="width:100%; min-width:1200px;">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-sm font-semibold">ID</th>
                    <th class="px-4 py-2 text-sm font-semibold">RUC</th>
                    <th class="px-4 py-2 text-sm font-semibold">Razón Social</th>
                    <th class="px-4 py-2 text-sm font-semibold">Nombre Comercial</th>
                    <th class="px-4 py-2 text-sm font-semibold">Dirección</th>
                    <th class="px-4 py-2 text-sm font-semibold">Teléfono</th>
                    <th class="px-4 py-2 text-sm font-semibold">Email</th>
                    <th class="px-4 py-2 text-sm font-semibold">Representante</th>
                    <th class="px-4 py-2 text-sm font-semibold">Convenio</th>
                    <th class="px-4 py-2 text-sm font-semibold">Estado</th>
                    <th class="px-4 py-2 text-sm font-semibold">Descuento (%)</th>
                    <th class="px-4 py-2 text-sm font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
            </tbody>
        </table>
    </div>
</div>

<!-- Incluye JS de Bootstrap y DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
// DataTables para desktop
$(document).ready(function() {
    $('#tabla-empresas').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=empresas_api',
            type: 'GET'
        },
        pageLength: 3,
        lengthMenu: [[3, 5, 10], [3, 5, 10]],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columns: [
            { data: 'id' },
            { data: 'ruc' },
            { data: 'razon_social', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'nombre_comercial', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'direccion' },
            { data: 'telefono' },
            { data: 'email' },
            { data: 'representante', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'convenio' },
            { data: 'estado', render: function(data) {
                if (data === 'activo') {
                    return `<span class='badge bg-success'>Activo</span>`;
                } else {
                    return `<span class='badge bg-danger'>Inactivo</span>`;
                }
            } },
            { data: 'descuento', render: function(data) { return data ? data + ' %' : '0 %'; } },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<a href='dashboard.php?vista=form_empresa&id=${row.id}' class='btn btn-warning btn-sm'>Editar</a>
                            <a href='dashboard.php?action=eliminar_empresa&id=${row.id}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar esta empresa?\");'>Eliminar</a>`;
                }
            }
        ],
        dom: 'lBfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Exportar Excel',
                className: 'btn btn-success'
            },
            {
                extend: 'pdfHtml5',
                text: 'Exportar PDF',
                className: 'btn btn-danger'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-info'
            }
        ]
    });
});
</script>

<script>
// Paginación y buscador móvil para empresas
function normalizarTextoEmpresa(txt) {
    return txt
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
let paginaEmpresa = 1;
let porPaginaEmpresa = <?= $por_pagina ?>;
let totalEmpresas = document.querySelectorAll('.empresa-card').length;
let totalPaginasEmpresa = Math.ceil(totalEmpresas / porPaginaEmpresa);

function mostrarPaginaActualEmpresas() {
    var cards = document.querySelectorAll('.empresa-card');
    var inicio = (paginaEmpresa - 1) * porPaginaEmpresa;
    var fin = inicio + porPaginaEmpresa;
    cards.forEach(function(card, idx) {
        if (idx >= inicio && idx < fin) {
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    });
    document.getElementById('paginacionEmpresaMovil').style.display = '';
    actualizarPaginacionEmpresa();
}

function actualizarPaginacionEmpresa() {
    let paginacion = document.getElementById('paginacionEmpresaPaginas');
    if (!paginacion) {
        paginacion = document.createElement('span');
        paginacion.id = 'paginacionEmpresaPaginas';
        document.getElementById('btnSiguienteEmpresa').before(paginacion);
    }
    paginacion.innerHTML = '';
    let pages = [];
    if (paginaEmpresa > 1) pages.push(paginaEmpresa - 1);
    pages.push(paginaEmpresa);
    if (paginaEmpresa < totalPaginasEmpresa) pages.push(paginaEmpresa + 1);
    pages.forEach(function(p) {
        let btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm' + (p === paginaEmpresa ? ' active' : '');
        btn.textContent = p;
        btn.onclick = function() {
            paginaEmpresa = p;
            mostrarPaginaActualEmpresas();
        };
        paginacion.appendChild(btn);
    });
    document.getElementById('btnAnteriorEmpresa').disabled = (paginaEmpresa <= 1);
    document.getElementById('btnSiguienteEmpresa').disabled = (paginaEmpresa >= totalPaginasEmpresa);
}

function filtrarCardsEmpresas(valor) {
    var filtro = normalizarTextoEmpresa(valor);
    var cards = document.querySelectorAll('.empresa-card');
    var algunoVisible = false;
    if (!filtro) {
        mostrarPaginaActualEmpresas();
    } else {
        cards.forEach(function(card) {
            var razon = normalizarTextoEmpresa(card.getAttribute('data-razon') || '');
            var comercial = normalizarTextoEmpresa(card.getAttribute('data-comercial') || '');
            var ruc = normalizarTextoEmpresa(card.getAttribute('data-ruc') || '');
            if (razon.includes(filtro) || comercial.includes(filtro) || ruc.includes(filtro)) {
                card.classList.remove('d-none');
                algunoVisible = true;
            } else {
                card.classList.add('d-none');
            }
        });
        document.getElementById('paginacionEmpresaMovil').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    mostrarPaginaActualEmpresas();
    document.getElementById('btnAnteriorEmpresa').onclick = function() {
        if (paginaEmpresa > 1) {
            paginaEmpresa--;
            mostrarPaginaActualEmpresas();
        }
    };
    document.getElementById('btnSiguienteEmpresa').onclick = function() {
        if (paginaEmpresa < totalPaginasEmpresa) {
            paginaEmpresa++;
            mostrarPaginaActualEmpresas();
        }
    };
    document.getElementById('por_pagina_movil_emp').addEventListener('change', function(e) {
        porPaginaEmpresa = parseInt(this.value);
        totalEmpresas = document.querySelectorAll('.empresa-card').length;
        totalPaginasEmpresa = Math.ceil(totalEmpresas / porPaginaEmpresa);
        paginaEmpresa = 1;
        mostrarPaginaActualEmpresas();
    });
});
document.getElementById('buscadorEmpresaMovil').addEventListener('input', function(e) {
    filtrarCardsEmpresas(e.target.value);
});
</script>
