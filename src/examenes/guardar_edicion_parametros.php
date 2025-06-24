<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$id = $_POST['id'] ?? null;
if (!$id) {
    $_SESSION['mensaje_error'] = 'ID de examen no especificado.';
    header('Location: dashboard.php?vista=examenes');
    exit;
}

$parametro = $_POST['parametros']['parametro'];
$unidades = $_POST['parametros']['unidad'];
$valores = $_POST['parametros']['valor'];
$calculados = $_POST['parametros']['calculado'];
$formulas = $_POST['parametros']['formula'];

$parametros = [];
for ($i = 0; $i < count($parametro); $i++) {
    if (trim($parametro[$i]) !== '') {
        $parametros[] = [
            'parametro' => trim($parametro[$i]),
            'unidad' => trim($unidades[$i]),
            'valor' => trim($valores[$i]),
            'calculado' => $calculados[$i] == '1' ? true : false,
            'formula' => trim($formulas[$i])
        ];
    }
}
$parametros_json = json_encode($parametros, JSON_UNESCAPED_UNICODE);

try {
    $stmt = $pdo->prepare("UPDATE examenes SET adicional = ? WHERE id = ?");
    $stmt->execute([$parametros_json, $id]);
    $_SESSION['mensaje_exito'] = 'Parámetros actualizados correctamente.';
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = 'Error al actualizar: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=editar_parametros&id=' . $id);
exit;
