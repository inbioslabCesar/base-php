<?php
require_once __DIR__ . '/../conexion/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos de la tabla config_empresa
$stmt = $pdo->query("SELECT * FROM config_empresa LIMIT 1");
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

$logo = !empty($empresa['logo']) ? $empresa['logo'] : 'images/inbioslab-logo.png';
?>

<div class="container mt-4">
    <h4>Configuración de Empresa</h4>
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']) ?></div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>
    <form method="POST" action="config/config_empresa_guardar.php" enctype="multipart/form-data" autocomplete="off">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="ruc" class="form-label">RUC *</label>
                <input type="text" class="form-control" id="ruc" name="ruc"
                    value="<?= htmlspecialchars($empresa['ruc'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="direccion" class="form-label">Dirección *</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" class="form-control" id="celular" name="celular"
                    value="<?= htmlspecialchars($empresa['celular'] ?? '') ?>">
            </div>
            <div class="col-md-12 mb-3 text-center">
                <label class="form-label fw-bold">Logo actual:</label><br>
                <img src="<?= htmlspecialchars($logo) ?>?v=<?= time() ?>" alt="Logo de la empresa" style="max-height: 80px;">
            </div>
            <div class="col-md-12 mb-3">
                <label for="logo" class="form-label">Actualizar logo (PNG):</label>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/png">
            </div>
            <div class="col-md-12 mb-3 text-center">
                <label class="form-label fw-bold">Firma actual:</label><br>
                <img id="firma-actual"
                    src="<?= htmlspecialchars($empresa['firma'] ?? 'images/empresa/firma.png') ?>?v=<?= time() ?>"
                    alt="Firma de la empresa"
                    style="max-height: 80px;">
            </div>
            <div class="col-md-12 mb-3">
                <label for="firma" class="form-label">Actualizar firma (PNG):</label>
                <input type="file" class="form-control" id="firma" name="firma" accept="image/png">
            </div>

        </div>
        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>