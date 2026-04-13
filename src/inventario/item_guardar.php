<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario');
    exit;
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$categoria = trim((string)($_POST['categoria'] ?? ''));
$marca = trim((string)($_POST['marca'] ?? ''));
$presentacion = trim((string)($_POST['presentacion'] ?? ''));
$factorPresentacion = round((float)($_POST['factor_presentacion'] ?? 1), 4);
$unidad = trim((string)($_POST['unidad_medida'] ?? ''));
$controlaStock = isset($_POST['controla_stock']) ? 1 : 0;
$stockMinimo = round((float)($_POST['stock_minimo'] ?? 0), 2);
$stockCritico = round((float)($_POST['stock_critico'] ?? 0), 2);
$codigo = trim((string)($_POST['codigo'] ?? ''));

$categoriasValidas = ['reactivo', 'insumo', 'material', 'activo_fijo'];
if ($nombre === '' || !in_array($categoria, $categoriasValidas, true) || $unidad === '') {
    $_SESSION['mensaje'] = 'Completa los campos obligatorios del ítem.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

if ($stockMinimo < 0 || $stockCritico < 0) {
    $_SESSION['mensaje'] = 'Los stocks de referencia no pueden ser negativos.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

if ($factorPresentacion <= 0) {
    $_SESSION['mensaje'] = 'El factor de presentación debe ser mayor a cero.';
    header('Location: dashboard.php?vista=inventario');
    exit;
}

if ($categoria === 'activo_fijo') {
    $controlaStock = 0;
}
if ($controlaStock === 0) {
    $stockMinimo = 0;
    $stockCritico = 0;
}

try {
    $stmtTbl = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmtTbl->execute(['inventario_items']);
    if (!$stmtTbl->fetchColumn()) {
        $_SESSION['mensaje'] = 'Faltan tablas de inventario. Ejecuta sql/agregar_tablas_inventario.sql.';
        header('Location: dashboard.php?vista=inventario');
        exit;
    }

    if ($codigo === '') {
        $stmtLast = $pdo->query("SELECT IFNULL(MAX(id),0) + 1 FROM inventario_items");
        $nextId = (int)$stmtLast->fetchColumn();
        $codigo = 'INV-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }

    $stmtCols = $pdo->query("SHOW COLUMNS FROM inventario_items");
    $defs = $stmtCols ? $stmtCols->fetchAll(\PDO::FETCH_ASSOC) : [];
    $cols = [];
    foreach ($defs as $def) {
        if (!empty($def['Field'])) {
            $cols[] = (string)$def['Field'];
        }
    }
    $hasMarca = in_array('marca', $cols, true);
    $hasPresentacion = in_array('presentacion', $cols, true);
    $hasFactorPresentacion = in_array('factor_presentacion', $cols, true);
    $hasControlaStock = in_array('controla_stock', $cols, true);

    if ($hasMarca && $hasPresentacion && $hasFactorPresentacion && $hasControlaStock) {
        $stmt = $pdo->prepare("INSERT INTO inventario_items (codigo, nombre, categoria, marca, presentacion, factor_presentacion, unidad_medida, controla_stock, stock_minimo, stock_critico, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $codigo,
            $nombre,
            $categoria,
            $marca !== '' ? $marca : null,
            $presentacion !== '' ? $presentacion : null,
            $factorPresentacion,
            $unidad,
            $controlaStock,
            $stockMinimo,
            $stockCritico,
        ]);
    } elseif ($hasMarca && $hasPresentacion && $hasFactorPresentacion) {
        $stmt = $pdo->prepare("INSERT INTO inventario_items (codigo, nombre, categoria, marca, presentacion, factor_presentacion, unidad_medida, stock_minimo, stock_critico, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $codigo,
            $nombre,
            $categoria,
            $marca !== '' ? $marca : null,
            $presentacion !== '' ? $presentacion : null,
            $factorPresentacion,
            $unidad,
            $stockMinimo,
            $stockCritico,
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventario_items (codigo, nombre, categoria, unidad_medida, stock_minimo, stock_critico, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$codigo, $nombre, $categoria, $unidad, $stockMinimo, $stockCritico]);
    }

    $_SESSION['mensaje'] = 'Ítem de inventario registrado correctamente.';
} catch (\Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo guardar el ítem: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario');
exit;
