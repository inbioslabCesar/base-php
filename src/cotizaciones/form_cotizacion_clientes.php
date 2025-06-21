<?php
require_once __DIR__ . '/../config/conexion.php';
session_start();

$id_cliente = $_SESSION['id'] ?? null;
if (!$id_cliente) {
    $_SESSION['msg'] = 'No tienes permisos para acceder a esta sección.';
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = date('Y-m-d H:i:s');
    $total = floatval($_POST['total'] ?? 0);
    $estado_pago = 'pendiente';
    $rol_creador = 'cliente';

    // Aquí deberías validar y procesar los exámenes seleccionados
    // Por simplicidad, solo se guarda la cotización base
    $stmt = $pdo->prepare("INSERT INTO cotizaciones (id_cliente, fecha, total, estado_pago, rol_creador) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id_cliente, $fecha, $total, $estado_pago, $rol_creador]);

    $_SESSION['msg'] = '¡Cotización registrada correctamente!';
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}
?>

<div class="container mt-4">
    <h4>Nueva Cotización</h4>
    <form method="POST">
        <div class="mb-3">
            <label for="total" class="form-label">Total (S/)</label>
            <input type="number" step="0.01" min="0" name="total" id="total" class="form-control" required>
        </div>
        <!-- Aquí puedes agregar campos para exámenes, observaciones, etc. -->
        <button type="submit" class="btn btn-success">Registrar Cotización</button>
        <a href="dashboard.php?vista=cotizaciones" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
