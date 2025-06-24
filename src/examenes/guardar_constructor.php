<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

// Validación básica
if (
    empty($_POST['nombre']) ||
    empty($_POST['area']) ||
    empty($_POST['metodologia']) ||
    empty($_POST['parametros']['nombre'])
) {
    $_SESSION['mensaje_error'] = 'Todos los campos son obligatorios y debe agregar al menos un parámetro.';
    header('Location: dashboard.php?vista=constructor');
    exit;
}

$nombre = trim($_POST['nombre']);
$area = trim($_POST['area']);
$metodologia = trim($_POST['metodologia']);

// Procesar parámetros
$parametros = [];
$nombres = $_POST['parametros']['parametro'];
$unidades = $_POST['parametros']['unidad'];
$valores = $_POST['parametros']['valor_referencia'];
$calculados = $_POST['parametros']['calculado'];
$formulas = $_POST['parametros']['formula'];

for ($i = 0; $i < count($nombres); $i++) {
    if (trim($nombres[$i]) !== '') {
        $parametros[] = [
            'parametro' => trim($nombres[$i]),
            'unidad' => trim($unidades[$i]),
            'valor' => trim($valores[$i]),
            'calculado' => $calculados[$i] == '1' ? true : false,
            'formula' => trim($formulas[$i])
        ];
    }
}
if (empty($parametros)) {
    $_SESSION['mensaje_error'] = 'Debe agregar al menos un parámetro válido.';
    header('Location: dashboard.php?vista=constructor');
    exit;
}

$parametros_json = json_encode($parametros, JSON_UNESCAPED_UNICODE);

try {
    $stmt = $pdo->prepare("INSERT INTO examenes (nombre, area, metodologia, adicional) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $area, $metodologia, $parametros_json]);
    $_SESSION['mensaje_exito'] = 'Examen guardado exitosamente.';
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = 'Error al guardar el examen: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=constructor');
exit;
