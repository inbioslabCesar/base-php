<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

use PDO;
use Exception;

$examenes = $_POST['examenes'] ?? [];
$cotizacion_id = $_POST['cotizacion_id'] ?? null;
$stayOnForm = isset($_POST['stay_on_form']) && (int)$_POST['stay_on_form'] === 1;
$referencia_personalizada = trim($_POST['referencia_personalizada'] ?? '');
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

$resumenConsumo = [
    'aplicados' => 0,
    'pendientes' => 0,
    'detalles' => [],
];

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
    $s = preg_replace('/[^a-z0-9 ._-]/', '', $s);
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

$alarmColumnMap = null;
$getAlarmColumnMap = function () use ($pdo, &$alarmColumnMap) {
    if ($alarmColumnMap !== null) {
        return $alarmColumnMap;
    }

    $alarmColumnMap = [
        'alarma_activa' => false,
        'alarma_dias' => false,
        'alarma_fecha_objetivo' => false,
        'alarma_estado' => false,
        'alarma_ultimo_aviso' => false,
        'alarma_whatsapp_destino' => false,
    ];

    try {
        $stmtCols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resultados_examenes'");
        $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
        foreach ($alarmColumnMap as $col => $_) {
            $alarmColumnMap[$col] = in_array($col, $cols, true);
        }
    } catch (Exception $e) {
    }

    return $alarmColumnMap;
};

$getCompanyWhatsappNumber = function () use ($pdo) {
    try {
        $stmtCfg = $pdo->query("SELECT redes_sociales FROM config_empresa ORDER BY id DESC LIMIT 1");
        $redesRaw = $stmtCfg->fetchColumn();
        if (!$redesRaw) {
            return null;
        }

        $redes = json_decode((string)$redesRaw, true);
        if (!is_array($redes)) {
            return null;
        }

        foreach ($redes as $red) {
            if (!is_array($red)) {
                continue;
            }
            $nombre = strtolower(trim((string)($red['nombre'] ?? '')));
            if ($nombre !== 'whatsapp') {
                continue;
            }

            $url = (string)($red['url'] ?? '');
            $digits = preg_replace('/\D+/', '', $url);
            if ($digits !== '') {
                return $digits;
            }
        }
    } catch (Exception $e) {
    }

    return null;
};

$isHexColor = function ($c) {
    if (!is_string($c)) return false;
    $c = trim($c);
    return (bool) preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/', $c);
};

$buildStableKey = function ($item) {
    if (!is_array($item)) {
        return '';
    }
    $idParametro = trim((string)($item['id_parametro'] ?? ''));
    if ($idParametro === '') {
        return '';
    }
    return 'id_parametro_' . $idParametro;
};

$normalizarResultadosPorSnapshot = function (array $resultados, $snapshotSrc) use ($normKey, $buildStableKey) {
    if (!is_array($snapshotSrc)) {
        return $resultados;
    }

    $indexNombre = [];
    foreach ($snapshotSrc as $item) {
        if (!is_array($item)) {
            continue;
        }
        $tipo = (string)($item['tipo'] ?? '');
        if (!in_array($tipo, ['Parámetro', 'Campo', 'Texto Largo'], true)) {
            continue;
        }
        $nombre = trim((string)($item['nombre'] ?? ''));
        if ($nombre === '') {
            continue;
        }
        $nk = $normKey($nombre);
        if ($nk === '' || isset($indexNombre[$nk])) {
            continue;
        }
        $indexNombre[$nk] = $item;
    }

    $salida = [];
    foreach ($resultados as $k => $v) {
        $key = (string)$k;
        if ($key === 'imprimir_examen') {
            $salida[$key] = $v;
            continue;
        }

        $destino = $key;
        $nk = $normKey($key);
        if ($nk !== '' && isset($indexNombre[$nk])) {
            $stable = $buildStableKey($indexNombre[$nk]);
            if ($stable !== '') {
                $destino = $stable;
            }
        }

        $salida[$destino] = $v;
    }

    return $salida;
};

$tieneTablasInventarioInterno = function () use ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('inventario_examen_recetas','inventario_consumos_examen','inventario_transferencias','inventario_transferencias_detalle')");
        $stmt->execute();
        return ((int)$stmt->fetchColumn() === 4);
    } catch (Exception $e) {
        return false;
    }
};

$aplicarConsumoPorResultado = function ($idResultado) use ($pdo, $usuario_id, &$resumenConsumo) {
    $stmtInfo = $pdo->prepare("SELECT id, id_examen, id_cotizacion FROM resultados_examenes WHERE id = ? LIMIT 1");
    $stmtInfo->execute([$idResultado]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    if (!$info) {
        return;
    }

    $idExamen = (int)($info['id_examen'] ?? 0);
    $idCotizacion = (int)($info['id_cotizacion'] ?? 0);
    if ($idExamen <= 0 || $idCotizacion <= 0) {
        return;
    }

    $stmtQty = $pdo->prepare("SELECT IFNULL(SUM(cantidad), 1) FROM cotizaciones_detalle WHERE id_cotizacion = ? AND id_examen = ?");
    $stmtQty->execute([$idCotizacion, $idExamen]);
    $factorCantidad = (float)$stmtQty->fetchColumn();
    if ($factorCantidad <= 0) {
        $factorCantidad = 1;
    }

    $stmtRecetas = $pdo->prepare("SELECT item_id, cantidad_por_prueba
        FROM inventario_examen_recetas
        WHERE id_examen = ? AND activo = 1");
    $stmtRecetas->execute([$idExamen]);
    $recetas = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recetas)) {
        return;
    }

    $stmtYaConsumido = $pdo->prepare("SELECT COUNT(*) FROM inventario_consumos_examen WHERE id_cotizacion = ? AND id_examen = ? AND item_id = ? AND origen_evento = 'resultado'");
    $stmtTransferido = $pdo->prepare("SELECT IFNULL(SUM(td.cantidad),0)
        FROM inventario_transferencias_detalle td
        JOIN inventario_transferencias t ON t.id = td.transferencia_id
        WHERE td.item_id = ? AND t.destino = 'laboratorio'");
    $stmtConsumido = $pdo->prepare("SELECT IFNULL(SUM(cantidad_consumida),0)
        FROM inventario_consumos_examen
        WHERE item_id = ? AND estado = 'aplicado'");
    $stmtItem = $pdo->prepare("SELECT codigo, nombre, unidad_medida FROM inventario_items WHERE id = ? LIMIT 1");
    $stmtInsert = $pdo->prepare("INSERT INTO inventario_consumos_examen
        (id_cotizacion, id_examen, item_id, cantidad_consumida, origen_evento, estado, usuario_id, observacion, fecha_hora)
        VALUES (?, ?, ?, ?, 'resultado', 'aplicado', ?, ?, NOW())");

    foreach ($recetas as $r) {
        $itemId = (int)($r['item_id'] ?? 0);
        $cantidadBase = (float)($r['cantidad_por_prueba'] ?? 0);
        if ($itemId <= 0 || $cantidadBase <= 0) {
            continue;
        }

        $cantidadNecesaria = round($cantidadBase * $factorCantidad, 4);
        if ($cantidadNecesaria <= 0) {
            continue;
        }

        $stmtYaConsumido->execute([$idCotizacion, $idExamen, $itemId]);
        $yaConsumido = ((int)$stmtYaConsumido->fetchColumn() > 0);
        if ($yaConsumido) {
            continue;
        }

        $stmtTransferido->execute([$itemId]);
        $transferido = (float)$stmtTransferido->fetchColumn();

        $stmtConsumido->execute([$itemId]);
        $consumido = (float)$stmtConsumido->fetchColumn();

        $saldoInterno = round($transferido - $consumido, 4);

        if ($saldoInterno + 0.0001 < $cantidadNecesaria) {
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            $nombreItem = trim((string)($item['codigo'] ?? '') . ' ' . (string)($item['nombre'] ?? ''));
            $unidad = (string)($item['unidad_medida'] ?? 'unid');
            $resumenConsumo['pendientes']++;
            $resumenConsumo['detalles'][] = 'Stock interno insuficiente para ' . $nombreItem . ' (' . number_format($cantidadNecesaria, 4) . ' ' . $unidad . ' requeridos, ' . number_format($saldoInterno, 4) . ' disponibles).';
            continue;
        }

        $obs = 'Consumo automático por resultado. Resultado ID: ' . (int)$idResultado;
        $stmtInsert->execute([
            $idCotizacion,
            $idExamen,
            $itemId,
            $cantidadNecesaria,
            $usuario_id > 0 ? $usuario_id : null,
            $obs,
        ]);
        $resumenConsumo['aplicados']++;
    }
};

$aplicarConsumoRepeticion = function ($idResultado, $motivo) use ($pdo, $usuario_id, &$resumenConsumo) {
    $stmtInfo = $pdo->prepare("SELECT id, id_examen, id_cotizacion FROM resultados_examenes WHERE id = ? LIMIT 1");
    $stmtInfo->execute([$idResultado]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    if (!$info) {
        return;
    }

    $idExamen = (int)($info['id_examen'] ?? 0);
    $idCotizacion = (int)($info['id_cotizacion'] ?? 0);
    if ($idExamen <= 0 || $idCotizacion <= 0) {
        return;
    }

    $stmtQty = $pdo->prepare("SELECT IFNULL(SUM(cantidad), 1) FROM cotizaciones_detalle WHERE id_cotizacion = ? AND id_examen = ?");
    $stmtQty->execute([$idCotizacion, $idExamen]);
    $factorCantidad = (float)$stmtQty->fetchColumn();
    if ($factorCantidad <= 0) {
        $factorCantidad = 1;
    }

    $stmtRecetas = $pdo->prepare("SELECT item_id, cantidad_por_prueba
        FROM inventario_examen_recetas
        WHERE id_examen = ? AND activo = 1");
    $stmtRecetas->execute([$idExamen]);
    $recetas = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);
    if (empty($recetas)) {
        return;
    }

    $stmtTransferido = $pdo->prepare("SELECT IFNULL(SUM(td.cantidad),0)
        FROM inventario_transferencias_detalle td
        JOIN inventario_transferencias t ON t.id = td.transferencia_id
        WHERE td.item_id = ? AND t.destino = 'laboratorio'");
    $stmtConsumido = $pdo->prepare("SELECT IFNULL(SUM(cantidad_consumida),0)
        FROM inventario_consumos_examen
        WHERE item_id = ? AND estado = 'aplicado'");
    $stmtItem = $pdo->prepare("SELECT codigo, nombre, unidad_medida FROM inventario_items WHERE id = ? LIMIT 1");

    $pendientes = [];
    $consumosAInsertar = [];

    foreach ($recetas as $r) {
        $itemId = (int)($r['item_id'] ?? 0);
        $cantidadBase = (float)($r['cantidad_por_prueba'] ?? 0);
        if ($itemId <= 0 || $cantidadBase <= 0) {
            continue;
        }

        $cantidadNecesaria = round($cantidadBase * $factorCantidad, 4);
        if ($cantidadNecesaria <= 0) {
            continue;
        }

        $stmtTransferido->execute([$itemId]);
        $transferido = (float)$stmtTransferido->fetchColumn();

        $stmtConsumido->execute([$itemId]);
        $consumido = (float)$stmtConsumido->fetchColumn();

        $saldoInterno = round($transferido - $consumido, 4);
        if ($saldoInterno + 0.0001 < $cantidadNecesaria) {
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            $nombreItem = trim((string)($item['codigo'] ?? '') . ' ' . (string)($item['nombre'] ?? ''));
            $unidad = (string)($item['unidad_medida'] ?? 'unid');
            $pendientes[] = 'Stock insuficiente para repetición en ' . $nombreItem . ' (' . number_format($cantidadNecesaria, 4) . ' ' . $unidad . ' requeridos, ' . number_format($saldoInterno, 4) . ' disponibles).';
            continue;
        }

        $consumosAInsertar[] = [
            'item_id' => $itemId,
            'cantidad' => $cantidadNecesaria,
        ];
    }

    if (empty($consumosAInsertar)) {
        if (!empty($pendientes)) {
            $resumenConsumo['pendientes'] += count($pendientes);
            foreach ($pendientes as $p) {
                $resumenConsumo['detalles'][] = $p;
            }
        }
        return;
    }

    if (!empty($pendientes)) {
        $resumenConsumo['pendientes'] += count($pendientes);
        foreach ($pendientes as $p) {
            $resumenConsumo['detalles'][] = $p;
        }
        return;
    }

    $origenEvento = 'repeticion_' . date('ymdHis') . '_' . (string)mt_rand(100, 999);
    if (strlen($origenEvento) > 30) {
        $origenEvento = substr($origenEvento, 0, 30);
    }

    $stmtInsert = $pdo->prepare("INSERT INTO inventario_consumos_examen
        (id_cotizacion, id_examen, item_id, cantidad_consumida, origen_evento, estado, usuario_id, observacion, fecha_hora)
        VALUES (?, ?, ?, ?, ?, 'aplicado', ?, ?, NOW())");

    foreach ($consumosAInsertar as $consumo) {
        $obs = 'Repetición confirmada al guardar resultados. Resultado ID: ' . (int)$idResultado . '. Motivo: ' . trim((string)$motivo);
        $stmtInsert->execute([
            $idCotizacion,
            $idExamen,
            (int)$consumo['item_id'],
            (float)$consumo['cantidad'],
            $origenEvento,
            $usuario_id > 0 ? $usuario_id : null,
            $obs,
        ]);
        $resumenConsumo['aplicados']++;
    }
};

$inventarioInternoDisponible = $tieneTablasInventarioInterno();
$alarmCols = $getAlarmColumnMap();
$hasAlarmColumns = !empty($alarmCols['alarma_activa']) && !empty($alarmCols['alarma_dias']) && !empty($alarmCols['alarma_fecha_objetivo']) && !empty($alarmCols['alarma_estado']);
$companyWhatsappNumber = $getCompanyWhatsappNumber();

if (!empty($examenes) && is_array($examenes)) {
    foreach ($examenes as $examen) {
        $id_resultado = $examen['id_resultado'] ?? null;
        $resultados = $examen['resultados'] ?? [];
        $repeticionConfirmada = (int)($examen['repeticion_confirmada'] ?? 0);
        $motivoRepeticion = trim((string)($examen['motivo_repeticion'] ?? ''));
        $imprimir_examen = isset($examen['imprimir_examen']) ? 1 : 0;
        $alarmaActiva = isset($examen['alarma_activa']) ? 1 : 0;
        $alarmaDiasRaw = isset($examen['alarma_dias']) ? (int)$examen['alarma_dias'] : 0;
        $alarmaDias = ($alarmaActiva === 1 && $alarmaDiasRaw > 0) ? $alarmaDiasRaw : null;
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

            $stmtSnapshot = $pdo->prepare("SELECT COALESCE(re.adicional_snapshot, e.adicional) AS adicional
                FROM resultados_examenes re
                JOIN examenes e ON e.id = re.id_examen
                WHERE re.id = :id
                LIMIT 1");
            $stmtSnapshot->execute(['id' => $id_resultado]);
            $snapshotRow = $stmtSnapshot->fetch(PDO::FETCH_ASSOC);
            $snapshotArr = [];
            if ($snapshotRow && !empty($snapshotRow['adicional'])) {
                $snapshotArr = json_decode((string)$snapshotRow['adicional'], true);
                if (!is_array($snapshotArr)) {
                    $snapshotArr = [];
                }
            }

            $resultados = $normalizarResultadosPorSnapshot($resultados, $snapshotArr);

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

            $hayDatoReal = false;
            foreach ($merged as $k => $v) {
                if ($k === 'imprimir_examen') {
                    continue;
                }
                if (!$isBlank($v)) {
                    $hayDatoReal = true;
                    break;
                }
            }

            $estadoResultado = $hayDatoReal ? 'completado' : 'pendiente';

            $json_resultados = json_encode($merged, JSON_UNESCAPED_UNICODE);
            if ($hasAlarmColumns) {
                $setWhatsappDestino = !empty($alarmCols['alarma_whatsapp_destino']) ? ", alarma_whatsapp_destino = CASE WHEN :alarma_activa = 1 THEN :alarma_whatsapp_destino ELSE NULL END" : '';
                $setUltimoAviso = !empty($alarmCols['alarma_ultimo_aviso']) ? ", alarma_ultimo_aviso = CASE WHEN :alarma_activa = 1 AND alarma_ultimo_aviso IS NULL THEN NULL WHEN :alarma_activa = 0 THEN NULL ELSE alarma_ultimo_aviso END" : '';

                $sql = "UPDATE resultados_examenes
                        SET resultados = :resultados,
                            estado = :estado_resultado,
                            alarma_activa = :alarma_activa,
                            alarma_dias = :alarma_dias,
                            alarma_fecha_objetivo = CASE
                                WHEN :alarma_activa = 1 AND :alarma_dias > 0 THEN DATE_ADD(fecha_ingreso, INTERVAL :alarma_dias DAY)
                                ELSE NULL
                            END,
                            alarma_estado = CASE
                                WHEN :alarma_activa = 1 AND :alarma_dias > 0 THEN
                                    CASE
                                        WHEN NOW() > DATE_ADD(fecha_ingreso, INTERVAL :alarma_dias DAY) THEN 'vencido'
                                        WHEN NOW() >= DATE_ADD(fecha_ingreso, INTERVAL GREATEST(:alarma_dias - 1, 0) DAY) THEN 'por_vencer'
                                        ELSE 'en_tiempo'
                                    END
                                ELSE NULL
                            END
                            {$setWhatsappDestino}
                            {$setUltimoAviso}
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'resultados' => $json_resultados,
                    'estado_resultado' => $estadoResultado,
                    'alarma_activa' => $alarmaActiva,
                    'alarma_dias' => $alarmaDias,
                    'alarma_whatsapp_destino' => $companyWhatsappNumber,
                    'id' => $id_resultado
                ]);
            } else {
                // Actualiza los resultados y el estado
                $sql = "UPDATE resultados_examenes SET resultados = :resultados, estado = :estado_resultado WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'resultados' => $json_resultados,
                    'estado_resultado' => $estadoResultado,
                    'id' => $id_resultado
                ]);
            }

            if ($inventarioInternoDisponible) {
                if ($hayDatoReal) {
                    $aplicarConsumoPorResultado((int)$id_resultado);
                    if ($repeticionConfirmada === 1 && $motivoRepeticion !== '') {
                        $aplicarConsumoRepeticion((int)$id_resultado, $motivoRepeticion);
                    }
                }
            }
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
    
    if ($resumenConsumo['aplicados'] > 0 || $resumenConsumo['pendientes'] > 0) {
        $mensaje = 'Resultados guardados. Consumos aplicados: ' . (int)$resumenConsumo['aplicados'] . '.';
        if ($resumenConsumo['pendientes'] > 0) {
            $mensaje .= ' Pendientes por stock interno: ' . (int)$resumenConsumo['pendientes'] . '.';
            if (!empty($resumenConsumo['detalles'])) {
                $mensaje .= ' ' . implode(' ', $resumenConsumo['detalles']);
            }
        }
        $_SESSION['mensaje'] = $mensaje;
    }

    if ($stayOnForm && $cotizacion_id) {
        header("Location: dashboard.php?vista=formulario&cotizacion_id=" . urlencode((string)$cotizacion_id));
    } else {
        header("Location: dashboard.php?vista=cotizaciones&mensaje=Resultados guardados correctamente");
    }
    exit;
} else {
    echo "Error: No se recibieron datos válidos.";
}
?>