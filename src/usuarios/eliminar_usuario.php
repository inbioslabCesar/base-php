<?php
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Usuario eliminado exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } catch (PDOException $e) {
        $sqlState = $e->getCode();
        $driverError = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000' && (int)$driverError === 1451) {
            $_SESSION['mensaje'] = "No se puede eliminar este usuario porque tiene registros asociados.";
            $_SESSION['mensaje_tipo'] = "warning";
        } else {
            $_SESSION['mensaje'] = "No se pudo eliminar el usuario en este momento. Intente nuevamente.";
            $_SESSION['mensaje_tipo'] = "error";
        }
    }
} else {
    $_SESSION['mensaje'] = "ID de usuario no válido.";
    $_SESSION['mensaje_tipo'] = "warning";
}

header('Location: dashboard.php?vista=usuarios');
exit;
?>
