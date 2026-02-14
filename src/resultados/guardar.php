<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$examenes = $_POST['examenes'] ?? [];
$cotizacion_id = $_POST['cotizacion_id'] ?? null;
$referencia_personalizada = trim($_POST['referencia_personalizada'] ?? '');

$normKey = function ($s) {
    $s = (string) $s;
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = mb_strtolower($s, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($ascii !== false && $ascii !== null) {
        $s = $ascii;
    }
    $s = preg_replace('/[^a-z0-9 ]/', '', $s);
    return $s;
};

$isBlank = function ($v) {
    if ($v === null) return true;
    if (is_string($v) && trim($v) === '') return true;
    if (is_array($v) && count($v) === 0) return true;
    return false;
};

$hasSnapshotCol = null;
$hasSnapshotColumn = function () use ($pdo, &$hasSnapshotCol) {
    if ($hasSnapshotCol !== null) {
        return $hasSnapshotCol;
    }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
        $hasSnapshotCol = !empty($col);
    } catch (Exception $e) {
        $hasSnapshotCol = false;
    }
    return $hasSnapshotCol;
};

$isHexColor = function ($c) {
    if (!is_string($c)) return false;
    $c = trim($c);
    return (bool) preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/', $c);
};

if (!empty($examenes) && is_array($examenes)) {
    foreach ($examenes as $examen) {
        $id_resultado = $examen['id_resultado'] ?? null;
        $resultados = $examen['resultados'] ?? [];
        $imprimir_examen = isset($examen['imprimir_examen']) ? 1 : 0;
        $cabeceras_nuevas = $examen['cabeceras_nuevas'] ?? [];
        $cabeceras_editar = $examen['cabeceras_editar'] ?? [];

        if ($id_resultado) {
            // Guardar/editar cabeceras por paciente en el snapshot (si existe la columna)
            if ($hasSnapshotColumn() && ((is_array($cabeceras_nuevas) && count($cabeceras_nuevas)) || (is_array($cabeceras_editar) && count($cabeceras_editar)))) {
                $stmtFmt = $pdo->prepare("SELECT re.adicional_snapshot, e.adicional
                    FROM resultados_examenes re
                    JOIN examenes e ON e.id = re.id_examen
                    WHERE re.id = :id");
                $stmtFmt->execute(['id' => $id_resultado]);
                $rowFmt = $stmtFmt->fetch(PDO::FETCH_ASSOC);
                $src = $rowFmt['adicional_snapshot'] ?? null;
                if ($src === null || $src === '') {
                    $src = $rowFmt['adicional'] ?? null;
                }
                $adicional_arr = $src ? json_decode($src, true) : [];
                if (!is_array($adicional_arr)) {
                    $adicional_arr = [];
                }

                // Editar/eliminar cabeceras existentes por índice
                if (is_array($cabeceras_editar)) {
                    // Procesar eliminaciones primero (índices altos -> bajos)
                    $toDelete = [];
                    foreach ($cabeceras_editar as $idx => $data) {
                        if (!is_array($data)) continue;
                        if (!empty($data['eliminar'])) {
                            $toDelete[] = intval($idx);
                        }
                    }
                    rsort($toDelete);
                    foreach ($toDelete as $idx) {
                        if (isset($adicional_arr[$idx]) && in_array(($adicional_arr[$idx]['tipo'] ?? ''), ['Título', 'Subtítulo'], true)) {
                            unset($adicional_arr[$idx]);
                        }
                    }
                    if (!empty($toDelete)) {
                        $adicional_arr = array_values($adicional_arr);
                    }

                    // Aplicar cambios de nombre/color
                    foreach ($cabeceras_editar as $idx => $data) {
                        if (!is_array($data)) continue;
                        if (!empty($data['eliminar'])) continue;
                        $idxInt = intval($idx);
                        if (!isset($adicional_arr[$idxInt])) continue;
                        $tipo = $adicional_arr[$idxInt]['tipo'] ?? '';
                        if (!in_array($tipo, ['Título', 'Subtítulo'], true)) continue;
                        $nombre = trim((string)($data['nombre'] ?? ''));
                        $color = trim((string)($data['color'] ?? ''));
                        if ($nombre !== '') {
                            $adicional_arr[$idxInt]['nombre'] = $nombre;
                        }
                        if ($color !== '' && $isHexColor($color)) {
                            // Para asemejar el PDF (texto en color), mantener fondo blanco
                            $adicional_arr[$idxInt]['color_texto'] = $color;
                            if (!isset($adicional_arr[$idxInt]['color_fondo']) || $adicional_arr[$idxInt]['color_fondo'] === '') {
                                $adicional_arr[$idxInt]['color_fondo'] = '#ffffff';
                            }
                        }
                    }

                    // Reordenar (mover) cabeceras existentes según ubicación seleccionada
                    $moves = [];
                    foreach ($cabeceras_editar as $idx => $data) {
                        if (!is_array($data)) continue;
                        if (!empty($data['eliminar'])) continue;
                        if (!isset($data['before'])) continue;
                        $idxInt = intval($idx);
                        if (!isset($adicional_arr[$idxInt])) continue;
                        $tipo = $adicional_arr[$idxInt]['tipo'] ?? '';
                        if (!in_array($tipo, ['Título', 'Subtítulo'], true)) continue;
                        $before = (string)$data['before'];
                        $moves[] = ['idx' => $idxInt, 'before' => $before];
                    }

                    if (!empty($moves)) {
                        // Extraer ítems a mover (de atrás hacia adelante para no romper índices)
                        usort($moves, function ($a, $b) { return $b['idx'] <=> $a['idx']; });
                        $extracted = [];
                        foreach ($moves as $m) {
                            $i = $m['idx'];
                            if (!isset($adicional_arr[$i])) continue;
                            $extracted[] = ['orig' => $i, 'before' => $m['before'], 'item' => $adicional_arr[$i]];
                            unset($adicional_arr[$i]);
                        }
                        $adicional_arr = array_values($adicional_arr);

                        // Insertar en orden original (arriba hacia abajo)
                        usort($extracted, function ($a, $b) { return $a['orig'] <=> $b['orig']; });
                        foreach ($extracted as $ex) {
                            $before = (string)$ex['before'];
                            $insertAt = null;
                            if ($before === '__FIRST__') {
                                $insertAt = 0;
                            } elseif ($before === '__END__' || $before === '') {
                                $insertAt = null;
                            } else {
                                foreach ($adicional_arr as $k => $it) {
                                    $t = $it['tipo'] ?? '';
                                    $n = $it['nombre'] ?? '';
                                    if (in_array($t, ['Parámetro', 'Campo', 'Texto Largo'], true) && $n === $before) {
                                        $insertAt = $k;
                                        break;
                                    }
                                }
                            }

                            if ($insertAt === null) {
                                $adicional_arr[] = $ex['item'];
                            } else {
                                array_splice($adicional_arr, $insertAt, 0, [$ex['item']]);
                            }
                        }
                    }
                }

                // Insertar nuevas cabeceras
                if (is_array($cabeceras_nuevas)) {
                    foreach ($cabeceras_nuevas as $data) {
                        if (!is_array($data)) continue;
                        $titulo = trim((string)($data['titulo'] ?? ''));
                        if ($titulo === '') continue;
                        $color = trim((string)($data['color'] ?? ''));
                        if (!$isHexColor($color)) {
                            $color = '#0923E1';
                        }
                        $before = (string)($data['before'] ?? '__END__');

                        $nuevo = [
                            'tipo' => 'Título',
                            'nombre' => $titulo,
                            'color_fondo' => '#ffffff',
                            'color_texto' => $color,
                            'negrita' => 1,
                            'alineacion' => 'left'
                        ];

                        $insertAt = null;
                        if ($before === '__FIRST__') {
                            $insertAt = 0;
                        } elseif ($before === '__END__' || $before === '') {
                            $insertAt = null;
                        } else {
                            foreach ($adicional_arr as $i => $it) {
                                if (($it['nombre'] ?? '') === $before) {
                                    $insertAt = $i;
                                    break;
                                }
                            }
                        }

                        if ($insertAt === null) {
                            $adicional_arr[] = $nuevo;
                        } else {
                            array_splice($adicional_arr, $insertAt, 0, [$nuevo]);
                        }
                    }
                }

                $stmtUpdFmt = $pdo->prepare("UPDATE resultados_examenes SET adicional_snapshot = :snap WHERE id = :id");
                $stmtUpdFmt->execute([
                    'snap' => json_encode($adicional_arr, JSON_UNESCAPED_UNICODE),
                    'id' => $id_resultado
                ]);
            }

            // Traer resultados existentes para no perder data al cambiar parámetros/metodología.
            $sqlGet = "SELECT resultados FROM resultados_examenes WHERE id = :id";
            $stmtGet = $pdo->prepare($sqlGet);
            $stmtGet->execute(['id' => $id_resultado]);
            $existing_json = $stmtGet->fetchColumn();
            $existing = $existing_json ? json_decode($existing_json, true) : [];
            if (!is_array($existing)) {
                $existing = [];
            }
            if (!is_array($resultados)) {
                $resultados = [];
            }

            // Índice normalizado de existentes para rescatar valores cuando cambian nombres.
            $existingNorm = [];
            foreach ($existing as $k => $v) {
                if ($k === 'imprimir_examen') continue;
                $nk = $normKey($k);
                if ($nk !== '' && !array_key_exists($nk, $existingNorm)) {
                    $existingNorm[$nk] = $v;
                }
            }

            // Merge: mantener existentes; aplicar los del POST; rescatar valores antiguos si el POST llega vacío.
            $merged = $existing;
            foreach ($resultados as $k => $v) {
                if ($k === 'imprimir_examen') {
                    continue;
                }
                if ($isBlank($v)) {
                    $nk = $normKey($k);
                    if ($nk !== '' && array_key_exists($nk, $existingNorm) && !$isBlank($existingNorm[$nk])) {
                        $merged[$k] = $existingNorm[$nk];
                        continue;
                    }
                    // Si el usuario realmente quiere borrar, seguirá vacío.
                    $merged[$k] = $v;
                    continue;
                }
                $merged[$k] = $v;
            }
            $merged['imprimir_examen'] = $imprimir_examen;

            $json_resultados = json_encode($merged, JSON_UNESCAPED_UNICODE);
            // Actualiza los resultados y el estado
            $sql = "UPDATE resultados_examenes SET resultados = :resultados, estado = 'completado' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'resultados' => $json_resultados,
                'id' => $id_resultado
            ]);
        }
    }
    
    // Guardar referencia personalizada si fue proporcionada
    if ($cotizacion_id && $referencia_personalizada !== '') {
        // Verificar si ya existe una referencia personalizada para esta cotización
        $sql_check = "SELECT COUNT(*) FROM cotizaciones WHERE id = :cotizacion_id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['cotizacion_id' => $cotizacion_id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            // Actualizar la cotización con la referencia personalizada
            $sql_ref = "UPDATE cotizaciones SET referencia_personalizada = :referencia WHERE id = :cotizacion_id";
            $stmt_ref = $pdo->prepare($sql_ref);
            $stmt_ref->execute([
                'referencia' => $referencia_personalizada,
                'cotizacion_id' => $cotizacion_id
            ]);
        }
    } elseif ($cotizacion_id && $referencia_personalizada === '') {
        // Si el campo está vacío, limpiar la referencia personalizada
        $sql_clear = "UPDATE cotizaciones SET referencia_personalizada = NULL WHERE id = :cotizacion_id";
        $stmt_clear = $pdo->prepare($sql_clear);
        $stmt_clear->execute(['cotizacion_id' => $cotizacion_id]);
    }
    
    // Redirige al dashboard a la vista de cotizaciones (o ajusta la ruta según prefieras)
    header("Location: dashboard.php?vista=cotizaciones&mensaje=Resultados guardados correctamente");
    exit;
} else {
    echo "Error: No se recibieron datos válidos.";
}
?>