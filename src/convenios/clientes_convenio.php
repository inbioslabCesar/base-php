<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_convenio = $_SESSION['convenio_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;
if (!$id_convenio || strtolower(trim($rol)) !== 'convenio') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

// Obtener IDs de clientes asociados al convenio
$sqlClientes = "SELECT cliente_id FROM convenio_cliente WHERE convenio_id = ?";
$stmtClientes = $pdo->prepare($sqlClientes);
$stmtClientes->execute([$id_convenio]);
$clientesAsociados = $stmtClientes->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="container mt-4">
    <h4>Clientes Asociados al Convenio</h4>
    <?php if ($clientesAsociados): ?>
        <?php
        $inClientes = implode(',', array_fill(0, count($clientesAsociados), '?'));
        $sql = "SELECT * FROM clientes WHERE id IN ($inClientes) ORDER BY nombre, apellido";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($clientesAsociados);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>DNI</th>
                        <th>Edad</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['codigo_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['nombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['apellido'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['dni'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['edad'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)$cliente['telefono'] ?? '') ?></td>
                            <td>
                                <a href="dashboard.php?vista=form_cliente&id=<?= $cliente['id'] ?>" class="btn btn-info btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="clientes/eliminar.php?id=<?= $cliente['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] ?>" class="btn btn-success btn-sm" title="Cotizar">
                                    <i class="bi bi-file-earmark-plus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No hay clientes asociados a este convenio.</div>
    <?php endif; ?>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[1, 'asc']]
    });
});
</script>
