<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
require_once __DIR__ . '/../config/operacion_context.php';
require_once __DIR__ . '/funciones/usuarios_schema.php';
require_once __DIR__ . '/funciones/usuarios_privilegios.php';

usuarios_asegurar_columnas_profesional($pdo);
$operacionForm = function_exists('app_operacion_context') ? app_operacion_context($pdo) : ['es_sis' => false];
$esModoSisForm = !empty($operacionForm['es_sis']);

$esEdicion = isset($_GET['id']);
$usuario = [
    'nombre' => '',
    'apellido' => '',
    'dni' => '',
    'sexo' => '',
    'fecha_nacimiento' => '',
    'email' => '',
    'telefono' => '',
    'direccion' => '',
    'cargo' => '',
    'profesion' => '',
    'colegiatura_numero' => '',
    'ctmp_numero' => '',
    'privilegios_json' => '',
    'firma' => '',
    'rol' => '',
    'estado' => 'activo',
    'password' => ''
];

if ($esEdicion) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['mensaje'] = "Usuario no encontrado.";
        header('Location: dashboard.php?vista=usuarios');
        exit;
    }
}

$privilegiosFormulario = usuarios_privilegios_desde_json((string)($usuario['privilegios_json'] ?? ''), (string)($usuario['rol'] ?? ''), $esModoSisForm);
$catalogoPrivilegios = usuarios_privilegios_catalogo($esModoSisForm);

function usuarioCheckboxChecked(array $privilegios, string $clave): string {
    return !empty($privilegios[$clave]) ? 'checked' : '';
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

$empresaCfg = ui_theme_fetch_company_config($pdo);
$dominioEmpresa = is_array($empresaCfg) ? (string)($empresaCfg['dominio'] ?? '') : '';
$dominioEmpresa = normalizarDominioEmpresa($dominioEmpresa !== '' ? $dominioEmpresa : (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($dominioEmpresa === '') {
    $dominioEmpresa = 'ejemplo.com';
}

$resolverPreviewFirma = static function (string $storedPath): string {
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return '';
    }
    $normalized = str_replace('\\', '/', ltrim($storedPath, '/'));

    // Las vistas viven bajo /src/, pero los assets se guardan en /uploads (raiz del proyecto).
    if (strpos($normalized, '../uploads/') === 0) {
        return BASE_URL . '../' . substr($normalized, 3);
    }
    if (strpos($normalized, 'uploads/') === 0) {
        return BASE_URL . '../' . $normalized;
    }

    return BASE_URL . $normalized;
};

$firmaPreviewUrl = $resolverPreviewFirma((string)($usuario['firma'] ?? ''));
?>

<div class="container mt-4">
    <h2><?= $esEdicion ? 'Editar Usuario' : 'Agregar Usuario' ?></h2>
    <form method="post" enctype="multipart/form-data" action="dashboard.php?action=<?= $esEdicion ? 'editar_usuario&id=' . $_GET['id'] : 'crear_usuario' ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required
                    value="<?= htmlspecialchars(capitalizar($usuario['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" id="apellido" name="apellido" required
                    value="<?= htmlspecialchars(capitalizar($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="dni" class="form-label">DNI *</label>
                <input type="text" class="form-control" id="dni" name="dni" required
                    value="<?= htmlspecialchars($usuario['dni'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="sexo" class="form-label">Sexo</label>
                <select class="form-select" id="sexo" name="sexo" >
                    <option value="">Seleccionar</option>
                    <option value="masculino" <?= (isset($usuario['sexo']) && $usuario['sexo'] == 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= (isset($usuario['sexo']) && $usuario['sexo'] == 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    <option value="otro" <?= (isset($usuario['sexo']) && $usuario['sexo'] == 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                    value="<?= htmlspecialchars($usuario['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required
                    value="<?= htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                <small class="text-muted d-block mt-1">
                    Ejemplo: 12345678@<?= htmlspecialchars($dominioEmpresa) ?>
                </small>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                    value="<?= htmlspecialchars($usuario['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="cargo" class="form-label">Cargo</label>
                <input type="text" class="form-control" id="cargo" name="cargo"
                    value="<?= htmlspecialchars($usuario['cargo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="profesion" class="form-label">Profesión</label>
                <input type="text" class="form-control" id="profesion" name="profesion"
                    value="<?= htmlspecialchars($usuario['profesion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="rol" class="form-label">Rol *</label>
                <select class="form-select" id="rol" name="rol" required>
                    <option value="">Seleccionar</option>
                    <option value="admin" <?= (isset($usuario['rol']) && $usuario['rol'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="recepcionista" <?= (isset($usuario['rol']) && $usuario['rol'] == 'recepcionista') ? 'selected' : '' ?>>Recepcionista</option>
                    <option value="laboratorista" <?= (isset($usuario['rol']) && $usuario['rol'] == 'laboratorista') ? 'selected' : '' ?>>Laboratorista</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activo" <?= (isset($usuario['estado']) && $usuario['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= (isset($usuario['estado']) && $usuario['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="colegiatura_numero" class="form-label">Numero de colegiatura</label>
                <input type="text" class="form-control" id="colegiatura_numero" name="colegiatura_numero"
                    value="<?= htmlspecialchars($usuario['colegiatura_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ejemplo: CMP 12345">
            </div>
            <div class="col-md-4 mb-3">
                <label for="ctmp_numero" class="form-label">C.T.M.P. (Tecnologo Medico)</label>
                <input type="text" class="form-control" id="ctmp_numero" name="ctmp_numero"
                    value="<?= htmlspecialchars($usuario['ctmp_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ejemplo: CTMP 67890">
                <small class="text-muted d-block mt-1">Completar cuando el profesional sea Tecnologo Medico.</small>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="password" class="form-label"><?= $esEdicion ? 'Nueva Contraseña' : 'Contraseña *' ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>>
                    <button type="button" class="btn btn-outline-secondary" onclick="generarPassword()">Generar Password</button>
                </div>
                <?php if ($esEdicion): ?>
                    <small class="text-muted">Deja en blanco si no deseas cambiar la contraseña.</small>
                <?php else: ?>
                    <small class="text-muted">Por defecto, la contraseña es el mismo DNI.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="firma" class="form-label">Firma del profesional</label>
                <input type="file" class="form-control" id="firma" name="firma" accept="image/png,image/jpeg,image/jpg,image/webp">
                <small class="text-muted d-block mt-1">Sube solo la firma. El sello se genera automáticamente en el PDF.</small>
                <?php if (!empty($firmaPreviewUrl)): ?>
                    <div class="mt-2">
                        <div class="small text-muted">Firma actual:</div>
                        <img src="<?= htmlspecialchars($firmaPreviewUrl, ENT_QUOTES, 'UTF-8') ?>?v=<?= time() ?>" alt="Firma" style="max-height: 90px; border: 1px solid #ddd; border-radius: 6px; background: #fff; padding: 4px;">
                        <?php if ($esEdicion): ?>
                            <div class="form-check mt-2 text-start">
                                <input class="form-check-input" type="checkbox" id="quitar_firma" name="quitar_firma" value="1">
                                <label class="form-check-label" for="quitar_firma">Quitar firma actual</label>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (($usuario['rol'] ?? '') === 'admin' || ($_SESSION['rol'] ?? '') === 'admin'): ?>
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white">Privilegios del usuario</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($catalogoPrivilegios as $clave => $meta): ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input type="hidden" name="privilegios[<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>]" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="priv_<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>" name="privilegios[<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>]" <?= usuarioCheckboxChecked($privilegiosFormulario, $clave) ?>>
                                    <label class="form-check-label" for="priv_<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>">
                                        <strong><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($meta['group'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Crear' ?></button>
        <a href="dashboard.php?vista=usuarios" class="btn btn-secondary">Cancelar</a>
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
        // 8 dígitos, iniciando con 9 (heurística)
        const n = Math.floor(Math.random() * 10000000);
        return '9' + String(n).padStart(7, '0');
    }

    function syncCredencialesFromDni() {
        const dniDigits = cleanDigits($dni.value);
        if (dniDigits.length === 0) return;

        $dni.value = dniDigits;
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
        }
    }

    $dni.addEventListener('input', function () {
        if ($dni.readOnly) return;
        syncCredencialesFromDni();
    });

    $sinDni.addEventListener('change', onToggleSinDni);

    if ($dni.value) {
        syncCredencialesFromDni();
    }
})();

function generarPassword() {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let pass = "";
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('password').value = pass;
}
</script>
