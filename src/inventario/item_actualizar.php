<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario');
    exit;
}

$itemId = (int)($_POST['id'] ?? 0);
$codigo = trim((string)($_POST['codigo'] ?? ''));
$nombre = trim((string)($_POST['nombre'] ?? ''));
$categoria = trim((string)($_POST['categoria'] ?? ''));
$marca = trim((string)($_POST['marca'] ?? ''));
$presentacion = trim((string)($_POST['presentacion'] ?? ''));
$factorPresentacion = round((float)($_POST['factor_presentacion'] ?? 1), 4);
$unidad = trim((string)($_POST['unidad_medida'] ?? ''));
$controlaStock = isset($_POST['controla_stock']) ? 1 : 0;
$stockMinimo = round((float)($_POST['stock_minimo'] ?? 0), 2);
$stockCritico = round((float)($_POST['stock_critico'] ?? 0), 2);
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

$categoriasValidas = ['reactivo', 'insumo', 'material', 'activo_fijo'];
if ($itemId <= 0 || $codigo === '' || $nombre === '' || !in_array($categoria, $categoriasValidas, true) || $unidad === '') {
    $_SESSION['mensaje'] = 'Datos incompletos para actualizar ítem.';
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
    header('Location: dashboard.php?vista=inventario&editar_id=' . $itemId);
    exit;
}

if ($categoria === 'activo_fijo') {
    $controlaStock = 0;
}
if ($controlaStock === 0) {
    $stockMinimo = 0;
    $stockCritico = 0;
}

$activo = $activo === 0 ? 0 : 1;

try {
    $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM inventario_items WHERE codigo = ? AND id <> ?");
    $stmtDup->execute([$codigo, $itemId]);
    if ((int)$stmtDup->fetchColumn() > 0) {
        $_SESSION['mensaje'] = 'Ya existe otro ítem con el mismo código.';
        header('Location: dashboard.php?vista=inventario&editar_id=' . $itemId);
        exit;
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
        $stmt = $pdo->prepare("UPDATE inventario_items SET codigo = ?, nombre = ?, categoria = ?, marca = ?, presentacion = ?, factor_presentacion = ?, unidad_medida = ?, controla_stock = ?, stock_minimo = ?, stock_critico = ?, activo = ?, updated_at = NOW() WHERE id = ?");
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
            $activo,
            $itemId,
        ]);
    } elseif ($hasMarca && $hasPresentacion && $hasFactorPresentacion) {
        $stmt = $pdo->prepare("UPDATE inventario_items SET codigo = ?, nombre = ?, categoria = ?, marca = ?, presentacion = ?, factor_presentacion = ?, unidad_medida = ?, stock_minimo = ?, stock_critico = ?, activo = ?, updated_at = NOW() WHERE id = ?");
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
            $activo,
            $itemId,
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE inventario_items SET codigo = ?, nombre = ?, categoria = ?, unidad_medida = ?, stock_minimo = ?, stock_critico = ?, activo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            $codigo,
            $nombre,
            $categoria,
            $unidad,
            $stockMinimo,
            $stockCritico,
            $activo,
            $itemId,
        ]);
    }

    $_SESSION['mensaje'] = 'Ítem actualizado correctamente.';
} catch (\Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo actualizar el ítem: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario');
exit;
