<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../resultados/servicios/SnapshotSyncService.php';

function capitalizar($texto)
{
    return mb_convert_case(trim($texto), MB_CASE_TITLE, "UTF-8");
}

function generarIdParametroUnico(array &$usados)
{
    do {
        $id = 'param_' . (int)round(microtime(true) * 1000) . '_' . random_int(100000, 999999);
    } while (isset($usados[$id]));
    $usados[$id] = true;
    return $id;
}

function normalizarIdsParametroUnicos($adicionalJson)
{
    $arr = json_decode((string)$adicionalJson, true);
    if (!is_array($arr)) {
        return [null, 0];
    }

    $tiposConValor = ['Parámetro', 'Campo', 'Texto Largo'];
    $usados = [];
    $regenerados = 0;

    foreach ($arr as &$fila) {
        if (!is_array($fila)) {
            continue;
        }

        $tipo = (string)($fila['tipo'] ?? '');
        if (!in_array($tipo, $tiposConValor, true)) {
            continue;
        }

        $id = trim((string)($fila['id_parametro'] ?? ''));
        if ($id === '' || isset($usados[$id])) {
            $fila['id_parametro'] = generarIdParametroUnico($usados);
            $regenerados++;
            continue;
        }

        $usados[$id] = true;
    }
    unset($fila);

    return [json_encode($arr, JSON_UNESCAPED_UNICODE), $regenerados];
}
$id = intval($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = capitalizar($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $metodologia = capitalizar($_POST['metodologia'] ?? '');
    $tiempo_respuesta = trim($_POST['tiempo_respuesta'] ?? '');
    $preanalitica_cliente = trim($_POST['preanalitica_cliente'] ?? '');
    $preanalitica_referencias = trim($_POST['preanalitica_referencias'] ?? '');
    $tipo_muestra = capitalizar($_POST['tipo_muestra'] ?? '');
    $tipo_tubo = capitalizar($_POST['tipo_tubo'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $precio_publico = floatval($_POST['precio_publico'] ?? 0);

    $adicional = $_POST['adicional'] ?? '';

    $vigente = isset($_POST['vigente']) ? 1 : 0;

    if ($adicional !== null) {
        json_decode($adicional);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = 'El formato de parámetros adicionales no es válido.';
            header('Location: dashboard.php?vista=form_examen&id=' . $id);
            exit;
        }
        // Normalizar decimales en referencias (comas → puntos) antes de guardar
        $adicional_dec = json_decode($adicional, true);
        if (is_array($adicional_dec)) {
            foreach ($adicional_dec as &$fila) {
                if (!empty($fila['referencias']) && is_array($fila['referencias'])) {
                    foreach ($fila['referencias'] as &$ref) {
                        foreach (['valor_min', 'valor_max'] as $key) {
                            if (isset($ref[$key]) && $ref[$key] !== '') {
                                $v = str_replace(',', '.', (string)$ref[$key]);
                                $ref[$key] = $v;
                            }
                        }
                    }
                    unset($ref);
                }
            }
            unset($fila);
            $adicional = json_encode($adicional_dec, JSON_UNESCAPED_UNICODE);
        }

        [$adicionalNormalizado, $idsRegenerados] = normalizarIdsParametroUnicos($adicional);
        if ($adicionalNormalizado === null) {
            $_SESSION['error'] = 'El formato de parámetros adicionales no es válido.';
            header('Location: dashboard.php?vista=form_examen&id=' . $id);
            exit;
        }
        $adicional = $adicionalNormalizado;
    }


    try {
        $stmt = $pdo->prepare("UPDATE examenes SET 
            codigo = ?, nombre = ?, descripcion = ?, area = ?, metodologia = ?, tiempo_respuesta = ?, 
            preanalitica_cliente = ?, preanalitica_referencias = ?, tipo_muestra = ?, tipo_tubo = ?, observaciones = ?, precio_publico = ?, adicional = ?,vigente = ?
            WHERE id = ?");
        $stmt->execute([
            $codigo,
            $nombre,
            $descripcion,
            $area,
            $metodologia,
            $tiempo_respuesta,
            $preanalitica_cliente,
            $preanalitica_referencias,
            $tipo_muestra,
            $tipo_tubo,
            $observaciones,
            $precio_publico,
            $adicional,
            $vigente,
            $id
        ]);
        $sincronizados = 0;
        try {
            $snapshotSyncService = new SnapshotSyncService($pdo);
            $sincronizados = (int)$snapshotSyncService->syncExamSnapshotsPreservingHeaders($id);
        } catch (Exception $e) {
            $sincronizados = 0;
        }

        $_SESSION['mensaje'] = "Examen actualizado correctamente.";
        if (!empty($idsRegenerados)) {
            $_SESSION['mensaje'] .= " Se normalizaron {$idsRegenerados} ID(s) de parámetros para evitar duplicados.";
        }
        if ($sincronizados > 0) {
            $_SESSION['mensaje'] .= " Se actualizaron automáticamente {$sincronizados} formato(s) pendiente(s) en resultados.";
        }
        header('Location: dashboard.php?vista=examenes');
        exit;
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al actualizar examen: " . $e->getMessage();
        header('Location: dashboard.php?vista=form_examen&id=' . $id);
        exit;
    }
}
