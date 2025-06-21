<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$id_cliente = $_SESSION['cliente_id'] ?? null;
$rol = $_SESSION['rol'] ?? null;

// Control de acceso seguro
if (!$id_cliente || strtolower(trim($rol)) !== 'cliente') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Acceso no autorizado.</div></div>';
    return;
}

require_once __DIR__ . '/../conexion/conexion.php';

// Consulta solo las cotizaciones del cliente logueado
$sql = "SELECT c.*, cl.nombre AS nombre_cliente, cl.apellido AS apellido_cliente, cl.dni 
        FROM cotizaciones c
        JOIN clientes cl ON c.id_cliente = cl.id
        WHERE c.id_cliente = ?
        ORDER BY c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-2 mb-md-0">Mis Cotizaciones</h4>
        <a href="dashboard.php?vista=form_cotizacion" class="btn btn-primary">Nueva Cotización</a>
    </div>
    <div class="table-responsive">
        <table id="tablaCotizaciones" class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre y Apellido</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cotizaciones): ?>
                    <?php foreach ($cotizaciones as $cotizacion): ?>
                        <?php
                        $estado = strtolower($cotizacion['estado_pago'] ?? '');
                        $claseEstado = 'bg-secondary text-white';
                        if ($estado === 'pagado') $claseEstado = 'bg-success text-white';
                        elseif ($estado === 'anulado') $claseEstado = 'bg-danger text-white';
                        elseif ($estado === 'pendiente') $claseEstado = 'bg-warning text-dark';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                            <td>S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></td>
                            <td><span class="badge <?= $claseEstado ?>"><?= htmlspecialchars(ucfirst($cotizacion['estado_pago'] ?? '')) ?></span></td>
                            <td>
                                <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-sm" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="dashboard.php?vista=descargar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm" title="Descargar PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No tienes cotizaciones registradas.</td></tr>
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
    $('#tablaCotizaciones').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
