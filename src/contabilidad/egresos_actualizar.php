<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

$id = $_POST['id'] ?? null;
if ($id) {
    $monto = floatval($_POST['monto'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    if ($monto > 0 && $descripcion !== '') {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE egresos SET monto = ?, descripcion = ?, fecha = ? WHERE id = ?");
        $stmt->execute([$monto, $descripcion, $fecha, $id]);

        $stmtMovTbl = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'caja_movimientos'");
        $stmtMovTbl->execute();
        $existsMov = (int)$stmtMovTbl->fetchColumn() > 0;

        if ($existsMov) {
            $stmtMov = $pdo->prepare("UPDATE caja_movimientos SET monto = ?, descripcion = ?, metodo_pago = 'efectivo', afecta_efectivo = 1 WHERE referencia_tipo = 'egreso' AND referencia_id = ? AND origen = 'egreso_manual'");
            $stmtMov->execute([$monto, $descripcion, $id]);
        }

        $pdo->commit();
        header("Location: dashboard.php?vista=egresos&editado=1");
        exit;
    } else {
        header("Location: dashboard.php?vista=egresos_editar&id=$id&msg=error");
        exit;
    }
}
header("Location: dashboard.php?vista=egresos");
exit;
?>
