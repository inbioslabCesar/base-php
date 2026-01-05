<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);
$empresa = [
    'ruc' => '',
    'razon_social' => '',
    'nombre_comercial' => '',
    'direccion' => '',
    'telefono' => '',
    'email' => '',
    'representante' => '',
    'password' => '',
    'convenio' => '',
    'estado' => 'activo',
    'descuento' => ''
];

if ($esEdicion) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ?");
    $stmt->execute([$id]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        $_SESSION['mensaje'] = "Empresa no encontrada.";
        header('Location: dashboard.php?vista=empresas');
        exit;
    }
}

function capitalizar($texto) {
    return $texto ? mb_convert_case($texto, MB_CASE_TITLE, "UTF-8") : '';
}

// Dominio para generar emails (config_empresa.dominio o HTTP_HOST)
function normalizarDominioEmpresa(string $dominio): string {
    $dominio = strtolower(trim($dominio));
    $dominio = preg_replace('#^https?://#', '', $dominio) ?? $dominio;
    $dominio = preg_replace('#/.*$#', '', $dominio) ?? $dominio;
    $dominio = preg_replace('#:\\d+$#', '', $dominio) ?? $dominio;
    $dominio = trim($dominio);
    $dominio = ltrim($dominio, '@');
    if (str_starts_with($dominio, 'www.')) {
        $dominio = substr($dominio, 4);
    }
    return $dominio;
}

$stmtDom = $pdo->query("SELECT dominio FROM config_empresa LIMIT 1");
$dominioEmpresa = (string)($stmtDom->fetchColumn() ?: '');
$dominioEmpresa = normalizarDominioEmpresa($dominioEmpresa !== '' ? $dominioEmpresa : (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($dominioEmpresa === '') {
    $dominioEmpresa = 'ejemplo.com';
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Empresa' : 'Agregar Empresa' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_empresa&id=' . $_GET['id'] : 'crear_empresa' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="ruc" class="form-label">RUC *</label>
                <input type="text" class="form-control" id="ruc" name="ruc" required
                    value="<?= htmlspecialchars($empresa['ruc'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="sin_ruc" name="sin_ruc">
                    <label class="form-check-label" for="sin_ruc">
                        Sin RUC (generar uno provisional)
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    El email y la contraseña se generan automáticamente según el RUC.
                </small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="razon_social" class="form-label">Razón Social *</label>
                <input type="text" class="form-control" id="razon_social" name="razon_social" required
                    value="<?= htmlspecialchars(capitalizar($empresa['razon_social'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="nombre_comercial" class="form-label">Nombre Comercial</label>
                <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial"
                    value="<?= htmlspecialchars(capitalizar($empresa['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($empresa['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($empresa['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required
                    value="<?= htmlspecialchars($empresa['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                <small class="text-muted d-block mt-1">
                    Ejemplo: 20393997258@<?= htmlspecialchars($dominioEmpresa) ?>
                </small>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="representante" class="form-label">Representante</label>
                <input type="text" class="form-control" id="representante" name="representante"
                    value="<?= htmlspecialchars(capitalizar($empresa['representante'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="convenio" class="form-label">Convenio</label>
                <input type="text" class="form-control" id="convenio" name="convenio"
                    value="<?= htmlspecialchars($empresa['convenio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activo" <?= (isset($empresa['estado']) && $empresa['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= (isset($empresa['estado']) && $empresa['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" id="descuento" name="descuento"
                    value="<?= htmlspecialchars($empresa['descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="password" class="form-label"><?= $esEdicion ? 'Nueva Contraseña' : 'Contraseña *' ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
                    <button type="button" class="btn btn-outline-secondary" onclick="generarPassword()">Generar Password</button>
                </div>
                <?php if ($esEdicion): ?>
                    <small class="text-muted">Deja en blanco si no deseas cambiar la contraseña.</small>
                <?php else: ?>
                    <small class="text-muted">Por defecto, la contraseña es el mismo RUC.</small>
                <?php endif; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Crear' ?></button>
        <a href="dashboard.php?vista=empresas" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
(function () {
    const esEdicion = <?= $esEdicion ? 'true' : 'false' ?>;
    const dominioEmpresa = <?= json_encode($dominioEmpresa) ?>;
    const $ruc = document.getElementById('ruc');
    const $sinRuc = document.getElementById('sin_ruc');
    const $email = document.getElementById('email');
    const $password = document.getElementById('password');

    if (!$ruc || !$sinRuc || !$email || !$password) return;

    function cleanDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function generarRucProvisional() {
        // 11 dígitos, iniciando con 9 (heurística para diferenciarlo)
        const n = Math.floor(Math.random() * 10000000000);
        return '9' + String(n).padStart(10, '0');
    }

    function syncCredencialesFromRuc() {
        const rucDigits = cleanDigits($ruc.value);
        if (rucDigits.length === 0) return;

        $ruc.value = rucDigits;
        $email.value = `${rucDigits}@${dominioEmpresa || 'ejemplo.com'}`;

        // En edición, NO autocompletar password para evitar cambios accidentales.
        if (!esEdicion) {
            $password.value = rucDigits;
        }
    }

    function onToggleSinRuc() {
        const enabled = $sinRuc.checked;
        if (enabled) {
            const provisional = generarRucProvisional();
            $ruc.value = provisional;
            $ruc.readOnly = true;
            syncCredencialesFromRuc();
        } else {
            $ruc.readOnly = false;
        }
    }

    $ruc.addEventListener('input', function () {
        if ($ruc.readOnly) return;
        syncCredencialesFromRuc();
    });

    $sinRuc.addEventListener('change', onToggleSinRuc);

    if ($ruc.value) {
        syncCredencialesFromRuc();
    }
})();

function generarPassword() {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let pass = "";
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('password').value = pass;
}
</script>
