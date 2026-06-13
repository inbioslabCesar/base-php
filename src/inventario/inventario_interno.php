<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

$tablesReady = false;
$recetas = [];
$examenes = [];
$itemsActivos = [];
$stockInterno = [];
$transferencias = [];
$repeticionesPrueba = [];
$resumen = [
    'recetas_activas' => 0,
    'examenes_configurados' => 0,
    'transferencias_hoy' => 0,
    'items_stock_interno' => 0,
];

$flashMensaje = trim((string)($_SESSION['mensaje'] ?? ''));
$flashTipo = (stripos($flashMensaje, 'no se pudo') !== false
    || stripos($flashMensaje, 'inválido') !== false
    || stripos($flashMensaje, 'insuficiente') !== false
    || stripos($flashMensaje, 'faltan') !== false
    || stripos($flashMensaje, 'error') !== false)
    ? 'danger'
    : 'success';
unset($_SESSION['mensaje']);

$_SESSION['inventario_transfer_form_token'] = bin2hex(random_bytes(16));
$transferFormToken = (string)$_SESSION['inventario_transfer_form_token'];

try {
    $stmtTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('inventario_items','inventario_examen_recetas','inventario_transferencias','inventario_transferencias_detalle')");
    $stmtTbl->execute();
    $tablesReady = ((int)$stmtTbl->fetchColumn() === 4);

    if ($tablesReady) {
        $stmtExamenes = $pdo->query("SELECT id, nombre FROM examenes WHERE COALESCE(vigente,1)=1 ORDER BY nombre ASC");
        $examenes = $stmtExamenes->fetchAll(\PDO::FETCH_ASSOC);

        $stmtItems = $pdo->query("SELECT i.id, i.codigo, i.nombre, i.marca, i.presentacion, i.unidad_medida, IFNULL(SUM(l.cantidad_actual),0) AS stock_actual
            FROM inventario_items i
            LEFT JOIN inventario_lotes l ON l.item_id = i.id
            WHERE i.activo = 1
            GROUP BY i.id, i.codigo, i.nombre, i.marca, i.presentacion, i.unidad_medida
            ORDER BY i.nombre ASC");
        $itemsActivos = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

        $stmtRecetas = $pdo->query("SELECT r.id, r.id_examen, r.item_id, r.cantidad_por_prueba, r.activo, r.observacion,
            e.nombre AS examen_nombre,
            i.codigo AS item_codigo,
            i.nombre AS item_nombre,
            i.unidad_medida,
            i.marca,
            i.presentacion
            FROM inventario_examen_recetas r
            LEFT JOIN examenes e ON e.id = r.id_examen
            JOIN inventario_items i ON i.id = r.item_id
            ORDER BY e.nombre ASC, i.nombre ASC");
        $recetas = $stmtRecetas->fetchAll(\PDO::FETCH_ASSOC);

        $resumen['recetas_activas'] = (int)array_reduce($recetas, function ($carry, $row) {
            return $carry + (((int)($row['activo'] ?? 1) === 1) ? 1 : 0);
        }, 0);

        $idsExamenes = [];
        foreach ($recetas as $r) {
            if ((int)($r['activo'] ?? 1) === 1 && !empty($r['id_examen'])) {
                $idsExamenes[(int)$r['id_examen']] = true;
            }
        }
        $resumen['examenes_configurados'] = count($idsExamenes);

        $stmtStockInterno = $pdo->query("SELECT
            i.id,
            i.codigo,
            i.nombre,
            i.unidad_medida,
            IFNULL(tra.total_transferido, 0) AS transferido,
            IFNULL(con.total_consumido, 0) AS consumido,
            (IFNULL(tra.total_transferido, 0) - IFNULL(con.total_consumido, 0)) AS saldo
        FROM inventario_items i
        LEFT JOIN (
            SELECT td.item_id, SUM(td.cantidad) AS total_transferido
            FROM inventario_transferencias_detalle td
            JOIN inventario_transferencias t ON t.id = td.transferencia_id
            WHERE t.destino = 'laboratorio'
            GROUP BY td.item_id
        ) tra ON tra.item_id = i.id
        LEFT JOIN (
            SELECT item_id, SUM(cantidad_consumida) AS total_consumido
            FROM inventario_consumos_examen
            WHERE estado = 'aplicado'
            GROUP BY item_id
        ) con ON con.item_id = i.id
        WHERE i.activo = 1
          AND (IFNULL(tra.total_transferido, 0) > 0 OR IFNULL(con.total_consumido, 0) > 0)
        ORDER BY saldo ASC, i.nombre ASC");
        $stockInterno = $stmtStockInterno->fetchAll(\PDO::FETCH_ASSOC);

        $stmtTransferencias = $pdo->query("SELECT
            t.id,
            t.origen,
            t.destino,
            t.fecha_hora,
            t.observacion,
            CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,'')) AS usuario,
            COUNT(td.id) AS items_count,
            IFNULL(SUM(td.cantidad),0) AS cantidad_total,
            GROUP_CONCAT(CONCAT(COALESCE(i.codigo,''), ' · ', COALESCE(i.nombre,'')) ORDER BY i.nombre ASC SEPARATOR ' | ') AS items_detalle
        FROM inventario_transferencias t
        LEFT JOIN inventario_transferencias_detalle td ON td.transferencia_id = t.id
        LEFT JOIN inventario_items i ON i.id = td.item_id
        LEFT JOIN usuarios u ON u.id = t.usuario_id
        GROUP BY t.id, t.origen, t.destino, t.fecha_hora, t.observacion, u.nombre, u.apellido
        ORDER BY t.fecha_hora DESC, t.id DESC
        LIMIT 20");
        $transferencias = $stmtTransferencias->fetchAll(\PDO::FETCH_ASSOC);

                $stmtRepeticiones = $pdo->query("SELECT
                        c.fecha_hora,
                        c.id_cotizacion,
                        c.cantidad_consumida,
                        c.observacion,
                        e.nombre AS examen_nombre,
                        i.codigo AS item_codigo,
                        i.nombre AS item_nombre,
                        i.unidad_medida,
                        CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,'')) AS usuario
                FROM inventario_consumos_examen c
                LEFT JOIN examenes e ON e.id = c.id_examen
                LEFT JOIN inventario_items i ON i.id = c.item_id
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.estado = 'aplicado'
                    AND c.origen_evento LIKE 'repeticion%'
                ORDER BY c.fecha_hora DESC, c.id DESC
                LIMIT 50");
                $repeticionesPrueba = $stmtRepeticiones->fetchAll(\PDO::FETCH_ASSOC);

        $resumen['items_stock_interno'] = count($stockInterno);

        $stmtTransferHoy = $pdo->prepare("SELECT COUNT(*) FROM inventario_transferencias WHERE DATE(fecha_hora) = CURDATE()");
        $stmtTransferHoy->execute();
        $resumen['transferencias_hoy'] = (int)$stmtTransferHoy->fetchColumn();
    }
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error cargando inventario interno: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Inventario Interno</h3>
            <small class="text-muted">Configuración de consumo por examen (base para descuento automático)</small>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php?vista=inventario" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a inventario
            </a>
        </div>
    </div>

    <?php if ($flashMensaje !== ''): ?>
        <div class="alert alert-<?= $flashTipo ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
            Faltan tablas para inventario interno. Ejecuta <strong>sql/agregar_tablas_inventario_interno.sql</strong> y recarga la página.
        </div>
    <?php else: ?>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-primary"><div class="card-body py-2"><small class="text-muted d-block">Recetas activas</small><strong><?= (int)$resumen['recetas_activas'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-success"><div class="card-body py-2"><small class="text-muted d-block">Exámenes configurados</small><strong><?= (int)$resumen['examenes_configurados'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-info"><div class="card-body py-2"><small class="text-muted d-block">Transferencias hoy</small><strong><?= (int)$resumen['transferencias_hoy'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-dark"><div class="card-body py-2"><small class="text-muted d-block">Items con stock interno</small><strong><?= (int)$resumen['items_stock_interno'] ?></strong></div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong>Nueva receta de consumo</strong></div>
                    <div class="card-body">
                        <form method="post" action="dashboard.php?action=inventario_receta_guardar" class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Examen</label>
                                <select name="id_examen" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($examenes as $ex): ?>
                                        <option value="<?= (int)$ex['id'] ?>"><?= htmlspecialchars((string)$ex['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ítem de inventario</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($itemsActivos as $it): ?>
                                        <option value="<?= (int)$it['id'] ?>">
                                            <?= htmlspecialchars((string)$it['codigo'] . ' · ' . (string)$it['nombre'] . (!empty($it['marca']) ? ' · ' . (string)$it['marca'] : '') . ' · ' . (string)$it['unidad_medida']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cantidad por prueba</label>
                                <input type="number" step="0.0001" min="0.0001" name="cantidad_por_prueba" class="form-control" placeholder="Ej: 0.5" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="activo" class="form-select">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación (opcional)</label>
                                <input type="text" name="observacion" class="form-control" placeholder="Ej: Rendimiento estimado 100 pruebas por frasco">
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar receta</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong>Transferir a laboratorio</strong></div>
                    <div class="card-body">
                        <form method="post" action="dashboard.php?action=inventario_transferencia_guardar" class="row g-2" id="formInventarioTransferencia">
                            <input type="hidden" name="form_token" value="<?= htmlspecialchars($transferFormToken) ?>">
                            <div class="col-md-6">
                                <label class="form-label">Ítem desde almacén principal</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($itemsActivos as $it): ?>
                                        <option value="<?= (int)$it['id'] ?>">
                                            <?= htmlspecialchars((string)$it['codigo'] . ' · ' . (string)$it['nombre'] . ' · Stock: ' . number_format((float)($it['stock_actual'] ?? 0), 2) . ' ' . (string)$it['unidad_medida']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" step="0.0001" min="0.0001" name="cantidad" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Destino</label>
                                <input type="text" class="form-control" value="Laboratorio" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación (opcional)</label>
                                <input type="text" name="observacion" class="form-control" placeholder="Ej: Retiro para turno mañana">
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary" id="btnInventarioTransferencia"><i class="bi bi-arrow-left-right"></i> Registrar transferencia interna</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong>Stock interno estimado (laboratorio)</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tablaStockInterno" class="table table-sm table-striped mb-0 align-middle js-paginated-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ítem</th>
                                        <th>Transferido</th>
                                        <th>Consumido</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($stockInterno)): ?>
                                        <tr class="js-empty-row"><td colspan="4" class="text-center text-muted py-3">Sin movimientos internos aún.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($stockInterno as $s): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$s['codigo'] . ' · ' . (string)$s['nombre']) ?></td>
                                                <td><?= number_format((float)$s['transferido'], 4) ?> <?= htmlspecialchars((string)$s['unidad_medida']) ?></td>
                                                <td><?= number_format((float)$s['consumido'], 4) ?> <?= htmlspecialchars((string)$s['unidad_medida']) ?></td>
                                                <td><strong><?= number_format((float)$s['saldo'], 4) ?> <?= htmlspecialchars((string)$s['unidad_medida']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 px-2 py-2 border-top js-pagination-controls" data-table-id="tablaStockInterno">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Mostrar</small>
                                <select class="form-select form-select-sm js-page-size" style="width: 90px;">
                                    <option value="3" selected>3</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-prev">Anterior</button>
                                <small class="text-muted js-page-info">Página 1 de 1</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-next">Siguiente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong>Últimas transferencias</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tablaUltimasTransferencias" class="table table-sm table-striped mb-0 align-middle js-paginated-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Origen/Destino</th>
                                        <th>Ítems transferidos</th>
                                        <th>Cantidad total</th>
                                        <th>Observación</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transferencias)): ?>
                                        <tr class="js-empty-row"><td colspan="6" class="text-center text-muted py-3">Sin transferencias registradas.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($transferencias as $t): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$t['fecha_hora']) ?></td>
                                                <td><?= htmlspecialchars((string)$t['origen'] . ' → ' . (string)$t['destino']) ?></td>
                                                <td>
                                                    <?php
                                                        $detalleItems = trim((string)($t['items_detalle'] ?? ''));
                                                        $detalleItemsSafe = htmlspecialchars($detalleItems);
                                                    ?>
                                                    <?php if ($detalleItemsSafe !== ''): ?>
                                                        <?= str_replace(' | ', '<br>', $detalleItemsSafe) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin detalle</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format((float)$t['cantidad_total'], 4) ?></td>
                                                <td><?= htmlspecialchars(trim((string)($t['observacion'] ?? '')) !== '' ? (string)$t['observacion'] : '-') ?></td>
                                                <td><?= htmlspecialchars(trim((string)($t['usuario'] ?? '')) !== '' ? (string)$t['usuario'] : 'Sin dato') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 px-2 py-2 border-top js-pagination-controls" data-table-id="tablaUltimasTransferencias">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Mostrar</small>
                                <select class="form-select form-select-sm js-page-size" style="width: 90px;">
                                    <option value="3" selected>3</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-prev">Anterior</button>
                                <small class="text-muted js-page-info">Página 1 de 1</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-next">Siguiente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><strong>Recetas configuradas</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaRecetasConfiguradas" class="table table-sm table-striped align-middle mb-0 js-paginated-table">
                        <thead class="table-light">
                            <tr>
                                <th>Examen</th>
                                <th>Ítem</th>
                                <th>Marca</th>
                                <th>Presentación</th>
                                <th>Cantidad/prueba</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recetas)): ?>
                                <tr class="js-empty-row"><td colspan="8" class="text-center text-muted py-4">Sin recetas configuradas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recetas as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($r['examen_nombre'] ?? 'Examen no encontrado')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['item_codigo'] ?? '') . ' · ' . (string)($r['item_nombre'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['marca'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($r['presentacion'] ?? '-')) ?></td>
                                        <td><?= number_format((float)($r['cantidad_por_prueba'] ?? 0), 4) ?></td>
                                        <td><?= htmlspecialchars((string)($r['unidad_medida'] ?? '')) ?></td>
                                        <td>
                                            <span class="badge <?= ((int)($r['activo'] ?? 1) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ((int)($r['activo'] ?? 1) === 1) ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if (((int)($r['activo'] ?? 1) === 1)): ?>
                                                    <form method="post" action="dashboard.php?action=inventario_receta_eliminar" onsubmit="return confirm('¿Desactivar esta receta? Se dejarán de aplicar consumos para nuevos resultados, pero el histórico se conserva.');" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Desactivar receta"><i class="bi bi-eye-slash"></i></button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" action="dashboard.php?action=inventario_receta_reactivar" onsubmit="return confirm('¿Reactivar esta receta?');" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Reactivar receta"><i class="bi bi-eye"></i></button>
                                                    </form>
                                                    <form method="post" action="dashboard.php?action=inventario_receta_eliminar" onsubmit="return confirm('¿Eliminar permanentemente esta receta inactiva? Esta acción no se puede deshacer.');" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar permanentemente"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 px-2 py-2 border-top js-pagination-controls" data-table-id="tablaRecetasConfiguradas">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Mostrar</small>
                        <select class="form-select form-select-sm js-page-size" style="width: 90px;">
                            <option value="3" selected>3</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary js-prev">Anterior</button>
                        <small class="text-muted js-page-info">Página 1 de 1</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-next">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><strong>Historial de repeticiones de prueba</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaRepeticionesPrueba" class="table table-sm table-striped align-middle mb-0 js-paginated-table">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Cotización</th>
                                <th>Examen</th>
                                <th>Ítem</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($repeticionesPrueba)): ?>
                                <tr class="js-empty-row"><td colspan="7" class="text-center text-muted py-4">Sin repeticiones registradas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($repeticionesPrueba as $rep): ?>
                                    <?php
                                        $obsRaw = trim((string)($rep['observacion'] ?? ''));
                                        $motivo = $obsRaw;
                                        $posMotivo = stripos($obsRaw, 'Motivo:');
                                        if ($posMotivo !== false) {
                                            $motivo = trim(substr($obsRaw, $posMotivo + 7));
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($rep['fecha_hora'] ?? '')) ?></td>
                                        <td><?= (int)($rep['id_cotizacion'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars((string)($rep['examen_nombre'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($rep['item_codigo'] ?? '') . ' · ' . (string)($rep['item_nombre'] ?? '')) ?></td>
                                        <td><?= number_format((float)($rep['cantidad_consumida'] ?? 0), 4) ?> <?= htmlspecialchars((string)($rep['unidad_medida'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars(trim((string)($rep['usuario'] ?? '')) !== '' ? (string)$rep['usuario'] : 'Sin dato') ?></td>
                                        <td><?= htmlspecialchars($motivo !== '' ? $motivo : 'Sin motivo') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 px-2 py-2 border-top js-pagination-controls" data-table-id="tablaRepeticionesPrueba">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Mostrar</small>
                        <select class="form-select form-select-sm js-page-size" style="width: 90px;">
                            <option value="3" selected>3</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary js-prev">Anterior</button>
                        <small class="text-muted js-page-info">Página 1 de 1</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-next">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($tablesReady): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var controls = document.querySelectorAll('.js-pagination-controls');

    controls.forEach(function (control) {
        var tableId = control.getAttribute('data-table-id');
        var table = document.getElementById(tableId);
        if (!table) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            control.style.display = 'none';
            return;
        }

        var rows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
            return !row.classList.contains('js-empty-row');
        });

        if (rows.length === 0) {
            control.style.display = 'none';
            return;
        }

        var pageSizeSelect = control.querySelector('.js-page-size');
        var prevBtn = control.querySelector('.js-prev');
        var nextBtn = control.querySelector('.js-next');
        var pageInfo = control.querySelector('.js-page-info');
        var currentPage = 1;
        var storageKey = 'inventario_interno_page_size_' + tableId;

        try {
            var savedPageSize = localStorage.getItem(storageKey);
            if (savedPageSize && pageSizeSelect.querySelector('option[value="' + savedPageSize + '"]')) {
                pageSizeSelect.value = savedPageSize;
            }
        } catch (error) {
        }

        var render = function () {
            var pageSize = parseInt(pageSizeSelect.value || '3', 10);
            if (pageSize <= 0) {
                pageSize = 3;
            }

            var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;

            rows.forEach(function (row, index) {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });

            pageInfo.textContent = 'Página ' + currentPage + ' de ' + totalPages;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        };

        pageSizeSelect.addEventListener('change', function () {
            currentPage = 1;
            try {
                localStorage.setItem(storageKey, pageSizeSelect.value);
            } catch (error) {
            }
            render();
        });

        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                render();
            }
        });

        nextBtn.addEventListener('click', function () {
            var pageSize = parseInt(pageSizeSelect.value || '3', 10);
            var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            if (currentPage < totalPages) {
                currentPage++;
                render();
            }
        });

        render();
    });
});

(function () {
    var transferForm = document.getElementById('formInventarioTransferencia');
    if (!transferForm) return;

    transferForm.addEventListener('submit', function () {
        var submitBtn = document.getElementById('btnInventarioTransferencia');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Registrando...';
        }
    });
})();
</script>
<?php endif; ?>
