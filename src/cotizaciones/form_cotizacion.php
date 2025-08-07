<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Rol y cliente
$rol = $_SESSION['rol'] ?? null;
if ($rol === 'cliente') {
    $id_cliente = $_SESSION['cliente_id'] ?? '';
} else {
    $id_cliente = isset($_GET['id']) ? intval($_GET['id']) : '';
}

// Validar cliente
if (empty($id_cliente)) {
    echo "<div class='alert alert-danger mt-4'>No se pudo identificar al cliente. Por favor, vuelve al listado de clientes.</div>";
    exit;
}

// Exámenes
$stmt = $pdo->query("SELECT id, codigo, nombre, descripcion, tiempo_respuesta, preanalitica_cliente, observaciones, precio_publico FROM examenes WHERE vigente = 1 ORDER BY nombre");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$examenes_json = json_encode($examenes);

// Empresas y convenios
$empresas = [];
$convenios = [];
if ($rol === 'admin' || $rol === 'recepcionista') {
    $stmtEmp = $pdo->query("SELECT id, razon_social, nombre_comercial, descuento FROM empresas WHERE estado = 1 ORDER BY nombre_comercial");
    $empresas = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    $stmtConv = $pdo->query("SELECT id, nombre, descuento FROM convenios ORDER BY nombre");
    $convenios = $stmtConv->fetchAll(PDO::FETCH_ASSOC);
}

// Descuentos
$descuento_cliente = 0;
if ($id_cliente) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM clientes WHERE id = ?");
    $stmtDesc->execute([$id_cliente]);
    $descuento_cliente = $stmtDesc->fetchColumn() ?: 0;
}
$descuento_empresa_convenio = 0;
if ($rol === 'empresa' && !empty($_SESSION['empresa_id'])) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM empresas WHERE id = ?");
    $stmtDesc->execute([$_SESSION['empresa_id']]);
    $descuento_empresa_convenio = $stmtDesc->fetchColumn() ?: 0;
} elseif ($rol === 'convenio' && !empty($_SESSION['convenio_id'])) {
    $stmtDesc = $pdo->prepare("SELECT descuento FROM convenios WHERE id = ?");
    $stmtDesc->execute([$_SESSION['convenio_id']]);
    $descuento_empresa_convenio = $stmtDesc->fetchColumn() ?: 0;
}
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
                <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="<?= $descuento_empresa_convenio ?: $descuento_cliente ?>">

                <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                    <div class="mb-3">
                        <label for="tipoCliente" class="form-label">Tipo de cliente</label>
                        <select id="tipoCliente" name="tipo_usuario" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="cliente">Particular</option>
                            <option value="empresa">Empresa</option>
                            <option value="convenio">Convenio</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="selectEmpresa">
                        <label for="empresa" class="form-label">Empresa</label>
                        <select id="empresa" name="id_empresa" class="form-select">
                            <option value="">Seleccione empresa...</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" data-descuento="<?= $emp['descuento'] ?>">
                                    <?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?>
                                    <?php if ($emp['descuento'] > 0): ?>
                                        (<?= $emp['descuento'] ?>% desc.)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="selectConvenio">
                        <label for="convenio" class="form-label">Convenio</label>
                        <select id="convenio" name="id_convenio" class="form-select">
                            <option value="">Seleccione convenio...</option>
                            <?php foreach ($convenios as $conv): ?>
                                <option value="<?= $conv['id'] ?>" data-descuento="<?= $conv['descuento'] ?>">
                                    <?= htmlspecialchars($conv['nombre']) ?>
                                    <?php if ($conv['descuento'] > 0): ?>
                                        (<?= $conv['descuento'] ?>% desc.)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="buscadorExamen" class="form-label">Buscar examen</label>
                    <select id="buscadorExamen" class="form-select" style="width:100%;">
                        <option value="">Escribe para buscar un examen...</option>
                        <?php foreach ($examenes as $ex): ?>
                            <option value="<?= $ex['id']; ?>"
                                data-nombre="<?= htmlspecialchars($ex['nombre']); ?>"
                                data-precio="<?= $ex['precio_publico']; ?>">
                                <?= htmlspecialchars($ex['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="examenes-seleccionados"></div>

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var rolUsuario = '<?= $rol ?>';
let examenesData = <?= $examenes_json ?>;
let examenesSeleccionados = [];
let descuentoCliente = <?= $descuento_cliente ?>;
let descuentoActual = <?= $descuento_empresa_convenio ?: $descuento_cliente ?>;

// Inicializar descuento y precios al cargar como empresa/convenio
$(document).ready(function() {
    if (rolUsuario === 'empresa' || rolUsuario === 'convenio') {
        actualizarDescuento();
        renderizarLista();
    }
});

// Manejo de tipo de cliente y descuento
$('#tipoCliente').on('change', function() {
    let tipo = $(this).val();
    $('#selectEmpresa, #selectConvenio').addClass('d-none');
    $('#empresa, #convenio').prop('required', false);
    if (tipo === 'empresa') {
        $('#selectEmpresa').removeClass('d-none');
        $('#empresa').prop('required', true);
        $('#convenio').val('');
    } else if (tipo === 'convenio') {
        $('#selectConvenio').removeClass('d-none');
        $('#convenio').prop('required', true);
        $('#empresa').val('');
    } else {
        $('#empresa, #convenio').val('');
    }
    actualizarDescuento();
});

// Detectar selección de empresa/convenio y actualizar descuento
$('#empresa').on('change', actualizarDescuento);
$('#convenio').on('change', actualizarDescuento);

function actualizarDescuento() {
    // Prioridad: si el usuario es empresa o convenio, usar ese descuento
    if (rolUsuario === 'empresa' || rolUsuario === 'convenio') {
        descuentoActual = <?= $descuento_empresa_convenio ?: 0 ?>;
    } else {
        let tipo = $('#tipoCliente').val();
        descuentoActual = 0;
        if (tipo === 'empresa') {
            let desc = $('#empresa option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
        } else if (tipo === 'convenio') {
            let desc = $('#convenio option:selected').data('descuento');
            descuentoActual = desc ? parseFloat(desc) : 0;
        } else if (tipo === 'cliente' || tipo === undefined) {
            descuentoActual = descuentoCliente;
        }
    }
    $('#descuento_aplicado').val(descuentoActual);
    // Aplicar descuento a todos los precios
    examenesSeleccionados.forEach((ex, idx) => {
        ex.precio_unitario = aplicarDescuento(ex.precio_publico, descuentoActual);
    });
    renderizarLista();
}

function aplicarDescuento(precio, descuento) {
    const precioNum = Number(precio);
    const descuentoNum = Number(descuento);
    const resultado = precioNum * (1 - (descuentoNum / 100));
    return resultado.toFixed(2);
}

// Select2 para buscar exámenes
$('#buscadorExamen').select2({
    placeholder: "Escribe para buscar un examen...",
    allowClear: true,
    width: '100%'
});

$('#buscadorExamen').on('select2:select', function(e) {
    let id = e.params.data.id;
    let examen = examenesData.find(ex => ex.id == id);
    if (!examenesSeleccionados.find(ex => ex.id == id)) {
        let precioPublico = Number(examen.precio_publico);
        let precioConDescuento = aplicarDescuento(precioPublico, descuentoActual);
        examenesSeleccionados.push({
            ...examen,
            precio_unitario: precioConDescuento,
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

// Cambiar precio manualmente (solo admin/recep)
$(document).on('input', '.precioExamen', function() {
    let idx = $(this).data('idx');
    let nuevoPrecio = parseFloat($(this).val());
    examenesSeleccionados[idx].precio_unitario = isNaN(nuevoPrecio) ? 0 : nuevoPrecio;
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
                <th style="width:120px;">Precio (S/.)</th>
                <th>Subtotal</th>
                <th colspan="2">Acciones</th>
            </tr>
        </thead>
        <tbody>`;
        examenesSeleccionados.forEach((ex, idx) => {
            let precio = parseFloat(ex.precio_unitario);
            let subtotal = precio * ex.cantidad;
            total += subtotal;
            html += `
<tr>
    <td>${ex.nombre}</td>
    <td>
        <input type="number" min="1" class="form-control cantidadExamen" data-idx="${idx}" value="${ex.cantidad}">
    </td>
    <td>
        ${
            (rolUsuario === 'admin' || rolUsuario === 'recepcionista')
                ? `<input type="number" step="0.01" class="form-control precioExamen" data-idx="${idx}" value="${precio.toFixed(2)}">`
                : `<span class="form-control-plaintext">${precio.toFixed(2)}</span>`
        }
        <input type="hidden" name="examenes[]" value="${ex.id}">
        <input type="hidden" name="cantidades[]" value="${ex.cantidad}">
        <input type="hidden" name="precios[]" value="${precio.toFixed(2)}">
    </td>
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
</tr>
            `;
        });
        html += `</tbody></table></div>`;
    }
    $('#examenes-seleccionados').html(html);
    $('#totalCotizacion').text('S/. ' + total.toFixed(2));
}
</script>
