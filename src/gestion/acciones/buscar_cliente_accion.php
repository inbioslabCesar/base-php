<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../conexion/conexion.php';

$dni = trim($_POST['dni'] ?? '');
$rol = $_SESSION['rol'] ?? '';
$empresa_id = $_SESSION['empresa_id'] ?? null;
$convenio_id = $_SESSION['convenio_id'] ?? null;

if ($dni && $rol) {
    // 1. Buscar cliente por DNI
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE dni = :dni");
    $stmt->execute([':dni' => $dni]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
    // Verificar relación según el rol
    if ($rol === 'empresa' && $empresa_id) {
        $stmt2 = $pdo->prepare("SELECT * FROM empresa_cliente WHERE empresa_id = :empresa_id AND cliente_id = :cliente_id");
        $stmt2->execute([':empresa_id' => $empresa_id, ':cliente_id' => $cliente['id']]);
        $relacion = $stmt2->fetch(PDO::FETCH_ASSOC);
    } elseif ($rol === 'convenio' && $convenio_id) {
        $stmt2 = $pdo->prepare("SELECT * FROM convenio_cliente WHERE convenio_id = :convenio_id AND cliente_id = :cliente_id");
        $stmt2->execute([':convenio_id' => $convenio_id, ':cliente_id' => $cliente['id']]);
        $relacion = $stmt2->fetch(PDO::FETCH_ASSOC);
    } else {
        $relacion = false;
    }

    if ($relacion) {
        $_SESSION['cliente_encontrado'] = $cliente;
    } else {
        // Cliente existe en la base pero no asociado
        $_SESSION['cliente_para_asociar'] = $cliente;
    }
} else {
    $_SESSION['cliente_no_encontrado'] = true;
    $_SESSION['dni_buscado'] = $dni;
}

}

header('Location: dashboard.php?vista=buscar_cliente');
exit;
