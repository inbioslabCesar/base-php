<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Empresa eliminada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } catch (PDOException $e) {
        $sqlState = $e->getCode();
        $driverError = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000' && (int)$driverError === 1451) {
            $_SESSION['mensaje'] = "No se puede eliminar esta empresa porque tiene registros asociados.";
            $_SESSION['mensaje_tipo'] = "warning";
        } else {
            $_SESSION['mensaje'] = "No se pudo eliminar la empresa en este momento. Intente nuevamente.";
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
} else {
    $_SESSION['mensaje'] = "ID de empresa no válido.";
    $_SESSION['mensaje_tipo'] = "warning";
}

header('Location: dashboard.php?vista=empresas');
exit;
?>
