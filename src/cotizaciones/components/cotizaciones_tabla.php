<?php
require_once __DIR__ . '/../../conexion/conexion.php';
$empresas = $pdo->query("SELECT id, nombre_comercial, razon_social FROM empresas WHERE estado = 1 ORDER BY nombre_comercial")->fetchAll(PDO::FETCH_ASSOC);
$convenios = $pdo->query("SELECT id, nombre FROM convenios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.btn-cotizacion-accion {
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
    min-width: 34px;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    padding: 0.375rem 0.5rem;
}
.cotizaciones-filters {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(44,62,80,0.07);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    margin-bottom: 2rem;
}
.cotizaciones-filters .form-label {
    color: #1565c0;
    font-weight: 600;
    font-size: 1rem;
}
.cotizaciones-filters .form-control, .cotizaciones-filters .form-select {
    border-radius: 10px;
    border: 1.5px solid #90caf9;
    font-size: 1rem;
}
@media (max-width: 768px) {
    .table-responsive { display: none; }
    .cards-container { display: block; }
    .mobile-pagination-cotizaciones {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 1.5rem 0 2rem 0;
        width: 100%;
    }
    .cotizacion-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: 1px solid #e3e6f0;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.3s ease-out;
    }
    .cotizacion-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    .cotizacion-codigo {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .cotizacion-nombre {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    .info-item {
        margin-bottom: 0.5rem;
    }
    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 500;
        margin-right: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }
    .info-value {
        font-size: 0.95rem;
        color: #2c3e50;
        font-weight: 500;
        display: inline-block;
    }
    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
        border: none;
        display: inline-block;
    }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<div class="cotizaciones-filters">
    <div class="row">
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🔍 DNI</label>
            <input type="text" id="filtroDni" class="form-control" placeholder="Buscar por DNI">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🏢 Empresa</label>
            <select id="filtroEmpresa" class="form-select">
                <option value="">Seleccionar empresa...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre_comercial'] ?: $emp['razon_social']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">🤝 Convenio</label>
            <select id="filtroConvenio" class="form-select">
                <option value="">Seleccionar convenio...</option>
                <?php foreach ($convenios as $conv): ?>
                    <option value="<?= $conv['id'] ?>"><?= htmlspecialchars($conv['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha desde</label>
            <input type="date" id="filtroFechaDesde" class="form-control">
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <label class="form-label">📅 Fecha hasta</label>
            <input type="date" id="filtroFechaHasta" class="form-control">
        </div>
        <div class="col-md-2 col-sm-12 d-flex align-items-end mb-2">
            <button id="btnLimpiarFiltros" class="btn btn-outline-secondary w-100" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
        </div>
    </div>
</div>
<div>
        <div class="table-responsive">
            <table id="tablaCotizaciones" class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllCotizaciones" title="Seleccionar todo"></th>
                        <th>Código</th>
                        <th>Paciente</th>
                        <th>DNI</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Referencia</th>
                        <th>Estado Pago</th>
                        <th>Estado Examen</th>
                        <th>Rol Creador</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- El contenido de la tabla será llenado dinámicamente por DataTables server-side -->
                </tbody>
            </table>
                        <div class="mt-3">
                                <button id="btnPagoMasivo" class="btn btn-success" disabled data-bs-toggle="modal" data-bs-target="#modalPagoMasivo">Pago masivo (<span id="totalPagoMasivo">S/ 0.00</span>)</button>
                        </div>

                        <!-- Modal de confirmación pago masivo -->
                        <div class="modal fade" id="modalPagoMasivo" tabindex="-1" aria-labelledby="modalPagoMasivoLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalPagoMasivoLabel">Confirmar pago masivo</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>¿Desea registrar el pago masivo para <span id="cantidadSeleccionadas">0</span> cotizaciones seleccionadas?</p>
                                        <p>Totales a pagar: <strong id="modalTotalPago">S/ 0.00</strong></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-success" id="confirmarPagoMasivo">Confirmar pago</button>
                                    </div>
                                </div>
                            </div>
                        </div>
        </div>
    </div>
    <!-- Buscador y cards para móvil -->
<div class="mb-3 d-block d-md-none">
    <div class="input-group">
        <input type="text" id="buscadorCotizacionesMovil" class="form-control" placeholder="Buscar por paciente, código, referencia...">
        <button class="btn btn-primary" type="button" id="btnClearCotizacionesMovil"><i class="bi bi-x"></i></button>
    </div>
</div>
<div class="d-block d-md-none mb-3" id="totalesCotizacionesMovil">
    <div class="card p-3 bg-info bg-opacity-10 border-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span class="fw-bold">Total a pagar: <span class="text-success" id="totalPagoMasivoMovil">S/ 0.00</span></span>
            <button id="btnPagoMasivoMovil" class="btn btn-success mt-2 mt-md-0" disabled data-bs-toggle="modal" data-bs-target="#modalPagoMasivoMovil">Pago masivo (<span id="cantidadSeleccionadasMovil">0</span>)</button>
        </div>
    </div>
</div>

    <!-- jQuery (requerido por DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
// Referencias a elementos DOM usados en el flujo
const btnPagoMasivo = document.getElementById('btnPagoMasivo');
const totalPagoMasivo = document.getElementById('totalPagoMasivo');
const modalTotalPago = document.getElementById('modalTotalPago');
const cantidadSeleccionadas = document.getElementById('cantidadSeleccionadas');
const confirmarPagoMasivo = document.getElementById('confirmarPagoMasivo');
const selectAll = document.getElementById('selectAllCotizaciones');
// --- Utilidades para selección manual ---
function getSeleccionadasManual() {
    try {
        return JSON.parse(localStorage.getItem('cotizacionesManualSeleccionadasDesktop') || '[]');
    } catch (e) {
        return [];
    }
}
function setSeleccionadasManual(arr) {
    localStorage.setItem('cotizacionesManualSeleccionadasDesktop', JSON.stringify(arr));
}
function getCheckboxes() {
    return Array.from(document.querySelectorAll('.cotizacion-checkbox'));
}
function restaurarSeleccionManual() {
    const seleccionadas = getSeleccionadasManual();
    getCheckboxes().forEach(cb => {
        cb.checked = seleccionadas.includes(cb.getAttribute('data-id'));
    });
}

// --- Selección global vs manual ---
function setSeleccionGlobal(flag) {
    localStorage.setItem('cotizacionesSeleccionGlobal', flag ? '1' : '0');
}
function getSeleccionGlobal() {
    return localStorage.getItem('cotizacionesSeleccionGlobal') === '1';
}


    $(document).ready(function() {
    // Mensajes rápidos por query (?msg=... | ?mensaje=...)
    try {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        const rawMsg = params.get('msg') || params.get('mensaje');
        if (rawMsg) {
            const map = {
                'dato_invalido': {text: 'Acceso inválido. Redirigido a Cotizaciones.', icon: 'info'},
                'sesion_incompleta': {text: 'Sesión incompleta. Vuelve a iniciar el flujo.', icon: 'warning'},
                'datos_incompletos': {text: 'Datos incompletos. Revisa el formulario e inténtalo de nuevo.', icon: 'warning'},
                'falta_id': {text: 'Falta el ID de la cotización.', icon: 'error'}
            };
            const conf = map[rawMsg] || {text: rawMsg, icon: 'info'};
            if (window.Swal && Swal.fire) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    icon: conf.icon,
                    title: conf.text
                });
            }
            params.delete('msg');
            params.delete('mensaje');
            url.search = params.toString();
            window.history.replaceState({}, '', url);
        }
    } catch (e) { /* noop */ }
    // Forzar ajuste visual de DataTables y botones de acciones al cambiar tamaño de pantalla
    let lastIsMobile = window.innerWidth <= 768;
    $(window).on('resize', function() {
        const isMobile = window.innerWidth <= 768;
        if (isMobile !== lastIsMobile) {
            window.location.reload();
        } else {
            if ($.fn.dataTable.isDataTable('#tablaCotizaciones')) {
                $('#tablaCotizaciones').DataTable().columns.adjust().draw(false);
            }
        }
        lastIsMobile = isMobile;
    });
        var tabla = $('#tablaCotizaciones').DataTable({
            "serverSide": true,
            "processing": true,
            "ajax": {
                "url": "dashboard.php?action=cotizaciones_api&debug=1",
                "type": "GET",
                "data": function(d) {
                    d.filtro_dni = $('#filtroDni').val();
                    d.filtro_empresa = $('#filtroEmpresa').val();
                    d.filtro_convenio = $('#filtroConvenio').val();
                    d.filtro_fecha_desde = $('#filtroFechaDesde').val();
                    d.filtro_fecha_hasta = $('#filtroFechaHasta').val();
                }
            },
            "pageLength": 3,
            "lengthMenu": [[3, 5, 10], [3, 5, 10]],
            "order": [],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            // "responsive": true,
            "columns": [
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `<input type='checkbox' class='cotizacion-checkbox' data-id='${row.id}' data-saldo='${parseFloat(row.saldo) || 0}'>`;
                    }
                },
                { "data": "codigo" },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        return `${row.nombre_cliente || ''} ${row.apellido_cliente || ''}`;
                    }
                },
                { "data": "dni" },
                { "data": "fecha" },
                {
                    "data": "total",
                    "render": function(data) {
                        return 'S/ ' + parseFloat(data).toFixed(2);
                    }
                },
                {
                    "data": "referencia",
                    "render": function(data, type, row) {
                        // Color único por empresa/convenio
                        function stringToColor(str) {
                            let hash = 0;
                            for (let i = 0; i < str.length; i++) {
                                hash = str.charCodeAt(i) + ((hash << 5) - hash);
                            }
                            let color = '#';
                            for (let i = 0; i < 3; i++) {
                                let value = (hash >> (i * 8)) & 0xFF;
                                color += ('00' + value.toString(16)).substr(-2);
                            }
                            return color;
                        }
                        if (row.referencia && row.referencia !== 'Particular') {
                            const color = stringToColor(row.referencia);
                            const textColor = '#fff';
                            return `<span class='badge' style='background:${color};color:${textColor};'>${row.referencia}</span>`;
                        } else {
                            return `<span class='badge bg-secondary'>Particular</span>`;
                        }
                    }
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        // Calcular estado de pago usando total pagado y descarga anticipada
                        const total = parseFloat(row.total) || 0;
                        const pagado = parseFloat(row.total_pagado) || 0;
                        if (row.tiene_descarga_anticipada == 1) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-clock'></i> Descarga anticipada</span>`;
                        } else if (pagado >= total && total > 0) {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
                        } else if (pagado > 0 && pagado < total) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Parcial</span>`;
                        } else {
                            return `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente</span>`;
                        }
                    }
                },
                {
                    "data": null,
                    "render": function(row) {
                        const estado = row.estado_examen;
                        const porcentaje = row.porcentaje_examen;
                        if (estado === 'completado_100' || estado === 'pendiente_100') {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado 100%</span>`;
                        } else if (estado === 'pendiente_0') {
                            return `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente 0%</span>`;
                        } else if (estado && estado.startsWith('pendiente_')) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente ${porcentaje}%</span>`;
                        } else {
                            return `<span class='badge bg-secondary'>Sin datos</span>`;
                        }
                    }
                },
                { "data": "rol_creador" },
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        let acciones = '';
                        acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm btn-cotizacion-accion' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
                        acciones += `<a href='dashboard.php?vista=form_cotizacion&id=${row.id}&edit=1' class='btn btn-dark btn-sm btn-cotizacion-accion' title='Editar cotización'><i class='bi bi-file-earmark-medical'></i></a>`;
                        if (row.modificada == 1) {
                            acciones += `<span class='badge bg-warning text-dark ms-1' title='Cotización modificada'><i class='bi bi-pencil'></i> Modificada</span>`;
                        }
                        acciones += `<a href='dashboard.php?vista=formulario&cotizacion_id=${row.id}' class='btn btn-primary btn-sm btn-cotizacion-accion' title='Editar o agregar resultados'><i class='bi bi-pencil-square'></i></a>`;
                        acciones += `<a href='dashboard.php?vista=pago_cotizacion&id=${row.id}' class='btn btn-warning btn-sm btn-cotizacion-accion' title='Registrar pago'><i class='bi bi-cash-coin'></i></a>`;
                        acciones += `<a href='dashboard.php?action=eliminar_cotizacion&id=${row.id}' class='btn btn-danger btn-sm btn-cotizacion-accion' title='Eliminar cotización' onclick='return confirm(\"¿Seguro que deseas eliminar esta cotización?\")'><i class='bi bi-trash'></i></a>`;
                        acciones += `<a href='resultados/descarga-pdf.php?cotizacion_id=${row.id}' class='btn btn-success btn-sm btn-cotizacion-accion' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
                        return acciones;
                    }
                }
            ]
        });

        // Recargar tabla al cambiar cualquier filtro
        $('#filtroDni, #filtroEmpresa, #filtroConvenio, #filtroFechaDesde, #filtroFechaHasta').on('change keyup', function() {
            tabla.ajax.reload();
        });
        // Limpiar filtros
        $('#btnLimpiarFiltros').on('click', function() {
            $('#filtroDni').val('');
            $('#filtroEmpresa').val('');
            $('#filtroConvenio').val('');
            $('#filtroFechaDesde').val('');
            $('#filtroFechaHasta').val('');
            tabla.ajax.reload();
        });
        $('#btnBuscarCotizaciones, #btnLimpiarCotizaciones').on('click', function() {
            tabla.ajax.reload();
        });

        function actualizarTotal() {
    let total = 0;
    let count = 0;
    let algunoSeleccionado = false;
    const seleccionadas = getSeleccionadasManual();
    if (seleccionadas.length > 0) {
        // Siempre obtener los saldos de todas las seleccionadas (aunque no estén en la página actual)
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                ids: seleccionadas.join(','),
                length: seleccionadas.length,
                start: 0,
                draw: 1
            },
            success: function(resp) {
                let data = resp.data || [];
                total = 0;
                count = 0;
                data.forEach(row => {
                    const saldo = parseFloat(row.saldo);
                    if (saldo > 0) {
                        total += saldo;
                        count++;
                        algunoSeleccionado = true;
                    }
                });
                totalPagoMasivo.textContent = 'S/ ' + total.toFixed(2);
                btnPagoMasivo.disabled = !algunoSeleccionado;
                modalTotalPago.textContent = 'S/ ' + total.toFixed(2);
                cantidadSeleccionadas.textContent = count;
            }
        });
    } else {
        totalPagoMasivo.textContent = 'S/ 0.00';
        btnPagoMasivo.disabled = true;
        modalTotalPago.textContent = 'S/ 0.00';
        cantidadSeleccionadas.textContent = 0;
    }
        }

        // Evento delegado para checkboxes
        $(document).on('change', '.cotizacion-checkbox', function() {
            // Si se marca/desmarca manualmente, desactivar selección global
            setSeleccionGlobal(false);
            let seleccionadas = getSeleccionadasManual();
            const id = $(this).attr('data-id');
            if (this.checked) {
                if (!seleccionadas.includes(id)) seleccionadas.push(id);
            } else {
                seleccionadas = seleccionadas.filter(x => x !== id);
            }
            setSeleccionadasManual(seleccionadas);
            restaurarSeleccionManual();
            actualizarTotal();
        });

        // Evento para selectAll
        selectAll.addEventListener('change', function() {
        if (selectAll.checked) {
            setSeleccionGlobal(true);
            // Tomar los valores actuales de todos los filtros
            const filtroEmpresa = $('#filtroEmpresa').val();
            const filtroConvenio = $('#filtroConvenio').val();
            const filtroDni = $('#filtroDni').val();
            const filtroFechaDesde = $('#filtroFechaDesde').val();
            const filtroFechaHasta = $('#filtroFechaHasta').val();
            $.ajax({
                url: 'dashboard.php?action=cotizaciones_api',
                type: 'GET',
                data: {
                    filtro_fecha_desde: filtroFechaDesde,
                    filtro_fecha_hasta: filtroFechaHasta,
                    filtro_empresa: filtroEmpresa,
                    filtro_convenio: filtroConvenio,
                    filtro_dni: filtroDni,
                    length: 10000,
                    start: 0,
                    draw: 1
                },
                success: function(resp) {
                    let data = resp.data || [];
                    // Filtrar solo cotizaciones con saldo pendiente
                    const ids = data.filter(row => parseFloat(row.saldo) > 0).map(row => row.id);
                    setSeleccionadasManual(ids);
                    restaurarSeleccionManual();
                    actualizarTotal();
                }
            });
        } else {
            setSeleccionGlobal(false);
            setSeleccionadasManual([]);
            getCheckboxes().forEach(cb => { cb.checked = false; });
            actualizarTotal();
        }
        });

        // Actualizar total cada vez que se dibuja la tabla
        $('#tablaCotizaciones').on('draw.dt', function() {
            // Mantener el estado del checkbox global si hay seleccionadas
            const seleccionadas = getSeleccionadasManual();
            selectAll.checked = seleccionadas.length > 0;
            restaurarSeleccionManual();
            actualizarTotal();
        });

        // Actualizar datos del modal al abrirlo
        btnPagoMasivo.addEventListener('click', function() {
            actualizarTotal();
        });

        // Lógica para confirmar pago masivo
        confirmarPagoMasivo.addEventListener('click', function() {
            const seleccionadas = getSeleccionadasManual();
            if (seleccionadas.length === 0) return;

            fetch('cotizaciones/api/pago_masivo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cotizaciones: seleccionadas })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pago masivo realizado correctamente',
                        showConfirmButton: false,
                        timer: 1800
                    });
                    setSeleccionadasManual([]);
                    setTimeout(() => location.reload(), 1800);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al realizar el pago masivo',
                        text: data.message || '',
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión al procesar el pago masivo',
                });
            });
        });
    });
    </script>

<div class="d-block d-md-none mb-2" id="selectAllCotizacionesMovilContainer">
    <label class="form-check-label" for="selectAllCotizacionesMovil">
        <input type="checkbox" id="selectAllCotizacionesMovil" class="form-check-input me-2"> Seleccionar todo
    </label>
</div>
<div class="cards-container" id="cardsCotizacionesAjax"></div>
<!-- Modal de confirmación pago masivo móvil -->
<div class="modal fade" id="modalPagoMasivoMovil" tabindex="-1" aria-labelledby="modalPagoMasivoMovilLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagoMasivoMovilLabel">Confirmar pago masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Desea registrar el pago masivo para <span id="cantidadSeleccionadasMovilModal">0</span> cotizaciones seleccionadas?</p>
                <p>Total a pagar: <strong id="modalTotalPagoMovil">S/ 0.00</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmarPagoMasivoMovil">Confirmar pago</button>
            </div>
        </div>
    </div>
</div>
    <script>
// --- Utilidades selección manual móvil ---
function getSeleccionadasManualMovil() {
    try {
        return JSON.parse(localStorage.getItem('cotizacionesManualSeleccionadasMovil') || '[]');
    } catch (e) { return []; }
}
function setSeleccionadasManualMovil(arr) {
    localStorage.setItem('cotizacionesManualSeleccionadasMovil', JSON.stringify(arr));
}
function actualizarTotalMovil() {
    let total = 0;
    let count = 0;
    let algunoSeleccionado = false;
    const seleccionadas = getSeleccionadasManualMovil();
    if (seleccionadas.length > 0) {
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                ids: seleccionadas.join(','),
                length: seleccionadas.length,
                start: 0,
                draw: 1
            },
            success: function(resp) {
                let data = resp.data || [];
                total = 0;
                count = 0;
                data.forEach(row => {
                    const saldo = parseFloat(row.saldo);
                    if (saldo > 0) {
                        total += saldo;
                        count++;
                        algunoSeleccionado = true;
                    }
                });
                document.getElementById('totalPagoMasivoMovil').textContent = 'S/ ' + total.toFixed(2);
                document.getElementById('btnPagoMasivoMovil').disabled = !algunoSeleccionado;
                document.getElementById('modalTotalPagoMovil').textContent = 'S/ ' + total.toFixed(2);
                document.getElementById('cantidadSeleccionadasMovil').textContent = count;
                document.getElementById('cantidadSeleccionadasMovilModal').textContent = count;
            }
        });
    } else {
        document.getElementById('totalPagoMasivoMovil').textContent = 'S/ 0.00';
        document.getElementById('btnPagoMasivoMovil').disabled = true;
        document.getElementById('modalTotalPagoMovil').textContent = 'S/ 0.00';
        document.getElementById('cantidadSeleccionadasMovil').textContent = 0;
        document.getElementById('cantidadSeleccionadasMovilModal').textContent = 0;
    }
}
function renderCotizacionCard(row) {
    // Badge referencia
    function stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        let color = '#';
        for (let i = 0; i < 3; i++) {
            let value = (hash >> (i * 8)) & 0xFF;
            color += ('00' + value.toString(16)).substr(-2);
        }
        return color;
    }
    let referenciaBadge = '';
    if (row.referencia && row.referencia !== 'Particular') {
        const color = stringToColor(row.referencia);
        referenciaBadge = `<span class='badge' style='background:${color};color:#fff;'>${row.referencia}</span>`;
    } else {
        referenciaBadge = `<span class='badge bg-secondary'>Particular</span>`;
    }
    // Estado pago
    const total = parseFloat(row.total) || 0;
    const pagado = parseFloat(row.total_pagado) || 0;
    let estadoPago = '';
    if (row.tiene_descarga_anticipada == 1) {
        estadoPago = `<span class='badge bg-warning text-dark'><i class='bi bi-clock'></i> Descarga anticipada</span>`;
    } else if (pagado >= total && total > 0) {
        estadoPago = `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
    } else if (pagado > 0 && pagado < total) {
        estadoPago = `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Parcial</span>`;
    } else {
        estadoPago = `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente</span>`;
    }
    // Estado examen con porcentaje
    let estadoExamen = '';
    const estado = row.estado_examen;
    const porcentaje = row.porcentaje_examen;
    if (estado === 'completado_100' || estado === 'pendiente_100') {
        estadoExamen = `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado 100%</span>`;
    } else if (estado === 'pendiente_0') {
        estadoExamen = `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente 0%</span>`;
    } else if (estado && estado.startsWith('pendiente_')) {
        estadoExamen = `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente ${porcentaje}%</span>`;
    } else {
        estadoExamen = `<span class='badge bg-secondary'>Sin datos</span>`;
    }
    // Acciones (todas como en escritorio)
    let acciones = '';
    acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm btn-cotizacion-accion' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
    acciones += `<a href='dashboard.php?vista=form_cotizacion&id=${row.id}&edit=1' class='btn btn-dark btn-sm btn-cotizacion-accion' title='Editar cotización'><i class='bi bi-file-earmark-medical'></i></a>`;
    if (row.modificada == 1) {
        acciones += `<span class='badge bg-warning text-dark ms-1' title='Cotización modificada'><i class='bi bi-pencil'></i> Modif.</span>`;
    }
    acciones += `<a href='dashboard.php?vista=formulario&cotizacion_id=${row.id}' class='btn btn-primary btn-sm btn-cotizacion-accion' title='Editar o agregar resultados'><i class='bi bi-pencil-square'></i></a>`;
    acciones += `<a href='dashboard.php?vista=pago_cotizacion&id=${row.id}' class='btn btn-warning btn-sm btn-cotizacion-accion' title='Registrar pago'><i class='bi bi-cash-coin'></i></a>`;
    acciones += `<a href='dashboard.php?action=eliminar_cotizacion&id=${row.id}' class='btn btn-danger btn-sm btn-cotizacion-accion' title='Eliminar cotización' onclick='return confirm("¿Seguro que deseas eliminar esta cotización?")'><i class='bi bi-trash'></i></a>`;
    acciones += `<a href='resultados/descarga-pdf.php?cotizacion_id=${row.id}' class='btn btn-success btn-sm btn-cotizacion-accion' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
    // Checkbox selección
    const seleccionadas = getSeleccionadasManualMovil();
    const checked = seleccionadas.includes(row.id) ? 'checked' : '';
    return `
    <div class='cotizacion-card mb-3'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <span class='cotizacion-nombre'>${row.nombre_cliente || ''} ${row.apellido_cliente || ''}</span>
            <span class='cotizacion-codigo'>${row.codigo || ''}</span>
        </div>
        <div class='info-item'><span class='info-label'>DNI</span><span class='info-value'>${row.dni || ''}</span></div>
        <div class='info-item'><span class='info-label'>Fecha</span><span class='info-value'>${row.fecha || ''}</span></div>
        <div class='info-item'><span class='info-label'>Total</span><span class='info-value'>S/ ${(parseFloat(row.total) || 0).toFixed(2)}</span></div>
        <div class='info-item'><span class='info-label'>Referencia</span><span class='info-value'>${referenciaBadge}</span></div>
        <div class='info-item'><span class='info-label'>Estado Pago</span><span class='info-value'>${estadoPago}</span></div>
        <div class='info-item'><span class='info-label'>Estado Examen</span><span class='info-value'>${estadoExamen}</span></div>
        <div class='info-item'><span class='info-label'>Rol Creador</span><span class='info-value'>${row.rol_creador || ''}</span></div>
        <div class='d-flex align-items-center gap-2 mt-2'>
            <input type='checkbox' class='cotizacion-checkbox-movil' data-id='${row.id}' data-saldo='${parseFloat(row.saldo) || 0}' ${checked}>
            <label class='mb-0'>Seleccionar</label>
            ${acciones}
        </div>
    </div>`;
}
function cargarCardsCotizaciones(pagina = 1, busqueda = '') {
    const porPagina = 3;
    const params = {
        draw: 1,
        start: (pagina - 1) * porPagina,
        length: porPagina,
        search: { value: busqueda },
        filtro_dni: $('#filtroDni').val(),
        filtro_empresa: $('#filtroEmpresa').val(),
        filtro_convenio: $('#filtroConvenio').val(),
        filtro_fecha_desde: $('#filtroFechaDesde').val(),
        filtro_fecha_hasta: $('#filtroFechaHasta').val()
    };
    $.ajax({
        url: 'dashboard.php?action=cotizaciones_api',
        data: params,
        dataType: 'json',
        success: function(resp) {
            const cont = document.getElementById('cardsCotizacionesAjax');
            cont.innerHTML = '';
            if (resp.data && resp.data.length > 0) {
                resp.data.forEach(row => {
                    cont.innerHTML += renderCotizacionCard(row);
                });
                renderPaginacionCotizacionesMovil(pagina, Math.ceil(resp.recordsFiltered / porPagina), busqueda);
            } else {
                cont.innerHTML = '<div class="text-center py-5">No hay cotizaciones</div>';
                renderPaginacionCotizacionesMovil(1, 1, busqueda);
            }
            actualizarTotalMovil();
        },
        error: function() {
            document.getElementById('cardsCotizacionesAjax').innerHTML = '<div class="alert alert-danger">Error al cargar las cotizaciones.</div>';
        }
    });
}
function renderPaginacionCotizacionesMovil(pagina, totalPaginas, busqueda) {
    let nav = document.getElementById('paginacionCotizacionesMovil');
    if (!nav) {
        nav = document.createElement('nav');
        nav.className = 'mobile-pagination-cotizaciones';
        nav.id = 'paginacionCotizacionesMovil';
        document.getElementById('cardsCotizacionesAjax').after(nav);
    }
    let html = '';
    html += `<button class='page-btn' onclick='cargarCardsCotizaciones(${pagina - 1}, ${JSON.stringify(busqueda)})' ${pagina <= 1 ? 'disabled' : ''}>&#8592;</button>`;
    for (let p = Math.max(1, pagina - 1); p <= Math.min(totalPaginas, pagina + 1); p++) {
        html += `<button class='page-btn${p === pagina ? ' active' : ''}' onclick='cargarCardsCotizaciones(${p}, ${JSON.stringify(busqueda)})'>${p}</button>`;
    }
    html += `<button class='page-btn' onclick='cargarCardsCotizaciones(${pagina + 1}, ${JSON.stringify(busqueda)})' ${pagina >= totalPaginas ? 'disabled' : ''}>&#8594;</button>`;
    nav.innerHTML = html;
}
// --- Checkbox global móvil ---
$(document).on('change', '#selectAllCotizacionesMovil', function() {
    const checked = this.checked;
    if (checked) {
        // Tomar los valores actuales de todos los filtros
        const filtroEmpresa = $('#filtroEmpresa').val();
        const filtroConvenio = $('#filtroConvenio').val();
        const filtroDni = $('#filtroDni').val();
        const filtroFechaDesde = $('#filtroFechaDesde').val();
        const filtroFechaHasta = $('#filtroFechaHasta').val();
        $.ajax({
            url: 'dashboard.php?action=cotizaciones_api',
            type: 'GET',
            data: {
                filtro_fecha_desde: filtroFechaDesde,
                filtro_fecha_hasta: filtroFechaHasta,
                filtro_empresa: filtroEmpresa,
                filtro_convenio: filtroConvenio,
                filtro_dni: filtroDni,
                length: 10000,
                start: 0,
                draw: 1
            },
            success: function(resp) {
                let data = resp.data || [];
                // Filtrar solo cotizaciones con saldo pendiente
                const ids = data.filter(row => parseFloat(row.saldo) > 0).map(row => row.id);
                // Marcar todos los checkboxes visibles
                $('.cotizacion-checkbox-movil').each(function() {
                    $(this).prop('checked', ids.includes($(this).attr('data-id')));
                });
                setSeleccionadasManualMovil(ids);
                actualizarTotalMovil();
            }
        });
    } else {
        setSeleccionadasManualMovil([]);
        $('.cotizacion-checkbox-movil').prop('checked', false);
        actualizarTotalMovil();
    }
});

(function() {
    let lastMode = window.innerWidth < 768 ? 'mobile' : 'desktop';
    let lastBusqueda = '';
    let resizeTimeout = null;
    function isMobile() { return window.innerWidth < 768; }
    function cargarSiMovilCotizaciones(force = false) {
        if (isMobile()) {
            const buscador = document.getElementById('buscadorCotizacionesMovil');
            let busqueda = buscador ? buscador.value : '';
            if (force || lastMode !== 'mobile' || lastBusqueda !== busqueda) {
                cargarCardsCotizaciones(1, busqueda);
                lastMode = 'mobile';
                lastBusqueda = busqueda;
            }
        } else {
            // Limpiar cards y paginación móvil
            const nav = document.getElementById('paginacionCotizacionesMovil');
            if (nav && nav.parentNode) nav.parentNode.removeChild(nav);
            const cont = document.getElementById('cardsCotizacionesAjax');
            if (cont) cont.innerHTML = '';
            lastMode = 'desktop';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        cargarSiMovilCotizaciones(true);
        // Buscador móvil
        const buscador = document.getElementById('buscadorCotizacionesMovil');
        const btnClear = document.getElementById('btnClearCotizacionesMovil');
        if (buscador) {
            buscador.addEventListener('input', function(e) {
                cargarSiMovilCotizaciones(true);
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', function() {
                buscador.value = '';
                cargarCardsCotizaciones(1, '');
            });
        }
        // Filtros avanzados: recargar cards al cambiar filtros
        $('#filtroDni, #filtroEmpresa, #filtroConvenio, #filtroFechaDesde, #filtroFechaHasta').on('change keyup', function() {
            cargarSiMovilCotizaciones(true);
        });
        // Selección de cards
        $(document).on('change', '.cotizacion-checkbox-movil', function() {
            let seleccionadas = getSeleccionadasManualMovil();
            const id = $(this).attr('data-id');
            if (this.checked) {
                if (!seleccionadas.includes(id)) seleccionadas.push(id);
            } else {
                seleccionadas = seleccionadas.filter(x => x !== id);
            }
            setSeleccionadasManualMovil(seleccionadas);
            // Actualizar el estado del checkbox global
            const totalCheckboxes = $('.cotizacion-checkbox-movil').length;
            const checkedCheckboxes = $('.cotizacion-checkbox-movil:checked').length;
            $('#selectAllCotizacionesMovil').prop('checked', totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
            actualizarTotalMovil();
        });
        // Pago masivo móvil
        document.getElementById('btnPagoMasivoMovil').addEventListener('click', function() {
            actualizarTotalMovil();
        });
        document.getElementById('confirmarPagoMasivoMovil').addEventListener('click', function() {
            const seleccionadas = getSeleccionadasManualMovil();
            if (seleccionadas.length === 0) return;
            fetch('cotizaciones/api/pago_masivo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cotizaciones: seleccionadas })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pago masivo realizado correctamente',
                        showConfirmButton: false,
                        timer: 1800
                    });
                    setSeleccionadasManualMovil([]);
                    setTimeout(() => location.reload(), 1800);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al realizar el pago masivo',
                        text: data.message || '',
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión al procesar el pago masivo',
                });
            });
        });
    });
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            cargarSiMovilCotizaciones();
        }, 150);
    });
})();
    </script>
</div>

