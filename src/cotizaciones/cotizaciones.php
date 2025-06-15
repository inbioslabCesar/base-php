<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';


// Consulta de cotizaciones (ajusta los filtros según el rol si lo necesitas)

if (isset($_SESSION['cliente_id'])) {
    $sql = "SELECT * FROM cotizaciones WHERE id_cliente = :id ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $_SESSION['cliente_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($_SESSION['empresa_id'])) {
    $sql = "SELECT * FROM cotizaciones WHERE id_empresa = :id ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $_SESSION['empresa_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($_SESSION['convenio_id'])) {
    $sql = "SELECT * FROM cotizaciones WHERE id_convenio = :id ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $_SESSION['convenio_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mostrar todas las cotizaciones si no hay filtro de cliente, empresa o convenio
    $sql = "SELECT * FROM cotizaciones ORDER BY fecha DESC";
    $stmt = $pdo->query($sql);
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Historial de Cotizaciones</h2>
        <a href="dashboard.php?vista=form_cotizacion" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Cotización
        </a>
    </div>
    <table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th class="capitalize">Rol Creador</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($cotizaciones)): ?>
        <?php foreach ($cotizaciones as $cotizacion): ?>
            <tr>
                <td><?php echo isset($cotizacion['codigo']) ? htmlspecialchars($cotizacion['codigo']) : ''; ?></td>
                <td class="capitalize">
                    <?php
                    // Si solo tienes id_cliente, muestra el ID o haz un JOIN en la consulta para mostrar el nombre real.
                    echo isset($cotizacion['id_cliente']) ? htmlspecialchars($cotizacion['id_cliente']) : 'Sin cliente';
                    ?>
                </td>
                <td><?php echo isset($cotizacion['fecha']) ? htmlspecialchars($cotizacion['fecha']) : ''; ?></td>
                <td><?php echo isset($cotizacion['total']) ? number_format($cotizacion['total'], 2) : '0.00'; ?></td>
                <td><?php echo isset($cotizacion['estado_pago']) ? htmlspecialchars($cotizacion['estado_pago']) : ''; ?></td>
                <td class="capitalize">
                    <?php echo isset($cotizacion['rol_creador']) && $cotizacion['rol_creador'] !== null
                        ? htmlspecialchars($cotizacion['rol_creador'])
                        : ''; ?>
                </td>
                <td>
                    <a href="dashboard.php?vista=ver_cotizacion&id=<?php echo $cotizacion['id']; ?>" class="btn btn-info btn-sm" title="Ver">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="dashboard.php?vista=buscar_cotizacion&id=<?php echo $cotizacion['id']; ?>" class="btn btn-secondary btn-sm" title="Buscar">
                        <i class="fas fa-search"></i>
                    </a>
                    <a href="dashboard.php?vista=descargar_pdf&id=<?php echo $cotizacion['id']; ?>" class="btn btn-danger btn-sm" title="Descargar PDF">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center">No hay cotizaciones registradas.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

</div>
<!-- Modal Detalles de Cotización -->
<div class="modal fade" id="modalDetalleCotizacion" tabindex="-1" aria-labelledby="modalDetalleCotizacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalDetalleCotizacionLabel">Detalle de Cotización</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="detalleCotizacionBody">
        <div class="text-center"><div class="spinner-border text-primary"></div> Cargando...</div>
      </div>
    </div>
  </div>
</div>
