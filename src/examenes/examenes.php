<?php
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT * FROM examenes WHERE vigente = 1 ORDER BY id ASC");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto)
{
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>

<style>
/* Fuerza el color de fondo del encabezado de la tabla de Exámenes */
#tabla-examenes thead th {
    background-color: #4f46e5 !important; /* indigo-600 */
    color: #ffffff !important;
}

#tabla-examenes thead th.sorting,
#tabla-examenes thead th.sorting_asc,
#tabla-examenes thead th.sorting_desc {
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
            <h3 class="mb-0 text-white text-3xl">Lista de Exámenes</h3>
        </div>
    </div>
    <a href="dashboard.php?vista=form_examen" class="btn btn-primary mb-3">Agregar Examen</a>
    <div class="table-responsive">
        <table id="tabla-examenes" class="table table-bordered table-striped" style="width:100%; min-width:1200px;">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-sm font-semibold">Código</th>
                    <th class="px-4 py-2 text-sm font-semibold">Nombre</th>
                    <th class="px-4 py-2 text-sm font-semibold">Área</th>
                    <th class="px-4 py-2 text-sm font-semibold">Metodología</th>
                    <th class="px-4 py-2 text-sm font-semibold">Precio Público</th>
                    <th class="px-4 py-2 text-sm font-semibold">Tiempo Respuesta</th>
                    <th class="px-4 py-2 text-sm font-semibold">Detalle</th>
                    <th class="px-4 py-2 text-sm font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- El contenido de la tabla será llenado dinámicamente por DataTables server-side -->
            </tbody>
        </table>
    </div>
</div>

<!-- Cards y paginación móvil -->
<style>
@media (max-width: 768px) {
    .table-responsive { display: none; }
    .examenes-cards-container { display: block; }
    .mobile-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 1.5rem 0 2rem 0;
    }
    .mobile-pagination .page-btn {
        background: #764ba2;
        color: #fff;
        border: none;
        border-radius: 8px;
        min-width: 38px;
        min-height: 38px;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 2px 8px #764ba233;
        transition: background 0.2s, color 0.2s;
    }
    .mobile-pagination .page-btn.active {
        background: #fff;
        color: #764ba2;
        border: 2px solid #764ba2;
    }
    .mobile-pagination .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}
</style>

<?php
// Solo para móvil: paginación y cards
if (isset($_GET['pagina'])) {
    $pagina = max(1, intval($_GET['pagina']));
} else {
    $pagina = 1;
}
$por_pagina = 3;
$total_examenes = count($examenes);
$total_paginas = max(1, ceil($total_examenes / $por_pagina));
$inicio = ($pagina - 1) * $por_pagina;
$examenes_pagina = array_slice($examenes, $inicio, $por_pagina);
?>
<div class="examenes-cards-container d-block d-md-none">
    <h4 class="mb-3">Exámenes</h4>
    <div class="mb-3">
        <div class="input-group">
            <input type="text" id="buscadorExamenMovil" class="form-control" placeholder="Buscar examen por nombre...">
            <button class="btn btn-primary" type="button" onclick="document.getElementById('buscadorExamenMovil').value = ''; filtrarCardsExamenes('');"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <?php
    // Filtrar exámenes por nombre si hay búsqueda
    $examenes_filtrados = $examenes;
    if (!empty($_GET['busqueda'])) {
        $busqueda = mb_strtolower(trim($_GET['busqueda']));
        $examenes_filtrados = array_filter($examenes, function($ex) use ($busqueda) {
            return strpos(mb_strtolower($ex['nombre']), $busqueda) !== false;
        });
    }
    $total_examenes = count($examenes_filtrados);
    $total_paginas = max(1, ceil($total_examenes / $por_pagina));
    $inicio = ($pagina - 1) * $por_pagina;
    $examenes_pagina = array_slice(array_values($examenes_filtrados), $inicio, $por_pagina);
    ?>
    <div id="cardsExamenesMovil">
    <?php foreach ($examenes as $examen): ?>
    <div class="card shadow mb-4 card-examen-movil" data-nombre="<?= htmlspecialchars($examen['nombre'] ?? '') ?>">
            <div class="card-body">
                <h5 class="card-title text-primary mb-2">
                    <i class="fa fa-flask me-2"></i><?= htmlspecialchars(capitalizar($examen['nombre'] ?? '')) ?>
                </h5>
                <span class="badge bg-<?= (isset($examen['vigente']) && $examen['vigente']) ? 'success' : 'danger' ?> ms-1">
                    <?= isset($examen['vigente']) && $examen['vigente'] ? 'Vigente' : 'No vigente' ?>
                </span>
                <div class="row mt-3">
                    <div class="col-6"><span class="fw-semibold text-primary">Código:</span> <span class="text-dark"><?= htmlspecialchars($examen['codigo'] ?? '') ?></span></div>
                    <div class="col-6"><span class="fw-semibold text-primary">Área:</span> <span class="text-dark"><?= htmlspecialchars(capitalizar($examen['area'] ?? '')) ?></span></div>
                    <div class="col-6"><span class="fw-semibold text-primary">Metodología:</span> <span class="text-dark"><?= htmlspecialchars(capitalizar($examen['metodologia'] ?? '')) ?></span></div>
                    <div class="col-6"><span class="fw-semibold text-primary">Precio Público:</span> <span class="text-dark">S/.<?= htmlspecialchars($examen['precio_publico'] ?? '') ?></span></div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="dashboard.php?vista=form_examen&id=<?= $examen['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                        <i class="fa fa-edit"></i>
                    </a>
                    <a href="dashboard.php?action=eliminar_examen&id=<?= $examen['id'] ?>" class="btn btn-danger btn-sm enlace-eliminar-examen" data-id="<?= $examen['id'] ?>" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <!-- Paginación móvil -->
    <nav class="mobile-pagination" id="paginacionExamenMovil">
    <script>
    function normalizarTexto(txt) {
        return txt
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // quita tildes
            .replace(/\s+/g, ' ') // espacios múltiples a uno
            .trim();
    }
    function mostrarPaginaActual() {
        var cards = document.querySelectorAll('.card-examen-movil');
        var inicio = <?= ($pagina - 1) * $por_pagina ?>;
        var fin = inicio + <?= $por_pagina ?>;
        cards.forEach(function(card, idx) {
            if (idx >= inicio && idx < fin) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
        document.getElementById('paginacionExamenMovil').style.display = '';
    }

    function filtrarCardsExamenes(valor) {
        var filtro = normalizarTexto(valor);
        var cards = document.querySelectorAll('.card-examen-movil');
        var algunoVisible = false;
        if (!filtro) {
            mostrarPaginaActual();
        } else {
            cards.forEach(function(card) {
                var nombre = normalizarTexto(card.getAttribute('data-nombre'));
                if (nombre.includes(filtro)) {
                    card.classList.remove('d-none');
                    algunoVisible = true;
                } else {
                    card.classList.add('d-none');
                }
            });
            document.getElementById('paginacionExamenMovil').style.display = 'none';
        }
    }

    // Al cargar la página, mostrar solo la página actual
    document.addEventListener('DOMContentLoaded', function() {
        mostrarPaginaActual();
    });
    document.getElementById('buscadorExamenMovil').addEventListener('input', function(e) {
        filtrarCardsExamenes(e.target.value);
    });
    </script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Interceptar cualquier enlace que apunte a eliminar_examen
document.addEventListener('click', async function (e) {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href') || '';
    if (href.includes('action=eliminar_examen')) {
        e.preventDefault();
        try {
            const url = new URL(href, window.location.origin);
            const id = url.searchParams.get('id');
            if (!id) return;

            // Consultar relaciones antes de proceder
            const resp = await fetch('examenes/examenes_relaciones.php?id=' + encodeURIComponent(id));
            const json = await resp.json();
            const relacionados = json.ok && json.tiene_relaciones ? (json.conteo || 0) : 0;

            if (relacionados > 0) {
                const r = await Swal.fire({
                    icon: 'warning',
                    title: 'Este examen está relacionado',
                    html: 'Se encontró <b>' + relacionados + '</b> registro(s) en otras tablas.<br>Para preservar el historial, se marcará como <b>INACTIVO</b> en lugar de eliminarse.',
                    confirmButtonText: 'Desactivar',
                    cancelButtonText: 'Cancelar',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                });
                if (r.isConfirmed) {
                    // Redirige al handler para baja lógica
                    window.location.href = href;
                }
            } else {
                const r = await Swal.fire({
                    icon: 'question',
                    title: 'Eliminar examen',
                    text: 'Esta acción eliminará definitivamente el examen. ¿Deseas continuar?',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                });
                if (r.isConfirmed) {
                    window.location.href = href;
                }
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo verificar relaciones',
                text: 'Intenta nuevamente. Si el problema persiste, contácta al administrador.'
            });
        }
    }
});
</script>
        <?php
        // Mantener todos los parámetros excepto 'pagina'
        $params = $_GET;
        unset($params['pagina']);
        $baseUrl = '';
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $baseUrl .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
            }
        }
        ?>
        <form method="get" style="display:inline">
            <?= $baseUrl ?>
            <button class="page-btn" type="submit" name="pagina" value="<?= max(1, $pagina-1) ?>" <?= $pagina <= 1 ? 'disabled' : '' ?>>&#8592;</button>
        </form>
        <?php
        $pages = [];
        if ($pagina > 1) $pages[] = $pagina-1;
        $pages[] = $pagina;
        if ($pagina < $total_paginas) $pages[] = $pagina+1;
        foreach ($pages as $p): ?>
            <form method="get" style="display:inline">
                <?= $baseUrl ?>
                <button class="page-btn<?= $p == $pagina ? ' active' : '' ?>" type="submit" name="pagina" value="<?= $p ?>"><?= $p ?></button>
            </form>
        <?php endforeach; ?>
        <form method="get" style="display:inline">
            <?= $baseUrl ?>
            <button class="page-btn" type="submit" name="pagina" value="<?= min($total_paginas, $pagina+1) ?>" <?= $pagina >= $total_paginas ? 'disabled' : '' ?>>&#8594;</button>
        </form>
    </nav>
</div>
</div>

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
    $(document).ready(function() {
        $('#tabla-examenes').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'dashboard.php?action=examenes_api',
                type: 'GET'
            },
            pageLength: 3,
            lengthMenu: [[3, 5, 10], [3, 5, 10]],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            dom: 'lBfrtip',
            columns: [
                { data: 'codigo' },
                { data: 'nombre', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
                { data: 'area', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
                { data: 'metodologia', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : ''; } },
                { data: 'precio_publico', render: function(data) { return 'S/.' + data; } },
                { data: 'tiempo_respuesta' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button type="button" class="btn btn-info btn-sm rounded-circle btn-detalle-examen" title="Ver detalle" data-id="${row.id}">
                            <i class="fa fa-search"></i>
                        </button>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `<a href="dashboard.php?vista=form_examen&id=${row.id}" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="dashboard.php?action=eliminar_examen&id=${row.id}" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este examen?');">
                                    <i class="fa fa-trash"></i>
                                </a>`;
                    }
                }
            ],
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
                // Handler para abrir modal de detalle
                $('#tabla-examenes').on('click', '.btn-detalle-examen', async function() {
                        try {
                                const id = this.getAttribute('data-id');
                                const resp = await fetch('examenes/detalle_examen_modal.php?id=' + encodeURIComponent(id));
                                const html = await resp.text();
                                const cont = document.getElementById('modalDetalleExamenContent');
                                if (cont) cont.innerHTML = html;
                                const modal = new bootstrap.Modal(document.getElementById('modalDetalleExamen'));
                                modal.show();
                        } catch (e) {
                                console.error(e);
                        }
                });
    });
</script>

<!-- Modal Detalle Examen -->
<div class="modal fade" id="modalDetalleExamen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="modalDetalleExamenContent"></div>
    </div>
</div>