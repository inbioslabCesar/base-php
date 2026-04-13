<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';
$tablesReady = false;
$items = [];
$itemsActivos = [];
$lotesPorVencer = [];
$movimientos = [];
$resumen = [
    'items_activos' => 0,
    'stock_critico' => 0,
    'sin_stock' => 0,
    'lotes_por_vencer' => 0,
];

$filtroQ = trim((string)($_GET['q'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? 'todos'));
$filtroEstado = trim((string)($_GET['estado_stock'] ?? 'todos'));
$paginaItems = max(1, (int)($_GET['page'] ?? 1));
$itemsPorPaginaSolicitado = (int)($_GET['per_page'] ?? 3);
$itemsPorPaginaPermitidos = [3, 5, 10];
$usaPerPageUsuario = isset($_GET['per_page_user']) && (int)$_GET['per_page_user'] === 1;
$itemsPorPagina = ($usaPerPageUsuario && in_array($itemsPorPaginaSolicitado, $itemsPorPaginaPermitidos, true)) ? $itemsPorPaginaSolicitado : 3;
$totalItemsTabla = 0;
$totalPaginasItems = 1;
$paginaLotes = max(1, (int)($_GET['page_lotes'] ?? 1));
$lotesPorPaginaSolicitado = (int)($_GET['per_page_lotes'] ?? 3);
$lotesPorPaginaPermitidos = [3, 5, 10];
$lotesPorPagina = in_array($lotesPorPaginaSolicitado, $lotesPorPaginaPermitidos, true) ? $lotesPorPaginaSolicitado : 3;
$totalLotesPorVencer = 0;
$totalPaginasLotes = 1;
$paginaMovimientos = max(1, (int)($_GET['page_mov'] ?? 1));
$movimientosPorPaginaSolicitado = (int)($_GET['per_page_mov'] ?? 3);
$movimientosPorPaginaPermitidos = [3, 5, 10];
$movimientosPorPagina = in_array($movimientosPorPaginaSolicitado, $movimientosPorPaginaPermitidos, true) ? $movimientosPorPaginaSolicitado : 3;
$totalMovimientos = 0;
$totalPaginasMovimientos = 1;
$editarId = isset($_GET['editar_id']) ? (int)$_GET['editar_id'] : 0;
$itemEditar = null;
$hasMarcaCol = false;
$hasPresentacionCol = false;
$hasFactorPresentacionCol = false;
$hasCreatedAtCol = false;
$hasOrigenMovCol = false;
$hasControlaStockCol = false;
$flashMensaje = trim((string)($_SESSION['mensaje'] ?? ''));
$flashTipo = (stripos($flashMensaje, 'no se pudo') !== false
    || stripos($flashMensaje, 'inválido') !== false
    || stripos($flashMensaje, 'insuficiente') !== false
    || stripos($flashMensaje, 'faltan') !== false
    || stripos($flashMensaje, 'error') !== false)
    ? 'danger'
    : 'success';
unset($_SESSION['mensaje']);

$_SESSION['inventario_mov_form_token'] = bin2hex(random_bytes(16));
$movFormToken = (string)$_SESSION['inventario_mov_form_token'];

try {
    $requiredTables = ['inventario_items', 'inventario_lotes', 'inventario_movimientos'];
    $tablesReady = true;
    $stmtTbl = $pdo->prepare("SHOW TABLES LIKE ?");
    foreach ($requiredTables as $tblName) {
        $stmtTbl->execute([$tblName]);
        if (!$stmtTbl->fetchColumn()) {
            $tablesReady = false;
            break;
        }
    }

    if ($tablesReady) {
        $stmtCols = $pdo->query("SHOW COLUMNS FROM inventario_items");
        $defs = $stmtCols ? $stmtCols->fetchAll(\PDO::FETCH_ASSOC) : [];
        $cols = [];
        foreach ($defs as $def) {
            if (!empty($def['Field'])) {
                $cols[] = (string)$def['Field'];
            }
        }
        $hasMarcaCol = in_array('marca', $cols, true);
        $hasPresentacionCol = in_array('presentacion', $cols, true);
        $hasFactorPresentacionCol = in_array('factor_presentacion', $cols, true);
        $hasControlaStockCol = in_array('controla_stock', $cols, true);

        $stmtColCreatedAt = $pdo->query("SHOW COLUMNS FROM inventario_items LIKE 'created_at'");
        $hasCreatedAtCol = (bool)($stmtColCreatedAt && $stmtColCreatedAt->fetch(\PDO::FETCH_ASSOC));

        $stmtColOrigenMov = $pdo->query("SHOW COLUMNS FROM inventario_movimientos LIKE 'origen'");
        $hasOrigenMovCol = (bool)($stmtColOrigenMov && $stmtColOrigenMov->fetch(\PDO::FETCH_ASSOC));

        if ($editarId > 0) {
            $sqlEditar = "SELECT id, codigo, nombre, categoria, " .
                ($hasMarcaCol ? "marca" : "NULL AS marca") . ", " .
                ($hasPresentacionCol ? "presentacion" : "NULL AS presentacion") . ", " .
                ($hasFactorPresentacionCol ? "factor_presentacion" : "1 AS factor_presentacion") . ", " .
                ($hasControlaStockCol ? "i.controla_stock" : "1 AS controla_stock") . ", " .
                "unidad_medida, stock_minimo, stock_critico, activo
                FROM inventario_items
                WHERE id = ?
                LIMIT 1";
            $stmtEditar = $pdo->prepare($sqlEditar);
            $stmtEditar->execute([$editarId]);
            $itemEditar = $stmtEditar->fetch(\PDO::FETCH_ASSOC) ?: null;
        }

        $where = [];
        $params = [];

        if ($filtroQ !== '') {
            $where[] = "(i.nombre LIKE ? OR i.codigo LIKE ? OR i.unidad_medida LIKE ?" .
                ($hasMarcaCol ? " OR i.marca LIKE ?" : "") .
                ($hasPresentacionCol ? " OR i.presentacion LIKE ?" : "") .
                ")";
            $q = '%' . $filtroQ . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            if ($hasMarcaCol) {
                $params[] = $q;
            }
            if ($hasPresentacionCol) {
                $params[] = $q;
            }
        }

        if (in_array($filtroCategoria, ['reactivo', 'insumo', 'material', 'activo_fijo'], true)) {
            $where[] = "i.categoria = ?";
            $params[] = $filtroCategoria;
        }

        $whereSqlItems = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';
        $estadoParams = [];
        $whereEstadoSql = '';
        if (in_array($filtroEstado, ['sin_stock', 'critico', 'bajo', 'ok'], true)) {
            $whereEstadoSql = ' WHERE t.estado_stock = ?';
            $estadoParams[] = $filtroEstado;
        }

        $sqlItemsBase = "SELECT
            i.id,
            i.codigo,
            i.nombre,
            i.categoria,
            " . ($hasMarcaCol ? "i.marca" : "NULL AS marca") . ",
            " . ($hasPresentacionCol ? "i.presentacion" : "NULL AS presentacion") . ",
            " . ($hasFactorPresentacionCol ? "i.factor_presentacion" : "1 AS factor_presentacion") . ",
            " . ($hasControlaStockCol ? "i.controla_stock" : "1 AS controla_stock") . ",
            i.unidad_medida,
            i.stock_minimo,
            i.stock_critico,
            i.activo,
            " . ($hasCreatedAtCol ? "i.created_at" : "NULL AS created_at") . ",
            IFNULL(SUM(l.cantidad_actual),0) AS stock_actual,
            CASE
                WHEN " . ($hasControlaStockCol ? "i.controla_stock" : "1") . " = 0 THEN 'ok'
                WHEN IFNULL(SUM(l.cantidad_actual),0) <= 0 THEN 'sin_stock'
                WHEN IFNULL(SUM(l.cantidad_actual),0) <= i.stock_critico AND i.stock_critico > 0 THEN 'critico'
                WHEN IFNULL(SUM(l.cantidad_actual),0) <= i.stock_minimo AND i.stock_minimo > 0 THEN 'bajo'
                ELSE 'ok'
            END AS estado_stock
        FROM inventario_items i
        LEFT JOIN inventario_lotes l ON l.item_id = i.id" .
        $whereSqlItems .
        " GROUP BY i.id, i.codigo, i.nombre, i.categoria, " .
            ($hasMarcaCol ? "i.marca, " : "") .
            ($hasPresentacionCol ? "i.presentacion, " : "") .
            ($hasFactorPresentacionCol ? "i.factor_presentacion, " : "") .
            ($hasControlaStockCol ? "i.controla_stock, " : "") .
            ($hasCreatedAtCol ? "i.created_at, " : "") .
            "i.unidad_medida, i.stock_minimo, i.stock_critico, i.activo";

        $sqlCountItems = "SELECT COUNT(*) FROM (" . $sqlItemsBase . ") t" . $whereEstadoSql;
        $stmtCountItems = $pdo->prepare($sqlCountItems);
        $stmtCountItems->execute(array_merge($params, $estadoParams));
        $totalItemsTabla = (int)$stmtCountItems->fetchColumn();

        $sqlResumenItems = "SELECT
            IFNULL(SUM(CASE WHEN t.activo = 1 THEN 1 ELSE 0 END),0) AS items_activos,
            IFNULL(SUM(CASE WHEN t.estado_stock = 'critico' THEN 1 ELSE 0 END),0) AS stock_critico,
            IFNULL(SUM(CASE WHEN t.estado_stock = 'sin_stock' THEN 1 ELSE 0 END),0) AS sin_stock
            FROM (" . $sqlItemsBase . ") t" . $whereEstadoSql;
        $stmtResumenItems = $pdo->prepare($sqlResumenItems);
        $stmtResumenItems->execute(array_merge($params, $estadoParams));
        $rowResumenItems = $stmtResumenItems->fetch(\PDO::FETCH_ASSOC) ?: [];
        $resumen['items_activos'] = (int)($rowResumenItems['items_activos'] ?? 0);
        $resumen['stock_critico'] = (int)($rowResumenItems['stock_critico'] ?? 0);
        $resumen['sin_stock'] = (int)($rowResumenItems['sin_stock'] ?? 0);

        $totalPaginasItems = max(1, (int)ceil($totalItemsTabla / $itemsPorPagina));
        if ($paginaItems > $totalPaginasItems) {
            $paginaItems = $totalPaginasItems;
        }
        $offsetItems = ($paginaItems - 1) * $itemsPorPagina;
        $orderItemsSql = $hasCreatedAtCol
            ? ' ORDER BY t.created_at DESC, t.id DESC'
            : ' ORDER BY t.id DESC';

        $sqlItemsPage = "SELECT t.* FROM (" . $sqlItemsBase . ") t" .
            $whereEstadoSql .
            $orderItemsSql .
            " LIMIT ? OFFSET ?";
        $stmtItemsPage = $pdo->prepare($sqlItemsPage);
        $bindPos = 1;
        foreach ($params as $param) {
            $stmtItemsPage->bindValue($bindPos++, $param);
        }
        foreach ($estadoParams as $param) {
            $stmtItemsPage->bindValue($bindPos++, $param);
        }
        $stmtItemsPage->bindValue($bindPos++, $itemsPorPagina, \PDO::PARAM_INT);
        $stmtItemsPage->bindValue($bindPos++, $offsetItems, \PDO::PARAM_INT);
        $stmtItemsPage->execute();
        $rowsItems = $stmtItemsPage->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rowsItems as $r) {
            $r['stock_actual'] = round((float)($r['stock_actual'] ?? 0), 2);
            $r['marca'] = $r['marca'] ?? null;
            $r['presentacion'] = $r['presentacion'] ?? null;
            $r['factor_presentacion'] = isset($r['factor_presentacion']) ? (float)$r['factor_presentacion'] : 1.0;
            $items[] = $r;
        }

        $sqlItemsActivos = "SELECT
            i.id,
            i.codigo,
            i.nombre,
            " . ($hasMarcaCol ? "i.marca" : "NULL AS marca") . ",
            " . ($hasPresentacionCol ? "i.presentacion" : "NULL AS presentacion") . ",
            " . ($hasFactorPresentacionCol ? "i.factor_presentacion" : "1 AS factor_presentacion") . ",
            i.unidad_medida,
            i.activo
        FROM inventario_items i
        WHERE i.activo = 1
        ORDER BY i.nombre ASC";
        $stmtItemsActivos = $pdo->prepare($sqlItemsActivos);
        $stmtItemsActivos->execute();
        $itemsActivos = $stmtItemsActivos->fetchAll(\PDO::FETCH_ASSOC);

        $stmtCountVencer = $pdo->query("SELECT COUNT(*)
            FROM inventario_lotes l
            WHERE l.cantidad_actual > 0
              AND l.fecha_vencimiento IS NOT NULL
              AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $totalLotesPorVencer = (int)$stmtCountVencer->fetchColumn();
        $resumen['lotes_por_vencer'] = $totalLotesPorVencer;
        $totalPaginasLotes = max(1, (int)ceil($totalLotesPorVencer / $lotesPorPagina));
        if ($paginaLotes > $totalPaginasLotes) {
            $paginaLotes = $totalPaginasLotes;
        }
        $offsetLotes = ($paginaLotes - 1) * $lotesPorPagina;

        $stmtVencer = $pdo->prepare("SELECT l.id, i.nombre, i.codigo, l.lote_codigo, l.fecha_vencimiento, l.cantidad_actual, i.unidad_medida
            FROM inventario_lotes l
            JOIN inventario_items i ON i.id = l.item_id
            WHERE l.cantidad_actual > 0
              AND l.fecha_vencimiento IS NOT NULL
              AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY l.fecha_vencimiento ASC, i.nombre ASC
            LIMIT :limit OFFSET :offset");
        $stmtVencer->bindValue(':limit', $lotesPorPagina, \PDO::PARAM_INT);
        $stmtVencer->bindValue(':offset', $offsetLotes, \PDO::PARAM_INT);
        $stmtVencer->execute();
        $lotesPorVencer = $stmtVencer->fetchAll(\PDO::FETCH_ASSOC);

        $whereMovimientosInventario = $hasOrigenMovCol
            ? "COALESCE(m.origen, CASE WHEN COALESCE(m.observacion, '') LIKE 'Transferencia interna #% a laboratorio%' THEN 'transferencia_interna' ELSE 'inventario' END) = 'inventario'"
            : "COALESCE(m.observacion, '') NOT LIKE 'Transferencia interna #% a laboratorio%'";

        $stmtCountMov = $pdo->query("SELECT COUNT(*) FROM inventario_movimientos m WHERE " . $whereMovimientosInventario);
        $totalMovimientos = (int)$stmtCountMov->fetchColumn();
        $totalPaginasMovimientos = max(1, (int)ceil($totalMovimientos / $movimientosPorPagina));
        if ($paginaMovimientos > $totalPaginasMovimientos) {
            $paginaMovimientos = $totalPaginasMovimientos;
        }
        $offsetMovimientos = ($paginaMovimientos - 1) * $movimientosPorPagina;

        $stmtMov = $pdo->prepare("SELECT m.id, m.tipo, m.cantidad, m.observacion, m.fecha_hora, i.nombre, i.unidad_medida, l.lote_codigo,
            CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,'')) AS usuario
            FROM inventario_movimientos m
            JOIN inventario_items i ON i.id = m.item_id
            LEFT JOIN inventario_lotes l ON l.id = m.lote_id
            LEFT JOIN usuarios u ON u.id = m.usuario_id
            WHERE " . $whereMovimientosInventario . "
            ORDER BY m.fecha_hora DESC, m.id DESC
            LIMIT :limit OFFSET :offset");
        $stmtMov->bindValue(':limit', $movimientosPorPagina, \PDO::PARAM_INT);
        $stmtMov->bindValue(':offset', $offsetMovimientos, \PDO::PARAM_INT);
        $stmtMov->execute();
        $movimientos = $stmtMov->fetchAll(\PDO::FETCH_ASSOC);
    }
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error cargando inventario: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

function badge_estado_stock(string $estado): string
{
    if ($estado === 'sin_stock') return 'bg-danger';
    if ($estado === 'critico') return 'bg-warning text-dark';
    if ($estado === 'bajo') return 'bg-info text-dark';
    return 'bg-success';
}

function label_estado_stock(string $estado): string
{
    if ($estado === 'sin_stock') return 'Sin stock';
    if ($estado === 'critico') return 'Crítico';
    if ($estado === 'bajo') return 'Bajo';
    return 'Óptimo';
}

function label_tipo_mov(string $tipo): string
{
    $map = [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
        'ajuste_pos' => 'Ajuste (+)',
        'ajuste_neg' => 'Ajuste (-)',
        'merma' => 'Merma',
        'vencido' => 'Vencido',
    ];
    return $map[$tipo] ?? ucfirst($tipo);
}
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Inventario de Reactivos e Insumos</h3>
            <small class="text-muted">Control de stock, lotes, vencimientos y movimientos</small>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php?vista=inventario_interno" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-diagram-3"></i> Inventario Interno
            </a>
            <span class="badge bg-dark d-flex align-items-center">MVP Inventario</span>
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
            Faltan tablas de inventario. Ejecuta <strong>sql/agregar_tablas_inventario.sql</strong> y recarga la página.
        </div>
    <?php else: ?>

        <?php if (!$hasMarcaCol || !$hasPresentacionCol || !$hasFactorPresentacionCol): ?>
            <div class="alert alert-warning">
                Faltan columnas de <strong>marca/presentación/factor</strong> en inventario_items. Vuelve a ejecutar <strong>sql/agregar_tablas_inventario.sql</strong> para habilitarlas.
            </div>
        <?php endif; ?>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-primary"><div class="card-body py-2"><small class="text-muted d-block">Items activos</small><strong><?= (int)$resumen['items_activos'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-warning"><div class="card-body py-2"><small class="text-muted d-block">Stock crítico</small><strong><?= (int)$resumen['stock_critico'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-danger"><div class="card-body py-2"><small class="text-muted d-block">Sin stock</small><strong><?= (int)$resumen['sin_stock'] ?></strong></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-info"><div class="card-body py-2"><small class="text-muted d-block">Por vencer (30 días)</small><strong><?= (int)$resumen['lotes_por_vencer'] ?></strong></div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong><?= $itemEditar ? 'Editar ítem' : 'Nuevo ítem' ?></strong></div>
                    <div class="card-body">
                        <form method="post" action="dashboard.php?action=<?= $itemEditar ? 'inventario_item_actualizar' : 'inventario_item_guardar' ?>" class="row g-2" id="formItemInventario">
                            <?php if ($itemEditar): ?>
                                <input type="hidden" name="id" value="<?= (int)$itemEditar['id'] ?>">
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label class="form-label">Código (opcional)</label>
                                <input type="text" name="codigo" class="form-control" placeholder="Auto si vacío" value="<?= htmlspecialchars((string)($itemEditar['codigo'] ?? '')) ?>" <?= $itemEditar ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="reactivo" <?= (($itemEditar['categoria'] ?? '') === 'reactivo') ? 'selected' : '' ?>>Reactivo</option>
                                    <option value="insumo" <?= (($itemEditar['categoria'] ?? '') === 'insumo') ? 'selected' : '' ?>>Insumo</option>
                                    <option value="material" <?= (($itemEditar['categoria'] ?? '') === 'material') ? 'selected' : '' ?>>Material</option>
                                    <option value="activo_fijo" <?= (($itemEditar['categoria'] ?? '') === 'activo_fijo') ? 'selected' : '' ?>>Activo fijo (bien/equipo)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars((string)($itemEditar['nombre'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marca (opcional)</label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej: BD, Greiner, Thermo" value="<?= htmlspecialchars((string)($itemEditar['marca'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Presentación (opcional)</label>
                                <input type="text" name="presentacion" class="form-control" placeholder="Ej: Caja x100, Frasco 500 ml" value="<?= htmlspecialchars((string)($itemEditar['presentacion'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unidad</label>
                                <input type="text" name="unidad_medida" class="form-control" placeholder="unid, ml, caja" value="<?= htmlspecialchars((string)($itemEditar['unidad_medida'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Factor presentación</label>
                                <input type="number" step="0.0001" min="0.0001" name="factor_presentacion" class="form-control" value="<?= htmlspecialchars((string)($itemEditar['factor_presentacion'] ?? '1')) ?>" required>
                                <small class="text-muted">Ej: caja x25 => factor 25.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Control de stock</label>
                                <?php $controlaStockActual = (int)($itemEditar['controla_stock'] ?? 1); ?>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="controlaStockSwitch" name="controla_stock" value="1" <?= $controlaStockActual === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="controlaStockSwitch">Aplicar alertas por mínimo/crítico</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock mínimo</label>
                                <input type="number" step="0.01" min="0" name="stock_minimo" id="stockMinimoInput" class="form-control" value="<?= htmlspecialchars((string)($itemEditar['stock_minimo'] ?? '0')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock crítico</label>
                                <input type="number" step="0.01" min="0" name="stock_critico" id="stockCriticoInput" class="form-control" value="<?= htmlspecialchars((string)($itemEditar['stock_critico'] ?? '0')) ?>">
                            </div>
                            <?php if ($itemEditar): ?>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select name="activo" class="form-select">
                                    <option value="1" <?= ((int)($itemEditar['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= ((int)($itemEditar['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> <?= $itemEditar ? 'Actualizar ítem' : 'Guardar ítem' ?></button>
                            </div>
                            <?php if ($itemEditar): ?>
                                <div class="col-12 d-grid">
                                    <a href="dashboard.php?vista=inventario" class="btn btn-outline-secondary">Cancelar edición</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light"><strong>Registrar movimiento</strong></div>
                    <div class="card-body">
                        <form method="post" action="dashboard.php?action=inventario_movimiento_guardar" class="row g-2" id="formInventarioMovimiento">
                            <input type="hidden" name="form_token" value="<?= htmlspecialchars($movFormToken) ?>">
                            <div class="col-md-6">
                                <label class="form-label">Ítem</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($itemsActivos as $it): ?>
                                        <option value="<?= (int)$it['id'] ?>" data-factor="<?= htmlspecialchars(number_format((float)($it['factor_presentacion'] ?? 1), 4, '.', '')) ?>"><?= htmlspecialchars($it['codigo'] . ' · ' . $it['nombre'] . (!empty($it['marca']) ? ' · ' . $it['marca'] : '') . (!empty($it['presentacion']) ? ' · ' . $it['presentacion'] : '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="entrada">Entrada</option>
                                    <option value="salida">Salida</option>
                                    <option value="ajuste_pos">Ajuste (+)</option>
                                    <option value="ajuste_neg">Ajuste (-)</option>
                                    <option value="merma">Merma</option>
                                    <option value="vencido">Vencido</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cant. presentación (opcional)</label>
                                <input type="number" step="0.0001" min="0.0001" name="cantidad_presentacion" class="form-control" placeholder="Ej: 1 caja">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lote (opcional)</label>
                                <input type="text" name="lote_codigo" class="form-control" placeholder="Auto en entrada">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vencimiento (opcional)</label>
                                <input type="date" name="fecha_vencimiento" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Observación</label>
                                <input type="text" name="observacion" class="form-control" placeholder="Motivo del movimiento">
                            </div>
                            <div class="col-12">
                                <small class="text-muted" id="inventarioHintConversion">Tip: en entradas puedes usar "Cant. presentación" y el sistema convertirá por factor.</small>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary" id="btnRegistrarMovimiento"><i class="bi bi-arrow-left-right"></i> Registrar movimiento</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><strong>Stock actual</strong></div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-end mb-3" autocomplete="off">
                    <input type="hidden" name="vista" value="inventario">
                    <input type="hidden" name="per_page_user" value="1">
                    <?php if ($itemEditar): ?>
                        <input type="hidden" name="editar_id" value="<?= (int)$itemEditar['id'] ?>">
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($filtroQ) ?>" class="form-control" placeholder="Código o nombre">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="todos" <?= $filtroCategoria === 'todos' ? 'selected' : '' ?>>Todas</option>
                            <option value="reactivo" <?= $filtroCategoria === 'reactivo' ? 'selected' : '' ?>>Reactivo</option>
                            <option value="insumo" <?= $filtroCategoria === 'insumo' ? 'selected' : '' ?>>Insumo</option>
                            <option value="material" <?= $filtroCategoria === 'material' ? 'selected' : '' ?>>Material</option>
                            <option value="activo_fijo" <?= $filtroCategoria === 'activo_fijo' ? 'selected' : '' ?>>Activo fijo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado stock</label>
                        <select name="estado_stock" class="form-select">
                            <option value="todos" <?= $filtroEstado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="sin_stock" <?= $filtroEstado === 'sin_stock' ? 'selected' : '' ?>>Sin stock</option>
                            <option value="critico" <?= $filtroEstado === 'critico' ? 'selected' : '' ?>>Crítico</option>
                            <option value="bajo" <?= $filtroEstado === 'bajo' ? 'selected' : '' ?>>Bajo</option>
                            <option value="ok" <?= $filtroEstado === 'ok' ? 'selected' : '' ?>>Óptimo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Por página</label>
                        <select name="per_page" class="form-select" id="inventarioPerPage" data-current="<?= (int)$itemsPorPagina ?>">
                            <option value="3" <?= $itemsPorPagina === 3 ? 'selected' : '' ?>>3</option>
                            <option value="5" <?= $itemsPorPagina === 5 ? 'selected' : '' ?>>5</option>
                            <option value="10" <?= $itemsPorPagina === 10 ? 'selected' : '' ?>>10</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Presentación</th>
                                <th>Categoría</th>
                                <th>Activo</th>
                                <th>Factor</th>
                                <th>Stock actual</th>
                                <th>Mínimo</th>
                                <th>Crítico</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="12" class="text-center text-muted py-4">Sin items para mostrar.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$it['codigo']) ?></td>
                                        <td><?= htmlspecialchars((string)$it['nombre']) ?></td>
                                        <td><?= htmlspecialchars((string)($it['marca'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($it['presentacion'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$it['categoria']))) ?></td>
                                        <td>
                                            <span class="badge <?= ((int)($it['activo'] ?? 1) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ((int)($it['activo'] ?? 1) === 1) ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td><?= number_format((float)($it['factor_presentacion'] ?? 1), 4) ?></td>
                                        <td><strong><?= number_format((float)$it['stock_actual'], 2) ?> <?= htmlspecialchars((string)$it['unidad_medida']) ?></strong></td>
                                        <td><?= number_format((float)$it['stock_minimo'], 2) ?></td>
                                        <td><?= number_format((float)$it['stock_critico'], 2) ?></td>
                                        <td><span class="badge <?= badge_estado_stock((string)$it['estado_stock']) ?>"><?= htmlspecialchars(label_estado_stock((string)$it['estado_stock'])) ?></span></td>
                                        <td>
                                            <a href="dashboard.php?<?= htmlspecialchars(http_build_query([
                                                'vista' => 'inventario',
                                                'editar_id' => (int)$it['id'],
                                                'q' => $filtroQ,
                                                'categoria' => $filtroCategoria,
                                                'estado_stock' => $filtroEstado,
                                                'page' => $paginaItems,
                                                'per_page' => $itemsPorPagina,
                                                'per_page_user' => 1,
                                                'page_lotes' => $paginaLotes,
                                                'per_page_lotes' => $lotesPorPagina,
                                                'page_mov' => $paginaMovimientos,
                                                'per_page_mov' => $movimientosPorPagina,
                                            ])) ?>" class="btn btn-sm btn-outline-warning" title="Editar ítem">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                    $queryPaginacionBase = [
                        'vista' => 'inventario',
                        'q' => $filtroQ,
                        'categoria' => $filtroCategoria,
                        'estado_stock' => $filtroEstado,
                        'per_page' => $itemsPorPagina,
                        'per_page_user' => 1,
                    ];
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2">
                    <small class="text-muted">
                        Mostrando
                        <strong><?= $totalItemsTabla > 0 ? (($paginaItems - 1) * $itemsPorPagina + 1) : 0 ?></strong>
                        -
                        <strong><?= min($paginaItems * $itemsPorPagina, $totalItemsTabla) ?></strong>
                        de
                        <strong><?= (int)$totalItemsTabla ?></strong>
                    </small>
                    <nav aria-label="Paginación inventario">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $paginaItems <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryPaginacionBase, ['page' => max(1, $paginaItems - 1)]))) ?>">Anterior</a>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Página <?= (int)$paginaItems ?> de <?= (int)$totalPaginasItems ?></span>
                            </li>
                            <li class="page-item <?= $paginaItems >= $totalPaginasItems ? 'disabled' : '' ?>">
                                <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryPaginacionBase, ['page' => min($totalPaginasItems, $paginaItems + 1)]))) ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <strong>Lotes por vencer (30 días)</strong>
                        <form method="get" class="d-flex align-items-center gap-1 w-100 w-md-auto">
                            <input type="hidden" name="vista" value="inventario">
                            <?php if ($itemEditar): ?>
                                <input type="hidden" name="editar_id" value="<?= (int)$itemEditar['id'] ?>">
                            <?php endif; ?>
                            <input type="hidden" name="q" value="<?= htmlspecialchars($filtroQ) ?>">
                            <input type="hidden" name="categoria" value="<?= htmlspecialchars($filtroCategoria) ?>">
                            <input type="hidden" name="estado_stock" value="<?= htmlspecialchars($filtroEstado) ?>">
                            <input type="hidden" name="page" value="<?= (int)$paginaItems ?>">
                            <input type="hidden" name="per_page" value="<?= (int)$itemsPorPagina ?>">
                            <input type="hidden" name="per_page_user" value="1">
                            <input type="hidden" name="page_mov" value="<?= (int)$paginaMovimientos ?>">
                            <input type="hidden" name="per_page_mov" value="<?= (int)$movimientosPorPagina ?>">
                            <input type="hidden" name="page_lotes" value="1">
                            <label class="small text-muted mb-0 me-1">Por página</label>
                            <select id="inventarioPerPageLotes" name="per_page_lotes" class="form-select form-select-sm" style="max-width: 90px;" onchange="this.form.submit()">
                                <option value="3" <?= $lotesPorPagina === 3 ? 'selected' : '' ?>>3</option>
                                <option value="5" <?= $lotesPorPagina === 5 ? 'selected' : '' ?>>5</option>
                                <option value="10" <?= $lotesPorPagina === 10 ? 'selected' : '' ?>>10</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ítem</th>
                                        <th>Lote</th>
                                        <th>Vence</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lotesPorVencer)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin lotes por vencer.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($lotesPorVencer as $l): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$l['nombre']) ?></td>
                                                <td><?= htmlspecialchars((string)$l['lote_codigo']) ?></td>
                                                <td><?= htmlspecialchars((string)$l['fecha_vencimiento']) ?></td>
                                                <td><?= number_format((float)$l['cantidad_actual'], 2) ?> <?= htmlspecialchars((string)$l['unidad_medida']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                            $queryLotesBase = [
                                'vista' => 'inventario',
                                'q' => $filtroQ,
                                'categoria' => $filtroCategoria,
                                'estado_stock' => $filtroEstado,
                                'page' => $paginaItems,
                                'per_page' => $itemsPorPagina,
                                'per_page_user' => 1,
                                'page_mov' => $paginaMovimientos,
                                'per_page_mov' => $movimientosPorPagina,
                                'per_page_lotes' => $lotesPorPagina,
                            ];
                        ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center p-2 border-top gap-2">
                            <small class="text-muted">
                                Mostrando <?= $totalLotesPorVencer > 0 ? (($paginaLotes - 1) * $lotesPorPagina + 1) : 0 ?> - <?= min($paginaLotes * $lotesPorPagina, $totalLotesPorVencer) ?> de <?= (int)$totalLotesPorVencer ?>
                            </small>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $paginaLotes <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryLotesBase, ['page_lotes' => max(1, $paginaLotes - 1)]))) ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?= (int)$paginaLotes ?>/<?= (int)$totalPaginasLotes ?></span></li>
                                <li class="page-item <?= $paginaLotes >= $totalPaginasLotes ? 'disabled' : '' ?>">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryLotesBase, ['page_lotes' => min($totalPaginasLotes, $paginaLotes + 1)]))) ?>">Siguiente</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <strong>Últimos movimientos</strong>
                        <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto justify-content-md-end">
                            <form method="get" class="d-flex align-items-center gap-1">
                                <input type="hidden" name="vista" value="inventario">
                                <?php if ($itemEditar): ?>
                                    <input type="hidden" name="editar_id" value="<?= (int)$itemEditar['id'] ?>">
                                <?php endif; ?>
                                <input type="hidden" name="q" value="<?= htmlspecialchars($filtroQ) ?>">
                                <input type="hidden" name="categoria" value="<?= htmlspecialchars($filtroCategoria) ?>">
                                <input type="hidden" name="estado_stock" value="<?= htmlspecialchars($filtroEstado) ?>">
                                <input type="hidden" name="page" value="<?= (int)$paginaItems ?>">
                                <input type="hidden" name="per_page" value="<?= (int)$itemsPorPagina ?>">
                                <input type="hidden" name="per_page_user" value="1">
                                <input type="hidden" name="page_lotes" value="<?= (int)$paginaLotes ?>">
                                <input type="hidden" name="per_page_lotes" value="<?= (int)$lotesPorPagina ?>">
                                <input type="hidden" name="page_mov" value="1">
                                <label class="small text-muted mb-0 me-1">Por página</label>
                                <select id="inventarioPerPageMov" name="per_page_mov" class="form-select form-select-sm" style="max-width: 90px;" onchange="this.form.submit()">
                                    <option value="3" <?= $movimientosPorPagina === 3 ? 'selected' : '' ?>>3</option>
                                    <option value="5" <?= $movimientosPorPagina === 5 ? 'selected' : '' ?>>5</option>
                                    <option value="10" <?= $movimientosPorPagina === 10 ? 'selected' : '' ?>>10</option>
                                </select>
                            </form>
                            <a href="dashboard.php?action=inventario_export&format=excel" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                            <a href="dashboard.php?action=inventario_export&format=pdf" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Ítem</th>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Lote</th>
                                        <th>Observación</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movimientos)): ?>
                                        <tr><td colspan="7" class="text-center text-muted py-3">Sin movimientos aún.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($movimientos as $m): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$m['fecha_hora']) ?></td>
                                                <td><?= htmlspecialchars((string)$m['nombre']) ?></td>
                                                <td><?= htmlspecialchars(label_tipo_mov((string)$m['tipo'])) ?></td>
                                                <td><?= number_format((float)$m['cantidad'], 2) ?> <?= htmlspecialchars((string)$m['unidad_medida']) ?></td>
                                                <td><?= htmlspecialchars((string)($m['lote_codigo'] ?? '-')) ?></td>
                                                <td><?= htmlspecialchars(trim((string)($m['observacion'] ?? '')) !== '' ? (string)$m['observacion'] : '-') ?></td>
                                                <td><?= htmlspecialchars(trim((string)($m['usuario'] ?? '')) !== '' ? (string)$m['usuario'] : 'Sin dato') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                            $queryMovBase = [
                                'vista' => 'inventario',
                                'q' => $filtroQ,
                                'categoria' => $filtroCategoria,
                                'estado_stock' => $filtroEstado,
                                'page' => $paginaItems,
                                'per_page' => $itemsPorPagina,
                                'per_page_user' => 1,
                                'page_lotes' => $paginaLotes,
                                'per_page_lotes' => $lotesPorPagina,
                                'per_page_mov' => $movimientosPorPagina,
                            ];
                        ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center p-2 border-top gap-2">
                            <small class="text-muted">
                                Mostrando <?= $totalMovimientos > 0 ? (($paginaMovimientos - 1) * $movimientosPorPagina + 1) : 0 ?> - <?= min($paginaMovimientos * $movimientosPorPagina, $totalMovimientos) ?> de <?= (int)$totalMovimientos ?>
                            </small>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $paginaMovimientos <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryMovBase, ['page_mov' => max(1, $paginaMovimientos - 1)]))) ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?= (int)$paginaMovimientos ?>/<?= (int)$totalPaginasMovimientos ?></span></li>
                                <li class="page-item <?= $paginaMovimientos >= $totalPaginasMovimientos ? 'disabled' : '' ?>">
                                    <a class="page-link" href="dashboard.php?<?= htmlspecialchars(http_build_query(array_merge($queryMovBase, ['page_mov' => min($totalPaginasMovimientos, $paginaMovimientos + 1)]))) ?>">Siguiente</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var perPageSelect = document.getElementById('inventarioPerPage');
    var perPageLotes = document.getElementById('inventarioPerPageLotes');
    var perPageMov = document.getElementById('inventarioPerPageMov');
    var isEditMode = false;

    try {
        var params = new URLSearchParams(window.location.search || '');
        isEditMode = params.has('editar_id') && String(params.get('editar_id') || '').trim() !== '';
    } catch (error) {
        isEditMode = false;
    }

    var applySavedValue = function (selectEl, storageKey) {
        if (!selectEl) return;

        var allowed = Array.from(selectEl.options).map(function (opt) {
            return opt.value;
        });

        try {
            var saved = localStorage.getItem(storageKey);
            if (saved && allowed.indexOf(saved) !== -1) {
                var current = selectEl.value;
                selectEl.value = saved;
                if (!isEditMode && saved !== current && selectEl.form) {
                    selectEl.form.submit();
                    return;
                }
            }
        } catch (error) {
        }

        selectEl.addEventListener('change', function () {
            try {
                localStorage.setItem(storageKey, selectEl.value);
            } catch (error) {
            }
        });
    };

    if (perPageSelect && perPageSelect.dataset.current) {
        perPageSelect.value = perPageSelect.dataset.current;
    }

    applySavedValue(perPageSelect, 'inventario_per_page_items');
    applySavedValue(perPageLotes, 'inventario_per_page_lotes');
    applySavedValue(perPageMov, 'inventario_per_page_movimientos');
})();

(function () {
    var form = document.getElementById('formItemInventario');
    if (!form) return;

    var categoriaSelect = form.querySelector('select[name="categoria"]');
    var controlaStockSwitch = form.querySelector('#controlaStockSwitch');
    var stockMinimoInput = form.querySelector('#stockMinimoInput');
    var stockCriticoInput = form.querySelector('#stockCriticoInput');

    var syncControlStockUI = function () {
        if (!controlaStockSwitch || !stockMinimoInput || !stockCriticoInput) return;

        if (categoriaSelect && categoriaSelect.value === 'activo_fijo') {
            controlaStockSwitch.checked = false;
        }

        var controla = controlaStockSwitch.checked;
        stockMinimoInput.disabled = !controla;
        stockCriticoInput.disabled = !controla;

        if (!controla) {
            stockMinimoInput.value = '0';
            stockCriticoInput.value = '0';
        }
    };

    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', syncControlStockUI);
    }
    if (controlaStockSwitch) {
        controlaStockSwitch.addEventListener('change', syncControlStockUI);
    }
    syncControlStockUI();

    form.addEventListener('submit', function (e) {
        var estadoSelect = form.querySelector('select[name="activo"]');
        if (!estadoSelect) return;

        if (estadoSelect.value === '0') {
            var ok = window.confirm('¿Seguro que deseas desactivar este ítem? No se eliminará el historial, pero ya no aparecerá en selección de movimientos.');
            if (!ok) {
                e.preventDefault();
            }
        }
    });
})();

(function () {
    var movForm = document.getElementById('formInventarioMovimiento');
    if (!movForm) return;

    var selectItem = movForm.querySelector('select[name="item_id"]');
    var inputCantidad = movForm.querySelector('input[name="cantidad"]');
    var inputCantPresentacion = movForm.querySelector('input[name="cantidad_presentacion"]');
    var hint = document.getElementById('inventarioHintConversion');

    var renderConversion = function () {
        if (!selectItem || !inputCantidad || !inputCantPresentacion) {
            return;
        }

        var valorPresentacion = parseFloat(inputCantPresentacion.value || '0');
        var opt = selectItem.options[selectItem.selectedIndex];
        var factor = parseFloat((opt && opt.dataset && opt.dataset.factor) ? opt.dataset.factor : '1');
        var factorSeguro = (!isNaN(factor) && factor > 0) ? factor : 1;

        if (!isNaN(valorPresentacion) && valorPresentacion > 0) {
            var cantidadConvertida = valorPresentacion * factorSeguro;
            inputCantidad.value = cantidadConvertida.toFixed(2);
            if (hint) {
                hint.textContent = 'Conversión: ' + valorPresentacion + ' presentación x factor ' + factorSeguro + ' = ' + cantidadConvertida.toFixed(2) + ' unidades operativas.';
            }
        } else if (hint) {
            hint.textContent = 'Tip: en entradas puedes usar "Cant. presentación" y el sistema convertirá por factor.';
        }
    };

    if (selectItem) {
        selectItem.addEventListener('change', renderConversion);
    }
    if (inputCantPresentacion) {
        inputCantPresentacion.addEventListener('input', renderConversion);
    }

    movForm.addEventListener('submit', function () {
        var submitBtn = document.getElementById('btnRegistrarMovimiento');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Registrando...';
        }
    });
})();
</script>
