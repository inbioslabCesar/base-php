<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';


$rol = $_SESSION['rol'] ?? null;

if ($rol === 'cliente') {
    $id_cliente = $_SESSION['cliente_id'] ?? null;
} else {
    $id_cliente = isset($_GET['id']) ? intval($_GET['id']) : null;
}

// Validar que el id_cliente es válido y existe en la base de datos
$clienteExiste = false;
if ($id_cliente) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE id = ?");
    $stmt->execute([$id_cliente]);
    $clienteExiste = $stmt->fetchColumn() > 0;
}

if (!$clienteExiste) {
    // Puedes mostrar un mensaje y detener la ejecución
    echo "<div class='alert alert-danger'>El cliente seleccionado no existe.</div>";
    exit;
}

echo "<div>ID Cliente: " . htmlspecialchars($id_cliente) . "</div>";


// 1. Promociones activas (todas, sin importar asociación)
$hoy = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT *
    FROM promociones
    WHERE activo = 1 AND vigente = 1
      AND fecha_inicio <= ? AND fecha_fin >= ?
");
$stmt->execute([$hoy, $hoy]);
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Exámenes disponibles
$stmt = $pdo->query("SELECT id, codigo, nombre, descripcion, tiempo_respuesta, preanalitica_cliente, observaciones, precio_publico FROM examenes WHERE vigente = 1 ORDER BY nombre");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$examenes_json = json_encode($examenes);

// 3. Promociones por examen (opcional, si quieres aplicar descuentos automáticos)
$stmt = $pdo->prepare("
    SELECT p.*, pe.examen_id
    FROM promociones p
    JOIN promociones_examen pe ON p.id = pe.promocion_id
    WHERE p.activo = 1 AND p.vigente = 1
      AND p.fecha_inicio <= ? AND p.fecha_fin >= ?
");
$stmt->execute([$hoy, $hoy]);
$promos_examen = $stmt->fetchAll(PDO::FETCH_ASSOC);
$promo_map = [];
foreach ($promos_examen as $promo) {
    $promo_map[$promo['examen_id']] = [
        'descuento' => $promo['descuento'],
        'precio_promocional' => $promo['precio_promocional'],
        'titulo' => $promo['titulo'],
        'fecha_inicio' => $promo['fecha_inicio'],
        'fecha_fin' => $promo['fecha_fin'],
    ];
}
$promo_map_json = json_encode($promo_map);
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<div class="container mt-4 mb-5">
    <h2>Nueva Cotización</h2>

    <!-- Carrusel de todas las promociones activas -->
    <?php if (count($promos) > 0): ?>
    <div id="promoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
      <div class="carousel-inner">
        <?php foreach ($promos as $idx => $promo): ?>
          <div class="carousel-item <?php if ($idx === 0) echo 'active'; ?>">
            <div class="row align-items-center">
              <div class="col-md-4">
                <?php if ($promo['imagen']): ?>
                    <img src="ruta/a/imagenes/<?php echo $promo['imagen']; ?>" class="d-block w-100 rounded" alt="Promo">
                <?php else: ?>
                    <img src="ruta/a/imagenes/promocion_default.jpg" class="d-block w-100 rounded" alt="Promo">
                <?php endif; ?>
              </div>
              <div class="col-md-8">
                <h5 class="mb-1"><?php echo htmlspecialchars($promo['titulo']); ?></h5>
                <p class="mb-1"><?php echo htmlspecialchars($promo['descripcion']); ?></p>
                <?php if ($promo['descuento'] > 0): ?>
                    <span class="badge bg-success">Descuento: <?php echo $promo['descuento']; ?>%</span>
                <?php elseif ($promo['precio_promocional'] > 0): ?>
                    <span class="badge bg-warning text-dark">Precio promocional: S/. <?php echo number_format($promo['precio_promocional'],2); ?></span>
                <?php endif; ?>
                <span class="badge bg-info text-dark">Válido: <?php echo date('d/m/Y', strtotime($promo['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
    <?php endif; ?>

    <!-- Formulario de cotización -->
    <form action="dashboard.php?action=crear_cotizacion" method="POST" id="formCotizacion">
        <input type="hidden" name="id_cliente" value="<?= htmlspecialchars($id_cliente) ?>">

        <div class="mb-3">
            <label for="buscadorExamen">Buscar examen</label>
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
                    <label>Total:</label>
                    <span id="totalCotizacion" class="fw-bold">S/. 0.00</span>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Guardar Cotización</button>
                    <a href="dashboard.php?action=cotizaciones" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal para detalles -->
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
let examenesData = <?php echo $examenes_json; ?>;
let promoMap = <?php echo $promo_map_json; ?>;
let examenesSeleccionados = [];

$(function(){
    $('#buscadorExamen').select2({
        placeholder: "Escribe para buscar un examen...",
        allowClear: true
    });

    $('#buscadorExamen').on('select2:select', function(e) {
        let id = e.params.data.id;
        let examen = examenesData.find(ex => ex.id == id);
        if (!examenesSeleccionados.find(ex => ex.id == id)) {
            examenesSeleccionados.push({...examen, cantidad: 1});
            renderizarLista();
        }
        $(this).val('').trigger('change');
    });

    function renderizarLista() {
        let html = '';
        let total = 0;
        examenesSeleccionados.forEach((ex, idx) => {
            let promo = promoMap[ex.id] || null;
            let precio = parseFloat(ex.precio_publico);
            let precio_final = precio;
            let promo_html = '';
            // Si hay promoción para este examen
            if (promo) {
                if (promo.descuento > 0) {
                    precio_final = precio - (precio * promo.descuento / 100);
                    promo_html = `<span class="badge bg-success ms-2">-${promo.descuento}%</span>`;
                } else if (promo.precio_promocional > 0) {
                    precio_final = parseFloat(promo.precio_promocional);
                    promo_html = `<span class="badge bg-warning text-dark ms-2">S/. ${precio_final.toFixed(2)}</span>`;
                }
            }
            let subtotal = precio_final * ex.cantidad;
            total += subtotal;

            html += `
            <div class="row align-items-center mb-2 examen-item border-bottom pb-2">
                <div class="col-md-4 fw-bold">
                    ${ex.nombre} ${promo_html}
                </div>
                <div class="col-md-1">
                    <input type="number" min="1" class="form-control cantidadExamen" data-idx="${idx}" value="${ex.cantidad}">
                </div>
                <div class="col-md-2">S/. ${precio_final.toFixed(2)}</div>
                <div class="col-md-2 fw-bold">S/. ${(subtotal).toFixed(2)}</div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-info btn-sm btn-detalle" data-idx="${idx}">Ver detalles</button>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm btn-remove" data-idx="${idx}">×</button>
                </div>
                <input type="hidden" name="examenes[]" value="${ex.id}">
                <input type="hidden" name="cantidades[]" value="${ex.cantidad}">
            </div>
            `;
        });
        $('#examenes-seleccionados').html(html);
        $('#totalCotizacion').text('S/. ' + total.toFixed(2));
    }

    // Cambiar cantidad
    $(document).on('input', '.cantidadExamen', function(){
        let idx = $(this).data('idx');
        examenesSeleccionados[idx].cantidad = parseInt($(this).val()) || 1;
        renderizarLista();
    });

    // Quitar examen
    $(document).on('click', '.btn-remove', function(){
        let idx = $(this).data('idx');
        examenesSeleccionados.splice(idx, 1);
        renderizarLista();
    });

    // Ver detalles
    $(document).on('click', '.btn-detalle', function(){
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

    // Inicializar
    renderizarLista();
});
</script>
