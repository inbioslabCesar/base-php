<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger">No se pudo inicializar el modulo de servicios.</div></div>';
    return;
}

$servicioId = (int)($_SESSION['servicio_id'] ?? 0);
if ($servicioId <= 0 || strtolower(trim((string)($_SESSION['rol'] ?? ''))) !== 'servicio') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT c.id, c.codigo_cliente, c.nombre, c.apellido, c.dni, c.edad, c.email, c.telefono
        FROM servicio_cliente sc
        INNER JOIN clientes c ON c.id = sc.cliente_id
        WHERE sc.servicio_id = ?";
$params = [$servicioId];

if ($q !== '') {
    $sql .= " AND (
        c.codigo_cliente LIKE ? OR
        c.nombre LIKE ? OR
        c.apellido LIKE ? OR
        c.dni LIKE ? OR
        c.email LIKE ?
    )";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= "
        ORDER BY c.nombre, c.apellido";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
    <h4 class="mb-3">Pacientes del Servicio</h4>

    <form method="get" action="dashboard.php" class="row g-2 mb-3">
        <input type="hidden" name="vista" value="servicio_clientes">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Buscar por codigo, nombre, apellido, DNI o email" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <div class="col-md-2 d-grid">
            <a href="dashboard.php?vista=servicio_clientes" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <?php if (!$clientes): ?>
        <div class="alert alert-warning">No hay pacientes asociados a este servicio.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>DNI</th>
                        <th>Edad</th>
                        <th>Email</th>
                        <th>Telefono</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($cliente['codigo_cliente'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['nombre'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['apellido'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['dni'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['edad'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['email'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($cliente['telefono'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
