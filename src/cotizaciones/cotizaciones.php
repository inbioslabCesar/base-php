<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Consulta de cotizaciones (ajusta los filtros según el rol si lo necesitas)

$nombre_columna = '';
if (isset($_SESSION['cliente_id'])) {
    $sql = "SELECT c.*, cl.nombre AS cliente 
            FROM cotizaciones c 
            INNER JOIN clientes cl ON c.id_cliente = cl.id 
            WHERE c.id_cliente = :id 
            ORDER BY c.fecha DESC";
    $id = $_SESSION['cliente_id'];
    $nombre_columna = 'cliente';
} elseif (isset($_SESSION['empresa_id'])) {
    $sql = "SELECT c.*, e.nombre AS empresa 
            FROM cotizaciones c 
            INNER JOIN empresas e ON c.id_empresa = e.id 
            WHERE c.id_empresa = :id 
            ORDER BY c.fecha DESC";
    $id = $_SESSION['empresa_id'];
    $nombre_columna = 'empresa';
} elseif (isset($_SESSION['convenio_id'])) {
    $sql = "SELECT c.*, v.nombre AS convenio 
            FROM cotizaciones c 
            INNER JOIN convenios v ON c.id_convenio = v.id 
            WHERE c.id_convenio = :id 
            ORDER BY c.fecha DESC";
    $id = $_SESSION['convenio_id'];
    $nombre_columna = 'convenio';
} else {
    $cotizaciones = [];
}

if (isset($id)) {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
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
    <table id="cotizacionesTable" class="table table-striped table-bordered table-responsive">
        <thead>
            <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th class="text-end">Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($cotizaciones) > 0): ?>
                <?php foreach ($cotizaciones as $cot): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cot['codigo']); ?></td>

                    <td class="capitalize"><?php echo ucwords(strtolower($cot['cliente'])); ?></td>
                    
                    <td><?php echo htmlspecialchars($cot['fecha']); ?></td>
                    <td class="text-end">S/. <?php echo number_format($cot['total'], 2); ?></td>
                    <td>
                        <?php if ($cot['estado_pago'] === 'pagado'): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Completado</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> En Proceso</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-info btn-sm btn-detalle-cot" data-id="<?php echo $cot['id']; ?>" title="Ver Detalles">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="dashboard.php?vista=ver_cotizacion&id=<?php echo $cot['id']; ?>" class="btn btn-secondary btn-sm" title="Ver">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="dashboard.php?vista=descargar_pdf&id=<?php echo $cot['id']; ?>" class="btn btn-danger btn-sm" title="PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No hay cotizaciones para mostrar.</td>
                </tr>
            <?php endif; ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).on('click', '.btn-detalle-cot', function(){
    var idCot = $(this).data('id');
    $('#detalleCotizacionBody').html('<div class="text-center"><div class="spinner-border text-primary"></div> Cargando...</div>');
    var modal = new bootstrap.Modal(document.getElementById('modalDetalleCotizacion'));
    modal.show();
    $.get('cotizaciones/detalle_cotizacion.php', {id: idCot}, function(res){
        $('#detalleCotizacionBody').html(res);
    });
});
</script>

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
