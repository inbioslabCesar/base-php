<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$cotizacion_id = $_POST['cotizacion_id'] ?? ($_GET['cotizacion_id'] ?? null);
$cotizacion_id = is_numeric($cotizacion_id) ? intval($cotizacion_id) : 0;
$id_resultado = $_POST['id_resultado'] ?? ($_GET['id_resultado'] ?? null);
$id_resultado = is_numeric($id_resultado) ? intval($id_resultado) : 0;
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

// Standby de seguridad: por ahora se desactiva el reseteo total (preserve_headers=0)
// para evitar pérdida accidental de cabeceras/estructura histórica personalizadas.
if ($preserve_headers !== 1) {
    $_SESSION['mensaje'] = 'La opción de reseteo total está temporalmente deshabilitada por seguridad. Usa "Actualizar formato" (conserva cabeceras).';
    header('Location: dashboard.php?vista=formulario&cotizacion_id=' . $cotizacion_id);
    exit;
}

try {
    if ($preserve_headers === 1) {
        $sql = "SELECT re.id, re.adicional_snapshot, re.resultados, e.adicional
                FROM resultados_examenes re
                JOIN examenes e ON e.id = re.id_examen
                WHERE re.id_cotizacion = :cotizacion_id";
        $params = ['cotizacion_id' => $cotizacion_id];
        if ($id_resultado > 0) {
            $sql .= " AND re.id = :id_resultado";
            $params['id_resultado'] = $id_resultado;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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

        $normKey = function ($s) {
            $s = (string)$s;
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
            $s = preg_replace('/[^a-z0-9 ._-]/', '', $s);
            return $s;
        };

        $isCampoValor = function ($tipo) {
            return in_array((string)$tipo, ['Parámetro', 'Campo', 'Texto Largo'], true);
        };

        $isFormatV2 = function ($arr) {
            return is_array($arr)
                && isset($arr['schema_version'])
                && intval($arr['schema_version'] ?? 0) >= 2
                && isset($arr['layout'])
                && is_array($arr['layout'])
                && isset($arr['layout']['columns'])
                && is_array($arr['layout']['columns'])
                && isset($arr['layout']['rows'])
                && is_array($arr['layout']['rows']);
        };

        $isCustomPatientHeaderV2 = function ($row) {
            if (!is_array($row)) {
                return false;
            }
            $type = strtolower(trim((string)($row['type'] ?? '')));
            if (!in_array($type, ['title', 'subtitle'], true)) {
                return false;
            }
            return (!empty($row['custom_paciente']) && (int)$row['custom_paciente'] === 1)
                || ((string)($row['origen'] ?? '') === 'paciente');
        };

        $sortColumnsByOrder = static function (array $cols): array {
            $out = array_values(array_filter($cols, static function ($c) {
                return is_array($c);
            }));
            usort($out, static function ($a, $b) {
                return intval($a['order'] ?? 0) <=> intval($b['order'] ?? 0);
            });
            return $out;
        };

        $stableV2Anchor = function (array $row, array $columns) use ($normKey) {
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            foreach ($columns as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $kind = strtolower(trim((string)($col['kind'] ?? '')));
                if (!in_array($kind, ['text', 'reference'], true)) {
                    continue;
                }
                $cid = trim((string)($col['id'] ?? ''));
                if ($cid === '') {
                    continue;
                }
                $val = trim((string)($cells[$cid] ?? ''));
                if ($val !== '') {
                    return $normKey($val);
                }
            }

            return $normKey((string)($row['label'] ?? ''));
        };

        $remapAssocKeys = static function ($value, array $map) {
            if (!is_array($value) || empty($map)) {
                return $value;
            }
            $out = [];
            foreach ($value as $k => $v) {
                $to = array_key_exists($k, $map) ? $map[$k] : $k;
                if (!is_string($to) || trim($to) === '') {
                    $to = $k;
                }
                if (is_array($v)) {
                    $out[$to] = $v;
                } else {
                    $out[$to] = $v;
                }
            }
            return $out;
        };

        $mergeV2PreservingBindings = function (array $oldFmt, array $baseFmt) use ($normKey, $sortColumnsByOrder, $stableV2Anchor, $remapAssocKeys, $isCustomPatientHeaderV2) {
            $oldCols = $sortColumnsByOrder((array)($oldFmt['layout']['columns'] ?? []));
            $newCols = $sortColumnsByOrder((array)($baseFmt['layout']['columns'] ?? []));

            $usedOldColIds = [];
            $newToOldCol = [];
            $oldByKindLabel = [];
            $oldByKind = [];

            foreach ($oldCols as $oc) {
                $oid = trim((string)($oc['id'] ?? ''));
                if ($oid === '') {
                    continue;
                }
                $kind = strtolower(trim((string)($oc['kind'] ?? 'text')));
                $lbl = $normKey((string)($oc['label'] ?? ''));
                $k = $kind . '|' . $lbl;
                if (!isset($oldByKindLabel[$k])) {
                    $oldByKindLabel[$k] = [];
                }
                $oldByKindLabel[$k][] = $oid;
                if (!isset($oldByKind[$kind])) {
                    $oldByKind[$kind] = [];
                }
                $oldByKind[$kind][] = $oid;
            }

            foreach ($newCols as &$nc) {
                $nid = trim((string)($nc['id'] ?? ''));
                if ($nid === '') {
                    continue;
                }
                $kind = strtolower(trim((string)($nc['kind'] ?? 'text')));
                $lbl = $normKey((string)($nc['label'] ?? ''));
                $targetOldId = '';

                $k = $kind . '|' . $lbl;
                if (isset($oldByKindLabel[$k])) {
                    foreach ($oldByKindLabel[$k] as $cand) {
                        if (!isset($usedOldColIds[$cand])) {
                            $targetOldId = $cand;
                            break;
                        }
                    }
                }

                if ($targetOldId === '' && isset($oldByKind[$kind])) {
                    foreach ($oldByKind[$kind] as $cand) {
                        if (!isset($usedOldColIds[$cand])) {
                            $targetOldId = $cand;
                            break;
                        }
                    }
                }

                if ($targetOldId !== '') {
                    $newToOldCol[$nid] = $targetOldId;
                    $usedOldColIds[$targetOldId] = true;
                    $nc['id'] = $targetOldId;
                }
            }
            unset($nc);

            $newRows = (array)($baseFmt['layout']['rows'] ?? []);
            $oldRows = (array)($oldFmt['layout']['rows'] ?? []);

            foreach ($newRows as &$row) {
                if (!is_array($row)) {
                    continue;
                }
                $row['cells'] = $remapAssocKeys((array)($row['cells'] ?? []), $newToOldCol);
                $row['formulas'] = $remapAssocKeys((array)($row['formulas'] ?? []), $newToOldCol);
                $row['select_options'] = $remapAssocKeys((array)($row['select_options'] ?? []), $newToOldCol);
                $row['reference_ranges'] = $remapAssocKeys((array)($row['reference_ranges'] ?? []), $newToOldCol);
                $row['decimales'] = $remapAssocKeys((array)($row['decimales'] ?? []), $newToOldCol);
            }
            unset($row);

            $oldSigMap = [];
            foreach ($oldRows as $idx => $orow) {
                if (!is_array($orow)) {
                    continue;
                }
                $otype = strtolower(trim((string)($orow['type'] ?? 'data')));
                $olabel = $normKey((string)($orow['label'] ?? ''));
                $oanchor = $stableV2Anchor($orow, $oldCols);
                $sig = $otype . '|' . $olabel . '|' . $oanchor;
                if (!isset($oldSigMap[$sig])) {
                    $oldSigMap[$sig] = [];
                }
                $oldSigMap[$sig][] = $idx;
            }

            $usedOldRow = [];
            $nextByType = [];
            foreach ($newRows as $idx => &$nrow) {
                if (!is_array($nrow)) {
                    continue;
                }
                $nType = strtolower(trim((string)($nrow['type'] ?? 'data')));
                $nLabel = $normKey((string)($nrow['label'] ?? ''));
                $nAnchor = $stableV2Anchor($nrow, $newCols);
                $sig = $nType . '|' . $nLabel . '|' . $nAnchor;

                $matchIdx = null;
                if (isset($oldSigMap[$sig])) {
                    foreach ($oldSigMap[$sig] as $candIdx) {
                        if (!isset($usedOldRow[$candIdx])) {
                            $matchIdx = $candIdx;
                            break;
                        }
                    }
                }

                if ($matchIdx === null) {
                    if (!isset($nextByType[$nType])) {
                        $nextByType[$nType] = 0;
                    }
                    for ($j = $nextByType[$nType]; $j < count($oldRows); $j++) {
                        if (!is_array($oldRows[$j])) {
                            continue;
                        }
                        $otype = strtolower(trim((string)($oldRows[$j]['type'] ?? 'data')));
                        if ($otype !== $nType) {
                            continue;
                        }
                        if (isset($usedOldRow[$j])) {
                            continue;
                        }
                        $matchIdx = $j;
                        $nextByType[$nType] = $j + 1;
                        break;
                    }
                }

                if ($matchIdx !== null && isset($oldRows[$matchIdx]) && is_array($oldRows[$matchIdx])) {
                    $oldId = trim((string)($oldRows[$matchIdx]['id'] ?? ''));
                    if ($oldId !== '') {
                        $nrow['id'] = $oldId;
                    }
                    $usedOldRow[$matchIdx] = true;
                }
            }
            unset($nrow);

            $existingHdr = [];
            foreach ($newRows as $nr) {
                if (!is_array($nr)) {
                    continue;
                }
                $t = strtolower(trim((string)($nr['type'] ?? '')));
                if (!in_array($t, ['title', 'subtitle'], true)) {
                    continue;
                }
                $k = $t . '|' . $normKey((string)($nr['label'] ?? ''));
                $existingHdr[$k] = true;
            }

            foreach ($oldRows as $or) {
                if (!$isCustomPatientHeaderV2($or)) {
                    continue;
                }
                $t = strtolower(trim((string)($or['type'] ?? '')));
                $k = $t . '|' . $normKey((string)($or['label'] ?? ''));
                if (isset($existingHdr[$k])) {
                    continue;
                }
                $newRows[] = $or;
                $existingHdr[$k] = true;
            }

            $baseFmt['layout']['columns'] = array_values($newCols);
            $baseFmt['layout']['rows'] = array_values($newRows);
            return $baseFmt;
        };

        $buildExpectedV2Keys = function (array $fmt) use ($sortColumnsByOrder) {
            $out = [];
            $columns = $sortColumnsByOrder((array)($fmt['layout']['columns'] ?? []));
            $rows = array_values((array)($fmt['layout']['rows'] ?? []));

            $editableCols = [];
            foreach ($columns as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $cid = trim((string)($col['id'] ?? ''));
                if ($cid === '') {
                    continue;
                }
                $kind = strtolower(trim((string)($col['kind'] ?? 'text')));
                $editable = array_key_exists('editable', $col)
                    ? (bool)$col['editable']
                    : in_array($kind, ['result', 'formula', 'select', 'long_text', 'number'], true);
                if ($editable) {
                    $editableCols[] = $cid;
                }
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $rid = trim((string)($row['id'] ?? ''));
                if ($rid === '') {
                    continue;
                }
                $type = strtolower(trim((string)($row['type'] ?? 'data')));
                if ($type === 'long_text') {
                    $out[] = 'v2__' . $rid . '____texto';
                    continue;
                }
                if (!in_array($type, ['data', 'formula'], true)) {
                    continue;
                }
                foreach ($editableCols as $cid) {
                    $out[] = 'v2__' . $rid . '__' . $cid;
                }
            }

            return $out;
        };

        $realignV2ResultValues = function (array $resultadosArr, array $expectedKeys) {
            $expectedSet = [];
            foreach ($expectedKeys as $ek) {
                $expectedSet[$ek] = true;
            }

            $actualV2Keys = [];
            foreach ($resultadosArr as $k => $v) {
                if (strpos((string)$k, 'v2__') === 0) {
                    $actualV2Keys[] = (string)$k;
                }
            }

            if (empty($actualV2Keys) || empty($expectedKeys)) {
                return [$resultadosArr, 0, 0, 0];
            }

            $orphan = [];
            $matchedCount = 0;
            foreach ($actualV2Keys as $k) {
                if (isset($expectedSet[$k])) {
                    $matchedCount++;
                } else {
                    $orphan[] = $k;
                }
            }

            $missing = [];
            foreach ($expectedKeys as $ek) {
                if (!array_key_exists($ek, $resultadosArr)) {
                    $missing[] = $ek;
                }
            }

            if (empty($orphan) || empty($missing)) {
                return [$resultadosArr, $matchedCount, count($orphan), 0];
            }

            $recovered = 0;
            $orphIdx = 0;
            foreach ($missing as $mk) {
                if ($orphIdx >= count($orphan)) {
                    break;
                }
                $source = $orphan[$orphIdx++];
                if (!array_key_exists($source, $resultadosArr)) {
                    continue;
                }
                $resultadosArr[$mk] = $resultadosArr[$source];
                $recovered++;
            }

            return [$resultadosArr, $matchedCount, count($orphan), $recovered];
        };

        $dedupeLegacyTitles = function (array &$rows) use ($normKey) {
            $seen = [];
            foreach ($rows as $idx => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $tipo = (string)($row['tipo'] ?? '');
                if (!in_array($tipo, ['Título', 'Subtítulo'], true)) {
                    continue;
                }
                $isCustom = (!empty($row['custom_paciente']) && (int)$row['custom_paciente'] === 1)
                    || ((string)($row['origen'] ?? '') === 'paciente');
                if (!$isCustom) {
                    continue;
                }
                $nombre = trim((string)($row['nombre'] ?? ''));
                $key = $tipo . '|' . $normKey($nombre);
                if ($key === $tipo . '|') {
                    continue;
                }
                if (isset($seen[$key])) {
                    unset($rows[$idx]);
                    continue;
                }
                $seen[$key] = true;
            }
            $rows = array_values($rows);
        };

        $upd = $pdo->prepare("UPDATE resultados_examenes SET adicional_snapshot = :snap WHERE id = :id");
        $updWithResults = $pdo->prepare("UPDATE resultados_examenes SET adicional_snapshot = :snap, resultados = :resultados WHERE id = :id");

        $updatedCount = 0;
        foreach ($rows as $r) {
            $old = $r['adicional_snapshot'] ?? '';
            $base = $r['adicional'] ?? '';
            $resultadosRaw = $r['resultados'] ?? '';

            $oldArr = $old ? json_decode($old, true) : [];
            if (!is_array($oldArr)) $oldArr = [];
            $baseArr = $base ? json_decode($base, true) : [];
            if (!is_array($baseArr)) $baseArr = [];

            $resultadosArr = $resultadosRaw ? json_decode($resultadosRaw, true) : [];
            if (!is_array($resultadosArr)) {
                $resultadosArr = [];
            }

            if ($isFormatV2($oldArr) && $isFormatV2($baseArr)) {
                $mergedV2 = $mergeV2PreservingBindings($oldArr, $baseArr);
                $expectedKeys = $buildExpectedV2Keys($mergedV2);
                [$resultadosRealigned, $matchedV2, $orphanV2, $recoveredV2] = $realignV2ResultValues($resultadosArr, $expectedKeys);

                if ($recoveredV2 > 0) {
                    $updWithResults->execute([
                        'snap' => json_encode($mergedV2, JSON_UNESCAPED_UNICODE),
                        'resultados' => json_encode($resultadosRealigned, JSON_UNESCAPED_UNICODE),
                        'id' => $r['id']
                    ]);
                } else {
                    $upd->execute([
                        'snap' => json_encode($mergedV2, JSON_UNESCAPED_UNICODE),
                        'id' => $r['id']
                    ]);
                }
                $updatedCount++;
                continue;
            }

            $resultadosStable = [];
            foreach ($resultadosArr as $k => $v) {
                if (preg_match('/^id_parametro_(.+)$/', (string)$k, $m)) {
                    $resultadosStable[] = trim((string)$m[1]);
                }
            }

            // Extraer cabeceras personalizadas de paciente: títulos/subtítulos SIN id_parametro
            $custom = [];
            foreach ($oldArr as $i => $it) {
                $tipo = $it['tipo'] ?? '';
                if (!in_array($tipo, ['Título', 'Subtítulo'], true)) continue;
                if (!empty($it['id_parametro'])) continue; // las del CRUD ya están en base
                $isCustomPaciente = (
                    (isset($it['origen']) && (string)$it['origen'] === 'paciente') ||
                    (!empty($it['custom_paciente']) && (int)$it['custom_paciente'] === 1)
                );
                if (!$isCustomPaciente) continue;
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

            $dedupeLegacyTitles($baseArr);

            $oldIdCounts = [];
            foreach ($oldArr as $itOld) {
                if (!is_array($itOld)) continue;
                if (!$isCampoValor($itOld['tipo'] ?? '')) continue;
                $idOld = trim((string)($itOld['id_parametro'] ?? ''));
                if ($idOld === '') continue;
                if (!isset($oldIdCounts[$idOld])) {
                    $oldIdCounts[$idOld] = 0;
                }
                $oldIdCounts[$idOld]++;
            }

            $oldByName = [];
            $oldByOrder = [];
            foreach ($oldArr as $itOld) {
                if (!is_array($itOld)) continue;
                if (!$isCampoValor($itOld['tipo'] ?? '')) continue;
                $idOld = trim((string)($itOld['id_parametro'] ?? ''));
                if ($idOld === '') continue;
                if (($oldIdCounts[$idOld] ?? 0) > 1) continue;

                $nameOld = trim((string)($itOld['nombre'] ?? ''));
                $nkOld = $normKey($nameOld);
                if ($nkOld !== '' && !isset($oldByName[$nkOld])) {
                    $oldByName[$nkOld] = $idOld;
                }

                $ordenOld = isset($itOld['orden']) ? (string)$itOld['orden'] : '';
                if ($ordenOld !== '' && !isset($oldByOrder[$ordenOld])) {
                    $oldByOrder[$ordenOld] = $idOld;
                }
            }

            $baseParamIdx = [];
            foreach ($baseArr as $idxBase => $itBase) {
                if (!is_array($itBase)) continue;
                if ($isCampoValor($itBase['tipo'] ?? '')) {
                    $baseParamIdx[] = $idxBase;
                }
            }

            $usedChosenIds = [];

            foreach ($baseParamIdx as $idxBase) {
                $itBase = $baseArr[$idxBase];
                $idBase = trim((string)($itBase['id_parametro'] ?? ''));
                $nameBase = trim((string)($itBase['nombre'] ?? ''));
                $nkBase = $normKey($nameBase);
                $ordenBase = isset($itBase['orden']) ? (string)$itBase['orden'] : '';

                $idElegido = $idBase;

                if ($nkBase !== '' && isset($oldByName[$nkBase])) {
                    $idElegido = $oldByName[$nkBase];
                } elseif ($ordenBase !== '' && isset($oldByOrder[$ordenBase])) {
                    $idElegido = $oldByOrder[$ordenBase];
                } elseif (count($baseParamIdx) === 1 && count($resultadosStable) === 1) {
                    $idElegido = $resultadosStable[0];
                }

                if (
                    $idElegido !== '' &&
                    isset($usedChosenIds[$idElegido]) &&
                    $usedChosenIds[$idElegido] !== $idxBase
                ) {
                    $idElegido = $idBase;
                }

                if ($idElegido !== '') {
                    $baseArr[$idxBase]['id_parametro'] = $idElegido;
                    $usedChosenIds[$idElegido] = $idxBase;
                }
            }

            $dedupeLegacyTitles($baseArr);

            $upd->execute([
                'snap' => json_encode($baseArr, JSON_UNESCAPED_UNICODE),
                'id' => $r['id']
            ]);
            $updatedCount++;
        }

        if ($id_resultado > 0) {
            $_SESSION['mensaje'] = 'Formato actualizado (conservando cabeceras) para el examen seleccionado.';
        } else {
            $_SESSION['mensaje'] = 'Formato actualizado (conservando cabeceras) para la cotización #' . $cotizacion_id . '.';
        }
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error al actualizar formato: ' . $e->getMessage();
}

header('Location: dashboard.php?vista=formulario&cotizacion_id=' . $cotizacion_id);
exit;
