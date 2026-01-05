<?php
require_once __DIR__ . '/../conexion/conexion.php';

$esEdicion = isset($_GET['id']);
$convenio = [
    'nombre' => '',
    'dni' => '',
    'especialidad' => '',
    'descuento' => '',
    'descripcion' => '',
    'email' => '',
    'password' => ''
];

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM convenios WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $convenio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$convenio) {
        $_SESSION['mensaje'] = "Convenio no encontrado";
        header('Location: dashboard.php?vista=convenios');
        exit;
    }
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

// Función para capitalizar
function capitalizar($texto) {
    return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
}
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Convenio' : 'Registrar Convenio' ?></h2>
    <form method="post" action="dashboard.php?action=<?= $esEdicion ? 'editar_convenio&id=' . htmlspecialchars($_GET['id']) : 'crear_convenio' ?>">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required
                value="<?= htmlspecialchars(capitalizar($convenio['nombre'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="dni" class="form-label">DNI *</label>
            <input type="text" class="form-control" id="dni" name="dni" required
                value="<?= htmlspecialchars($convenio['dni'] ?? '') ?>">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="sin_dni" name="sin_dni">
                <label class="form-check-label" for="sin_dni">
                    Sin DNI (generar uno provisional)
                </label>
            </div>
            <small class="text-muted d-block mt-1">
                El email y la contraseña se generan automáticamente según el DNI.
            </small>
        </div>
        <div class="mb-3">
            <label for="especialidad" class="form-label">Especialidad</label>
            <input type="text" class="form-control" id="especialidad" name="especialidad"
                value="<?= htmlspecialchars(capitalizar($convenio['especialidad'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label for="descuento" class="form-label">Descuento (%)</label>
            <input type="number" class="form-control" id="descuento" name="descuento" min="0" max="100" step="0.01"
                value="<?= htmlspecialchars($convenio['descuento'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion"><?= htmlspecialchars(capitalizar($convenio['descripcion'] ?? '')) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required
                value="<?= htmlspecialchars($convenio['email'] ?? '') ?>" readonly>
            <small class="text-muted d-block mt-1">
                Ejemplo: 12345678@<?= htmlspecialchars($dominioEmpresa) ?>
            </small>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña <?= $esEdicion ? '(dejar vacío para no cambiar)' : '*' ?></label>
            <input type="text" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
            <small class="text-muted d-block mt-1">
                Por defecto, la contraseña es el mismo DNI.
            </small>
        </div>
        <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
        <a href="dashboard.php?vista=convenios" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
(function () {
    const esEdicion = <?= $esEdicion ? 'true' : 'false' ?>;
    const dominioEmpresa = <?= json_encode($dominioEmpresa) ?>;
    const $dni = document.getElementById('dni');
    const $sinDni = document.getElementById('sin_dni');
    const $email = document.getElementById('email');
    const $password = document.getElementById('password');

    if (!$dni || !$sinDni || !$email || !$password) return;

    function cleanDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function generarDniProvisional() {
        // 8 dígitos, iniciando con 9 para diferenciarlo de un DNI real (heurística)
        const n = Math.floor(Math.random() * 10000000);
        return '9' + String(n).padStart(7, '0');
    }

    function syncCredencialesFromDni() {
        const dniDigits = cleanDigits($dni.value);
        if (dniDigits.length === 0) return;

        $dni.value = dniDigits;

        // Email y password basados en DNI
        $email.value = `${dniDigits}@${dominioEmpresa || 'ejemplo.com'}`;

        // En edición, NO autocompletar password para evitar cambios accidentales.
        if (!esEdicion) {
            $password.value = dniDigits;
        }
    }

    function onToggleSinDni() {
        const enabled = $sinDni.checked;
        if (enabled) {
            const provisional = generarDniProvisional();
            $dni.value = provisional;
            $dni.readOnly = true;
            syncCredencialesFromDni();
        } else {
            $dni.readOnly = false;
            // No borrar automáticamente para no perder datos
        }
    }

    $dni.addEventListener('input', function () {
        if ($dni.readOnly) return;
        syncCredencialesFromDni();
    });

    $sinDni.addEventListener('change', onToggleSinDni);

    // Inicial: si ya hay DNI precargado (edición), sincronizar si faltan credenciales
    if ($dni.value) {
        syncCredencialesFromDni();
    }
})();
</script>
