<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$cotizacion_id = $_POST['cotizacion_id'] ?? ($_GET['cotizacion_id'] ?? null);
$cotizacion_id = is_numeric($cotizacion_id) ? intval($cotizacion_id) : 0;
$preserve_headers = $_POST['preserve_headers'] ?? ($_GET['preserve_headers'] ?? null);
$preserve_headers = ($preserve_headers === null) ? 1 : intval($preserve_headers);

if ($cotizacion_id <= 0) {
    $_SESSION['mensaje'] = 'Cotización no válida para actualizar formato.';
    header('Location: dashboard.php?vista=cotizaciones');
    exit;
}

// Verificar que existe la columna adicional_snapshot (migración aplicada)
$hasSnapshotCol = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
    $hasSnapshotCol = !empty($col);
} catch (Exception $e) {
    $hasSnapshotCol = false;
}

if (!$hasSnapshotCol) {
    $_SESSION['mensaje'] = "No existe la columna adicional_snapshot. Ejecuta el SQL de migración: sql/resultados_examenes_adicional_snapshot.sql";
    header('Location: dashboard.php?vista=formulario&cotizacion_id=' . $cotizacion_id);
    exit;
}

try {
    if ($preserve_headers === 1) {
        $sql = "SELECT re.id, re.adicional_snapshot, e.adicional
                FROM resultados_examenes re
                JOIN examenes e ON e.id = re.id_examen
                WHERE re.id_cotizacion = :cotizacion_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $detectBefore = function (array $arr, int $idx): string {
            if ($idx <= 0) {
                return '__FIRST__';
            }
            for ($j = $idx + 1; $j < count($arr); $j++) {
                $t2 = $arr[$j]['tipo'] ?? '';
                if (in_array($t2, ['Parámetro', 'Campo', 'Texto Largo'], true)) {
                    $n2 = $arr[$j]['nombre'] ?? '';
                    return ($n2 !== '') ? $n2 : '__END__';
                }
            }
            return '__END__';
        };

        $upd = $pdo->prepare("UPDATE resultados_examenes SET adicional_snapshot = :snap WHERE id = :id");

        foreach ($rows as $r) {
            $old = $r['adicional_snapshot'] ?? '';
            $base = $r['adicional'] ?? '';

            $oldArr = $old ? json_decode($old, true) : [];
            if (!is_array($oldArr)) $oldArr = [];
            $baseArr = $base ? json_decode($base, true) : [];
            if (!is_array($baseArr)) $baseArr = [];

            // Extraer cabeceras personalizadas: títulos/subtítulos SIN id_parametro
            $custom = [];
            foreach ($oldArr as $i => $it) {
                $tipo = $it['tipo'] ?? '';
                if (!in_array($tipo, ['Título', 'Subtítulo'], true)) continue;
                if (!empty($it['id_parametro'])) continue; // las del CRUD ya están en base
                $custom[] = [
                    'before' => $detectBefore($oldArr, intval($i)),
                    'item' => $it
                ];
            }

            // Insertar custom en el base respetando before
            foreach ($custom as $c) {
                $before = (string)($c['before'] ?? '__END__');
                $insertAt = null;
                if ($before === '__FIRST__') {
                    $insertAt = 0;
                } elseif ($before === '__END__' || $before === '') {
                    $insertAt = null;
                } else {
                    foreach ($baseArr as $k => $it2) {
                        $t = $it2['tipo'] ?? '';
                        $n = $it2['nombre'] ?? '';
                        if (in_array($t, ['Parámetro', 'Campo', 'Texto Largo'], true) && $n === $before) {
                            $insertAt = $k;
                            break;
                        }
                    }
                }

                if ($insertAt === null) {
                    $baseArr[] = $c['item'];
                } else {
                    array_splice($baseArr, $insertAt, 0, [$c['item']]);
                }
            }

            $upd->execute([
                'snap' => json_encode($baseArr, JSON_UNESCAPED_UNICODE),
                'id' => $r['id']
            ]);
        }

        $_SESSION['mensaje'] = 'Formato actualizado (conservando cabeceras) para la cotización #' . $cotizacion_id . '.';
    } else {
        $sql = "UPDATE resultados_examenes re
                JOIN examenes e ON e.id = re.id_examen
                SET re.adicional_snapshot = e.adicional
                WHERE re.id_cotizacion = :cotizacion_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['cotizacion_id' => $cotizacion_id]);

        $_SESSION['mensaje'] = 'Formato reemplazado para la cotización #' . $cotizacion_id . '.';
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error al actualizar formato: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=formulario&cotizacion_id=' . $cotizacion_id);
exit;
