<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    try {
        // Intento de eliminación física primero
        $stmt = $pdo->prepare("DELETE FROM examenes WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = "Examen eliminado exitosamente.";
    } catch (PDOException $e) {
        // Si hay restricción de integridad (23000), hacer baja lógica
        if ($e->getCode() === '23000') {
            try {
                $upd = $pdo->prepare("UPDATE examenes SET vigente = 0, estado = 'inactivo' WHERE id = ?");
                $upd->execute([$id]);
                $_SESSION['mensaje'] = "El examen tiene movimientos relacionados. Se marcó como INACTIVO.";
            } catch (PDOException $e2) {
                $_SESSION['mensaje'] = "No se pudo desactivar el examen: " . $e2->getMessage();
            }
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el examen: " . $e->getMessage();
        }
    }
} else {
    $_SESSION['mensaje'] = "ID de examen no válido.";
}

header('Location: dashboard.php?vista=examenes');
exit;
?>
