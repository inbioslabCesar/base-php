<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Validar rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    echo "<div class='alert alert-danger'>Acceso no permitido.</div>";
    exit;
}

$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

// Consulta los exámenes vigentes
$stmt = $pdo->query("SELECT id, nombre, precio_publico FROM examenes WHERE vigente = 1");
$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h4>Crear Cotización para Cliente #<?php echo $cliente_id; ?></h4>

<form method="POST" action="dashboard.php?action=crear_cotizacion_recepcionista">
    <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($cliente_id); ?>">

    <h5>Selecciona los exámenes:</h5>

    <?php foreach ($examenes as $examen): ?>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="examenes[]" value="<?php echo $examen['id']; ?>" id="examen<?php echo $examen['id']; ?>">
            <label class="form-check-label" for="examen<?php echo $examen['id']; ?>">
                <?php echo htmlspecialchars($examen['nombre']); ?> (S/. <?php echo number_format($examen['precio_publico'], 2); ?>)
            </label>
            <input type="number" name="cantidades[<?php echo $examen['id']; ?>]" min="1" value="1" class="form-control d-inline-block" style="width:80px; margin-left:10px;" placeholder="Cantidad">
        </div>
    <?php endforeach; ?>

    <button type="submit" name="guardar_cotizacion" class="btn btn-primary mt-3">Guardar Cotización</button>
</form>
