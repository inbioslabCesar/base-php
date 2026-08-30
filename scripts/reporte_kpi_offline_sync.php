<?php
require_once __DIR__ . '/../src/conexion/conexion.php';

/**
 * Reporte diario de KPI de sincronizacion offline por modulo.
 * Uso CLI:
 *   php scripts/reporte_kpi_offline_sync.php
 *   php scripts/reporte_kpi_offline_sync.php --date=2026-08-22
 *   php scripts/reporte_kpi_offline_sync.php --date=2026-08-22 --json
 */

function getCliOption(array $argv, string $name, ?string $default = null): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
        if ($arg === '--' . $name) {
            return '1';
        }
    }
    return $default;
}

function normalizeDate(?string $rawDate): string
{
    if ($rawDate === null || trim($rawDate) === '') {
        return date('Y-m-d');
    }
    $rawDate = trim($rawDate);
    $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
    if ($dt && $dt->format('Y-m-d') === $rawDate) {
        return $rawDate;
    }
    return date('Y-m-d');
}

function toInt($value): int
{
    return (int)($value ?? 0);
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

function getModuleStats(PDO $pdo, string $tableName, string $date): array
{
    if (!tableExists($pdo, $tableName)) {
        return [
            'exists' => false,
            'total' => 0,
            'aplicado' => 0,
            'pendiente' => 0,
            'error' => 0,
            'duplicados' => 0,
            'aplicado_pct' => 0.0,
            'error_pct' => 0.0,
            'duplicado_pct' => 0.0,
        ];
    }

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'aplicado' THEN 1 ELSE 0 END) AS aplicado,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) AS error_count,
                COUNT(DISTINCT operation_id) AS unique_ops
            FROM {$tableName}
            WHERE DATE(created_at) = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $total = toInt($row['total'] ?? 0);
    $aplicado = toInt($row['aplicado'] ?? 0);
    $pendiente = toInt($row['pendiente'] ?? 0);
    $error = toInt($row['error_count'] ?? 0);
    $uniqueOps = toInt($row['unique_ops'] ?? 0);
    $duplicados = max(0, $total - $uniqueOps);

    $base = $total > 0 ? (float)$total : 1.0;

    return [
        'exists' => true,
        'total' => $total,
        'aplicado' => $aplicado,
        'pendiente' => $pendiente,
        'error' => $error,
        'duplicados' => $duplicados,
        'aplicado_pct' => round(($aplicado / $base) * 100, 2),
        'error_pct' => round(($error / $base) * 100, 2),
        'duplicado_pct' => round(($duplicados / $base) * 100, 2),
    ];
}

function getModuleStatsRange(PDO $pdo, string $tableName, string $startDate, string $endDate): array
{
    if (!tableExists($pdo, $tableName)) {
        return [
            'exists' => false,
            'total' => 0,
            'aplicado' => 0,
            'pendiente' => 0,
            'error' => 0,
            'duplicados' => 0,
            'aplicado_pct' => 0.0,
            'error_pct' => 0.0,
            'duplicado_pct' => 0.0,
        ];
    }

    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'aplicado' THEN 1 ELSE 0 END) AS aplicado,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) AS error_count,
                COUNT(DISTINCT operation_id) AS unique_ops
            FROM {$tableName}
            WHERE DATE(created_at) BETWEEN ? AND ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $total = toInt($row['total'] ?? 0);
    $aplicado = toInt($row['aplicado'] ?? 0);
    $pendiente = toInt($row['pendiente'] ?? 0);
    $error = toInt($row['error_count'] ?? 0);
    $uniqueOps = toInt($row['unique_ops'] ?? 0);
    $duplicados = max(0, $total - $uniqueOps);
    $base = $total > 0 ? (float)$total : 1.0;

    return [
        'exists' => true,
        'total' => $total,
        'aplicado' => $aplicado,
        'pendiente' => $pendiente,
        'error' => $error,
        'duplicados' => $duplicados,
        'aplicado_pct' => round(($aplicado / $base) * 100, 2),
        'error_pct' => round(($error / $base) * 100, 2),
        'duplicado_pct' => round(($duplicados / $base) * 100, 2),
    ];
}

function buildReportFromStats(array $stats): array
{
    $global = [
        'total' => 0,
        'aplicado' => 0,
        'pendiente' => 0,
        'error' => 0,
        'duplicados' => 0,
        'aplicado_pct' => 0.0,
        'error_pct' => 0.0,
        'duplicado_pct' => 0.0,
    ];

    foreach ($stats as $moduleStats) {
        $global['total'] += (int)($moduleStats['total'] ?? 0);
        $global['aplicado'] += (int)($moduleStats['aplicado'] ?? 0);
        $global['pendiente'] += (int)($moduleStats['pendiente'] ?? 0);
        $global['error'] += (int)($moduleStats['error'] ?? 0);
        $global['duplicados'] += (int)($moduleStats['duplicados'] ?? 0);
    }

    $globalBase = $global['total'] > 0 ? (float)$global['total'] : 1.0;
    $global['aplicado_pct'] = round(($global['aplicado'] / $globalBase) * 100, 2);
    $global['error_pct'] = round(($global['error'] / $globalBase) * 100, 2);
    $global['duplicado_pct'] = round(($global['duplicados'] / $globalBase) * 100, 2);

    return [
        'global' => $global,
        'health_flags' => buildHealthFlags($global),
    ];
}

function buildHealthFlags(array $global): array
{
    $hasData = ((int)($global['total'] ?? 0)) > 0;

    $flags = [];
    $flags[] = [
        'kpi' => 'operaciones_fallidas_pct',
        'valor' => $global['error_pct'],
        'objetivo' => '< 1.00',
        'ok' => $hasData ? ($global['error_pct'] < 1.0) : true,
        'status' => $hasData ? null : 'SIN_DATOS',
    ];
    $flags[] = [
        'kpi' => 'duplicados_pct',
        'valor' => $global['duplicado_pct'],
        'objetivo' => '< 0.20',
        'ok' => $hasData ? ($global['duplicado_pct'] < 0.2) : true,
        'status' => $hasData ? null : 'SIN_DATOS',
    ];
    $flags[] = [
        'kpi' => 'sincronizacion_aplicada_pct',
        'valor' => $global['aplicado_pct'],
        'objetivo' => '>= 95.00',
        'ok' => $hasData ? ($global['aplicado_pct'] >= 95.0) : true,
        'status' => $hasData ? null : 'SIN_DATOS',
    ];
    return $flags;
}

function formatPct(float $value): string
{
    return number_format($value, 2, '.', '') . '%';
}

function normalizeOutputDir(?string $rawDir): string
{
    if ($rawDir === null || trim($rawDir) === '') {
        return __DIR__ . '/../docs/offline_kpi_reports';
    }

    $rawDir = trim($rawDir);
    if (preg_match('#^[A-Za-z]:\\\\#', $rawDir) || strpos($rawDir, '/') === 0 || strpos($rawDir, '\\\\') === 0) {
        return $rawDir;
    }

    return __DIR__ . '/../' . ltrim(str_replace('\\', '/', $rawDir), '/');
}

function saveDailyReport(string $outputDir, string $date, array $report): array
{
    $dir = rtrim($outputDir, "\\/");
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        return ['ok' => false, 'path' => $dir, 'error' => 'No se pudo crear directorio'];
    }
    $resolvedDir = realpath($dir);
    if ($resolvedDir !== false && $resolvedDir !== null) {
        $dir = $resolvedDir;
    }

    $jsonPath = $dir . DIRECTORY_SEPARATOR . 'kpi_offline_' . $date . '.json';
    $mdPath = $dir . DIRECTORY_SEPARATOR . 'kpi_offline_' . $date . '.md';

    $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || @file_put_contents($jsonPath, $json . PHP_EOL) === false) {
        return ['ok' => false, 'path' => $jsonPath, 'error' => 'No se pudo escribir JSON'];
    }

    $line = str_repeat('-', 86);
    $md = [];
    $md[] = '# KPI Offline Sync ' . $date;
    $md[] = '';
    $md[] = '- Generado: ' . ($report['generated_at'] ?? '');
    $md[] = '';
    $md[] = '## Modulos';
    $md[] = '';
    $md[] = '| Modulo | Total | Aplicado | Pendiente | Error | Duplicado | Apl% | Err% | Dup% |';
    $md[] = '|---|---:|---:|---:|---:|---:|---:|---:|---:|';
    foreach (($report['modules'] ?? []) as $module => $row) {
        $label = $module . (!empty($row['exists']) ? '' : ' (N/A)');
        $md[] = '| ' . $label
            . ' | ' . (int)($row['total'] ?? 0)
            . ' | ' . (int)($row['aplicado'] ?? 0)
            . ' | ' . (int)($row['pendiente'] ?? 0)
            . ' | ' . (int)($row['error'] ?? 0)
            . ' | ' . (int)($row['duplicados'] ?? 0)
            . ' | ' . formatPct((float)($row['aplicado_pct'] ?? 0))
            . ' | ' . formatPct((float)($row['error_pct'] ?? 0))
            . ' | ' . formatPct((float)($row['duplicado_pct'] ?? 0))
            . ' |';
    }
    $md[] = '';
    $md[] = '## Global';
    $md[] = '';
    $g = $report['global'] ?? [];
    $md[] = '- Total: ' . (int)($g['total'] ?? 0);
    $md[] = '- Aplicado: ' . (int)($g['aplicado'] ?? 0) . ' (' . formatPct((float)($g['aplicado_pct'] ?? 0)) . ')';
    $md[] = '- Pendiente: ' . (int)($g['pendiente'] ?? 0);
    $md[] = '- Error: ' . (int)($g['error'] ?? 0) . ' (' . formatPct((float)($g['error_pct'] ?? 0)) . ')';
    $md[] = '- Duplicado: ' . (int)($g['duplicados'] ?? 0) . ' (' . formatPct((float)($g['duplicado_pct'] ?? 0)) . ')';
    $md[] = '';
    $md[] = '## Semaforo';
    foreach (($report['health_flags'] ?? []) as $flag) {
        $status = !empty($flag['status']) ? (string)$flag['status'] : (!empty($flag['ok']) ? 'OK' : 'ALERTA');
        $md[] = '- ' . (string)($flag['kpi'] ?? '') . ': ' . formatPct((float)($flag['valor'] ?? 0)) . ' (objetivo ' . (string)($flag['objetivo'] ?? '-') . ') => ' . $status;
    }

    if (@file_put_contents($mdPath, implode(PHP_EOL, $md) . PHP_EOL) === false) {
        return ['ok' => false, 'path' => $mdPath, 'error' => 'No se pudo escribir Markdown'];
    }

    return ['ok' => true, 'json_path' => $jsonPath, 'md_path' => $mdPath, 'line' => $line];
}

function saveWeeklyReport(string $outputDir, string $startDate, string $endDate, array $report): array
{
    $dir = rtrim($outputDir, "\\/") . DIRECTORY_SEPARATOR . 'weekly';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        return ['ok' => false, 'path' => $dir, 'error' => 'No se pudo crear directorio semanal'];
    }
    $resolvedDir = realpath($dir);
    if ($resolvedDir !== false && $resolvedDir !== null) {
        $dir = $resolvedDir;
    }

    $suffix = str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate);
    $jsonPath = $dir . DIRECTORY_SEPARATOR . 'kpi_offline_weekly_' . $suffix . '.json';
    $mdPath = $dir . DIRECTORY_SEPARATOR . 'kpi_offline_weekly_' . $suffix . '.md';

    $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || @file_put_contents($jsonPath, $json . PHP_EOL) === false) {
        return ['ok' => false, 'path' => $jsonPath, 'error' => 'No se pudo escribir JSON semanal'];
    }

    $md = [];
    $md[] = '# KPI Offline Sync Semanal ' . $startDate . ' a ' . $endDate;
    $md[] = '';
    $md[] = '- Generado: ' . ($report['generated_at'] ?? '');
    $md[] = '';
    $md[] = '## Modulos';
    $md[] = '';
    $md[] = '| Modulo | Total | Aplicado | Pendiente | Error | Duplicado | Apl% | Err% | Dup% |';
    $md[] = '|---|---:|---:|---:|---:|---:|---:|---:|---:|';
    foreach (($report['modules'] ?? []) as $module => $row) {
        $label = $module . (!empty($row['exists']) ? '' : ' (N/A)');
        $md[] = '| ' . $label
            . ' | ' . (int)($row['total'] ?? 0)
            . ' | ' . (int)($row['aplicado'] ?? 0)
            . ' | ' . (int)($row['pendiente'] ?? 0)
            . ' | ' . (int)($row['error'] ?? 0)
            . ' | ' . (int)($row['duplicados'] ?? 0)
            . ' | ' . formatPct((float)($row['aplicado_pct'] ?? 0))
            . ' | ' . formatPct((float)($row['error_pct'] ?? 0))
            . ' | ' . formatPct((float)($row['duplicado_pct'] ?? 0))
            . ' |';
    }
    $md[] = '';
    $md[] = '## Global';
    $g = $report['global'] ?? [];
    $md[] = '- Total: ' . (int)($g['total'] ?? 0);
    $md[] = '- Aplicado: ' . (int)($g['aplicado'] ?? 0) . ' (' . formatPct((float)($g['aplicado_pct'] ?? 0)) . ')';
    $md[] = '- Pendiente: ' . (int)($g['pendiente'] ?? 0);
    $md[] = '- Error: ' . (int)($g['error'] ?? 0) . ' (' . formatPct((float)($g['error_pct'] ?? 0)) . ')';
    $md[] = '- Duplicado: ' . (int)($g['duplicados'] ?? 0) . ' (' . formatPct((float)($g['duplicado_pct'] ?? 0)) . ')';
    $md[] = '';
    $md[] = '## Semaforo';
    foreach (($report['health_flags'] ?? []) as $flag) {
        $status = !empty($flag['status']) ? (string)$flag['status'] : (!empty($flag['ok']) ? 'OK' : 'ALERTA');
        $md[] = '- ' . (string)($flag['kpi'] ?? '') . ': ' . formatPct((float)($flag['valor'] ?? 0)) . ' (objetivo ' . (string)($flag['objetivo'] ?? '-') . ') => ' . $status;
    }

    if (@file_put_contents($mdPath, implode(PHP_EOL, $md) . PHP_EOL) === false) {
        return ['ok' => false, 'path' => $mdPath, 'error' => 'No se pudo escribir Markdown semanal'];
    }

    return ['ok' => true, 'json_path' => $jsonPath, 'md_path' => $mdPath];
}

$dateArg = null;
$jsonMode = false;
$saveMode = false;
$outputDirArg = null;

if (PHP_SAPI === 'cli') {
    $dateArg = getCliOption($argv, 'date');
    $jsonMode = getCliOption($argv, 'json') !== null;
    $saveMode = getCliOption($argv, 'save') !== null;
    $outputDirArg = getCliOption($argv, 'output-dir');
} else {
    $dateArg = isset($_GET['date']) ? (string)$_GET['date'] : null;
    $jsonMode = isset($_GET['json']) && (string)$_GET['json'] === '1';
    $saveMode = isset($_GET['save']) && (string)$_GET['save'] === '1';
    $outputDirArg = isset($_GET['output_dir']) ? (string)$_GET['output_dir'] : null;
}

$date = normalizeDate($dateArg);

$modules = [
    'agenda' => 'agenda_sync_operaciones',
    'caja' => 'caja_sync_operaciones',
    'inventario' => 'inventario_sync_operaciones',
    'pacientes' => 'clientes_sync_operaciones',
    'resultados' => 'resultados_sync_operaciones',
];

$stats = [];

foreach ($modules as $module => $tableName) {
    $moduleStats = getModuleStats($pdo, $tableName, $date);
    $stats[$module] = array_merge(['table' => $tableName], $moduleStats);
}

$summary = buildReportFromStats($stats);
$global = $summary['global'];

$report = [
    'date' => $date,
    'generated_at' => date('Y-m-d H:i:s'),
    'global' => $global,
    'modules' => $stats,
    'health_flags' => $summary['health_flags'],
];

if ($saveMode) {
    $saveResult = saveDailyReport(normalizeOutputDir($outputDirArg), $date, $report);
    $report['saved'] = $saveResult;

    $endDt = new DateTime($date);
    $startDt = (clone $endDt)->modify('-6 days');
    $startDate = $startDt->format('Y-m-d');
    $endDate = $endDt->format('Y-m-d');
    $weeklyStats = [];
    foreach ($modules as $module => $tableName) {
        $weeklyRow = getModuleStatsRange($pdo, $tableName, $startDate, $endDate);
        $weeklyStats[$module] = array_merge(['table' => $tableName], $weeklyRow);
    }
    $weeklySummary = buildReportFromStats($weeklyStats);
    $weeklyReport = [
        'period_start' => $startDate,
        'period_end' => $endDate,
        'generated_at' => date('Y-m-d H:i:s'),
        'global' => $weeklySummary['global'],
        'modules' => $weeklyStats,
        'health_flags' => $weeklySummary['health_flags'],
    ];
    $report['saved_weekly'] = saveWeeklyReport(normalizeOutputDir($outputDirArg), $startDate, $endDate, $weeklyReport);
}

if ($jsonMode) {
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=UTF-8');
    }
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}

$line = str_repeat('-', 86);
echo "Reporte KPI Offline Sync - {$report['date']}\n";
echo $line . "\n";
echo str_pad('Modulo', 18)
    . str_pad('Total', 10)
    . str_pad('Aplicado', 10)
    . str_pad('Pendiente', 11)
    . str_pad('Error', 8)
    . str_pad('Duplicado', 11)
    . str_pad('Apl%', 9)
    . str_pad('Err%', 7)
    . "Dup%\n";
echo $line . "\n";

foreach ($report['modules'] as $module => $row) {
    $label = $module;
    if (!$row['exists']) {
        $label .= ' (N/A)';
    }
    echo str_pad($label, 18)
        . str_pad((string)$row['total'], 10)
        . str_pad((string)$row['aplicado'], 10)
        . str_pad((string)$row['pendiente'], 11)
        . str_pad((string)$row['error'], 8)
        . str_pad((string)$row['duplicados'], 11)
        . str_pad(formatPct((float)$row['aplicado_pct']), 9)
        . str_pad(formatPct((float)$row['error_pct']), 7)
        . formatPct((float)$row['duplicado_pct'])
        . "\n";
}

echo $line . "\n";
echo 'GLOBAL'
    . str_repeat(' ', 12)
    . str_pad((string)$report['global']['total'], 10)
    . str_pad((string)$report['global']['aplicado'], 10)
    . str_pad((string)$report['global']['pendiente'], 11)
    . str_pad((string)$report['global']['error'], 8)
    . str_pad((string)$report['global']['duplicados'], 11)
    . str_pad(formatPct((float)$report['global']['aplicado_pct']), 9)
    . str_pad(formatPct((float)$report['global']['error_pct']), 7)
    . formatPct((float)$report['global']['duplicado_pct'])
    . "\n\n";

echo "Semaforo KPI:\n";
foreach ($report['health_flags'] as $flag) {
    $status = !empty($flag['status']) ? (string)$flag['status'] : ($flag['ok'] ? 'OK' : 'ALERTA');
    echo '- ' . $flag['kpi'] . ': ' . formatPct((float)$flag['valor']) . ' (objetivo ' . $flag['objetivo'] . ') => ' . $status . "\n";
}

if ($saveMode) {
    echo "\nHistorico diario:\n";
    if (!empty($report['saved']['ok'])) {
        echo '- JSON: ' . (string)$report['saved']['json_path'] . "\n";
        echo '- MD: ' . (string)$report['saved']['md_path'] . "\n";
    } else {
        echo '- Error: ' . (string)($report['saved']['error'] ?? 'No se pudo guardar historico') . "\n";
    }

    echo "\nHistorico semanal (ultimos 7 dias):\n";
    if (!empty($report['saved_weekly']['ok'])) {
        echo '- JSON: ' . (string)$report['saved_weekly']['json_path'] . "\n";
        echo '- MD: ' . (string)$report['saved_weekly']['md_path'] . "\n";
    } else {
        echo '- Error: ' . (string)($report['saved_weekly']['error'] ?? 'No se pudo guardar historico semanal') . "\n";
    }
}
