<?php
require_once __DIR__ . '/../conexion/conexion.php';

$stmt = $pdo->query("SELECT * FROM examenes ORDER BY id ASC");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalizar($texto) {
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
                                <div class="modal-dialog modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel<?= $examen['id'] ?>">Detalle del Examen</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <strong>Codigo:</strong> <?= htmlspecialchars($examen['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                            <strong>Descripción:</strong> <?= nl2br(htmlspecialchars($examen['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
                                            <strong>Preanalítica Cliente:</strong> <?= htmlspecialchars($examen['preanalitica_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                            <strong>Preanalítica Referencias:</strong> <?= htmlspecialchars($examen['preanalitica_referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                            <strong>Tipo de Muestra:</strong> <?= htmlspecialchars($examen['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                            <strong>Tipo de Tubo:</strong> <?= htmlspecialchars($examen['tipo_tubo'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                            <strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($examen['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
                                            <strong>Adicional:</strong> <?= nl2br(htmlspecialchars($examen['adicional'] ?? '', ENT_QUOTES, 'UTF-8')) ?><br>
                                            <strong>Vigente:</strong> <?= isset($examen['vigente']) && $examen['vigente'] ? 'Sí' : 'No' ?><br>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="dashboard.php?vista=form_examen&id=<?= $examen['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                            <a href="dashboard.php?action=eliminar_examen&id=<?= $examen['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este examen?');">Eliminar</a>
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
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        dom: 'Bfrtip',
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
