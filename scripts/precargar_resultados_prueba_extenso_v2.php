<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$cotizacionId = isset($argv[1]) && is_numeric($argv[1]) ? (int)$argv[1] : 2356;
$examId = isset($argv[2]) && is_numeric($argv[2]) ? (int)$argv[2] : 374;

$st = $pdo->prepare('SELECT id, resultados FROM resultados_examenes WHERE id_cotizacion = ? AND id_examen = ? LIMIT 1');
$st->execute([$cotizacionId, $examId]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    fwrite(STDERR, "No se encontro resultado para cotizacion={$cotizacionId}, examen={$examId}\n");
    exit(1);
}

$resultadoId = (int)$row['id'];
$data = $row['resultados'] ? json_decode($row['resultados'], true) : [];
if (!is_array($data)) {
    $data = [];
}

// Precargar un subconjunto amplio para forzar visual de varias filas llenas.
for ($s = 1; $s <= 6; $s++) {
    $secMap = [
        1 => 'sec_examen_fisico',
        2 => 'sec_examen_microscopico',
        3 => 'sec_concentracion',
        4 => 'sec_vitalidad',
        5 => 'sec_motilidad',
        6 => 'sec_morfologia',
    ];
    $sec = $secMap[$s];
    for ($i = 1; $i <= 10; $i++) {
        $base = ($s * 7) + $i;
        $k1 = 'v2__' . $sec . '_row_' . $i . '__eval_1h';
        $k2 = 'v2__' . $sec . '_row_' . $i . '__eval_2h';
        $k3 = 'v2__' . $sec . '_row_' . $i . '__eval_3h';
        $k4 = 'v2__' . $sec . '_row_' . $i . '__eval_4h';
        $k6 = 'v2__' . $sec . '_row_' . $i . '__eval_6h';
        $k8 = 'v2__' . $sec . '_row_' . $i . '__eval_8h';

        $data[$k1] = (string)$base;
        $data[$k2] = (string)($base - 2);
        $data[$k3] = (string)($base - 4);
        $data[$k4] = (string)($base - 6);
        $data[$k6] = (string)($base - 8);
        $data[$k8] = (string)($base - 10);
    }
}

$up = $pdo->prepare('UPDATE resultados_examenes SET resultados = ? WHERE id = ?');
$up->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $resultadoId]);

echo "OK\n";
echo "id_resultado={$resultadoId}\n";
echo "cotizacion={$cotizacionId}\n";
echo "examen={$examId}\n";
