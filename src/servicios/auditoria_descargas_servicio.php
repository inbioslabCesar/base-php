<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/funciones/servicios_schema.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    servicios_asegurar_tabla($pdo);
} catch (Throwable $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger">No se pudo inicializar la auditoria de servicios.</div></div>';
    return;
}

$servicioId = (int)($_SESSION['servicio_id'] ?? 0);
if ($servicioId <= 0 || strtolower(trim((string)($_SESSION['rol'] ?? ''))) !== 'servicio') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

$q = trim((string)($_GET['q'] ?? ''));
$fechaDesde = trim((string)($_GET['fecha_desde'] ?? ''));
$fechaHasta = trim((string)($_GET['fecha_hasta'] ?? ''));

$sql = "SELECT a.id, a.cotizacion_id, a.cliente_id, a.usuario_email, a.ip, a.created_at,
               c.codigo AS codigo_cotizacion,
               cl.nombre, cl.apellido, cl.dni
        FROM servicio_descargas_auditoria a
        LEFT JOIN cotizaciones c ON c.id = a.cotizacion_id
        LEFT JOIN clientes cl ON cl.id = a.cliente_id
        WHERE a.servicio_id = ?";
$params = [$servicioId];

if ($q !== '') {
    $sql .= " AND (
        c.codigo LIKE ? OR
        cl.nombre LIKE ? OR
        cl.apellido LIKE ? OR
        cl.dni LIKE ? OR
        a.usuario_email LIKE ? OR
        a.ip LIKE ?
    )";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($fechaDesde !== '') {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $fechaDesde;
}
if ($fechaHasta !== '') {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $fechaHasta;
}

$sql .= " ORDER BY a.id DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h4 class="mb-3">Auditoria de descargas (Servicio)</h4>

    <form method="get" action="dashboard.php" class="row g-2 mb-3">
        <input type="hidden" name="vista" value="servicio_auditoria">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Buscar por cotizacion, paciente, DNI, email o IP" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fechaDesde) ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fechaHasta) ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <div class="col-md-2 d-grid">
            <a href="dashboard.php?vista=servicio_auditoria" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <?php if (!$rows): ?>
        <div class="alert alert-warning">No hay descargas registradas con los filtros actuales.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cotizacion</th>
                        <th>Paciente</th>
                        <th>DNI</th>
                        <th>Email usuario</th>
                        <th>IP</th>
                        <th>Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $pacienteNombre = trim((string)($row['nombre'] ?? '') . ' ' . (string)($row['apellido'] ?? '')); ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><?= htmlspecialchars((string)($row['codigo_cotizacion'] ?: ('COT-' . (int)$row['cotizacion_id']))) ?></td>
                            <td><?= htmlspecialchars($pacienteNombre !== '' ? $pacienteNombre : ('Paciente #' . (int)($row['cliente_id'] ?? 0))) ?></td>
                            <td><?= htmlspecialchars((string)($row['dni'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['usuario_email'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['ip'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
