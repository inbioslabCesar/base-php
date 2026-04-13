<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['mensaje'] = "ID de convenio no válido.";
    $_SESSION['mensaje_tipo'] = "warning";
    header('Location: dashboard.php?vista=convenios');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM convenios WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['mensaje'] = "Convenio eliminado exitosamente.";
    $_SESSION['mensaje_tipo'] = "success";
} catch (PDOException $e) {
    $sqlState = $e->getCode();
    $driverError = $e->errorInfo[1] ?? null;

    if ($sqlState === '23000' && (int)$driverError === 1451) {
        $_SESSION['mensaje'] = "No se puede eliminar este convenio porque tiene clientes asociados. Desvincule o elimine esos registros primero.";
        $_SESSION['mensaje_tipo'] = "warning";
    } else {
        $_SESSION['mensaje'] = "No se pudo eliminar el convenio en este momento. Intente nuevamente.";
        $_SESSION['mensaje_tipo'] = "error";
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = "No se pudo eliminar el convenio en este momento. Intente nuevamente.";
    $_SESSION['mensaje_tipo'] = "error";
}
header('Location: dashboard.php?vista=convenios');
exit;
