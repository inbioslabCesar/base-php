<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';

// Filtro por DNI
$dniFiltro = trim($_GET['dni'] ?? '');

// Consulta principal con JOINs para traer los nombres correctos de empresa y convenio
$sql = "
SELECT c.*, 
    (SELECT e.nombre_comercial FROM empresa_cliente ec 
     JOIN empresas e ON ec.empresa_id = e.id 
     WHERE ec.cliente_id = c.id LIMIT 1) AS nombre_empresa,
    (SELECT v.nombre FROM convenio_cliente cc 
     JOIN convenios v ON cc.convenio_id = v.id 
     WHERE cc.cliente_id = c.id LIMIT 1) AS nombre_convenio
FROM clientes c
";
$params = [];
if ($dniFiltro !== '') {
    $sql .= " WHERE c.dni LIKE ?";
    $params[] = "%$dniFiltro%";
}
$sql .= " ORDER BY c.fecha_registro DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function capitalize($string) {
    return mb_convert_case(strtolower(trim((string)$string)), MB_CASE_TITLE, "UTF-8");
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h3 class="mb-0">Pacientes</h3>
    <a href="dashboard.php?vista=form_cliente" class="btn btn-primary">Nuevo Paciente</a>
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
                    <th>Rol creador</th>
                    <th>Referencia</th>
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
                                <?php
                                    $rol_creador = strtolower(trim($cliente['rol_creador'] ?? ''));
                                    $roles_validos = ['admin', 'recepcionista', 'empresa', 'convenio'];
                                    $rol_mostrar = in_array($rol_creador, $roles_validos) && $rol_creador !== '' 
                                        ? ucfirst($rol_creador) 
                                        : 'Paciente';
                                ?>
                                <span class="badge bg-info text-dark"><?= $rol_mostrar ?></span>
                            </td>
                            <td>
                                <?php
                                    $emp = $cliente['nombre_empresa'] ?? '';
                                    $conv = $cliente['nombre_convenio'] ?? '';
                                    $output = [];
                                    if ($emp) $output[] = '<span class="badge bg-success">' . htmlspecialchars($emp) . '</span>';
                                    if ($conv) $output[] = '<span class="badge bg-primary">' . htmlspecialchars($conv) . '</span>';
                                    echo implode(' ', $output) ?: '<span class="text-muted">-</span>';
                                ?>
                            </td>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="13" class="text-center">No hay pacientes registrados.</td></tr>
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
        "pageLength": 5,
        "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
        "order": [], // No aplicar ordenamiento inicial, mantener el orden de la consulta
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
