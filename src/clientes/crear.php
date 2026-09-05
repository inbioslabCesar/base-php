<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
require_once __DIR__ . '/../resultados/servicios/EdadPacienteService.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cliente_crear_json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Solo los campos obligatorios

$codigo_cliente    = trim($_POST['codigo_cliente'] ?? '');
$nombre            = trim($_POST['nombre'] ?? '');
$apellido          = trim($_POST['apellido'] ?? '');
$razon_social      = trim($_POST['razon_social'] ?? '');
$dni               = trim($_POST['dni'] ?? '');
$tipo_documento    = $_POST['tipo_documento'] ?? 'dni';
$edad_valor        = trim($_POST['edad_valor'] ?? '');
$edad_unidad       = trim($_POST['edad_unidad'] ?? '');
$edad = ($edad_valor !== '' && $edad_unidad !== '') ? (intval($edad_valor) . ' ' . $edad_unidad) : '';
$email             = trim($_POST['email'] ?? '');
$password          = $_POST['password'] ?? '';
$telefono          = trim($_POST['telefono'] ?? '');
$direccion         = trim($_POST['direccion'] ?? '');
$sexo              = $_POST['sexo'] ?? '';
$fecha_nacimiento  = $_POST['fecha_nacimiento'] ?? null;
$estado            = $_POST['estado'] ?? 'activo';
$descuento         = $_POST['descuento'] ?? null;
$usar_precio_convenio = isset($_POST['usar_precio_convenio']) ? 1 : 0;
$procedencia       = trim($_POST['procedencia'] ?? '');
$rol_creador       = $_SESSION['rol'] ?? 'desconocido';
$operationId       = trim((string)($_POST['offline_operation_id'] ?? ''));
$isOfflineSync     = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
$acceptHeader      = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$expectsJson       = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;
$contextEmpresaId  = isset($_POST['id_empresa_contexto']) ? (int)$_POST['id_empresa_contexto'] : 0;
$contextConvenioId = isset($_POST['id_convenio_contexto']) ? (int)$_POST['id_convenio_contexto'] : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($expectsJson) {
        cliente_crear_json_response(405, ['ok' => false, 'message' => 'Metodo no permitido']);
    }
    header('Location: ../dashboard.php?vista=form_cliente');
    exit;
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

function cliente_has_column(PDO $pdo, string $column): bool {
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }

    $stmt = $pdo->prepare('SHOW COLUMNS FROM clientes LIKE ?');
    $stmt->execute([$column]);
    $cache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$column];
}

function asociarClienteAEmpresa(PDO $pdo, int $empresaId, int $clienteId): void {
    if ($empresaId <= 0 || $clienteId <= 0) {
        return;
    }
    $stmtCheck = $pdo->prepare('SELECT 1 FROM empresa_cliente WHERE empresa_id = ? AND cliente_id = ? LIMIT 1');
    $stmtCheck->execute([$empresaId, $clienteId]);
    if (!$stmtCheck->fetchColumn()) {
        $stmtIns = $pdo->prepare('INSERT INTO empresa_cliente (empresa_id, cliente_id) VALUES (?, ?)');
        $stmtIns->execute([$empresaId, $clienteId]);
    }
}

function asociarClienteAConvenio(PDO $pdo, int $convenioId, int $clienteId): void {
    if ($convenioId <= 0 || $clienteId <= 0) {
        return;
    }
    $stmtCheck = $pdo->prepare('SELECT 1 FROM convenio_cliente WHERE convenio_id = ? AND cliente_id = ? LIMIT 1');
    $stmtCheck->execute([$convenioId, $clienteId]);
    if (!$stmtCheck->fetchColumn()) {
        $stmtIns = $pdo->prepare('INSERT INTO convenio_cliente (convenio_id, cliente_id) VALUES (?, ?)');
        $stmtIns->execute([$convenioId, $clienteId]);
    }
}

// Nuevos campos para empresa/convenio y tipo_registro
$empresa_nombre   = $_SESSION['empresa_nombre'] ?? null;
$convenio_nombre = $_SESSION['convenio_nombre'] ?? null;
$tipo_registro   = 'cliente'; // Valor por defecto
$rolesRegistroCentral = ['admin', 'recepcionista', 'laboratorista'];

if (in_array((string)($_SESSION['rol'] ?? ''), $rolesRegistroCentral, true)) {
    $tipo_registro = 'central';
}

if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_nombre'])) {
    $empresa_nombre = $_SESSION['empresa_nombre'];
    $tipo_registro = 'empresa';
}
if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_nombre'])) {
    $convenio_nombre = $_SESSION['convenio_nombre'];
    $tipo_registro = 'convenio';
}

if ($contextEmpresaId > 0) {
    $stmtEmpresaNombre = $pdo->prepare('SELECT nombre_comercial FROM empresas WHERE id = ? LIMIT 1');
    $stmtEmpresaNombre->execute([$contextEmpresaId]);
    $empresaNombreContexto = (string)($stmtEmpresaNombre->fetchColumn() ?: '');
    if ($empresaNombreContexto !== '') {
        $empresa_nombre = $empresaNombreContexto;
    }
}
if ($contextConvenioId > 0) {
    $stmtConvenioNombre = $pdo->prepare('SELECT nombre FROM convenios WHERE id = ? LIMIT 1');
    $stmtConvenioNombre->execute([$contextConvenioId]);
    $convenioNombreContexto = (string)($stmtConvenioNombre->fetchColumn() ?: '');
    if ($convenioNombreContexto !== '') {
        $convenio_nombre = $convenioNombreContexto;
    }
}

// Validación de requeridos

// Documento
if ($tipo_documento === 'sin_dni') {
    if ($dni === '') {
        $intentos = 0;
        do {
            $dni = (string)random_int(10000000, 99999999);
            $stmt = $pdo->prepare('SELECT id FROM clientes WHERE dni = ? LIMIT 1');
            $stmt->execute([$dni]);
            $existe = (bool)$stmt->fetchColumn();
            $intentos++;
        } while ($existe && $intentos < 20);

        if ($existe) {
            if ($expectsJson) {
                cliente_crear_json_response(409, ['ok' => false, 'message' => 'No se pudo generar documento provisional unico']);
            }
            $_SESSION['msg'] = 'No se pudo generar un documento provisional único. Intente nuevamente.';
            header('Location: ../dashboard.php?vista=form_cliente');
            exit;
        }
    }
} else {
    if ($dni === '') {
        if ($expectsJson) {
            cliente_crear_json_response(422, ['ok' => false, 'message' => 'Documento requerido']);
        }
        $_SESSION['msg'] = 'Por favor, ingrese el documento.';
        header('Location: ../dashboard.php?vista=form_cliente');
        exit;
    }

    $docDigits = preg_replace('/\D+/', '', $dni) ?? '';
    if ($tipo_documento === 'dni') {
        if (strlen($docDigits) !== 8) {
            if ($expectsJson) {
                cliente_crear_json_response(422, ['ok' => false, 'message' => 'DNI invalido: debe tener 8 digitos']);
            }
            $_SESSION['msg'] = 'DNI invalido: debe tener 8 digitos.';
            header('Location: ../dashboard.php?vista=form_cliente');
            exit;
        }
        $dni = $docDigits;
    } elseif ($tipo_documento === 'ruc') {
        if (strlen($docDigits) !== 11) {
            if ($expectsJson) {
                cliente_crear_json_response(422, ['ok' => false, 'message' => 'RUC invalido: debe tener 11 digitos']);
            }
            $_SESSION['msg'] = 'RUC invalido: debe tener 11 digitos.';
            header('Location: ../dashboard.php?vista=form_cliente');
            exit;
        }
        $dni = $docDigits;
    } elseif ($tipo_documento === 'carnet') {
        if (!preg_match('/^[A-Za-z0-9]{6,20}$/', $dni)) {
            if ($expectsJson) {
                cliente_crear_json_response(422, ['ok' => false, 'message' => 'Carnet invalido: 6 a 20 caracteres alfanumericos']);
            }
            $_SESSION['msg'] = 'Carnet invalido: 6 a 20 caracteres alfanumericos.';
            header('Location: ../dashboard.php?vista=form_cliente');
            exit;
        }
    }
}

if ($tipo_documento === 'ruc') {
    if ($razon_social === '' && $nombre !== '') {
        $razon_social = $nombre . ($apellido !== '' ? (' ' . $apellido) : '');
    }
    if ($nombre === '' && $razon_social !== '') {
        $nombre = $razon_social;
    }
    if ($apellido === '') {
        $apellido = '-';
    }
}

// Dominio empresa para email
$empresaCfg = ui_theme_fetch_company_config($pdo);
$dominio = is_array($empresaCfg) ? (string)($empresaCfg['dominio'] ?? '') : '';
$dominio = normalizarDominioEmpresa($dominio !== '' ? $dominio : (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($dominio === '') {
    $dominio = 'localhost';
}

// Siempre forzar email según documento@dominio
$email = ($dni !== '' && $dominio !== '') ? ($dni . '@' . $dominio) : $email;

// Si la contraseña está vacía, asignar el DNI como contraseña
if (!$password) {
    $password = $dni;
}

if (!$codigo_cliente || !$nombre || !$email || !$password) {
    if ($expectsJson) {
        cliente_crear_json_response(422, ['ok' => false, 'message' => 'Faltan campos obligatorios']);
    }
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=form_cliente');
    exit;
}

// Capitaliza nombre y apellido
function capitalize($string) {
    return mb_convert_case(strtolower(trim($string)), MB_CASE_TITLE, "UTF-8");
}

try {
    date_default_timezone_set('America/Lima');
    $pdo->beginTransaction();

    $edadReferidaValor = null;
    $edadReferidaFecha = null;
    if (trim((string)$fecha_nacimiento) === '' && trim((string)$edad) !== '') {
        $parsedEdad = EdadPacienteService::convertirEdadTextoADecimal($edad);
        if ($parsedEdad !== null) {
            $edadReferidaValor = number_format($parsedEdad, 6, '.', '');
            $edadReferidaFecha = date('Y-m-d H:i:s');
        }
    }

    if ($operationId !== '') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS clientes_sync_operaciones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_id VARCHAR(80) NOT NULL,
            tipo_operacion VARCHAR(20) NOT NULL,
            cliente_id INT NULL,
            estado ENUM('pendiente','aplicado','error') NOT NULL DEFAULT 'pendiente',
            payload_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_clientes_sync_operation_id (operation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmtOp = $pdo->prepare("SELECT estado, cliente_id FROM clientes_sync_operaciones WHERE operation_id = ? LIMIT 1");
        $stmtOp->execute([$operationId]);
        $existingOp = $stmtOp->fetch(PDO::FETCH_ASSOC);
        if ($existingOp && (string)($existingOp['estado'] ?? '') === 'aplicado') {
            $pdo->commit();
            if ($expectsJson) {
                cliente_crear_json_response(200, [
                    'ok' => true,
                    'duplicate' => true,
                    'cliente_id' => isset($existingOp['cliente_id']) ? (int)$existingOp['cliente_id'] : null,
                    'message' => 'Operacion ya aplicada'
                ]);
            }
            header('Location: ../dashboard.php?vista=clientes');
            exit;
        }

        if (!$existingOp) {
            $payloadRaw = json_encode([
                'codigo_cliente' => $codigo_cliente,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'tipo_documento' => $tipo_documento,
                'telefono' => $telefono,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmtInsOp = $pdo->prepare("INSERT INTO clientes_sync_operaciones (operation_id, tipo_operacion, estado, payload_json) VALUES (?, 'crear', 'pendiente', ?)");
            $stmtInsOp->execute([$operationId, $payloadRaw]);
        }
    }

    // Validar DNI único
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? LIMIT 1");
    $stmt->execute([$dni]);
    if ($stmt->fetchColumn()) {
        if ($operationId !== '') {
            $stmtErr = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        }
        $pdo->commit();
        if ($expectsJson) {
            cliente_crear_json_response(409, ['ok' => false, 'message' => 'Documento duplicado']);
        }
        header('Location: ../dashboard.php?vista=form_cliente&error=dni_duplicado');
        exit;
    }

    $cols = [
        'codigo_cliente', 'nombre', 'apellido', 'dni', 'tipo_documento', 'edad', 'email', 'password', 'telefono',
        'direccion', 'sexo', 'fecha_nacimiento', 'estado', 'descuento', 'procedencia', 'rol_creador',
        'empresa_nombre', 'convenio_nombre', 'tipo_registro'
    ];
    $vals = [
        $codigo_cliente,
        capitalize($nombre),
        capitalize($apellido),
        $dni,
        $tipo_documento,
        $edad,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $telefono ?: null,
        $direccion ?: null,
        $sexo ?: null,
        $fecha_nacimiento ?: null,
        $estado,
        $descuento !== '' ? $descuento : null,
        $procedencia !== '' ? $procedencia : null,
        $rol_creador,
        $empresa_nombre,
        $convenio_nombre,
        $tipo_registro
    ];

    if (cliente_has_column($pdo, 'usar_precio_convenio')) {
        $cols[] = 'usar_precio_convenio';
        $vals[] = $usar_precio_convenio;
    }

    if (cliente_has_column($pdo, 'razon_social')) {
        $cols[] = 'razon_social';
        $vals[] = $razon_social !== '' ? mb_convert_case($razon_social, MB_CASE_TITLE, 'UTF-8') : null;
    }

    if (cliente_has_column($pdo, 'edad_referida_valor')) {
        $cols[] = 'edad_referida_valor';
        $vals[] = $edadReferidaValor;
    }
    if (cliente_has_column($pdo, 'edad_referida_fecha')) {
        $cols[] = 'edad_referida_fecha';
        $vals[] = $edadReferidaFecha;
    }

    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $stmt = $pdo->prepare(
        'INSERT INTO clientes (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')'
    );
    $stmt->execute($vals);

    $id_cliente_nuevo = $pdo->lastInsertId();

    $mensajeRegistro = 'Cliente registrado correctamente.';
    $vistaRedirect = 'clientes';

    if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_id'])) {
        asociarClienteAEmpresa($pdo, (int)$_SESSION['empresa_id'], (int)$id_cliente_nuevo);
        $mensajeRegistro = 'Cliente registrado y asociado correctamente.';
        $vistaRedirect = 'clientes_empresa';
    }
    if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_id'])) {
        asociarClienteAConvenio($pdo, (int)$_SESSION['convenio_id'], (int)$id_cliente_nuevo);
        $mensajeRegistro = 'Cliente registrado y asociado correctamente.';
        $vistaRedirect = 'clientes_convenio';
    }

    $rolSesion = (string)($_SESSION['rol'] ?? '');
    if (in_array($rolSesion, $rolesRegistroCentral, true)) {
        if ($contextEmpresaId > 0) {
            asociarClienteAEmpresa($pdo, $contextEmpresaId, (int)$id_cliente_nuevo);
            $mensajeRegistro = 'Cliente registrado y asociado a la empresa seleccionada.';
        }
        if ($contextConvenioId > 0) {
            asociarClienteAConvenio($pdo, $contextConvenioId, (int)$id_cliente_nuevo);
            $mensajeRegistro = 'Cliente registrado y asociado al convenio seleccionado.';
        }
    }

    if ($operationId !== '') {
        $stmtOk = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'aplicado', cliente_id = ? WHERE operation_id = ?");
        $stmtOk->execute([(int)$id_cliente_nuevo, $operationId]);
    }

    $pdo->commit();

    if ($expectsJson) {
        cliente_crear_json_response(200, [
            'ok' => true,
            'duplicate' => false,
            'cliente_id' => (int)$id_cliente_nuevo,
            'message' => $mensajeRegistro
        ]);
    }

    $_SESSION['msg'] = $mensajeRegistro;
    header('Location: ../dashboard.php?vista=' . $vistaRedirect);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($operationId !== '') {
        try {
            $stmtErr = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        } catch (Throwable $inner) {
            // Ignorar error secundario.
        }
    }
    if ($expectsJson) {
        cliente_crear_json_response(500, [
            'ok' => false,
            'message' => 'Error al registrar cliente',
            'error' => $e->getMessage()
        ]);
    }
    $_SESSION['msg'] = 'Error al registrar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=form_cliente');
    exit;
}
