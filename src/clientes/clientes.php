<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';

// Filtro por DNI
$dniFiltro = trim($_GET['dni'] ?? '');

// Consulta con filtro por DNI si aplica
$sql = "SELECT * FROM clientes";
$params = [];
if ($dniFiltro !== '') {
    $sql .= " WHERE dni LIKE ?";
    $params[] = "%$dniFiltro%";
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalize($string) {
    return mb_convert_case(strtolower(trim((string)$string)), MB_CASE_TITLE, "UTF-8");
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h3 class="mb-0">Clientes</h3>
        <a href="dashboard.php?vista=form_cliente" class="btn btn-primary">Nuevo Cliente</a>
    </div>

    <!-- Filtro por DNI -->
    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="hidden" name="vista" value="clientes">
            <input type="text" name="dni" class="form-control" placeholder="Buscar por DNI" value="<?= htmlspecialchars($dniFiltro) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
            <a href="dashboard.php?vista=clientes" class="btn btn-outline-dark">Limpiar</a>
        </div>
    </form>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>
    <div class="table-responsive">
        <table id="tablaClientes" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>DNI</th>
                    <th>Edad</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes): ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= (int)$cliente['id'] ?></td>
                            <td><?= htmlspecialchars($cliente['codigo_cliente'] ?? '') ?></td>
                            <td><?= capitalize($cliente['nombre'] ?? '') ?></td>
                            <td><?= capitalize($cliente['apellido'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['dni'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['edad'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['telefono'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['direccion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cliente['estado'] ?? '') ?></td>
                            <td>
                                <a href="dashboard.php?vista=form_cliente&id=<?= $cliente['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="clientes/eliminar.php?id=<?= $cliente['id'] ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro de eliminar este cliente?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php if ($rol === 'recepcionista' || $rol === 'admin'): ?>
                                <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] ?>" class="btn btn-primary btn-sm" title="Cotizar">
                                    <i class="bi bi-file-earmark-plus"></i> Cotizar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>xs
                <?php else: ?>
                    <tr><td colspan="11" class="text-center">No hay clientes registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables y Bootstrap JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaClientes').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
