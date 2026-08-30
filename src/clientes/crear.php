<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/ui_theme.php';
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
$procedencia       = trim($_POST['procedencia'] ?? '');
$rol_creador       = $_SESSION['rol'] ?? 'desconocido';
$operationId       = trim((string)($_POST['offline_operation_id'] ?? ''));
$isOfflineSync     = isset($_POST['offline_sync']) && (string)$_POST['offline_sync'] === '1';
$acceptHeader      = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$expectsJson       = $isOfflineSync || strpos($acceptHeader, 'application/json') !== false;

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

// Nuevos campos para empresa/convenio y tipo_registro
$empresa_nombre   = $_SESSION['empresa_nombre'] ?? null;
$convenio_nombre = $_SESSION['convenio_nombre'] ?? null;
$tipo_registro   = 'cliente'; // Valor por defecto

if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_nombre'])) {
    $empresa_nombre = $_SESSION['empresa_nombre'];
    $tipo_registro = 'empresa';
}
if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_nombre'])) {
    $convenio_nombre = $_SESSION['convenio_nombre'];
    $tipo_registro = 'convenio';
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

if (!$codigo_cliente || !$nombre || !$apellido || !$email || !$password) {
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
    $pdo->beginTransaction();

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

    $stmt = $pdo->prepare(
        "INSERT INTO clientes 
        (codigo_cliente, nombre, apellido, dni, tipo_documento, edad, email, password, telefono, direccion, sexo, fecha_nacimiento, estado, descuento, procedencia, rol_creador, empresa_nombre, convenio_nombre, tipo_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
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
    ]);

    $id_cliente_nuevo = $pdo->lastInsertId();

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
            'message' => 'Cliente registrado correctamente'
        ]);
    }

    // Asociación automática y redirección según rol
    if ($_SESSION['rol'] === 'empresa' && isset($_SESSION['empresa_id'])) {
        $stmt = $pdo->prepare("INSERT INTO empresa_cliente (empresa_id, cliente_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['empresa_id'], $id_cliente_nuevo]);
        $_SESSION['msg'] = 'Cliente registrado y asociado correctamente.';
        header('Location: ../dashboard.php?vista=clientes_empresa');
        exit;
    }
    if ($_SESSION['rol'] === 'convenio' && isset($_SESSION['convenio_id'])) {
        $stmt = $pdo->prepare("INSERT INTO convenio_cliente (convenio_id, cliente_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['convenio_id'], $id_cliente_nuevo]);
        $_SESSION['msg'] = 'Cliente registrado y asociado correctamente.';
        header('Location: ../dashboard.php?vista=clientes_convenio');
        exit;
    }

    $_SESSION['msg'] = 'Cliente registrado correctamente.';
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
