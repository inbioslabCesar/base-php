<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
require_once __DIR__ . '/../resultados/servicios/EdadPacienteService.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cliente_editar_json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
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

$id = $_GET['id'] ?? ($_POST['id'] ?? null);
if (!$id) {
    $acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $isOfflineSync = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
    $expectsJson = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;
    if ($expectsJson) {
        cliente_editar_json_response(422, ['ok' => false, 'message' => 'ID de cliente no proporcionado']);
    }
    $_SESSION['msg'] = 'ID de cliente no proporcionado.';
    header('Location: ../dashboard.php?vista=clientes');
    exit;
}

$operationId = trim((string)($_POST['offline_operation_id'] ?? ''));
$isOfflineSync = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
$baseHash = trim((string)($_POST['offline_base_hash'] ?? ''));
$acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$expectsJson = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($expectsJson) {
        cliente_editar_json_response(405, ['ok' => false, 'message' => 'Metodo no permitido']);
    }
    header('Location: ../dashboard.php?vista=form_cliente&id=' . urlencode((string)$id));
    exit;
}
// Campos requeridos
$codigo_cliente = trim($_POST['codigo_cliente'] ?? '');
$nombre         = trim($_POST['nombre'] ?? '');
$apellido       = trim($_POST['apellido'] ?? '');
$razon_social   = trim($_POST['razon_social'] ?? '');
$dni            = trim($_POST['dni'] ?? '');
$tipo_documento = $_POST['tipo_documento'] ?? 'dni';
$edad_valor     = trim($_POST['edad_valor'] ?? '');
$edad_unidad    = trim($_POST['edad_unidad'] ?? '');
$edad = ($edad_valor !== '' && $edad_unidad !== '') ? (intval($edad_valor) . ' ' . $edad_unidad) : '';
$email          = trim($_POST['email'] ?? '');

// Campos opcionales
$password       = $_POST['password'] ?? '';
$telefono       = trim($_POST['telefono'] ?? '');
$direccion      = trim($_POST['direccion'] ?? '');
$procedencia     = trim($_POST['procedencia'] ?? '');
$sexo           = $_POST['sexo'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$estado         = $_POST['estado'] ?? 'activo';
$descuento      = $_POST['descuento'] ?? null;
$usar_precio_convenio = isset($_POST['usar_precio_convenio']) ? 1 : 0;

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

// Documento
if ($tipo_documento === 'sin_dni') {
    if ($dni === '') {
        $intentos = 0;
        do {
            $dni = (string)random_int(10000000, 99999999);
            $stmt = $pdo->prepare('SELECT id FROM clientes WHERE dni = ? AND id <> ? LIMIT 1');
            $stmt->execute([$dni, $id]);
            $existe = (bool)$stmt->fetchColumn();
            $intentos++;
        } while ($existe && $intentos < 20);

        if ($existe) {
            if ($expectsJson) {
                cliente_editar_json_response(409, ['ok' => false, 'message' => 'No se pudo generar documento provisional unico']);
            }
            $_SESSION['msg'] = 'No se pudo generar un documento provisional único. Intente nuevamente.';
            header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
            exit;
        }
    }
} else {
    if ($dni === '') {
        if ($expectsJson) {
            cliente_editar_json_response(422, ['ok' => false, 'message' => 'Documento requerido']);
        }
        $_SESSION['msg'] = 'Por favor, ingrese el documento.';
        header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
        exit;
    }

    $docDigits = preg_replace('/\D+/', '', $dni) ?? '';
    if ($tipo_documento === 'dni') {
        if (strlen($docDigits) !== 8) {
            if ($expectsJson) {
                cliente_editar_json_response(422, ['ok' => false, 'message' => 'DNI invalido: debe tener 8 digitos']);
            }
            $_SESSION['msg'] = 'DNI invalido: debe tener 8 digitos.';
            header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
            exit;
        }
        $dni = $docDigits;
    } elseif ($tipo_documento === 'ruc') {
        if (strlen($docDigits) !== 11) {
            if ($expectsJson) {
                cliente_editar_json_response(422, ['ok' => false, 'message' => 'RUC invalido: debe tener 11 digitos']);
            }
            $_SESSION['msg'] = 'RUC invalido: debe tener 11 digitos.';
            header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
            exit;
        }
        $dni = $docDigits;
    } elseif ($tipo_documento === 'carnet') {
        if (!preg_match('/^[A-Za-z0-9]{6,20}$/', $dni)) {
            if ($expectsJson) {
                cliente_editar_json_response(422, ['ok' => false, 'message' => 'Carnet invalido: 6 a 20 caracteres alfanumericos']);
            }
            $_SESSION['msg'] = 'Carnet invalido: 6 a 20 caracteres alfanumericos.';
            header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
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

// Validación de requeridos
if (!$codigo_cliente || !$nombre || !$dni || !$email) {
    if ($expectsJson) {
        cliente_editar_json_response(422, ['ok' => false, 'message' => 'Faltan campos obligatorios']);
    }
    $_SESSION['msg'] = 'Por favor, complete todos los campos obligatorios.';
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
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
                cliente_editar_json_response(200, [
                    'ok' => true,
                    'duplicate' => true,
                    'cliente_id' => isset($existingOp['cliente_id']) ? (int)$existingOp['cliente_id'] : (int)$id,
                    'message' => 'Operacion ya aplicada'
                ]);
            }
            header('Location: ../dashboard.php?vista=clientes');
            exit;
        }

        if (!$existingOp) {
            $payloadRaw = json_encode([
                'id' => (int)$id,
                'codigo_cliente' => $codigo_cliente,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'tipo_documento' => $tipo_documento,
                'telefono' => $telefono,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmtInsOp = $pdo->prepare("INSERT INTO clientes_sync_operaciones (operation_id, tipo_operacion, cliente_id, estado, payload_json) VALUES (?, 'editar', ?, 'pendiente', ?)");
            $stmtInsOp->execute([$operationId, (int)$id, $payloadRaw]);
        }
    }

    $stmtCurrent = $pdo->prepare("SELECT codigo_cliente, nombre, apellido, dni, tipo_documento, edad, email, telefono, direccion, sexo, fecha_nacimiento, estado, descuento, procedencia FROM clientes WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmtCurrent->execute([(int)$id]);
    $clienteActual = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
    if (!$clienteActual) {
        if ($operationId !== '') {
            $stmtErr = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        }
        $pdo->commit();
        if ($expectsJson) {
            cliente_editar_json_response(404, ['ok' => false, 'message' => 'Cliente no encontrado']);
        }
        $_SESSION['msg'] = 'Cliente no encontrado.';
        header('Location: ../dashboard.php?vista=clientes');
        exit;
    }

    if ($isOfflineSync && $baseHash !== '') {
        $serverHash = cliente_conflicto_hash($clienteActual);
        if (!hash_equals($serverHash, $baseHash)) {
            if ($operationId !== '') {
                $stmtErr = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
                $stmtErr->execute([$operationId]);
            }
            $pdo->commit();
            cliente_editar_json_response(409, [
                'ok' => false,
                'message' => 'Conflicto de edicion: el paciente fue actualizado en otra sesion. Requiere revision manual.',
                'code' => 'offline_conflict_edit_stale'
            ]);
        }
    }

    // Validar DNI único (excluyendo el registro actual)
    $stmt = $pdo->prepare('SELECT id FROM clientes WHERE dni = ? AND id <> ? LIMIT 1');
    $stmt->execute([$dni, $id]);
    if ($stmt->fetchColumn()) {
        if ($operationId !== '') {
            $stmtErr = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'error' WHERE operation_id = ?");
            $stmtErr->execute([$operationId]);
        }
        $pdo->commit();
        if ($expectsJson) {
            cliente_editar_json_response(409, ['ok' => false, 'message' => 'Documento duplicado']);
        }
        header('Location: ../dashboard.php?vista=form_cliente&id=' . $id . '&error=dni_duplicado');
        exit;
    }

    $set = [
        'codigo_cliente=?',
        'nombre=?',
        'apellido=?',
        'dni=?',
        'tipo_documento=?',
        'edad=?',
        'email=?',
        'telefono=?',
        'direccion=?',
        'sexo=?',
        'fecha_nacimiento=?',
        'estado=?',
        'descuento=?',
        'procedencia=?'
    ];
    $params = [
        $codigo_cliente,
        capitalize($nombre),
        capitalize($apellido),
        $dni,
        $tipo_documento,
        $edad,
        $email,
        $telefono ?: null,
        $direccion ?: null,
        $sexo ?: null,
        $fecha_nacimiento ?: null,
        $estado,
        $descuento !== '' ? $descuento : null,
        $procedencia !== '' ? $procedencia : null,
    ];

    if (cliente_has_column($pdo, 'razon_social')) {
        $set[] = 'razon_social=?';
        $params[] = $razon_social !== '' ? mb_convert_case($razon_social, MB_CASE_TITLE, 'UTF-8') : null;
    }

    if (cliente_has_column($pdo, 'usar_precio_convenio')) {
        $set[] = 'usar_precio_convenio=?';
        $params[] = $usar_precio_convenio;
    }

    if (cliente_has_column($pdo, 'edad_referida_valor')) {
        $set[] = 'edad_referida_valor=?';
        $params[] = $edadReferidaValor;
    }
    if (cliente_has_column($pdo, 'edad_referida_fecha')) {
        $set[] = 'edad_referida_fecha=?';
        $params[] = $edadReferidaFecha;
    }

    if ($password) {
        $set[] = 'password=?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $params[] = $id;
    $sql = 'UPDATE clientes SET ' . implode(', ', $set) . ' WHERE id=?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($operationId !== '') {
        $stmtOk = $pdo->prepare("UPDATE clientes_sync_operaciones SET estado = 'aplicado', cliente_id = ? WHERE operation_id = ?");
        $stmtOk->execute([(int)$id, $operationId]);
    }

    $pdo->commit();

    if ($expectsJson) {
        cliente_editar_json_response(200, [
            'ok' => true,
            'duplicate' => false,
            'cliente_id' => (int)$id,
            'message' => 'Cliente actualizado correctamente'
        ]);
    }

    $_SESSION['msg'] = 'Cliente actualizado correctamente.';

    // Redirección según rol
    if ($_SESSION['rol'] === 'empresa') {
        header('Location: ../dashboard.php?vista=clientes_empresa');
        exit;
    }
    if ($_SESSION['rol'] === 'convenio') {
        header('Location: ../dashboard.php?vista=clientes_convenio');
        exit;
    }

    header('Location: ../dashboard.php?vista=clientes');
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
        cliente_editar_json_response(500, [
            'ok' => false,
            'message' => 'Error al actualizar cliente',
            'error' => $e->getMessage()
        ]);
    }
    $_SESSION['msg'] = 'Error al actualizar: ' . $e->getMessage();
    header('Location: ../dashboard.php?vista=form_cliente&id=' . $id);
    exit;
}
