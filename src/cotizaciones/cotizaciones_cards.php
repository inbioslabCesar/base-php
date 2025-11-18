<?php
require_once __DIR__ . '/../auth/empresa_config.php';
?>
<div class="mobile-view mt-3">
    <?php if ($cotizaciones_pagina) { ?>
        <div class="d-flex align-items-center mb-2 px-2">
            <input type="checkbox" id="selectAllCards" style="margin-right:8px;">
            <label for="selectAllCards" style="margin-bottom:0; cursor:pointer;">Seleccionar todo</label>
        </div>
        <?php foreach ($cotizaciones_pagina as $cotizacion) {
            // Calcular estado de pago
            $total = floatval($cotizacion['total']);
            $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
            $saldo = $total - $pagado;

            // Detectar si existe pago con método descarga anticipada
            $pagosCot = $pagosPorCotizacionDetalle[$cotizacion['id']] ?? [];
            $descargaAnticipada = false;
            foreach ($pagosCot as $pago) {
                if ($pago['metodo_pago'] === 'descarga_anticipada') {
                    $descargaAnticipada = true;
                    break;
                }
            }
            if ($saldo <= 0) {
                $badgeClassPago = 'bg-success';
                $iconPago = 'bi-check-circle-fill';
                $textoPago = 'Pagado';
            } elseif ($descargaAnticipada) {
                $badgeClassPago = 'bg-orange text-dark'; // Clase personalizada para naranja
                $iconPago = 'bi-clock';
                $textoPago = 'Descarga anticipada';
            } elseif ($pagado > 0) {
                $badgeClassPago = 'bg-warning text-dark';
                $iconPago = 'bi-hourglass-split';
                $textoPago = 'Parcial: S/ ' . number_format($saldo, 2);
            } else {
                $badgeClassPago = 'bg-danger';
                $iconPago = 'bi-x-circle-fill';
                $textoPago = 'Pendiente: S/ ' . number_format($saldo, 2);
            }

            // Estado de exámenes
            $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
            $pendientes = array_filter($examenes, function ($ex) {
                return $ex['estado'] === 'pendiente';
            });
        ?>
            <div class="cotizacion-card position-relative">
                <input type="checkbox" class="card-checkbox position-absolute" style="top:10px; left:10px; z-index:2;" data-id="<?= $cotizacion['id'] ?>" data-saldo="<?= $saldo ?>">
                <div class="card-header-cotizacion">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="codigo">📋 <?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></div>
                            <div class="fecha"><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5">S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></div>
                            <small><?= htmlspecialchars($cotizacion['rol_creador'] ?? '') ?></small>
                        </div>
                    </div>
                </div>
                <div class="card-body-cotizacion">
                    <!-- Información del paciente -->
                    <div class="patient-info-card">
                        <div class="patient-name">👤 <?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></div>
                        <div class="patient-dni">DNI: <?= htmlspecialchars($cotizacion['dni'] ?? '') ?></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="info-row">
                        <span class="info-label">📋 Tipo:</span>
                        <span class="info-value">
                            <?php
                            if (!empty($cotizacion['id_empresa']) && (!empty($cotizacion['nombre_comercial']) || !empty($cotizacion['razon_social']))) {
                                echo '<span class="badge bg-info">' .
                                    htmlspecialchars($cotizacion['nombre_comercial'] ?: $cotizacion['razon_social']) .
                                    '</span>';
                            } elseif (!empty($cotizacion['id_convenio']) && !empty($cotizacion['nombre_convenio'])) {
                                echo '<span class="badge bg-warning">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Particular</span>';
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Estados -->
                    <div class="status-section">
                        <span class="badge badge-status <?= $badgeClassPago ?>">
                            <i class="bi <?= $iconPago ?>"></i>
                            <?= $textoPago ?>
                        </span>
                        <span class="badge badge-status <?= $pendientes ? 'bg-warning text-dark' : 'bg-success' ?>">
                            <i class="bi <?= $pendientes ? 'bi-hourglass-split' : 'bi-check-circle-fill' ?>"></i>
                            <?= $pendientes ? 'Examen Pendiente' : 'Examen Completado' ?>
                        </span>
                    </div>
                </div>
                <!-- Acciones -->
                <div class="actions-card">
                    <?php
                    if ($rol === 'admin' || $rol === 'recepcionista') {
                        echo '<a href="dashboard.php?vista=detalle_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-info btn-card-action" title="Ver cotización"><i class="bi bi-eye"></i></a>';
                    }
                    if ($rol === 'admin' || $rol === 'recepcionista' || $rol === 'laboratorista') {
                        echo '<a href="dashboard.php?vista=formulario&cotizacion_id=' . $cotizacion['id'] . '" class="btn btn-primary btn-card-action" title="Editar o agregar resultados"><i class="bi bi-pencil-square"></i></a>';
                    }
                    if ($rol === 'admin' || $rol === 'recepcionista') {
                        if ($saldo <= 0) {
                            // Pagado: mostrar botón gris historial
                            echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-secondary btn-card-action" title="Ver historial de pagos"><i class="bi bi-clock-history"></i></a>';
                        } else {
                            // Pendiente o parcial: mostrar botón amarillo registrar pago
                            echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-warning btn-card-action" title="Registrar pago"><i class="bi bi-cash-coin"></i></a>';
                        }
                    }
                    ?>
                    <?php if ($rol === 'admin'): ?>
                        <a href="dashboard.php?action=eliminar_cotizacion&id=<?= $cotizacion['id'] ?>" class="btn btn-danger btn-card-action" title="Eliminar cotización" onclick="return confirm('¿Seguro que deseas eliminar esta cotización?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($rol === 'admin' || $rol === 'recepcionista'): ?>
                        <a href="resultados/descarga-pdf.php?cotizacion_id=<?= $cotizacion['id'] ?>" class="btn btn-success btn-card-action" title="Descargar PDF de todos los resultados" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php
        }
        ?>
        <!-- Botón pago masivo flotante -->
        <div id="pagoMasivoMobile" class="fixed-bottom mb-4 d-flex justify-content-center" style="pointer-events:none;">
            <button id="btnPagoMasivoMobile" class="btn btn-success px-4 py-2" disabled style="pointer-events:auto; font-size:1.1em;">
                Pago masivo (<span id="totalPagoMasivoMobile">S/ 0.00</span>)
            </button>
        </div>
        <!-- Paginación para cards -->
        <nav class="mt-3">
            <nav class="mobile-pagination">
                <?php
                // Mantener todos los parámetros activos en la URL
                $params = $_GET;
                unset($params['pagina']);
                $baseUrl = '';
                if (!empty($params)) {
                    foreach ($params as $key => $value) {
                        $baseUrl .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                }
                ?>
                <form method="get" style="display:inline">
                    <?= $baseUrl ?>
                    <button class="page-btn" type="submit" name="pagina" value="<?= max(1, $pagina - 1) ?>" <?= $pagina <= 1 ? 'disabled' : '' ?>>&#8592;</button>
                </form>
                <?php
                $pages = [];
                if ($pagina > 1) $pages[] = $pagina - 1;
                $pages[] = $pagina;
                if ($pagina < $total_paginas) $pages[] = $pagina + 1;
                foreach ($pages as $p) {
                ?>
                    <form method="get" style="display:inline">
                        <?= $baseUrl ?>
                        <button class="page-btn<?= $p == $pagina ? ' active' : '' ?>" type="submit" name="pagina" value="<?= $p ?>"><?= $p ?></button>
                    </form>
                <?php }
                ?>
                <form method="get" style="display:inline">
                    <?= $baseUrl ?>
                    <button class="page-btn" type="submit" name="pagina" value="<?= min($total_paginas, $pagina + 1) ?>" <?= $pagina >= $total_paginas ? 'disabled' : '' ?>>&#8594;</button>
                </form>
            </nav>
        </nav>
<?php } else { ?>
    <div class="text-center py-5">
        <div class="text-muted">
            <i class="bi bi-inbox display-1 d-block mb-3"></i>
            <h5>No hay cotizaciones registradas</h5>
        </div>
        <div class="d-flex align-items-center mb-2 px-2">
            <input type="checkbox" id="selectAllCards" style="margin-right:8px;">
            <label for="selectAllCards" style="margin-bottom:0; cursor:pointer;">Seleccionar todo</label>
        </div>
    </div>
<?php } ?>
    <div class="text-center py-5">
        <!-- Modal pago masivo móvil -->
        <div class="modal fade" id="modalPagoMasivoMobile" tabindex="-1" aria-labelledby="modalPagoMasivoMobileLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPagoMasivoMobileLabel">Confirmar pago masivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Desea registrar el pago masivo para <span id="cantidadSeleccionadasMobile">0</span> cotizaciones seleccionadas?</p>
                        <p>Total a pagar: <strong id="modalTotalPagoMobile">S/ 0.00</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="confirmarPagoMasivoMobile">Confirmar pago</button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.getElementById('selectAllCards');
                const btnPagoMasivo = document.getElementById('btnPagoMasivoMobile');
                const totalPagoMasivo = document.getElementById('totalPagoMasivoMobile');
                const modalTotalPago = document.getElementById('modalTotalPagoMobile');
                const cantidadSeleccionadas = document.getElementById('cantidadSeleccionadasMobile');
                const confirmarPagoMasivo = document.getElementById('confirmarPagoMasivoMobile');

                function getCheckboxes() {
                    return document.querySelectorAll('.card-checkbox');
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
                    if (totalPagoMasivo) totalPagoMasivo.textContent = 'S/ ' + total.toFixed(2);
                    if (btnPagoMasivo) btnPagoMasivo.disabled = !algunoSeleccionado;
                    if (modalTotalPago) modalTotalPago.textContent = 'S/ ' + total.toFixed(2);
                    if (cantidadSeleccionadas) cantidadSeleccionadas.textContent = count;
                }

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        getCheckboxes().forEach(cb => {
                            cb.checked = selectAll.checked;
                        });
                        actualizarTotal();
                    });
                }

                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('card-checkbox')) {
                        actualizarTotal();
                        if (selectAll) {
                            if (!e.target.checked) selectAll.checked = false;
                            if ([...getCheckboxes()].every(c => c.checked)) selectAll.checked = true;
                        }
                    }
                });

                if (btnPagoMasivo) {
                    btnPagoMasivo.addEventListener('click', function() {
                        actualizarTotal();
                        var modal = new bootstrap.Modal(document.getElementById('modalPagoMasivoMobile'));
                        modal.show();
                    });
                }

                if (confirmarPagoMasivo) {
                    confirmarPagoMasivo.addEventListener('click', function() {
                        const seleccionadas = Array.from(getCheckboxes())
                            .filter(cb => cb.checked)
                            .map(cb => cb.getAttribute('data-id'));
                        if (seleccionadas.length === 0) return;

                        fetch('cotizaciones/pago_masivo.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    cotizaciones: seleccionadas
                                })
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
                                    // Actualizar visualmente los cards seleccionados
                                    seleccionadas.forEach(function(id) {
                                        // Ocultar checkbox
                                        var cb = document.querySelector('.card-checkbox[data-id="' + id + '"]');
                                        if (cb) cb.style.display = 'none';
                                        // Cambiar badge de estado a Pagado y eliminar badge de descarga anticipada
                                        var card = cb ? cb.closest('.cotizacion-card') : null;
                                        if (card) {
                                            // Buscar todos los badges de estado de pago
                                            var badges = card.querySelectorAll('.badge-status');
                                            badges.forEach(function(badge) {
                                                // Si es descarga anticipada, eliminarlo
                                                if (badge.textContent.trim().toLowerCase().includes('descarga anticipada')) {
                                                    badge.remove();
                                                }
                                            });
                                            // Buscar el primer badge de estado de pago y actualizarlo a Pagado
                                            var badgePago = card.querySelector('.badge-status');
                                            if (badgePago) {
                                                badgePago.className = 'badge badge-status bg-success';
                                                badgePago.innerHTML = '<i class="bi bi-check-circle-fill"></i> Pagado';
                                            }
                                            // Actualizar o crear badge de estado de examen a Completado
                                            var badgeExamen = Array.from(card.querySelectorAll('.badge-status')).find(b => b.textContent.trim().toLowerCase().includes('examen'));
                                            if (badgeExamen) {
                                                badgeExamen.className = 'badge badge-status bg-success';
                                                badgeExamen.innerHTML = '<i class="bi bi-check-circle-fill"></i> Examen Completado';
                                            } else {
                                                // Si no existe, crear el badge y agregarlo
                                                var statusSection = card.querySelector('.status-section');
                                                if (statusSection) {
                                                    var newBadge = document.createElement('span');
                                                    newBadge.className = 'badge badge-status bg-success';
                                                    newBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Examen Completado';
                                                    statusSection.appendChild(newBadge);
                                                }
                                            }
                                        }
                                    });
                                    // Deshabilitar botón de pago masivo y resetear total
                                    if (btnPagoMasivo) btnPagoMasivo.disabled = true;
                                    if (totalPagoMasivo) totalPagoMasivo.textContent = 'S/ 0.00';
                                    if (modalTotalPago) modalTotalPago.textContent = 'S/ 0.00';
                                    if (cantidadSeleccionadas) cantidadSeleccionadas.textContent = '0';
                                    // Cerrar el modal automáticamente y recargar la página para sincronizar desktop
                                    var modalElement = document.getElementById('modalPagoMasivoMobile');
                                    if (modalElement) {
                                        var modalInstance = bootstrap.Modal.getInstance(modalElement);
                                        if (modalInstance) {
                                            setTimeout(function() {
                                                modalInstance.hide();
                                                setTimeout(function() {
                                                    window.location.reload();
                                                }, 400); // Espera breve tras cerrar modal
                                            }, 1200);
                                        }
                                    }
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
                }

                // Actualizar total al cargar y tras paginación
                actualizarTotal();
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('page-btn')) {
                        setTimeout(actualizarTotal, 300);
                    }
                });
            });
        </script>