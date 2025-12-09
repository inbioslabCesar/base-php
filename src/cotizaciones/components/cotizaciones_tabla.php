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
                                        <p>Total a pagar: <strong id="modalTotalPago">S/ 0.00</strong></p>
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
    <!-- jQuery (requerido por DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>


    $(document).ready(function() {
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
                        return `<input type='checkbox' class='cotizacion-checkbox' data-id='${row.id}' data-saldo='${parseFloat(row.total) || 0}'>`;
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
                        // Calcular estado de pago usando total pagado
                        const total = parseFloat(row.total) || 0;
                        const pagado = parseFloat(row.total_pagado) || 0;
                        if (pagado >= total && total > 0) {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
                        } else if (pagado > 0 && pagado < total) {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Parcial</span>`;
                        } else {
                            return `<span class='badge bg-danger'><i class='bi bi-x-circle-fill'></i> Pendiente</span>`;
                        }
                    }
                },
                {
                    "data": "estado_examen",
                    "render": function(data) {
                        if (data === 'pendiente') {
                            return `<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente</span>`;
                        } else {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado</span>`;
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
    });
        $('#btnBuscarCotizaciones, #btnLimpiarCotizaciones').on('click', function() {
            tabla.ajax.reload();
        });
    $(function() {
        const btnPagoMasivo = document.getElementById('btnPagoMasivo');
        const totalPagoMasivo = document.getElementById('totalPagoMasivo');
        const modalTotalPago = document.getElementById('modalTotalPago');
        const cantidadSeleccionadas = document.getElementById('cantidadSeleccionadas');
        const confirmarPagoMasivo = document.getElementById('confirmarPagoMasivo');
        const selectAll = document.getElementById('selectAllCotizaciones');

        function getCheckboxes() {
            return Array.from(document.querySelectorAll('.cotizacion-checkbox'));
        }
        // Array global de IDs seleccionados manualmente
        function getSeleccionadasManual() {
            try {
                return JSON.parse(localStorage.getItem('cotizacionesManualSeleccionadasDesktop') || '[]');
            } catch(e) { return []; }
        }
        function setSeleccionadasManual(arr) {
            localStorage.setItem('cotizacionesManualSeleccionadasDesktop', JSON.stringify(arr));
        }
        // Restaurar selección manual al cargar página
        function restaurarSeleccionManual() {
            const seleccionadas = getSeleccionadasManual();
            getCheckboxes().forEach(cb => {
                cb.checked = seleccionadas.includes(cb.getAttribute('data-id'));
            });
        }
        

        function actualizarTotal() {
            let total = 0;
            let count = 0;
            let algunoSeleccionado = false;
            getCheckboxes().forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.getAttribute('data-saldo'));
                    count++;
                    algunoSeleccionado = true;
                }
            });
            totalPagoMasivo.textContent = 'S/ ' + total.toFixed(2);
            btnPagoMasivo.disabled = !algunoSeleccionado;
            modalTotalPago.textContent = 'S/ ' + total.toFixed(2);
            cantidadSeleccionadas.textContent = count;
        }

        // Evento delegado para checkboxes
        $(document).on('change', '.cotizacion-checkbox', function() {
            // Actualizar array global de seleccionadas manualmente
            let seleccionadas = getSeleccionadasManual();
            const id = $(this).attr('data-id');
            if (this.checked) {
                if (!seleccionadas.includes(id)) seleccionadas.push(id);
            } else {
                seleccionadas = seleccionadas.filter(x => x !== id);
            }
            setSeleccionadasManual(seleccionadas);
            actualizarTotal();
            if (!this.checked) selectAll.checked = false;
            if (getCheckboxes().length > 0 && getCheckboxes().every(c => c.checked)) selectAll.checked = true;
        });

        // Evento para selectAll
        selectAll.addEventListener('change', function() {
            getCheckboxes().forEach(cb => {
                cb.checked = selectAll.checked;
            });
            // Si se selecciona todo, guardar todos los IDs visibles; si se deselecciona, limpiar
            if (selectAll.checked) {
                const ids = getCheckboxes().map(cb => cb.getAttribute('data-id'));
                setSeleccionadasManual(ids);
            } else {
                setSeleccionadasManual([]);
            }
            actualizarTotal();
        });

        // Actualizar total cada vez que se dibuja la tabla
        $('#tablaCotizaciones').on('draw.dt', function() {
            selectAll.checked = false;
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

            fetch('cotizaciones/pago_masivo.php', {
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
</div>