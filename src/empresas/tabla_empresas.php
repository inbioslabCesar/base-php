<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/empresas_crud.php';
$empresas = obtenerTodasLasEmpresas();
?>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] == 1): ?>
                    <div class="alert alert-success">¡Empresa registrada exitosamente!</div>
                <?php elseif ($_GET['success'] == 2): ?>
                    <div class="alert alert-success">¡Empresa actualizada correctamente!</div>
                <?php elseif ($_GET['success'] == 3): ?>
                    <div class="alert alert-success">¡Empresa eliminada correctamente!</div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h4 class="mb-0">Gestión de Empresas</h4>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=form_empresa" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle"></i> Nueva Empresa
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tablaEmpresas" class="table table-hover table-bordered align-middle mb-0" style="width:100%;">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>RUC</th>
                                    <th>Razón Social</th>
                                    <th>Nombre Comercial</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Representante</th>
                                    <th>Convenio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td><?= htmlspecialchars($empresa['id'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['ruc'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['razon_social'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['nombre_comercial'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['direccion'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['telefono'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['representante'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($empresa['convenio'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($empresa['estado'] ?? '') === 'activo' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($empresa['estado'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>dashboard.php?vista=editar_empresa&id=<?= $empresa['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <a href="<?= BASE_URL ?>empresas/funciones/empresas_crud.php?id=<?= $empresa['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta empresa?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables y Bootstrap JS (solo si no están ya incluidos en el footer) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaEmpresas').DataTable({
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        dom: 'Bfrtip',
        buttons: [
            'excelHtml5',
            'pdfHtml5',
            'print'
        ]
    });
});
</script>
