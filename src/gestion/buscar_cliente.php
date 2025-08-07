<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<div class="container mt-4">
    <h4>Buscar Cliente</h4>
    <form method="POST" action="dashboard.php?action=buscar_cliente_accion">
        <label for="dni">DNI del cliente:</label>
        <input type="text" name="dni" id="dni" class="form-control d-inline w-auto" required>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
    <hr>
    <?php if (isset($_SESSION['cliente_encontrado'])): 
        $cliente = $_SESSION['cliente_encontrado']; ?>
        <h5>Datos del Cliente</h5>
        <ul>
            <li>Nombre: <?= htmlspecialchars($cliente['nombre']) ?></li>
            <li>Apellido: <?= htmlspecialchars($cliente['apellido']) ?></li>
            <li>DNI: <?= htmlspecialchars($cliente['dni']) ?></li>
            <!-- Otros datos si deseas -->
        </ul>
        <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] ?>" 
           class="btn btn-primary btn-sm" 
           title="Cotizar">
            <i class="bi bi-file-earmark-plus"></i> Cotizar
        </a>
        <?php unset($_SESSION['cliente_encontrado']); ?>

    <?php elseif (isset($_SESSION['cliente_para_asociar'])): 
        $cliente = $_SESSION['cliente_para_asociar']; ?>
        <div class="alert alert-warning">
            El cliente existe en el sistema pero no está asociado a tu empresa/convenio.
        </div>
        <ul>
            <li>Nombre: <?= htmlspecialchars($cliente['nombre']) ?></li>
            <li>Apellido: <?= htmlspecialchars($cliente['apellido']) ?></li>
            <li>DNI: <?= htmlspecialchars($cliente['dni']) ?></li>
        </ul>
        <form method="POST" action="dashboard.php?action=asociar_cliente_existente">
            <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
            <button type="submit" class="btn btn-warning">Asociar este cliente</button>
        </form>
        <?php unset($_SESSION['cliente_para_asociar']); ?>

    <?php elseif (isset($_SESSION['cliente_no_encontrado'])): ?>
        <div class="alert alert-danger">
            Cliente no encontrado. 
            <?php if (isset($_SESSION['dni_buscado'])): ?>
                <a href="dashboard.php?vista=form_cliente&dni=<?= urlencode($_SESSION['dni_buscado']) ?>">Registrar cliente</a>
            <?php endif; ?>
        </div>
        <?php unset($_SESSION['cliente_no_encontrado'], $_SESSION['dni_buscado']); ?>
    <?php endif; ?>
</div>
