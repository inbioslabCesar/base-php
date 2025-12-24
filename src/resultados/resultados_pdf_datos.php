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
    $sql = "SELECT re.*, c.nombre, c.apellido, c.edad, c.sexo, c.codigo_cliente, c.dni, c.tipo_documento, c.id AS cliente_id
        FROM resultados_examenes re
        JOIN clientes c ON re.id_cliente = c.id
        WHERE re.id_cotizacion = :cotizacion_id";
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

        $adicional = $examen && $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
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
                $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
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
                // Recalcular siempre que sea posible (para evitar quedar con valores antiguos/incorrectos guardados).
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
                    if (($valor === '' || $valor === null) && isset($resultados_json[$nombre]) && $resultados_json[$nombre] !== '') {
                        $valor = $formatValor($resultados_json[$nombre], $item);
                    }
                }
                $examen_items[] = array_merge($item, [
                    'prueba' => $nombre,
                    'valor' => $valor,
                    'tipo' => 'Parámetro'
                ]);
            } elseif ($entry['kind'] === 'texto') {
                $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
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
