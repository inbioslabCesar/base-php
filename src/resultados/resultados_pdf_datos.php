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

        // Normaliza valores numéricos (quita comas)
        foreach ($resultados_json as $k => $v) {
            if (is_string($v) && preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $v)) {
                $resultados_json[$k] = str_replace(',', '', $v);
            }
        }

        usort($adicional, function ($a, $b) {
            return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
        });

        $valores = [];
        $examen_items = [];
        foreach ($adicional as $item) {
            // Ignorar tipo "Campo" y otros no relevantes
            if (!in_array($item['tipo'], ['Parámetro', 'Título', 'Subtítulo'])) {
                continue;
            }
            $nombre = $item['nombre'];
            $valor = '';

            if ($item['tipo'] === 'Parámetro') {
                if (!empty($item['formula'])) {
                    $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
                    if ($valor === '' || $valor === null) {
                        $formula = $item['formula'];
                        $formula_eval = preg_replace_callback('/\[(.*?)\]/', function($matches) use ($valores) {
                            $param = trim($matches[1]);
                            return isset($valores[$param]) && is_numeric($valores[$param]) ? $valores[$param] : 0;
                        }, $formula);
                        try {
                            $expr = $formula_eval;
                            if (strpos($expr, '^') !== false) {
                                $expr = str_replace('^', '**', $expr);
                            }
                            $valor = eval('return ' . $expr . ';');
                            if (is_numeric($valor)) {
                                $dec = (isset($item['decimales']) && $item['decimales'] !== '') ? intval($item['decimales']) : null;
                                if ($dec !== null) {
                                    $valor = number_format($valor, $dec, '.', '');
                                } else {
                                    // Sin decimales configurados: entero sin .0; fracción como valor natural
                                    if (floor($valor) == $valor) {
                                        $valor = (string) intval($valor);
                                    } else {
                                        $valor = (string) $valor;
                                    }
                                }
                            }
                        } catch (Throwable $e) {
                            $valor = '';
                        }
                    }
                } else {
                    $valor = isset($resultados_json[$nombre]) ? $resultados_json[$nombre] : '';
                    // Formateo para valores ingresados (sin fórmula): respetar decimales; sin decimales, natural
                    if ($valor !== '' && is_numeric($valor)) {
                        $dec = (isset($item['decimales']) && $item['decimales'] !== '') ? intval($item['decimales']) : null;
                        if ($dec !== null) {
                            // Si hay decimales configurados, respetarlos
                            $valor = number_format($valor, $dec, '.', '');
                        } else {
                            // Sin decimales configurados: entero sin .0, fracción como valor natural
                            if (floor($valor) == $valor) {
                                $valor = (string) intval($valor);
                            } else {
                                $valor = (string) $valor;
                            }
                        }
                    }
                }
                $valores[$nombre] = $valor;
                $examen_items[] = array_merge($item, [
                    "prueba" => $nombre,
                    "valor" => $valor,
                    "tipo" => "Parámetro"
                ]);
            } else {
                $examen_items[] = array_merge($item, [
                    "prueba" => $nombre
                ]);
            }
        }
        $items = array_merge($items, $examen_items);
    }
    return $items;
}
