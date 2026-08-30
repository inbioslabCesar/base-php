<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../conexion/conexion.php';
require_once __DIR__ . '/../../servicios/documento_lookup.php';

header('Content-Type: application/json; charset=UTF-8');

$rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));
$rolesPermitidos = ['admin', 'recepcionista', 'empresa', 'convenio'];
if (!in_array($rol, $rolesPermitidos, true)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'status' => 'forbidden',
        'message' => 'No autorizado para consultar documentos.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$scope = strtolower(trim((string)($_GET['scope'] ?? 'cliente')));
$allowedScopes = ['cliente', 'empresa', 'convenio', 'buscar_paciente', 'buscar_cliente'];
if (!in_array($scope, $allowedScopes, true)) {
    $scope = 'cliente';
}

$documento = (string)($_GET['documento'] ?? '');
$result = documento_lookup_consultar($pdo, $scope === 'buscar_paciente' || $scope === 'buscar_cliente' ? 'cliente' : $scope, $documento);

$statusCode = 200;
if (empty($result['ok']) && ($result['status'] ?? '') === 'formato_no_soportado') {
    $statusCode = 422;
}
if (empty($result['ok']) && ($result['status'] ?? '') === 'forbidden') {
    $statusCode = 403;
}
if (empty($result['ok']) && ($result['status'] ?? '') === 'no_encontrado') {
    $statusCode = 404;
}
if (!empty($result['http_status']) && is_int($result['http_status'])) {
    $statusCode = $result['http_status'];
}

http_response_code($statusCode);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
