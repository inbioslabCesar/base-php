<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/clientes_crud.php';
$clientes = obtenerTodosLosClientes();
?>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] == 1): ?>
                    <div class="alert alert-success">¡Cliente registrado exitosamente!</div>
                <?php elseif ($_GET['success'] == 2): ?>
                    <div class="alert alert-success">¡Cliente actualizado correctamente!</div>
                <?php elseif ($_GET['success'] == 3): ?>
                    <div class="alert alert-success">¡Cliente eliminado correctamente!</div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h4 class="mb-0">Gestión de Clientes</h4>
                    <a href="<?= BASE_URL ?>dashboard.php?vista=form_clientes" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle"></i> Nuevo Cliente
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tablaClientes" class="table table-hover table-bordered align-middle mb-0" style="width:100%;">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Sexo</th>
                                    <th>Edad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cliente['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['codigo_cliente'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['nombre'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['apellido'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['dni'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['telefono'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['sexo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cliente['edad'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($cliente['estado'] ?? '') === 'activo' ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($cliente['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>dashboard.php?vista=editar_cliente&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                            <a href="<?= BASE_URL ?>clientes/eliminar_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
        $('#tablaClientes').DataTable({
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