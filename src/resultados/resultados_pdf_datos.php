<?php
// Funciones para obtener datos de cotización, paciente, empresa y resultados
function obtenerDatosCotizacion($pdo, $cotizacion_id) {
    $sqlCot = "SELECT c.id_empresa, c.id_convenio, c.referencia_personalizada, e.nombre_comercial, e.razon_social, v.nombre AS nombre_convenio
               FROM cotizaciones c
               LEFT JOIN empresas e ON c.id_empresa = e.id
               LEFT JOIN convenios v ON c.id_convenio = v.id
               WHERE c.id = :cotizacion_id";
    $stmtCot = $pdo->prepare($sqlCot);
    $stmtCot->execute(['cotizacion_id' => $cotizacion_id]);
    return $stmtCot->fetch(PDO::FETCH_ASSOC);
}

function obtenerResultadosExamenes($pdo, $cotizacion_id) {
    static $hasOrdenImpresion = null;
    if ($hasOrdenImpresion === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'orden_impresion'")->fetch(PDO::FETCH_ASSOC);
            $hasOrdenImpresion = !empty($col);
        } catch (Throwable $e) {
            $hasOrdenImpresion = false;
        }
    }

    $orderSql = $hasOrdenImpresion
        ? " ORDER BY COALESCE(re.orden_impresion, 2147483647), re.id"
        : " ORDER BY re.id";

    $sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.tipo_documento, c.id AS cliente_id
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id" . $orderSql;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cotizacion_id' => $cotizacion_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerDatosEmpresa($pdo) {
    $dominio_actual = $_SERVER['HTTP_HOST'];
    $sql3 = "SELECT nombre, ruc, dominio, direccion, telefono, celular, logo, firma FROM config_empresa WHERE dominio = ? LIMIT 1";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([$dominio_actual]);
    $empresa = $stmt3->fetch(PDO::FETCH_ASSOC);
    if (!$empresa) {
        // Si no hay empresa para el dominio, usar la primera empresa como fallback
        $sql3 = "SELECT nombre, ruc, dominio, direccion, telefono, celular, logo, firma FROM config_empresa LIMIT 1";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute();
        $empresa = $stmt3->fetch(PDO::FETCH_ASSOC);
        if (!$empresa) {
            $empresa = [
                "nombre" => "",
                "ruc" => "",
                "dominio" => "",
                "direccion" => "",
                "telefono" => "",
                "celular" => "",
                "logo" => "",
                "firma" => ""
            ];
        }
    }
    return $empresa;
}

function obtenerItemsResultados($pdo, $rows) {
    $items = [];
    foreach ($rows as $row) {
        $sql2 = "SELECT nombre AS nombre_examen, adicional FROM examenes WHERE id = :id_examen";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['id_examen' => $row['id_examen']]);
        $examen = $stmt2->fetch(PDO::FETCH_ASSOC);

        $adicional_src = $row['adicional_snapshot'] ?? null;
        if ($adicional_src === null || $adicional_src === '') {
            $adicional_src = $examen['adicional'] ?? null;
        }
        $adicional = $adicional_src ? json_decode($adicional_src, true) : [];
        $resultados_json = $row['resultados'] ? json_decode($row['resultados'], true) : [];

        // Respetar el flag de "Imprimir" por examen: si está deshabilitado, omitir todo el examen del PDF
        $imprimir_examen = isset($resultados_json['imprimir_examen']) ? intval($resultados_json['imprimir_examen']) : 1;
        if ($imprimir_examen !== 1) {
            continue; // No incluir este examen en el reporte
        }

        // Normaliza valores numéricos (quita comas)
        foreach ($resultados_json as $k => $v) {
            if (is_string($v) && preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $v)) {
                $resultados_json[$k] = str_replace(',', '', $v);
            }
        }

        usort($adicional, function ($a, $b) {
            return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
        });

        $normKey = function ($name) {
            $s = is_string($name) ? $name : '';
            $s = trim($s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return mb_strtolower($s, 'UTF-8');
        };

        $formatValor = function ($valor, $item) {
            if ($valor === '' || $valor === null) {
                return '';
            }
            if (!is_numeric($valor)) {
                return (string) $valor;
            }
            $num = floatval($valor);
            $dec = (isset($item['decimales']) && $item['decimales'] !== '') ? intval($item['decimales']) : null;
            if ($dec !== null) {
                return number_format($num, $dec, '.', '');
            }
            if (floor($num) == $num) {
                return (string) intval($num);
            }
            return (string) $valor;
        };

        $extractVars = function ($formula) {
            $vars = [];
            if (!is_string($formula) || trim($formula) === '') {
                return $vars;
            }
            if (preg_match_all('/\[(.*?)\]/', $formula, $m)) {
                foreach ($m[1] as $v) {
                    $vars[] = trim($v);
                }
            }
            return $vars;
        };

        $evalFormula = function ($formula, $valoresNorm) use ($extractVars, $normKey) {
            $vars = $extractVars($formula);
            foreach ($vars as $varName) {
                $k = $normKey($varName);
                if (!array_key_exists($k, $valoresNorm)) {
                    return null;
                }
                $raw = $valoresNorm[$k];
                if ($raw === '' || $raw === null || !is_numeric($raw)) {
                    return null;
                }
            }
            $expr = preg_replace_callback('/\[(.*?)\]/', function ($matches) use ($valoresNorm, $normKey) {
                $param = trim($matches[1]);
                $k = $normKey($param);
                $v = $valoresNorm[$k] ?? null;
                return (is_numeric($v)) ? $v : '0';
            }, $formula);

            // Soportar multiplicación implícita: 2(3+4) o (2+3)4
            $expr = preg_replace('/([0-9\.]|\))\s*\(/', '$1*(', $expr);
            $expr = preg_replace('/\)\s*([0-9\.-])/', ')*$1', $expr);

            if (strpos($expr, '^') !== false) {
                $expr = str_replace('^', '**', $expr);
            }
            try {
                $res = eval('return ' . $expr . ';');
                return is_numeric($res) ? floatval($res) : null;
            } catch (Throwable $e) {
                return null;
            }
        };

        // Índice normalizado para compatibilidad cuando cambia el nombre solo en mayúsculas/minúsculas o signos.
        $resultadosNorm = [];
        foreach ($resultados_json as $k => $v) {
            if ($k === 'imprimir_examen') {
                continue;
            }
            $nk = $normKey($k);
            if ($nk !== '' && !array_key_exists($nk, $resultadosNorm)) {
                $resultadosNorm[$nk] = $v;
            }
        }
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

        $getResultado = function ($nombre, $item = null, $default = '') use ($resultados_json, $resultadosNorm, $normKey, $buildStableKey) {
            $stableKey = $buildStableKey($item);
            if ($stableKey !== '' && array_key_exists($stableKey, $resultados_json)) {
                return $resultados_json[$stableKey];
            }
            if (isset($resultados_json[$nombre])) {
                return $resultados_json[$nombre];
            }
            $upper = mb_strtoupper((string) $nombre, 'UTF-8');
            if (isset($resultados_json[$upper])) {
                return $resultados_json[$upper];
            }
            $nk = $normKey($nombre);
            if ($nk !== '' && array_key_exists($nk, $resultadosNorm)) {
                return $resultadosNorm[$nk];
            }
            return $default;
        };

        $valores = [];
        $valoresNorm = [];
        $ordered = [];
        $formulaItems = [];

        foreach ($adicional as $item) {
            if (!in_array($item['tipo'], ['Parámetro', 'Título', 'Subtítulo', 'Texto Largo'])) {
                continue;
            }

            $nombre = $item['nombre'];

            if ($item['tipo'] === 'Parámetro') {
                $valor = $getResultado($nombre, $item, '');
                $valores[$nombre] = $valor;
                $valoresNorm[$normKey($nombre)] = $valor;
                $ordered[] = ['kind' => 'param', 'item' => $item, 'nombre' => $nombre];
                if (!empty($item['formula'])) {
                    $formulaItems[] = ['nombre' => $nombre, 'item' => $item];
                } else {
                    $valores[$nombre] = $formatValor($valor, $item);
                    $valoresNorm[$normKey($nombre)] = $valores[$nombre];
                }
            } elseif ($item['tipo'] === 'Texto Largo') {
                $ordered[] = ['kind' => 'texto', 'item' => $item, 'nombre' => $nombre];
            } else {
                $ordered[] = ['kind' => 'otro', 'item' => $item, 'nombre' => $nombre];
            }
        }

        // Resolver fórmulas en cadena (A depende de B) con iteraciones.
        $maxIter = max(1, count($formulaItems) + 3);
        for ($i = 0; $i < $maxIter; $i++) {
            $changed = false;
            foreach ($formulaItems as $fi) {
                $nombre = $fi['nombre'];
                $item = $fi['item'];
                $res = $evalFormula($item['formula'], $valoresNorm);
                if ($res === null) {
                    continue;
                }
                $formatted = $formatValor($res, $item);
                if (($valores[$nombre] ?? '') !== $formatted) {
                    $valores[$nombre] = $formatted;
                    $valoresNorm[$normKey($nombre)] = $formatted;
                    $changed = true;
                }
            }
            if (!$changed) {
                break;
            }
        }

        $examen_items = [];
        foreach ($ordered as $entry) {
            $item = $entry['item'];
            $nombre = $entry['nombre'];

            if ($entry['kind'] === 'param') {
                $valor = $valores[$nombre] ?? '';
                // Si no es fórmula, asegurar formato final.
                if (empty($item['formula'])) {
                    $valor = $formatValor($valor, $item);
                } else {
                    // Si no se pudo recalcular (dependencias faltantes), usar el valor guardado si existe.
                    $raw = $getResultado($nombre, $item, '');
                    if (($valor === '' || $valor === null) && $raw !== '') {
                        $valor = $formatValor($raw, $item);
                    }
                }
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'valor' => $valor,
                    'tipo' => 'Parámetro'
                ]);
            } elseif ($entry['kind'] === 'texto') {
                $valor = $getResultado($nombre, $item, '');
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'valor' => $valor,
                    'tipo' => 'Texto Largo'
                ]);
            } else {
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre
                ]);
            }
        }

        $items = array_merge($items, $examen_items);
    }
    return $items;
}
