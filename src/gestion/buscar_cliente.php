<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$examenIdParam = isset($_GET['examen_id']) ? (int)$_GET['examen_id'] : 0;
if ($examenIdParam > 0) {
    $_SESSION['buscar_cliente_examen_id'] = $examenIdParam;
}
$examenIdPrefill = (int)($_SESSION['buscar_cliente_examen_id'] ?? 0);
$queryExamenPrefill = $examenIdPrefill > 0 ? '&examen_id=' . $examenIdPrefill : '';
?>
<div class="container mt-4">
    <h4>Buscar Cliente</h4>
    <form method="POST" action="dashboard.php?action=buscar_cliente_accion">
        <?php if ($examenIdPrefill > 0): ?>
            <input type="hidden" name="examen_id" value="<?= $examenIdPrefill ?>">
        <?php endif; ?>
        <label for="dni">Documento del cliente (DNI/RUC):</label>
        <input type="text" name="dni" id="dni" class="form-control d-inline w-auto" required>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
    <hr>
    <?php
    $rolSesion = strtolower(trim((string)($_SESSION['rol'] ?? '')));
    $contextoRegistro = '';
    if ($rolSesion === 'empresa' && !empty($_SESSION['empresa_id'])) {
        $contextoRegistro = '&id_empresa=' . urlencode((string)$_SESSION['empresa_id']);
    } elseif ($rolSesion === 'convenio' && !empty($_SESSION['convenio_id'])) {
        $contextoRegistro = '&id_convenio=' . urlencode((string)$_SESSION['convenio_id']);
    }
    ?>
    <?php if (isset($_SESSION['cliente_encontrado'])): 
        $cliente = $_SESSION['cliente_encontrado']; ?>
        <h5>Datos del Cliente</h5>
        <ul>
            <li>Nombre: <?= htmlspecialchars($cliente['nombre']) ?></li>
            <li>Apellido: <?= htmlspecialchars($cliente['apellido']) ?></li>
            <li>DNI: <?= htmlspecialchars($cliente['dni']) ?></li>
            <!-- Otros datos si deseas -->
        </ul>
        <a href="dashboard.php?vista=form_cotizacion&id=<?= $cliente['id'] . $queryExamenPrefill ?>" 
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
                <a href="dashboard.php?vista=form_cliente&dni=<?= urlencode($_SESSION['dni_buscado']) . $contextoRegistro . $queryExamenPrefill ?>">Registrar cliente</a>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['cliente_api_sugerido']) && is_array($_SESSION['cliente_api_sugerido'])):
            $sugerido = $_SESSION['cliente_api_sugerido'];
            $doc = (string)($sugerido['documento'] ?? ($_SESSION['dni_buscado'] ?? ''));
            $tipo = (string)($sugerido['tipo_documento'] ?? 'dni');
            $nombre = '';
            $apellido = '';
            $razon = '';
            if ($tipo === 'ruc') {
                $razon = (string)($sugerido['razon_social'] ?? '');
                $nombre = $razon;
                $apellido = '-';
            } else {
                $nombre = (string)($sugerido['nombres'] ?? '');
                $apellido = trim(((string)($sugerido['apellido_paterno'] ?? '')) . ' ' . ((string)($sugerido['apellido_materno'] ?? '')));
            }
            $direccion = (string)($sugerido['direccion'] ?? '');
            $url = 'dashboard.php?vista=form_cliente'
                . '&dni=' . urlencode($doc)
                . '&tipo_documento=' . urlencode($tipo)
                . '&nombre=' . urlencode($nombre)
                . '&apellido=' . urlencode($apellido)
                . '&razon_social=' . urlencode($razon)
                . '&direccion=' . urlencode($direccion)
                . $contextoRegistro
                . $queryExamenPrefill;
        ?>
            <div class="alert alert-info mt-2">
                Se encontró información en APISPERU para el documento consultado.
            </div>
            <a href="<?= $url ?>" class="btn btn-success">Registrar con datos sugeridos</a>
        <?php endif; ?>
        <?php unset($_SESSION['cliente_no_encontrado'], $_SESSION['dni_buscado'], $_SESSION['cliente_api_sugerido']); ?>
    <?php endif; ?>
</div>
