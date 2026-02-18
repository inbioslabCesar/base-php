<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

$idExamen = (int)($_POST['id_examen'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);
$cantidadPorPrueba = (float)($_POST['cantidad_por_prueba'] ?? 0);
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
$observacion = trim((string)($_POST['observacion'] ?? ''));

if ($idExamen <= 0 || $itemId <= 0 || $cantidadPorPrueba <= 0) {
    $_SESSION['mensaje'] = 'Datos inválidos para la receta.';
    header('Location: dashboard.php?vista=inventario_interno');
    exit;
}

try {
    $stmtEx = $pdo->prepare("SELECT id FROM examenes WHERE id = ? LIMIT 1");
    $stmtEx->execute([$idExamen]);
    if (!$stmtEx->fetch(\PDO::FETCH_ASSOC)) {
        $_SESSION['mensaje'] = 'Examen no válido.';
        header('Location: dashboard.php?vista=inventario_interno');
        exit;
    }

    $stmtItem = $pdo->prepare("SELECT id FROM inventario_items WHERE id = ? LIMIT 1");
    $stmtItem->execute([$itemId]);
    if (!$stmtItem->fetch(\PDO::FETCH_ASSOC)) {
        $_SESSION['mensaje'] = 'Ítem de inventario no válido.';
        header('Location: dashboard.php?vista=inventario_interno');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO inventario_examen_recetas (id_examen, item_id, cantidad_por_prueba, activo, observacion, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            cantidad_por_prueba = VALUES(cantidad_por_prueba),
            activo = VALUES(activo),
            observacion = VALUES(observacion),
            updated_at = NOW()");
    $stmt->execute([
        $idExamen,
        $itemId,
        $cantidadPorPrueba,
        $activo === 1 ? 1 : 0,
        $observacion !== '' ? $observacion : null,
    ]);

    $_SESSION['mensaje'] = 'Receta guardada correctamente.';
} catch (\Throwable $e) {
    $_SESSION['mensaje'] = 'No se pudo guardar la receta: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=inventario_interno');
exit;
