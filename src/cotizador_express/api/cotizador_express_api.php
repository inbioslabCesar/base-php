<?php
// Acción JSON del Cotizador Express: búsqueda de pacientes (solo lectura), historial y plantillas.
require_once __DIR__ . '/../funciones/cotizador_express_servicio.php';

date_default_timezone_set('America/Lima');
header('Content-Type: application/json; charset=utf-8');

$responder = static function (array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

$op = (string)($_GET['op'] ?? $_POST['op'] ?? '');
$esPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$rolActual = strtolower((string)($_SESSION['rol'] ?? ''));
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$ctx = cotExpContexto($pdo);

if ($op === 'buscar_paciente') {
    $q = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($q) < 2) {
        $responder(['items' => []]);
    }
    $qDocumento = preg_replace('/\D+/', '', $q) ?? '';

    $tieneCelular = cotExpHasColumn($pdo, 'clientes', 'celular');
    $tieneDni = cotExpHasColumn($pdo, 'clientes', 'dni');
    $selCelular = $tieneCelular ? "COALESCE(celular, '')" : "''";
    $selDni = $tieneDni ? "COALESCE(dni, '')" : "''";

    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $condiciones = ["CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellido, '')) LIKE :q1", "COALESCE(codigo_cliente, '') LIKE :q2"];
    $params = [':q1' => $like, ':q2' => $like];
    if ($tieneDni) {
        $condiciones[] = "COALESCE(dni, '') LIKE :q3";
        $params[':q3'] = $like;
        if ($qDocumento !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(COALESCE(dni, ''), '.', ''), '-', ''), ' ', '') LIKE :q4";
            $params[':q4'] = '%' . $qDocumento . '%';
        }
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id, TRIM(CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellido, ''))) AS nombre, {$selDni} AS dni,
                    COALESCE(telefono, '') AS telefono, {$selCelular} AS celular
             FROM clientes
             WHERE " . implode(' OR ', $condiciones) . '
             ORDER BY nombre ASC
             LIMIT 12'
        );
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $tel = cotExpNormalizarTelefono((string)$r['celular']);
            if ($tel === '') {
                $tel = cotExpNormalizarTelefono((string)$r['telefono']);
            }
            $items[] = ['id' => (int)$r['id'], 'nombre' => (string)$r['nombre'], 'dni' => (string)$r['dni'], 'telefono' => $tel];
        }
        $responder(['items' => $items]);
    } catch (Throwable $e) {
        $responder(['error' => 'No se pudo buscar pacientes'], 500);
    }
}

if (!$esPost) {
    $responder(['error' => 'Operación no válida'], 400);
}

if ($op === 'guardar') {
    if (!$ctx['muestra_precios']) {
        $responder(['error' => 'El historial solo aplica al modo con precios'], 409);
    }
    if (!cotExpHistorialDisponible($pdo)) {
        $responder(['error' => 'Historial no disponible: falta ejecutar la migración'], 409);
    }

    $calc = cotExpCalcular(
        $pdo,
        true,
        json_decode((string)($_POST['items'] ?? '[]'), true),
        (string)($_POST['perfil'] ?? 'particular'),
        (float)($_POST['desc_extra'] ?? 0)
    );
    if (empty($calc['lineas'])) {
        $responder(['error' => 'Agrega al menos un examen'], 400);
    }

    $canal = (string)($_POST['canal'] ?? '');
    try {
        $res = cotExpGuardar($pdo, $calc, [
            'modo' => $ctx['modo'],
            'destinatario' => trim((string)($_POST['destinatario'] ?? '')),
            'telefono' => preg_replace('/\D+/', '', (string)($_POST['telefono'] ?? '')) ?? '',
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'vigencia' => (int)($_POST['vigencia'] ?? 7),
            'indicaciones' => ($_POST['indicaciones'] ?? '1') === '1',
            'canal' => in_array($canal, ['copiar', 'whatsapp', 'ticket', 'pdf'], true) ? $canal : '',
            'usuario_id' => $usuarioId,
            'rol' => $rolActual,
        ]);
        $responder(['ok' => true] + $res + ['total' => $calc['total']]);
    } catch (Throwable $e) {
        error_log('cotizador_express guardar: ' . $e->getMessage());
        $responder(['error' => 'No se pudo guardar en el historial'], 500);
    }
}

if ($op === 'canal') {
    $id = (int)($_POST['id'] ?? 0);
    $canal = (string)($_POST['canal'] ?? '');
    if ($id <= 0 || !in_array($canal, ['copiar', 'whatsapp', 'ticket', 'pdf'], true) || !cotExpHistorialDisponible($pdo)) {
        $responder(['error' => 'Solicitud inválida'], 400);
    }
    $stmt = $pdo->prepare('SELECT canales FROM cotizaciones_express WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $actual = $stmt->fetchColumn();
    if ($actual === false) {
        $responder(['error' => 'No encontrada'], 404);
    }
    $lista = array_values(array_unique(array_filter(array_merge(explode(',', (string)$actual), [$canal]))));
    $pdo->prepare('UPDATE cotizaciones_express SET canales = ?, ultimo_envio_en = NOW() WHERE id = ?')->execute([implode(',', $lista), $id]);
    $responder(['ok' => true]);
}

if ($op === 'anular') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0 || !cotExpHistorialDisponible($pdo)) {
        $responder(['error' => 'Solicitud inválida'], 400);
    }
    $stmt = $pdo->prepare("UPDATE cotizaciones_express SET estado = 'anulada', anulada_en = NOW(), anulada_por = ? WHERE id = ? AND estado = 'emitida'");
    $stmt->execute([$usuarioId ?: null, $id]);
    $responder(['ok' => $stmt->rowCount() > 0]);
}

if ($op === 'plantilla_guardar' || $op === 'plantilla_restaurar') {
    if ($rolActual !== 'admin') {
        $responder(['error' => 'Solo el administrador puede editar la plantilla'], 403);
    }
    if (!app_database_has_table($pdo, 'cotizador_express_plantillas')) {
        $responder(['error' => 'Plantillas no disponibles: falta ejecutar la migración'], 409);
    }
    $tipo = $ctx['muestra_precios'] ? 'precios' : 'info';

    if ($op === 'plantilla_restaurar') {
        $pdo->prepare('DELETE FROM cotizador_express_plantillas WHERE tipo = ?')->execute([$tipo]);
        $responder(['ok' => true, 'plantilla' => cotExpPlantillaDefecto($tipo)]);
    }

    $texto = str_replace("\r\n", "\n", (string)($_POST['plantilla'] ?? ''));
    if (mb_strlen($texto) > 4000 || strpos($texto, '{detalle}') === false) {
        $responder(['error' => 'La plantilla debe incluir {detalle} y tener máximo 4000 caracteres'], 400);
    }
    $stmt = $pdo->prepare('INSERT INTO cotizador_express_plantillas (tipo, plantilla, actualizado_por) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE plantilla = VALUES(plantilla), actualizado_por = VALUES(actualizado_por)');
    $stmt->execute([$tipo, $texto, $usuarioId ?: null]);
    $responder(['ok' => true]);
}

$responder(['error' => 'Operación no válida'], 400);
