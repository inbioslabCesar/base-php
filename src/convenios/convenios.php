<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function capitalizar($texto) {
    return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
}

$stmt = $pdo->query("SELECT * FROM convenios ORDER BY id DESC");
$convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Fuerza el color de fondo del encabezado de la tabla de Convenios */
#tabla-convenios thead th {
    background-color: #4f46e5 !important; /* indigo-600 */
    color: #ffffff !important;
}

#tabla-convenios thead th.sorting,
#tabla-convenios thead th.sorting_asc,
#tabla-convenios thead th.sorting_desc {
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
            <h3 class="mb-0 text-white text-3xl">Convenios</h3>
        </div>
    </div>
    <?php if (!empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['mensaje']) ?></div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <a href="dashboard.php?vista=form_convenio" class="btn btn-success mb-3">Registrar Convenio</a>
    <!-- Buscador y cards para móvil -->
    <div class="mb-3 d-block d-md-none">
        <div class="input-group">
            <input type="text" id="buscadorConvenioMovil" class="form-control" placeholder="Buscar por nombre, especialidad, DNI...">
            <button class="btn btn-primary" type="button" onclick="document.getElementById('buscadorConvenioMovil').value = ''; filtrarCardsConvenios('');"><i class="bi bi-x"></i></button>
        </div>
    </div>
    <div class="d-block d-md-none">
        <?php
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $por_pagina = isset($_GET['por_pagina']) ? max(3, (int)$_GET['por_pagina']) : 3;
        $total_convenios = count($convenios);
        $total_paginas = ceil($total_convenios / $por_pagina);
        ?>
        <form method="get" class="mb-2 d-flex justify-content-end align-items-center gap-2">
            <label for="por_pagina_movil_conv" class="form-label mb-0">Mostrar:</label>
            <select name="por_pagina" id="por_pagina_movil_conv" class="form-select form-select-sm" style="width: auto;">
                <option value="3"<?= $por_pagina==3?' selected':'' ?>>3</option>
                <option value="5"<?= $por_pagina==5?' selected':'' ?>>5</option>
                <option value="10"<?= $por_pagina==10?' selected':'' ?>>10</option>
            </select>
        </form>
        <?php if ($convenios): ?>
            <?php foreach ($convenios as $convenio): ?>
                <div class="card mb-3 shadow-sm convenio-card" data-nombre="<?= htmlspecialchars($convenio['nombre']) ?>" data-especialidad="<?= htmlspecialchars($convenio['especialidad']) ?>" data-dni="<?= htmlspecialchars($convenio['dni']) ?>">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people me-2"></i><?= htmlspecialchars($convenio['nombre']) ?></span>
                        <span class="badge bg-secondary">#<?= htmlspecialchars($convenio['id']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><strong>DNI:</strong> <?= htmlspecialchars($convenio['dni']) ?></div>
                            <div class="col-6"><strong>Especialidad:</strong> <?= htmlspecialchars($convenio['especialidad']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Descuento:</strong> <?= htmlspecialchars($convenio['descuento']) ?> %</div>
                            <div class="col-6"><strong>Email:</strong> <?= htmlspecialchars($convenio['email']) ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12"><strong>Descripción:</strong> <?= htmlspecialchars($convenio['descripcion']) ?></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="dashboard.php?vista=form_convenio&id=<?= $convenio['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="dashboard.php?action=eliminar_convenio&id=<?= $convenio['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este convenio?');">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <nav class="mobile-pagination d-flex justify-content-center align-items-center gap-2 mb-3" id="paginacionConvenioMovil">
                <button class="btn btn-outline-success btn-sm" id="btnAnteriorConvenio" type="button">&#8592;</button>
                <span id="paginacionConvenioPaginas"></span>
                <button class="btn btn-outline-success btn-sm" id="btnSiguienteConvenio" type="button">&#8594;</button>
            </nav>
        <?php else: ?>
            <div class="alert alert-info">No hay convenios registrados.</div>
        <?php endif; ?>
    </div>
    <div class="table-responsive d-none d-md-block">
        <table id="tabla-convenios" class="table table-bordered table-striped" style="width:100%; min-width:1200px;">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-sm font-semibold"><input type="checkbox" id="selectAllConvenios"></th>
                    <th class="px-4 py-2 text-sm font-semibold">ID</th>
                    <th class="px-4 py-2 text-sm font-semibold">Nombre</th>
                    <th class="px-4 py-2 text-sm font-semibold">DNI</th>
                    <th class="px-4 py-2 text-sm font-semibold">Especialidad</th>
                    <th class="px-4 py-2 text-sm font-semibold">Descuento (%)</th>
                    <th class="px-4 py-2 text-sm font-semibold">Descripción</th>
                    <th class="px-4 py-2 text-sm font-semibold">Email</th>
                    <th class="px-4 py-2 text-sm font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- El contenido será llenado dinámicamente por DataTables server-side -->
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables y Bootstrap JS (ajusta rutas/CDN según tu proyecto) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script>
// DataTables y lógica móvil en un solo bloque
$(document).ready(function() {
    // DataTables para desktop
    var tablaConvenios = $('#tabla-convenios').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'dashboard.php?action=convenios_api',
            type: 'GET'
        },
        pageLength: 3,
        lengthMenu: [[3, 5, 10], [3, 5, 10]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        columns: [
            {
                data: null,
                orderable: false,
                className: 'select-checkbox',
                render: function(data, type, row) {
                    return `<input type='checkbox' class='fila-convenio-checkbox' data-id='${row.id}'>`;
                }
            },
            { data: 'id' },
            { data: 'nombre', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'dni' },
            { data: 'especialidad', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'descuento', render: function(data) { return data ? data + ' %' : '0 %'; } },
            { data: 'descripcion', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
            { data: 'email' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<a href='dashboard.php?vista=form_convenio&id=${row.id}' class='btn btn-primary btn-sm'>Editar</a>
                            <a href='dashboard.php?action=eliminar_convenio&id=${row.id}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este convenio?\");'>Eliminar</a>`;
                }
            }
        ],
        dom: 'lBfrtip',
        buttons: [
            { extend: 'excel', text: 'Exportar Excel', className: 'btn btn-success' },
            { extend: 'pdf', text: 'Exportar PDF', className: 'btn btn-danger' },
            { extend: 'print', text: 'Imprimir', className: 'btn btn-secondary' }
        ]
    });

    // Selector de filas: seleccionar/deseleccionar todos
    $('#selectAllConvenios').on('change', function() {
        var checked = this.checked;
        $('.fila-convenio-checkbox').prop('checked', checked);
    });
    // Si se desmarca alguna fila, desmarcar el selectAll
    $('#tabla-convenios').on('change', '.fila-convenio-checkbox', function() {
        if (!this.checked) {
            $('#selectAllConvenios').prop('checked', false);
        } else if ($('.fila-convenio-checkbox:checked').length === $('.fila-convenio-checkbox').length) {
            $('#selectAllConvenios').prop('checked', true);
        }
    });

    // Lógica móvil
    function normalizarTextoConvenio(txt) {
        return txt
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }
    let paginaConvenio = 1;
    let porPaginaConvenio = <?= $por_pagina ?>;
    let totalConvenios = document.querySelectorAll('.convenio-card').length;
    let totalPaginasConvenio = Math.ceil(totalConvenios / porPaginaConvenio);

    function mostrarPaginaActualConvenios() {
        var cards = document.querySelectorAll('.convenio-card');
        var inicio = (paginaConvenio - 1) * porPaginaConvenio;
        var fin = inicio + porPaginaConvenio;
        cards.forEach(function(card, idx) {
            if (idx >= inicio && idx < fin) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
        document.getElementById('paginacionConvenioMovil').style.display = '';
        actualizarPaginacionConvenio();
    }

    function actualizarPaginacionConvenio() {
        let paginacion = document.getElementById('paginacionConvenioPaginas');
        if (!paginacion) {
            paginacion = document.createElement('span');
            paginacion.id = 'paginacionConvenioPaginas';
            document.getElementById('btnSiguienteConvenio').before(paginacion);
        }
        paginacion.innerHTML = '';
        let pages = [];
        if (paginaConvenio > 1) pages.push(paginaConvenio - 1);
        pages.push(paginaConvenio);
        if (paginaConvenio < totalPaginasConvenio) pages.push(paginaConvenio + 1);
        pages.forEach(function(p) {
            let btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm' + (p === paginaConvenio ? ' active' : '');
            btn.textContent = p;
            btn.onclick = function() {
                paginaConvenio = p;
                mostrarPaginaActualConvenios();
            };
            paginacion.appendChild(btn);
        });
        document.getElementById('btnAnteriorConvenio').disabled = (paginaConvenio <= 1);
        document.getElementById('btnSiguienteConvenio').disabled = (paginaConvenio >= totalPaginasConvenio);
    }

    function filtrarCardsConvenios(valor) {
        var filtro = normalizarTextoConvenio(valor);
        var cards = document.querySelectorAll('.convenio-card');
        var algunoVisible = false;
        if (!filtro) {
            mostrarPaginaActualConvenios();
        } else {
            cards.forEach(function(card) {
                var nombre = normalizarTextoConvenio(card.getAttribute('data-nombre') || '');
                var especialidad = normalizarTextoConvenio(card.getAttribute('data-especialidad') || '');
                var dni = normalizarTextoConvenio(card.getAttribute('data-dni') || '');
                if (nombre.includes(filtro) || especialidad.includes(filtro) || dni.includes(filtro)) {
                    card.classList.remove('d-none');
                    algunoVisible = true;
                } else {
                    card.classList.add('d-none');
                }
            });
            document.getElementById('paginacionConvenioMovil').style.display = 'none';
        }
    }

    mostrarPaginaActualConvenios();
    document.getElementById('btnAnteriorConvenio').onclick = function() {
        if (paginaConvenio > 1) {
            paginaConvenio--;
            mostrarPaginaActualConvenios();
        }
    };
    document.getElementById('btnSiguienteConvenio').onclick = function() {
        if (paginaConvenio < totalPaginasConvenio) {
            paginaConvenio++;
            mostrarPaginaActualConvenios();
        }
    };
    document.getElementById('por_pagina_movil_conv').addEventListener('change', function(e) {
        porPaginaConvenio = parseInt(this.value);
        totalConvenios = document.querySelectorAll('.convenio-card').length;
        totalPaginasConvenio = Math.ceil(totalConvenios / porPaginaConvenio);
        paginaConvenio = 1;
        mostrarPaginaActualConvenios();
    });
    document.getElementById('buscadorConvenioMovil').addEventListener('input', function(e) {
        filtrarCardsConvenios(e.target.value);
    });
});
</script>
