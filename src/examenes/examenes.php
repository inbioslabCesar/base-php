<?php
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT * FROM examenes ORDER BY id ASC");
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

<div class="container mt-4">
    <h2>Lista de Exámenes</h2>
    <a href="dashboard.php?vista=form_examen" class="btn btn-primary mb-3">Agregar Examen</a>
    <div class="table-responsive">
        <table id="tabla-examenes" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Metodología</th>
                    <th>Precio Público</th>
                    <th>Tiempo Respuesta</th>
                    <th>Detalle</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($examenes): ?>
                    <?php foreach ($examenes as $examen): ?>
                        <tr>
                            <td><?= htmlspecialchars($examen['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(capitalizar($examen['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(capitalizar($examen['area'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(capitalizar($examen['metodologia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>S/.<?= htmlspecialchars($examen['precio_publico'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($examen['tiempo_respuesta'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-info btn-sm rounded-circle" title="Ver detalle"
                                    data-bs-toggle="modal" data-bs-target="#modalDetalle<?= $examen['id'] ?>">
                                    <i class="fa fa-search"></i>
                                </button>
                                <!-- Modal Detalle -->
                                <div class="modal fade" id="modalDetalle<?= $examen['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $examen['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content shadow-lg border-0">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title fw-bold" id="modalLabel<?= $examen['id'] ?>">
                                                    <i class="fa fa-flask me-2"></i>Detalle del Examen
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body bg-light">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle mb-4" style="background: #fff; border-radius: 12px; overflow: hidden;">
                                                        <tbody>
                                                            <tr>
                                                                <th class="text-primary">Código</th>
                                                                <td><?= htmlspecialchars($examen['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <th class="text-primary">Preanalítica Cliente</th>
                                                                <td><?= htmlspecialchars($examen['preanalitica_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="text-primary">Tipo de Muestra</th>
                                                                <td><?= htmlspecialchars($examen['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <th class="text-primary">Preanalítica Referencias</th>
                                                                <td><?= htmlspecialchars($examen['preanalitica_referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="text-primary">Tipo de Tubo</th>
                                                                <td><?= htmlspecialchars($examen['tipo_tubo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <th class="text-primary">Vigente</th>
                                                                <td><span class="badge bg-<?= (isset($examen['vigente']) && $examen['vigente']) ? 'success' : 'danger' ?> ms-1"><?= isset($examen['vigente']) && $examen['vigente'] ? 'Sí' : 'No' ?></span></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="fw-semibold text-primary">Descripción:</span><br>
                                                    <span class="text-dark small"><?= nl2br(htmlspecialchars($examen['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?></span>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="fw-semibold text-primary">Observaciones:</span><br>
                                                    <span class="text-dark small"><?= nl2br(htmlspecialchars($examen['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')) ?></span>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-white border-0 d-flex justify-content-end">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-dismiss="modal" title="Cerrar">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                                <a href="dashboard.php?vista=form_examen&id=<?= $examen['id'] ?>" class="btn btn-warning btn-sm ms-2" title="Editar">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="dashboard.php?action=eliminar_examen&id=<?= $examen['id'] ?>" class="btn btn-danger btn-sm ms-2" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este examen?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="d-flex gap-2 justify-content-center">
                                <a href="dashboard.php?vista=form_examen&id=<?= $examen['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="dashboard.php?action=eliminar_examen&id=<?= $examen['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este examen?');">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No hay exámenes registrados.</td>
                    </tr>
                <?php endif; ?>
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
                    <a href="dashboard.php?action=eliminar_examen&id=<?= $examen['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este examen?');">
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
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            dom: 'Bfrtip',
            buttons: [{
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