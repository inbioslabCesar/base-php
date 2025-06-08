<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones/usuarios_crud.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location:" . BASE_URL . "dashboard.php?vista=usuarios");
    exit;
}

$usuario = obtenerUsuarioPorId($id);
if (!$usuario) {
    echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
    exit;
}

// Procesar confirmación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../conexion/conexion.php';
    $sql = "DELETE FROM usuarios WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "dashboard.php?vista=usuarios&success=3");

    exit;
}
?>
<div class="container mt-4">
    <div class="alert alert-warning">
        <h4>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></strong>?</h4>
        <form method="POST">
            <a href="<?= BASE_URL ?>dashboard.php?vista=usuarios" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Eliminar Usuario
            </button>
        </form>
    </div>
</div>
