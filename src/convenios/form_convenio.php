<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';

$esEdicion = isset($_GET['id']);
$convenio = [
    'nombre' => '',
    'dni' => '',
    'especialidad' => '',
    'descuento' => '',
    'descripcion' => '',
    'email' => '',
    'password' => '',
    'usar_precio_convenio' => 0
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

$empresaCfg = ui_theme_fetch_company_config($pdo);
$dominioEmpresa = is_array($empresaCfg) ? (string)($empresaCfg['dominio'] ?? '') : '';
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
            <label for="dni" class="form-label">Documento (DNI/RUC) *</label>
            <input type="text" class="form-control" id="dni" name="dni" required
                value="<?= htmlspecialchars($convenio['dni'] ?? '') ?>">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="sin_dni" name="sin_dni">
                <label class="form-check-label" for="sin_dni">
                    Sin DNI (generar uno provisional)
                </label>
            </div>
            <small class="text-muted d-block mt-1">
                El email y la contraseña se generan automáticamente según el documento.
            </small>
            <small id="docLookupStatusConvenio" class="form-text"></small>
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
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="usar_precio_convenio" name="usar_precio_convenio" value="1" <?= !empty($convenio['usar_precio_convenio']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="usar_precio_convenio">Usar precio convenio por defecto</label>
            </div>
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
    const $nombre = document.getElementById('nombre');
    const $descripcion = document.getElementById('descripcion');
    const $status = document.getElementById('docLookupStatusConvenio');

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

    function setLookupStatus(msg, type) {
        if (!$status) return;
        $status.textContent = msg || '';
        $status.classList.remove('text-muted', 'text-success', 'text-danger', 'text-warning');
        if (!msg) return;
        if (type === 'success') {
            $status.classList.add('text-success');
        } else if (type === 'error') {
            $status.classList.add('text-danger');
        } else if (type === 'warning') {
            $status.classList.add('text-warning');
        } else {
            $status.classList.add('text-muted');
        }
    }

    function titleCase(text) {
        return String(text || '')
            .toLowerCase()
            .replace(/\b\w/g, function (m) { return m.toUpperCase(); })
            .trim();
    }

    let lastLookupDoc = '';
    async function lookupDocumento() {
        if ($sinDni.checked) {
            return;
        }

        const doc = cleanDigits($dni.value);
        if (doc.length !== 8 && doc.length !== 11) {
            setLookupStatus('', 'muted');
            return;
        }
        if (lastLookupDoc === doc) {
            return;
        }

        setLookupStatus('Consultando documento...', 'muted');
        try {
            const resp = await fetch('dashboard.php?action=consultar_documento_identidad&scope=convenio&documento=' + encodeURIComponent(doc), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const result = await resp.json();

            if (result && result.ok && result.data) {
                lastLookupDoc = doc;
                const data = result.data;
                if (doc.length === 8) {
                    const nombres = (data.nombres || '').toString().trim();
                    const apPat = (data.apellido_paterno || '').toString().trim();
                    const apMat = (data.apellido_materno || '').toString().trim();
                    const nombreCompleto = (nombres + ' ' + apPat + ' ' + apMat).trim();
                    if ($nombre && nombreCompleto && !$nombre.value) {
                        $nombre.value = titleCase(nombreCompleto);
                    }
                } else if (doc.length === 11) {
                    const razon = (data.razon_social || '').toString().trim();
                    if ($nombre && razon && !$nombre.value) {
                        $nombre.value = titleCase(razon);
                    }
                    if ($descripcion && data.direccion && !$descripcion.value) {
                        $descripcion.value = titleCase(data.direccion);
                    }
                }

                if (result.status === 'encontrado_bd') {
                    setLookupStatus('Documento encontrado en base local.', 'success');
                } else {
                    setLookupStatus('Documento encontrado en APISPERU.', 'success');
                }
                return;
            }

            if (result && result.status === 'no_encontrado') {
                setLookupStatus('No se encontro informacion para este documento.', 'warning');
                return;
            }

            setLookupStatus((result && result.message) ? result.message : 'No se pudo consultar el documento.', 'error');
        } catch (error) {
            setLookupStatus('Error de red al consultar documento.', 'error');
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
        lastLookupDoc = '';
        setLookupStatus('', 'muted');
        syncCredencialesFromDni();
    });

    $dni.addEventListener('blur', function () {
        lookupDocumento();
    });

    $sinDni.addEventListener('change', onToggleSinDni);

    // Inicial: si ya hay DNI precargado (edición), sincronizar si faltan credenciales
    if ($dni.value) {
        syncCredencialesFromDni();
    }
})();
</script>
