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

$presets = ui_theme_predefined();
$temaKey = strtolower(trim((string)($_POST['tema_ui_activo'] ?? '')));
$empresaCfgId = (int)($_POST['empresa_cfg_id'] ?? 0);
$portalEstilo = strtolower(trim((string)($_POST['portal_publico_estilo'] ?? 'clasico')));
if ($portalEstilo === 'premium') {
    $portalEstilo = 'premium_a';
}
if (!in_array($portalEstilo, ['clasico', 'premium_a', 'premium_b'], true)) {
    $portalEstilo = 'clasico';
}
if ($temaKey === '' || !isset($presets[$temaKey])) {
    $_SESSION['msg'] = 'Selecciona una apariencia valida.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_personalizacion_engineer');
    exit;
}

$p = $presets[$temaKey];

$cols = [];
try {
    $stmtCols = $pdo->query('SHOW COLUMNS FROM config_empresa');
    $rowsCols = $stmtCols ? $stmtCols->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rowsCols as $colRow) {
        if (!empty($colRow['Field'])) {
            $cols[] = (string)$colRow['Field'];
        }
    }
} catch (Throwable $e) {
    $_SESSION['msg'] = 'No se pudo verificar la estructura de config_empresa.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_personalizacion_engineer');
    exit;
}

if (!in_array('portal_publico_estilo', $cols, true)) {
    try {
        $pdo->exec("ALTER TABLE config_empresa ADD COLUMN portal_publico_estilo VARCHAR(20) NULL DEFAULT 'clasico' AFTER portal_publico_enable");
        $stmtCols = $pdo->query('SHOW COLUMNS FROM config_empresa');
        $rowsCols = $stmtCols ? $stmtCols->fetchAll(PDO::FETCH_ASSOC) : [];
        $cols = [];
        foreach ($rowsCols as $colRow) {
            if (!empty($colRow['Field'])) {
                $cols[] = (string)$colRow['Field'];
            }
        }
    } catch (Throwable $e) {
    }
}

$setParts = [];
$params = [];

if (in_array('color_principal', $cols, true)) {
    $setParts[] = 'color_principal = ?';
    $params[] = $p['primary'];
}
if (in_array('color_secundario', $cols, true)) {
    $setParts[] = 'color_secundario = ?';
    $params[] = $p['secondary'];
}
if (in_array('color_footer', $cols, true)) {
    $setParts[] = 'color_footer = ?';
    $params[] = $p['footer'];
}
if (in_array('color_botones', $cols, true)) {
    $setParts[] = 'color_botones = ?';
    $params[] = $p['button'];
}
if (in_array('color_texto', $cols, true)) {
    $setParts[] = 'color_texto = ?';
    $params[] = $p['text'];
}
if (in_array('tema_ui_activo', $cols, true)) {
    $setParts[] = 'tema_ui_activo = ?';
    $params[] = $temaKey;
}
if (in_array('portal_publico_estilo', $cols, true)) {
    $setParts[] = 'portal_publico_estilo = ?';
    $params[] = $portalEstilo;
}

if (empty($setParts)) {
    $_SESSION['msg'] = 'No hay columnas de personalizacion disponibles en config_empresa.';
    header('Location: ' . BASE_URL . 'dashboard.php?vista=config_personalizacion_engineer');
    exit;
}

$empresaObjetivo = ui_theme_fetch_company_config($pdo, $empresaCfgId > 0 ? $empresaCfgId : null);
$empresaTargetId = (int)($empresaObjetivo['id'] ?? 0);

try {
    if ($empresaTargetId > 0) {
        $params[] = $empresaTargetId;
        $sql = 'UPDATE config_empresa SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
    } else {
        $insertCols = [];
        $insertVals = [];
        $insertParams = [];

        foreach ($setParts as $idx => $setPart) {
            $colName = trim((string)explode('=', $setPart)[0]);
            $insertCols[] = $colName;
            $insertVals[] = '?';
            $insertParams[] = $params[$idx];
        }

        $ins = $pdo->prepare('INSERT INTO config_empresa (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')');
        $ins->execute($insertParams);
    }

    $_SESSION['msg'] = 'Apariencia visual aplicada correctamente.';
} catch (Throwable $e) {
    $_SESSION['msg'] = 'No se pudo guardar la apariencia visual.';
}

$redirect = BASE_URL . 'dashboard.php?vista=config_personalizacion_engineer';
if ($empresaTargetId > 0) {
    $redirect .= '&empresa_cfg_id=' . $empresaTargetId;
}
header('Location: ' . $redirect);
exit;
