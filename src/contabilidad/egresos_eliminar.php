<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $pdo->beginTransaction();

    $stmtMovTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
    $stmtMovTbl->execute();
    $existsMov = (int)$stmtMovTbl->fetchColumn() > 0;

    if ($existsMov) {
        $stmtMov = $pdo->prepare("DELETE FROM caja_movimientos WHERE referencia_tipo = 'egreso' AND referencia_id = ? AND origen = 'egreso_manual'");
        $stmtMov->execute([$id]);
    }

    $stmt = $pdo->prepare("DELETE FROM egresos WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
}
header("Location: dashboard.php?vista=egresos&eliminado=1");
exit;
?>
