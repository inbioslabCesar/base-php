<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Definir el rol y el id_cliente de forma segura
$rol = $_SESSION['rol'] ?? null;
if ($rol === 'cliente') {
    $id_cliente = $_SESSION['cliente_id'] ?? '';
} else {
    $id_cliente = isset($_GET['id']) ? intval($_GET['id']) : '';
}

// Validar que el id_cliente esté presente
if (empty($id_cliente)) {
    echo "<div class='alert alert-danger mt-4'>No se pudo identificar al cliente. Por favor, vuelve al listado de clientes.</div>";
    exit;
}

// 1. Exámenes disponibles
$stmt = $pdo->query("SELECT id, codigo, nombre, descripcion, tiempo_respuesta, preanalitica_cliente, observaciones, precio_publico FROM examenes WHERE vigente = 1 ORDER BY nombre");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$examenes_json = json_encode($examenes);
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<div class="container mt-5 mb-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Nueva Cotización</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Después de guardar la cotización, podrás agendar la cita para la toma de muestra.
            </div>
            <form action="dashboard.php?action=crear_cotizacion" method="POST" id="formCotizacion">
                <input type="hidden" name="id_cliente" value="<?= htmlspecialchars($id_cliente) ?>">
                <div class="mb-3">
                    <label for="buscadorExamen" class="form-label">Buscar examen</label>
                    <select id="buscadorExamen" class="form-select" style="width:100%;">
                        <option value="">Escribe para buscar un examen...</option>
                        <?php foreach ($examenes as $ex): ?>
                            <option value="<?php echo $ex['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($ex['nombre']); ?>"
                                data-precio="<?php echo $ex['precio_publico']; ?>">
                                <?php echo htmlspecialchars($ex['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="examenes-seleccionados"></div>
                <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="0">
                <!-- Footer fijo -->
                <div class="fixed-bottom bg-white border-top shadow-sm p-3" id="footerCotizacion" style="z-index:1040;">
                    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div>
                            <label class="me-2">Total:</label>
                            <span id="totalCotizacion" class="fw-bold fs-5 text-primary">S/. 0.00</span>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-success me-2">Guardar Cotización</button>
                            <a href="javascript:history.back()" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal para detalles del examen -->
<div class="modal fade" id="modalDetalleExamen" tabindex="-1" aria-labelledby="modalDetalleExamenLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleExamenLabel">Detalle del Examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detalleExamenBody">
                <!-- Detalle dinámico -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let examenesData = <?= $examenes_json ?>;
let examenesSeleccionados = [];

$(function() {
    $('#buscadorExamen').select2({
        placeholder: "Escribe para buscar un examen...",
        allowClear: true,
        width: '100%'
    });

    $('#buscadorExamen').on('select2:select', function(e) {
        let id = e.params.data.id;
        let examen = examenesData.find(ex => ex.id == id);
        if (!examenesSeleccionados.find(ex => ex.id == id)) {
            examenesSeleccionados.push({
                ...examen,
                cantidad: 1
            });
            renderizarLista();
        }
        $(this).val('').trigger('change');
    });

    // Cambiar cantidad
    $(document).on('input', '.cantidadExamen', function() {
        let idx = $(this).data('idx');
        examenesSeleccionados[idx].cantidad = parseInt($(this).val()) || 1;
        renderizarLista();
    });

    // Quitar examen
    $(document).on('click', '.btn-remove', function() {
        let idx = $(this).data('idx');
        examenesSeleccionados.splice(idx, 1);
        renderizarLista();
    });

    // Ver detalles
    $(document).on('click', '.btn-detalle', function() {
        let idx = $(this).data('idx');
        let ex = examenesSeleccionados[idx];
        let detalle = `
            <table class="table table-bordered">
                <tr><th>Código</th><td>${ex.codigo}</td></tr>
                <tr><th>Nombre</th><td>${ex.nombre}</td></tr>
                <tr><th>Descripción</th><td>${ex.descripcion || '-'}</td></tr>
                <tr><th>Tiempo de respuesta</th><td>${ex.tiempo_respuesta || '-'}</td></tr>
                <tr><th>Preanalítica cliente</th><td>${ex.preanalitica_cliente || '-'}</td></tr>
                <tr><th>Observaciones</th><td>${ex.observaciones || '-'}</td></tr>
            </table>
        `;
        $('#detalleExamenBody').html(detalle);
        let modal = new bootstrap.Modal(document.getElementById('modalDetalleExamen'));
        modal.show();
    });

    renderizarLista();
});

function renderizarLista() {
    let html = '';
    let total = 0;
    if (examenesSeleccionados.length === 0) {
        html = '<div class="alert alert-warning text-center">No hay exámenes seleccionados.</div>';
    } else {
        html += `<div class="table-responsive"><table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Examen</th>
                    <th style="width:90px;">Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                    <th colspan="2">Acciones</th>
                </tr>
            </thead>
            <tbody>`;
        examenesSeleccionados.forEach((ex, idx) => {
            let precio = parseFloat(ex.precio_publico);
            let subtotal = precio * ex.cantidad;
            total += subtotal;
            html += `
                <tr>
                    <td>${ex.nombre}</td>
                    <td>
                        <input type="number" min="1" class="form-control cantidadExamen" data-idx="${idx}" value="${ex.cantidad}">
                    </td>
                    <td>S/. ${precio.toFixed(2)}</td>
                    <td class="fw-bold">S/. ${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-info btn-sm btn-detalle" data-idx="${idx}" title="Ver detalles">
                            <i class="bi bi-info-circle"></i> Detalles
                        </button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm btn-remove" data-idx="${idx}" title="Quitar">
                            <i class="bi bi-x-circle"></i> Quitar
                        </button>
                    </td>
                    <input type="hidden" name="examenes[]" value="${ex.id}">
                    <input type="hidden" name="cantidades[]" value="${ex.cantidad}">
                </tr>
            `;
        });
        html += `</tbody></table></div>`;
    }
    $('#examenes-seleccionados').html(html);
    $('#totalCotizacion').text('S/. ' + total.toFixed(2));
}
</script>
