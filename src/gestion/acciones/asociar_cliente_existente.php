<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../conexion/conexion.php';

$cliente_id = $_POST['cliente_id'] ?? null;
$rol = $_SESSION['rol'] ?? '';
$empresa_id = $_SESSION['empresa_id'] ?? null;
$convenio_id = $_SESSION['convenio_id'] ?? null;

if ($cliente_id && $rol) {
    if ($rol === 'empresa' && $empresa_id) {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM empresa_cliente WHERE empresa_id = :empresa_id AND cliente_id = :cliente_id LIMIT 1");
        $stmtCheck->execute([':empresa_id' => $empresa_id, ':cliente_id' => $cliente_id]);
        if (!$stmtCheck->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO empresa_cliente (empresa_id, cliente_id) VALUES (:empresa_id, :cliente_id)");
            $stmt->execute([':empresa_id' => $empresa_id, ':cliente_id' => $cliente_id]);
        }
    } elseif ($rol === 'convenio' && $convenio_id) {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM convenio_cliente WHERE convenio_id = :convenio_id AND cliente_id = :cliente_id LIMIT 1");
        $stmtCheck->execute([':convenio_id' => $convenio_id, ':cliente_id' => $cliente_id]);
        if (!$stmtCheck->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO convenio_cliente (convenio_id, cliente_id) VALUES (:convenio_id, :cliente_id)");
            $stmt->execute([':convenio_id' => $convenio_id, ':cliente_id' => $cliente_id]);
        }
    }
    $_SESSION['msg'] = 'Cliente asociado correctamente.';
}

header('Location: dashboard.php?vista=buscar_cliente');
exit;
