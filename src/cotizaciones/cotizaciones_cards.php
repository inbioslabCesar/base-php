<div class="mobile-view mt-3">
        <?php if ($cotizaciones_pagina): ?>
            <?php foreach ($cotizaciones_pagina as $cotizacion): ?>
                <?php
                // Calcular estado de pago
                $total = floatval($cotizacion['total']);
                $pagado = floatval($pagosPorCotizacion[$cotizacion['id']] ?? 0);
                $saldo = $total - $pagado;

                if ($saldo <= 0) {
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

                // Estado de exámenes
                $examenes = $examenesPorCotizacion[$cotizacion['id']] ?? [];
                $pendientes = array_filter($examenes, function ($ex) {
                    return $ex['estado'] === 'pendiente';
                });
                ?>
                <div class="cotizacion-card">
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

            <?php endforeach; ?>
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
                    foreach ($pages as $p): ?>
                        <form method="get" style="display:inline">
                            <?= $baseUrl ?>
                            <button class="page-btn<?= $p == $pagina ? ' active' : '' ?>" type="submit" name="pagina" value="<?= $p ?>"><?= $p ?></button>
                        </form>
                    <?php endforeach; ?>
                    <form method="get" style="display:inline">
                        <?= $baseUrl ?>
                        <button class="page-btn" type="submit" name="pagina" value="<?= min($total_paginas, $pagina + 1) ?>" <?= $pagina >= $total_paginas ? 'disabled' : '' ?>>&#8594;</button>
                    </form>
                </nav>
            </nav>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-inbox display-1 d-block mb-3"></i>
                    <h5>No hay cotizaciones registradas</h5>
                    <p>Cuando se registren cotizaciones aparecerán aquí.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>