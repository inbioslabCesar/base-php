<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/ui_theme.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower(trim((string)($_SESSION['rol'] ?? '')));
if ($rolActual !== 'engineer') {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Acceso restringido.</div></div>";
    exit;
}

$modo_operativo = strtoupper(trim((string)($_POST['modo_operativo'] ?? 'PARTICULAR')));
$empresaCfgId = (int)($_POST['empresa_cfg_id'] ?? 0);
if (!in_array($modo_operativo, ['PARTICULAR', 'SIS', 'MIXTO'], true)) {
    $modo_operativo = 'PARTICULAR';
}
$portal_publico_enable = isset($_POST['portal_publico_enable']) ? 1 : 0;

$cols = [];
try {
    $stmtCols = $pdo->query("SHOW COLUMNS FROM config_empresa");
    $rowsCols = $stmtCols ? $stmtCols->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rowsCols as $colRow) {
        if (!empty($colRow['Field'])) {
            $cols[] = (string)$colRow['Field'];
        }
    }
} catch (Throwable $e) {
    $_SESSION['msg'] = 'No se pudo verificar la estructura de config_empresa.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_operacion_engineer');
    exit;
}

if (!in_array('modo_operativo', $cols, true) || !in_array('portal_publico_enable', $cols, true)) {
    $_SESSION['msg'] = 'Faltan columnas operativas en config_empresa. Ejecuta migraciones SIS.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_operacion_engineer');
    exit;
}

$empresaObjetivo = ui_theme_fetch_company_config($pdo, $empresaCfgId > 0 ? $empresaCfgId : null);
$empresaTargetId = (int)($empresaObjetivo['id'] ?? 0);

try {
    if ($empresaTargetId > 0) {
        $upd = $pdo->prepare("UPDATE config_empresa SET modo_operativo = ?, portal_publico_enable = ? WHERE id = ?");
        $upd->execute([$modo_operativo, $portal_publico_enable, $empresaTargetId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO config_empresa (modo_operativo, portal_publico_enable) VALUES (?, ?)");
        $ins->execute([$modo_operativo, $portal_publico_enable]);
        $empresaTargetId = (int)$pdo->lastInsertId();
    }
    $_SESSION['msg'] = 'Configuracion operativa actualizada correctamente.';
} catch (Throwable $e) {
    $_SESSION['msg'] = 'No se pudo guardar la configuracion operativa.';
}

$redirect = BASE_URL . 'dashboard.php?vista=config_operacion_engineer';
if ($empresaTargetId > 0) {
    $redirect .= '&empresa_cfg_id=' . $empresaTargetId;
}
header('Location: ' . $redirect);
exit;
