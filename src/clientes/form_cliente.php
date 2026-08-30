<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
$esEdicion = !empty($id);

$cliente = [
    'codigo_cliente' => '',
    'nombre' => '',
    'apellido' => '',
    'dni' => isset($_GET['dni']) ? $_GET['dni'] : '',
    'tipo_documento' => 'dni',
    'edad' => '',
    'email' => '',
    'telefono' => '',
    'direccion' => '',
    'sexo' => '',
    'fecha_nacimiento' => '',
    'estado' => 'activo',
    'descuento' => ''
];

// Variables para mostrar información del último paciente
$ultimoCodigoCliente = '';
$ultimoNombreCliente = '';
$fechaUltimoRegistro = '';

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cli) $cliente = $cli;
    else header('Location: dashboard.php?vista=clientes&msg=sin_id');
} else {
    // Si es creación, obtener información del último paciente registrado
    $stmt = $pdo->prepare("
        SELECT codigo_cliente, nombre, apellido, fecha_registro 
        FROM clientes 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $ultimoCliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ultimoCliente) {
        $ultimoCodigoCliente = $ultimoCliente['codigo_cliente'];
        $ultimoNombreCliente = trim($ultimoCliente['nombre'] . ' ' . $ultimoCliente['apellido']);
        $fechaUltimoRegistro = $ultimoCliente['fecha_registro'];

        // Generar el siguiente código consecutivo
        $codigoBase = '';
        $numeroConsecutivo = '';
        if (preg_match('/^(CLI-\d{6}-)(\d{6})$/', $ultimoCodigoCliente, $matches)) {
            $codigoBase = $matches[1];
            $numeroConsecutivo = str_pad((int)$matches[2] + 1, 6, '0', STR_PAD_LEFT);
            $cliente['codigo_cliente'] = $codigoBase . $numeroConsecutivo;
        } else {
            // Si el formato no coincide, generar uno nuevo con 000001
            $fecha = date('ymd');
            $cliente['codigo_cliente'] = 'CLI-' . $fecha . '-000001';
        }
    } else {
        // Primer paciente
        $fecha = date('ymd');
        $cliente['codigo_cliente'] = 'CLI-' . $fecha . '-000001';
    }
}

function capitalize($string) {
    return mb_convert_case(strtolower(trim((string)$string)), MB_CASE_TITLE, "UTF-8");
}

function cliente_conflicto_hash(array $row): string {
    $keys = [
        'codigo_cliente', 'nombre', 'apellido', 'dni', 'tipo_documento', 'edad', 'email',
        'telefono', 'direccion', 'sexo', 'fecha_nacimiento', 'estado', 'descuento', 'procedencia'
    ];
    $values = [];
    foreach ($keys as $key) {
        $value = isset($row[$key]) ? (string)$row[$key] : '';
        $values[] = mb_strtolower(trim($value), 'UTF-8');
    }
    return sha1(implode('|', $values));
}

function normalizarDominioEmpresa(string $dominio): string {
    $dominio = trim($dominio);
    if ($dominio === '') return '';

    $dominio = preg_replace('#^https?://#i', '', $dominio);
    $dominio = preg_replace('#/.*$#', '', $dominio);
    $dominio = preg_replace('#:\\d+$#', '', $dominio);
    $dominio = preg_replace('#^www\\.#i', '', $dominio);
    return strtolower(trim($dominio));
}

$empresaCfg = ui_theme_fetch_company_config($pdo);
$dominioEmpresa = is_array($empresaCfg) ? (string)($empresaCfg['dominio'] ?? '') : '';
$dominioEmpresa = normalizarDominioEmpresa($dominioEmpresa !== '' ? $dominioEmpresa : (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($dominioEmpresa === '') {
    $dominioEmpresa = 'localhost';
}

$offlineBaseHash = $esEdicion ? cliente_conflicto_hash($cliente) : '';
?>
<div class="container mt-4">
    <h4><?= $esEdicion ? 'Editar Paciente' : 'Nuevo Paciente' ?></h4>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'dni_duplicado'): ?>
        <div class="alert alert-danger">El DNI ingresado ya está registrado.</div>
    <?php endif; ?>

    <?php if (!$esEdicion && $ultimoCodigoCliente): ?>
        <div class="alert alert-info border-0 shadow-sm" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2" style="font-size: 1.2rem; color: #1976d2;"></i>
                <div>
                    <h6 class="mb-1" style="color: #1565c0;">
                        <i class="bi bi-person-check me-1"></i>
                        Último paciente registrado
                    </h6>
                    <p class="mb-0" style="color: #1976d2;">
                        <strong>Código:</strong> <span class="badge bg-primary"><?= htmlspecialchars($ultimoCodigoCliente) ?></span>
                        <strong class="ms-3">Paciente:</strong> <?= htmlspecialchars($ultimoNombreCliente) ?>
                        <strong class="ms-3">Fecha:</strong> <?= date('d/m/Y H:i', strtotime($fechaUltimoRegistro)) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php elseif (!$esEdicion && !$ultimoCodigoCliente): ?>
        <div class="alert alert-success border-0 shadow-sm" style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-star-fill me-2" style="font-size: 1.2rem; color: #388e3c;"></i>
                <div>
                    <h6 class="mb-1" style="color: #2e7d32;">
                        <i class="bi bi-trophy me-1"></i>
                        ¡Primer paciente!
                    </h6>
                    <p class="mb-0" style="color: #388e3c;">
                        Este será el primer paciente registrado en el sistema.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="clientes/<?= $esEdicion ? 'editar.php?id='.$cliente['id'] : 'crear.php' ?>" id="formClienteOffline">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="offline_base_hash" id="offline_base_hash" value="<?= htmlspecialchars($offlineBaseHash) ?>">
        <?php endif; ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="codigo_cliente" class="form-label">Código Paciente *</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']??'') ?>" required>
                    <button class="btn btn-secondary" type="button" onclick="generarCodigo()" title="Generar código automático">
                        <i class="bi bi-arrow-clockwise"></i>
                        Generar
                    </button>
                </div>
                <small class="text-muted">
                    <i class="bi bi-lightbulb"></i>
                    Haz clic en "Generar" para crear un código automático
                </small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" name="nombre" id="nombre" value="<?= capitalize($cliente['nombre']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" name="apellido" id="apellido" value="<?= capitalize($cliente['apellido']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="dni" class="form-label">Documento</label>
                <div class="input-group">
                    <?php
                    // Usar tipo_documento directamente si existe
                    $tipoDocumento = $cliente['tipo_documento'] ?? 'dni';
                    $dniValue = htmlspecialchars($cliente['dni'] ?? '');
                    ?>
                    <select class="form-select" id="tipo_documento" name="tipo_documento" style="max-width: 180px;">
                        <option value="dni" <?= $tipoDocumento==='dni'?'selected':'' ?>>DNI</option>
                        <option value="carnet" <?= $tipoDocumento==='carnet'?'selected':'' ?>>Carnet de extranjería</option>
                        <option value="sin_dni" <?= $tipoDocumento==='sin_dni'?'selected':'' ?>>Sin DNI</option>
                    </select>
                    <input type="text" class="form-control" name="dni" id="dni" value="<?= $dniValue ?>" maxlength="20" pattern="[A-Za-z0-9]{6,20}">
                </div>
                <small id="dniHelp" class="form-text text-muted">Selecciona el tipo de documento y completa el campo.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento</label>
                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" value="<?= htmlspecialchars($cliente['fecha_nacimiento']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="edad" class="form-label">Edad</label>
                <?php
                    // Separar valor y unidad si es posible
                    $edad_valor = '';
                    $edad_unidad = '';
                    if (preg_match('/^([0-9]+)\\s*(día|días|mes|meses|año|años)$/iu', trim($cliente['edad']), $m)) {
                        $edad_valor = $m[1];
                        $edad_unidad = strtolower($m[2]);
                    } elseif (is_numeric($cliente['edad'])) {
                        $edad_valor = $cliente['edad'];
                        $edad_unidad = 'años';
                    }
                ?>
                <div class="input-group">
                    <input type="text" class="form-control" name="edad_valor" id="edad_valor" value="<?= htmlspecialchars($edad_valor) ?>" pattern="[0-9]+">
                    <select class="form-select" name="edad_unidad" id="edad_unidad">
                        <option value="días" <?= ($edad_unidad==="día"||$edad_unidad==="días")?'selected':'' ?>>Días</option>
                        <option value="meses" <?= ($edad_unidad==="mes"||$edad_unidad==="meses")?'selected':'' ?>>Meses</option>
                        <option value="años" <?= ($edad_unidad==="año"||$edad_unidad==="años"||$edad_unidad==="")?'selected':'' ?>>Años</option>
                    </select>
                </div>
                <small class="form-text text-muted">Ejemplo: 15 días, 2 meses, 1 año</small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="sexo" class="form-label">Sexo</label>
                <select class="form-select" name="sexo" id="sexo">
                    <option value="">Seleccionar</option>
                    <optgroup label="👤 Humanos">
                        <option value="masculino" <?= ($cliente['sexo'] === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                        <option value="femenino" <?= ($cliente['sexo'] === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    </optgroup>
                    <optgroup label="🐾 Animales">
                        <option value="macho" <?= ($cliente['sexo'] === 'macho') ? 'selected' : '' ?>>Macho</option>
                        <option value="hembra" <?= ($cliente['sexo'] === 'hembra') ? 'selected' : '' ?>>Hembra</option>
                    </optgroup>
                    <option value="otro" <?= ($cliente['sexo'] === 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Selecciona según el tipo de paciente: humano o animal
                </small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" id="direccion" value="<?= htmlspecialchars($cliente['direccion']??'') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="procedencia" class="form-label">Procedencia</label>
                <input type="text" class="form-control" name="procedencia" id="procedencia" value="<?= htmlspecialchars($cliente['procedencia'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="password" class="form-label">Contraseña (por defecto es el DNI)</label>
                <input type="text" class="form-control" name="password" id="password" value="<?= $esEdicion ? '' : htmlspecialchars((string)($cliente['dni'] ?? '')) ?>" autocomplete="new-password">
                <?php if ($esEdicion): ?>
                    <small class="text-muted">Si lo dejas vacío, no se cambiará la contraseña.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars((string)($cliente['email'] ?? '')) ?>" readonly>
                <small class="text-muted">Se genera automáticamente según el documento y dominio de la empresa.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" id="telefono" value="<?= htmlspecialchars($cliente['telefono']??'') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" class="form-control" name="descuento" id="descuento" value="<?= htmlspecialchars($cliente['descuento']) ?>" min="0" max="100">
            </div>
            <div class="col-md-4 mb-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" name="estado" id="estado">
                    <option value="activo" <?= ($cliente['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($cliente['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-success"><?= $esEdicion ? 'Actualizar' : 'Registrar' ?></button>
            <a href="dashboard.php?vista=clientes" class="btn btn-secondary">Cancelar</a>
        </div>
        <div class="alert alert-light border mt-3 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2" role="status" aria-live="polite">
            <small class="text-muted" id="clientesOfflineEstado">Sin pendientes offline de pacientes.</small>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-primary" id="clientesSyncNowBtn">Sincronizar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="clientesVerColaBtn">Ver cola</button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="clientesLimpiarErroresBtn">Limpiar errores</button>
                <button type="button" class="btn btn-sm btn-outline-warning" id="clientesIncidenciaBtn">Marcar incidencia</button>
            </div>
        </div>
        <div class="small text-muted mt-1" id="clientesColaDetalle" style="display:none;"></div>
    </form>
</div>
<script>
const DOMINIO_EMPRESA = <?= json_encode($dominioEmpresa, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const ES_EDICION = <?= $esEdicion ? 'true' : 'false' ?>;
const CLIENTE_ID_ACTUAL = <?= (int)($cliente['id'] ?? 0) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Poner el foco en el campo nombre al cargar el formulario
    var nombreInput = document.getElementById('nombre');
    if (nombreInput) {
        nombreInput.focus();
    }
});
function generarCodigo() {
    let fecha = new Date();
    let año = fecha.getFullYear().toString().slice(-2); // últimos 2 dígitos del año
    let mes = ('0' + (fecha.getMonth() + 1)).slice(-2); // mes con dos dígitos
    let dia = ('0' + fecha.getDate()).slice(-2); // día con dos dígitos
    let aleatorio = Math.random().toString(36).substr(2, 6).toUpperCase();
    let codigo = 'CLI-' + año + mes + dia + '-' + aleatorio;
    document.getElementById('codigo_cliente').value = codigo;
}

// Documento dinámico
document.addEventListener('DOMContentLoaded', function() {
    const tipoDocumento = document.getElementById('tipo_documento');
    const dniInput = document.getElementById('dni');
    const dniHelp = document.getElementById('dniHelp');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    let usuarioTocoDocumento = false;
    let lastAutoPassword = '';

    function actualizarCredenciales() {
        const doc = (dniInput?.value || '').trim();
        if (emailInput) {
            emailInput.value = doc && DOMINIO_EMPRESA ? `${doc}@${DOMINIO_EMPRESA}` : '';
        }

        if (!passwordInput) return;

        if (ES_EDICION) {
            // IMPORTANTE: no llenar al cargar; solo si el usuario cambió el documento.
            if (usuarioTocoDocumento && passwordInput.value.trim() === '' && doc !== '') {
                passwordInput.value = doc;
                lastAutoPassword = doc;
            }
            return;
        }

        // Creación: no pisar password si el usuario lo editó manualmente.
        if (doc === '') return;
        if (passwordInput.value.trim() === '' || passwordInput.value === lastAutoPassword) {
            passwordInput.value = doc;
            lastAutoPassword = doc;
        }
    }

    if (tipoDocumento && dniInput) {
        tipoDocumento.addEventListener('change', function() {
            usuarioTocoDocumento = true;
            if (this.value === 'sin_dni') {
                // Solo generar si el campo está vacío
                if (!dniInput.value) {
                    let prov = Math.floor(10000000 + Math.random() * 90000000);
                    dniInput.value = prov;
                }
                dniInput.setAttribute('readonly', 'readonly');
                dniInput.setAttribute('maxlength', '8');
                dniInput.setAttribute('pattern', '[0-9]{8}');
                dniHelp.textContent = 'Se generó un número provisional de 8 dígitos.';
            } else if (this.value === 'dni') {
                dniInput.removeAttribute('readonly');
                dniInput.setAttribute('maxlength', '8');
                dniInput.setAttribute('pattern', '[0-9]{8}');
                dniHelp.textContent = 'Ingrese el DNI (8 dígitos numéricos).';
            } else if (this.value === 'carnet') {
                dniInput.removeAttribute('readonly');
                dniInput.setAttribute('maxlength', '20');
                dniInput.setAttribute('pattern', '[A-Za-z0-9]{6,20}');
                dniHelp.textContent = 'Ingrese el número de carnet (6 a 20 caracteres alfanuméricos).';
            }

            actualizarCredenciales();
        });
        // Inicializar según valor actual, sin borrar el valor existente
        tipoDocumento.dispatchEvent(new Event('change'));

        dniInput.addEventListener('input', function() {
            usuarioTocoDocumento = true;
            actualizarCredenciales();
        });
    }

    // Calcular edad automáticamente al seleccionar fecha de nacimiento
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    const edadValor = document.getElementById('edad_valor');
    const edadUnidad = document.getElementById('edad_unidad');
    if (fechaNacimiento && edadValor && edadUnidad) {
        fechaNacimiento.addEventListener('change', function() {
            if (this.value) {
                const hoy = new Date();
                const nacimiento = new Date(this.value);
                let edadAnios = hoy.getFullYear() - nacimiento.getFullYear();
                let m = hoy.getMonth() - nacimiento.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edadAnios--;
                }
                if (edadAnios < 1) {
                    // Si es menos de 1 año, calcular meses
                    let edadMeses = (hoy.getFullYear() - nacimiento.getFullYear()) * 12 + (hoy.getMonth() - nacimiento.getMonth());
                    if (hoy.getDate() < nacimiento.getDate()) edadMeses--;
                    if (edadMeses < 1) {
                        // Si es menos de 1 mes, calcular días
                        const diffTime = Math.abs(hoy - nacimiento);
                        const edadDias = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                        edadValor.value = edadDias;
                        edadUnidad.value = 'días';
                    } else {
                        edadValor.value = edadMeses;
                        edadUnidad.value = 'meses';
                    }
                } else {
                    edadValor.value = edadAnios;
                    edadUnidad.value = 'años';
                }
            } else {
                edadValor.value = '';
            }
        });
    }
});

(function () {
    var form = document.getElementById('formClienteOffline');
    var estadoEl = document.getElementById('clientesOfflineEstado');
    var syncBtn = document.getElementById('clientesSyncNowBtn');
    var verColaBtn = document.getElementById('clientesVerColaBtn');
    var limpiarErroresBtn = document.getElementById('clientesLimpiarErroresBtn');
    var incidenciaBtn = document.getElementById('clientesIncidenciaBtn');
    var colaDetalleEl = document.getElementById('clientesColaDetalle');
    if (!form || !estadoEl || !syncBtn || !window.indexedDB) {
        return;
    }

    var DB_NAME = 'clientes_offline_db_v1';
    var STORE_NAME = 'clientes_queue';
    var INCIDENT_KEY = 'offline_sync_incidents_v1';

    function showToast(msg, type) {
        if (typeof window.Swal !== 'undefined') {
            var icon = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success');
            window.Swal.fire({ toast: true, position: 'top-end', icon: icon, title: msg, showConfirmButton: false, timer: 3600 });
            return;
        }
        alert(msg);
    }

    function createOperationId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'cli_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = function (event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    var store = db.createObjectStore(STORE_NAME, { keyPath: 'operation_id' });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('created_at', 'created_at', { unique: false });
                }
            };
            req.onsuccess = function (event) { resolve(event.target.result); };
            req.onerror = function (event) { reject(event.target.error || new Error('No se pudo abrir IndexedDB')); };
        });
    }

    async function withStore(mode, fn) {
        var db = await openDb();
        return new Promise(function (resolve, reject) {
            var tx = db.transaction(STORE_NAME, mode);
            var store = tx.objectStore(STORE_NAME);
            var result;
            try {
                result = fn(store, tx);
            } catch (err) {
                reject(err);
                db.close();
                return;
            }
            tx.oncomplete = function () {
                resolve(result);
                db.close();
            };
            tx.onerror = function (event) {
                reject(event.target.error || new Error('Error en transaccion IndexedDB'));
                db.close();
            };
        });
    }

    async function queuePayload(payload) {
        var record = {
            operation_id: payload.offline_operation_id,
            payload: payload,
            endpoint: payload.endpoint,
            mode: payload.mode,
            status: 'pending',
            created_at: Date.now(),
            retries: 0,
            last_error: ''
        };
        await withStore('readwrite', function (store) { store.put(record); });
    }

    async function getPending() {
        return withStore('readonly', function (store) {
            return new Promise(function (resolve, reject) {
                var req = store.getAll();
                req.onsuccess = function () {
                    var rows = Array.isArray(req.result) ? req.result : [];
                    resolve(rows.filter(function (r) { return r.status !== 'synced'; }));
                };
                req.onerror = function (event) { reject(event.target.error || new Error('No se pudo leer cola de pacientes')); };
            });
        });
    }

    async function updateRecord(record) {
        await withStore('readwrite', function (store) { store.put(record); });
    }

    async function deleteRecord(operationId) {
        await withStore('readwrite', function (store) { store.delete(operationId); });
    }

    async function clearErrorRecords() {
        var pending = await getPending();
        var errors = pending.filter(function (r) { return String(r.status || '') === 'error'; });
        for (var i = 0; i < errors.length; i++) {
            await deleteRecord(errors[i].operation_id);
        }
        return errors.length;
    }

    function summarizeQueue(items) {
        var list = Array.isArray(items) ? items : [];
        var total = list.length;
        var errores = list.filter(function (r) { return String(r.status || '') === 'error'; }).length;
        var conflictos = list.filter(function (r) { return String(r.status || '') === 'conflict'; }).length;
        return { total: total, errores: errores, conflictos: conflictos };
    }

    function renderQueueDetail(items) {
        if (!colaDetalleEl) return;
        var list = Array.isArray(items) ? items : [];
        if (!list.length) {
            colaDetalleEl.textContent = 'Cola vacia.';
            return;
        }
        var lines = list.slice(0, 8).map(function (r, idx) {
            var st = String(r.status || 'pending');
            var retries = Number(r.retries || 0);
            var at = r.created_at ? new Date(r.created_at).toLocaleString() : '-';
            var err = r.last_error ? (' | error: ' + String(r.last_error).slice(0, 90)) : '';
            return (idx + 1) + '. [' + st + '] ' + (r.mode || '-') + ' | ' + (r.operation_id || '-') + ' | reintentos: ' + retries + ' | ' + at + err;
        });
        colaDetalleEl.textContent = lines.join(' | ');
    }

    function pushIncident(moduleName, detail) {
        try {
            var rows = JSON.parse(localStorage.getItem(INCIDENT_KEY) || '[]');
            var list = Array.isArray(rows) ? rows : [];
            list.push({ module: moduleName, detail: detail, at: new Date().toISOString(), path: location.pathname + location.search });
            localStorage.setItem(INCIDENT_KEY, JSON.stringify(list.slice(-200)));
        } catch (error) {
        }
    }

    async function refreshStatus() {
        try {
            var pending = await getPending();
            renderQueueDetail(pending);
            var r = summarizeQueue(pending);
            if (!r.total) {
                estadoEl.textContent = navigator.onLine
                    ? 'Sin pendientes offline de pacientes.'
                    : 'Sin internet. No hay pendientes de pacientes en cola.';
                return;
            }
            estadoEl.textContent = 'Pendientes: ' + r.total + ' | errores: ' + r.errores + ' | conflictos: ' + r.conflictos + '.';
        } catch (err) {
            estadoEl.textContent = 'No se pudo leer cola offline de pacientes.';
        }
    }

    async function sendPayload(payload) {
        var body = new URLSearchParams();
        Object.keys(payload.fields || {}).forEach(function (k) {
            body.set(k, payload.fields[k]);
        });
        body.set('offline_sync', '1');
        body.set('offline_operation_id', String(payload.offline_operation_id || ''));
        if (payload.mode === 'editar') {
            body.set('id', String(payload.id || CLIENTE_ID_ACTUAL || ''));
        }

        var resp = await fetch(payload.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: body.toString()
        });

        var data = await resp.json().catch(function () { return {}; });
        if (!resp.ok || !data || data.ok !== true) {
            var err = new Error((data && data.message) ? data.message : 'Fallo de sincronizacion de pacientes');
            err.httpStatus = resp.status;
            throw err;
        }
        return data;
    }

    async function syncQueue() {
        var stats = { ok: 0, error: 0, conflict: 0 };
        if (!navigator.onLine) {
            await refreshStatus();
            return stats;
        }
        var pending = await getPending();
        if (!pending.length) {
            await refreshStatus();
            return stats;
        }
        for (var i = 0; i < pending.length; i++) {
            var rec = pending[i];
            try {
                await sendPayload(rec.payload || {});
                await deleteRecord(rec.operation_id);
                stats.ok += 1;
            } catch (err) {
                var isConflict = Number(err && err.httpStatus ? err.httpStatus : 0) === 409
                    && /conflicto|desfasada|version/i.test(String(err && err.message ? err.message : ''));
                rec.status = isConflict ? 'conflict' : 'error';
                rec.retries = Number(rec.retries || 0) + 1;
                rec.last_error = String(err && err.message ? err.message : 'Error de sincronizacion');
                await updateRecord(rec);
                if (isConflict) {
                    stats.conflict += 1;
                } else {
                    stats.error += 1;
                }
            }
        }
        await refreshStatus();
        return stats;
    }

    function collectPayloadFromForm() {
        var fd = new FormData(form);
        var fields = {};
        fd.forEach(function (value, key) {
            fields[key] = String(value == null ? '' : value);
        });
        var modo = ES_EDICION ? 'editar' : 'crear';
        var endpoint = ES_EDICION
            ? ('dashboard.php?action=editar_cliente&id=' + encodeURIComponent(String(CLIENTE_ID_ACTUAL || '0')))
            : 'dashboard.php?action=crear_cliente';
        return {
            mode: modo,
            endpoint: endpoint,
            id: CLIENTE_ID_ACTUAL,
            fields: fields,
            offline_operation_id: createOperationId()
        };
    }

    form.addEventListener('submit', function (event) {
        if (navigator.onLine) {
            return;
        }
        event.preventDefault();
        var payload = collectPayloadFromForm();
        if (!payload.fields.codigo_cliente || !payload.fields.nombre || !payload.fields.apellido) {
            showToast('Completa los campos obligatorios para encolar.', 'error');
            return;
        }
        queuePayload(payload)
            .then(function () {
                showToast('Paciente encolado offline para sincronizar.', 'warning');
                refreshStatus();
            })
            .catch(function () {
                showToast('No se pudo guardar en cola offline de pacientes.', 'error');
            });
    });

    syncBtn.addEventListener('click', function () {
        syncQueue()
            .then(function (stats) {
                if (stats.conflict > 0) {
                    showToast('Sincronizacion parcial: ' + stats.ok + ' ok, ' + stats.conflict + ' conflicto(s). Revisar cola.', 'warning');
                    return;
                }
                if (stats.error > 0) {
                    showToast('Sincronizacion parcial: ' + stats.ok + ' ok, ' + stats.error + ' con error.', 'warning');
                    return;
                }
                showToast('Sincronizacion de pacientes completada.', 'success');
            })
            .catch(function (err) {
                showToast('Error al sincronizar pacientes: ' + (err && err.message ? err.message : 'desconocido'), 'error');
            });
    });

    if (verColaBtn && colaDetalleEl) {
        verColaBtn.addEventListener('click', function () {
            var hidden = colaDetalleEl.style.display === 'none';
            colaDetalleEl.style.display = hidden ? 'block' : 'none';
            if (hidden) {
                refreshStatus();
            }
        });
    }

    if (limpiarErroresBtn) {
        limpiarErroresBtn.addEventListener('click', function () {
            clearErrorRecords()
                .then(function (n) {
                    showToast('Registros con error eliminados: ' + n, 'warning');
                    refreshStatus();
                })
                .catch(function () {
                    showToast('No se pudieron limpiar errores de pacientes.', 'error');
                });
        });
    }

    if (incidenciaBtn) {
        incidenciaBtn.addEventListener('click', function () {
            getPending().then(function (pending) {
                var r = summarizeQueue(pending);
                pushIncident('pacientes', 'Pacientes pendientes=' + r.total + ', errores=' + r.errores);
                showToast('Incidencia registrada para soporte.', 'warning');
            });
        });
    }

    window.addEventListener('online', function () {
        syncQueue().catch(function () {});
    });

    refreshStatus();
    if (navigator.onLine) {
        syncQueue().catch(function () {});
    }
})();
</script>
