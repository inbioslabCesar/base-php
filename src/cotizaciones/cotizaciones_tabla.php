   <div class="desktop-view">
        <div class="table-responsive">
            <table id="tablaCotizaciones" class="table table-modern align-middle">
                <thead>
                    <tr>
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
                                <td><?= htmlspecialchars($cotizacion['codigo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['nombre_cliente'] ?? '') . ' ' . htmlspecialchars($cotizacion['apellido_cliente'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['dni'] ?? '') ?></td>
                                <td><?= htmlspecialchars($cotizacion['fecha'] ?? '') ?></td>
                                <td>S/ <?= number_format($cotizacion['total'] ?? 0, 2) ?></td>
                                <!-- Columna de referencia -->
                                <td>
                                    <?php
                                    if (!empty($cotizacion['id_empresa']) && (!empty($cotizacion['nombre_comercial']) || !empty($cotizacion['razon_social']))) {
                                        echo '<span class="badge bg-info text-dark">' .
                                            htmlspecialchars($cotizacion['nombre_comercial'] ?: $cotizacion['razon_social']) .
                                            '</span>';
                                    } elseif (!empty($cotizacion['id_convenio']) && !empty($cotizacion['nombre_convenio'])) {
                                        echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($cotizacion['nombre_convenio']) . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Particular</span>';
                                    }
                                    ?>
                                </td>

                                <!-- Estado Pago calculado -->
                                <td>
                                    <?php
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
                                    if ($descargaAnticipada) {
                                        $badgeClassPago = 'bg-orange text-dark'; // Clase personalizada para naranja
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
                                <!-- Estado Examen -->
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
                                    // Mostrar botón según estado de pago
                                    if (($rol === 'admin' || $rol === 'recepcionista')) {
                                        if ($saldo <= 0) {
                                            // Pagado: mostrar botón gris historial
                                            echo '<a href="dashboard.php?vista=pago_cotizacion&id=' . $cotizacion['id'] . '" class="btn btn-secondary btn-sm mb-1" title="Ver historial de pagos"><i class="bi bi-clock-history"></i></a>';
                                        } else {
                                            // Pendiente o parcial: mostrar botón amarillo registrar pago
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
                            <td colspan="10" class="text-center">No hay cotizaciones registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>