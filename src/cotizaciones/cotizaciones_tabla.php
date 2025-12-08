   <div class="desktop-view">
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
            "responsive": true,
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
                        if (row.referencia) {
                            return `<span class='badge bg-info text-dark'>${row.referencia}</span>`;
                        } else {
                            return `<span class='badge bg-secondary'>Particular</span>`;
                        }
                    }
                },
                {
                    "data": "estado_pago",
                    "render": function(data, type, row) {
                        // Personaliza según tu lógica de pagos
                        if (data === 'pagado') {
                            return `<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Pagado</span>`;
                        } else if (data === 'parcial') {
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
                        acciones += `<a href='dashboard.php?vista=detalle_cotizacion&id=${row.id}' class='btn btn-info btn-sm mb-1' title='Ver cotización'><i class='bi bi-eye'></i></a>`;
                        acciones += `<a href='dashboard.php?vista=form_cotizacion&id=${row.id}&edit=1' class='btn btn-dark btn-sm mb-1' title='Editar cotización'><i class='bi bi-file-earmark-medical'></i></a>`;
                        if (row.modificada == 1) {
                            acciones += `<span class='badge bg-warning text-dark ms-1' title='Cotización modificada'><i class='bi bi-pencil'></i> Modificada</span>`;
                        }
                        acciones += `<a href='dashboard.php?vista=formulario&cotizacion_id=${row.id}' class='btn btn-primary btn-sm mb-1' title='Editar o agregar resultados'><i class='bi bi-pencil-square'></i></a>`;
                        acciones += `<a href='dashboard.php?vista=pago_cotizacion&id=${row.id}' class='btn btn-warning btn-sm mb-1' title='Registrar pago'><i class='bi bi-cash-coin'></i></a>`;
                        acciones += `<a href='dashboard.php?action=eliminar_cotizacion&id=${row.id}' class='btn btn-danger btn-sm mb-1' title='Eliminar cotización' onclick='return confirm(\"¿Seguro que deseas eliminar esta cotización?\")'><i class='bi bi-trash'></i></a>`;
                        acciones += `<a href='resultados/descarga-pdf.php?cotizacion_id=${row.id}' class='btn btn-success btn-sm mb-1' title='Descargar PDF de todos los resultados' target='_blank'><i class='bi bi-file-earmark-pdf'></i></a>`;
                        return acciones;
                    }
                }
            ]
        });

        // Recargar tabla al buscar o limpiar
        $('#btnBuscarCotizaciones, #btnLimpiarCotizaciones').on('click', function() {
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
            actualizarTotal();
            if (!this.checked) selectAll.checked = false;
            if (getCheckboxes().length > 0 && getCheckboxes().every(c => c.checked)) selectAll.checked = true;
        });

        // Evento para selectAll
        selectAll.addEventListener('change', function() {
            getCheckboxes().forEach(cb => {
                cb.checked = selectAll.checked;
            });
            actualizarTotal();
        });

        // Actualizar total cada vez que se dibuja la tabla
        $('#tablaCotizaciones').on('draw.dt', function() {
            selectAll.checked = false;
            actualizarTotal();
        });

        // Actualizar datos del modal al abrirlo
        btnPagoMasivo.addEventListener('click', function() {
            actualizarTotal();
        });

        // Lógica para confirmar pago masivo
        confirmarPagoMasivo.addEventListener('click', function() {
            const seleccionadas = getCheckboxes()
                .filter(cb => cb.checked)
                .map(cb => cb.getAttribute('data-id'));
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