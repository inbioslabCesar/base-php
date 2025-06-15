<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $area = trim($_POST['area']);
    $metodologia = trim($_POST['metodologia']);
    $parametros = $_POST['parametros'] ?? [];

    if (!$nombre || !$area || !$metodologia || empty($parametros)) {
        $_SESSION['error'] = "Completa todos los campos obligatorios.";
        header('Location: dashboard.php?vista=constructor');
        exit;
    }

    // Procesar parámetros y fórmulas
    foreach ($parametros as &$p) {
        $p['calculado'] = isset($p['calculado']) ? true : false;
        if (!$p['calculado']) {
            $p['formula'] = null;
        }
    }

    $adicional = json_encode(['parametros' => $parametros], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("INSERT INTO examenes (nombre, area, metodologia, adicional, vigente) VALUES (?, ?, ?, ?, 1)");
    if ($stmt->execute([$nombre, $area, $metodologia, $adicional])) {
        $_SESSION['exito'] = "Examen/perfil creado correctamente.";
        header('Location: dashboard.php?vista=listar_examenes');
        exit;
    } else {
        $_SESSION['error'] = "Error al guardar el examen/perfil.";
        header('Location: dashboard.php?vista=constructor');
        exit;
    }
} else {
    header('Location: dashboard.php?vista=constructor');
    exit;
}
