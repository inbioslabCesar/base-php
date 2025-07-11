<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
if (!in_array($rol, ['admin', 'recepcionista'])) {
    echo "<div class='alert alert-danger'>No tienes permiso para ver esta sección.</div>";
    exit;
}

// Consulta mejorada: trae código, nombre del cliente y datos de la toma
$pendientes = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.codigo, 
            c.fecha_toma, 
            c.hora_toma, 
            c.tipo_toma, 
            c.direccion_toma,
            cl.nombre as nombre_cliente,
            cl.apellido as apellido_cliente
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.estado_muestra = 'pendiente'
          AND c.fecha_toma IS NOT NULL
          AND c.hora_toma IS NOT NULL
          AND (
              (c.tipo_toma = 'domicilio')
              OR (c.tipo_toma = 'laboratorio' AND (c.fecha_toma > CURDATE() OR (c.fecha_toma = CURDATE() AND c.hora_toma > CURTIME())))
          )
        ORDER BY c.fecha_toma, c.hora_toma
    ");
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error al obtener los datos: " . $e->getMessage() . "</div>";
    exit;
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h3 class="mb-4 text-warning">
                <i class="bi bi-clock-history"></i> Exámenes pendientes de toma de muestra
            </h3>
            <?php if (empty($pendientes)): ?>
                <div class="alert alert-info">No hay exámenes pendientes de toma de muestra.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código Cotización</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Dirección</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                                    <td>
                                        <i class="bi bi-person"></i>
                                        <?= htmlspecialchars($p['nombre_cliente'] . ' ' . ($p['apellido_cliente'] ?? '')) ?>
                                    </td>
                                    <td><?= htmlspecialchars($p['fecha_toma']) ?></td>
                                    <td><?= htmlspecialchars($p['hora_toma']) ?></td>
                                    <td>
                                        <span class="badge <?= $p['tipo_toma'] === 'domicilio' ? 'bg-info text-dark' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars(ucfirst($p['tipo_toma'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($p['direccion_toma'] ?? '-') ?></td>
                                    <td>
                                        <form action="dashboard.php?action=confirmar_toma" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle"></i> Confirmar toma
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>dashboard.php?vista=admin" class="btn btn-secondary mt-3">
                <i class="bi bi-arrow-left"></i> Volver al panel
            </a>
        </div>
    </div>
</div>
