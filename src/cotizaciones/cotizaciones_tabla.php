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
                    <?php if ($cotizaciones): ?>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="cotizacion-checkbox" data-id="<?= $cotizacion['id'] ?>" data-saldo="<?= floatval($cotizacion['total']) - floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0) ?>">
                                </td>
                                <!-- ...existing code... -->
                                <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['dni'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                                <td>S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></td>
                                <!-- ...existing code... -->
                                <td>
                                    <?php
                                    if (!empty($cotizacion['id_empresa']) && (!empty($cotizacion['nombre_comercial']) || !empty($cotizacion['razon_social']))) {
                                        echo '<span class="badge bg-info text-dark">' .
                                            htmlspecialchars(!empty($cotizacion['nombre_comercial']) ? $cotizacion['nombre_comercial'] : $cotizacion['razon_social']) .
                                            '</span>';
                                    } elseif (!empty($cotizacion['id_convenio']) && !empty($cotizacion['nombre_convenio'])) {
                                        echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Particular</span>';
                                    }
                                    ?>
                                </td>
                                <!-- ...existing code... -->
                                <td>
                                    <?php
                                    $total = floatval($cotizacion['total']);
                                    $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
                                    $saldo = $total - $pagado;
                                    $pagosCot = $pagosPorCotizacionDetalle[$cotizacion['id']] ?? [];
                                    $ultimoPago = !empty($pagosCot) ? $pagosCot[0] : null;
                                    if ($ultimoPago && $ultimoPago['metodo_pago'] === 'descarga_anticipada') {
                                        $badgeClassPago = 'bg-orange text-dark';
                                        $iconPago = 'bi-clock';
                                        $textoPago = 'Descarga anticipada';
                                    } elseif ($saldo <= 0) {
                                        $badgeClassPago = 'bg-success';
                                        $iconPago = 'bi-check-circle-fill';
                                        $textoPago = 'Pagado';
                                    } elseif ($pagado > 0) {
                                        $badgeClassPago = 'bg-warning text-dark';
                                        $iconPago = 'bi-hourglass-split';
                                        $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
                                    } else {
                                        $badgeClassPago = 'bg-danger';
                                        $iconPago = 'bi-x-circle-fill';
                                        $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClassPago ?>">
                                        <i class="bi <?= $iconPago ?>"></i>
                                        <?= $textoPago ?>
                                    </span>
                                </td>
                                <!-- ...existing code... -->
                                <td>
                                    <?php
                                    $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
                                    $pendientes = array_filter($examenes, function ($ex) {
                                        return $ex['estado'] === 'pendiente';
                                    });
                                    if ($pendientes) {
                                        echo "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> Pendiente</span>";
                                    } else {
                                        echo "<span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> Completado</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></td>
                                <style>
                                .bg-orange {
                                    background-color: #ff9800 !important;
                                    color: #212529 !important;
                                }
                                </style>
                                <!-- ...existing code... -->
                                <td>
                                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                        <a href="dashboard.php?vista=detalle_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-info btn-sm mb-1" title="Ver cotización">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista'): ?>
                                        <a href="dashboard.php?vista=formulario&cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-sm mb-1" title="Editar o agregar resultados">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php
                                    if (($rol === 'admin' || $rol === 'recepcionista')) {
                                        if ($saldo <= 0) {
                                            echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-secondary btn-sm mb-1" title="Ver historial de pagos"><i class="bi bi-clock-history"></i></a>';
                                        } else {
                                            echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-warning btn-sm mb-1" title="Registrar pago"><i class="bi bi-cash-coin"></i></a>';
                                        }
                                    }
                                    ?>
                                    <?php if ($rol === 'admin'): ?>
                                        <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-sm mb-1" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                                        <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-sm mb-1" title="Descargar PDF de todos los resultados" target="_blank">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No hay cotizaciones registradas.</td>
                        </tr>
                    <?php endif; ?>
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAllCotizaciones');
        const checkboxes = document.querySelectorAll('.cotizacion-checkbox');
        const btnPagoMasivo = document.getElementById('btnPagoMasivo');
        const totalPagoMasivo = document.getElementById('totalPagoMasivo');
        const modalTotalPago = document.getElementById('modalTotalPago');
        const cantidadSeleccionadas = document.getElementById('cantidadSeleccionadas');
        const confirmarPagoMasivo = document.getElementById('confirmarPagoMasivo');

        function actualizarTotal() {
            let total = 0;
            let count = 0;
            let algunoSeleccionado = false;
            checkboxes.forEach(cb => {
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

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            actualizarTotal();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                actualizarTotal();
                if (!cb.checked) selectAll.checked = false;
                if ([...checkboxes].every(c => c.checked)) selectAll.checked = true;
            });
        });

        // Actualizar datos del modal al abrirlo
        btnPagoMasivo.addEventListener('click', function() {
            actualizarTotal();
        });

        // Lógica para confirmar pago masivo
        confirmarPagoMasivo.addEventListener('click', function() {
            const seleccionadas = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.getAttribute('data-id'));
            if (seleccionadas.length === 0) return;

            // Enviar por AJAX al backend
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