<?php
// Servicio del Cotizador Express: cálculo autoritativo, historial, plantillas y HTML del PDF.
require_once __DIR__ . '/cotizador_express_utils.php';
require_once __DIR__ . '/../../config/operacion_context.php';

if (!function_exists('cotExpHistorialDisponible')) {
    function cotExpHistorialDisponible(PDO $pdo): bool
    {
        static $ok = null;
        if ($ok === null) {
            $ok = app_database_has_table($pdo, 'cotizaciones_express') && app_database_has_table($pdo, 'cotizaciones_express_items');
        }
        return $ok;
    }
}

if (!function_exists('cotExpPlantillaDefecto')) {
    function cotExpPlantillaDefecto(string $tipo): string
    {
        if ($tipo === 'info') {
            return "Hola{nombre_saludo} 👋\n"
                . "Le saluda *{laboratorio}* 🧪, su laboratorio clínico de confianza.\n\n"
                . "Con gusto le compartimos la información para sus exámenes:\n\n"
                . "{detalle}\n\n"
                . "{contacto}\n\n"
                . "¡Será un gusto atenderle! 😊";
        }
        return "Hola{nombre_saludo} 👋\n"
            . "Le saluda *{laboratorio}* 🧪, su laboratorio clínico de confianza.\n\n"
            . "Con gusto le compartimos su cotización{folio}:\n\n"
            . "{detalle}\n\n"
            . "{total}\n\n"
            . "{entrega}\n\n"
            . "{indicaciones}\n\n"
            . "🗓 Cotización referencial, vigente hasta el {vigencia}.\n\n"
            . "{enlace}\n\n"
            . "{contacto}\n\n"
            . "¡Será un gusto atenderle! 😊";
    }
}

if (!function_exists('cotExpPlantillaActual')) {
    // Devuelve la plantilla guardada (si existe la tabla y el registro) o la de fábrica.
    function cotExpPlantillaActual(PDO $pdo, string $tipo): array
    {
        $defecto = cotExpPlantillaDefecto($tipo);
        if (app_database_has_table($pdo, 'cotizador_express_plantillas')) {
            try {
                $stmt = $pdo->prepare('SELECT plantilla FROM cotizador_express_plantillas WHERE tipo = ? LIMIT 1');
                $stmt->execute([$tipo]);
                $txt = $stmt->fetchColumn();
                if (is_string($txt) && trim($txt) !== '') {
                    return ['plantilla' => $txt, 'defecto' => $defecto, 'personalizada' => true, 'editable' => true];
                }
            } catch (Throwable $e) {
            }
            return ['plantilla' => $defecto, 'defecto' => $defecto, 'personalizada' => false, 'editable' => true];
        }
        return ['plantilla' => $defecto, 'defecto' => $defecto, 'personalizada' => false, 'editable' => false];
    }
}

if (!function_exists('cotExpCalcular')) {
    // Recalcula en el servidor cantidades, precios y totales. $solicitados: [['id'=>int,'cant'=>int], ...]
    function cotExpCalcular(PDO $pdo, bool $conPrecios, $solicitados, string $perfilKey, float $descExtra): array
    {
        $catalogo = [];
        foreach (cotExpCatalogo($pdo, $conPrecios) as $ex) {
            $catalogo[$ex['id']] = $ex;
        }

        $cantidades = [];
        if (is_array($solicitados)) {
            foreach (array_slice($solicitados, 0, 100) as $s) {
                $id = (int)($s['id'] ?? 0);
                if (isset($catalogo[$id])) {
                    $cantidades[$id] = min(99, ($cantidades[$id] ?? 0) + max(1, (int)($s['cant'] ?? 1)));
                }
            }
        }

        $perfil = null;
        $descExtra = $conPrecios ? max(0.0, min(100.0, $descExtra)) : 0.0;
        if ($conPrecios) {
            $partes = explode(':', $perfilKey);
            if (count($partes) === 2 && in_array($partes[0], ['convenio', 'empresa'], true)) {
                $perfil = cotExpPerfilPorId($pdo, $partes[0], (int)$partes[1]);
            }
        }

        $lineas = [];
        $subtotal = 0.0;
        foreach ($cantidades as $id => $cant) {
            $ex = $catalogo[$id];
            $unit = 0.0;
            $sub = 0.0;
            if ($conPrecios) {
                $unit = $perfil
                    ? cotExpPrecioUnitario($ex['precio_publico'], $ex['precio_convenio'], (bool)$perfil['usar'], (float)$perfil['descuento'])
                    : round($ex['precio_publico'], 2);
                $sub = round($unit * $cant, 2);
                $subtotal += $sub;
            }
            $lineas[] = [
                'id' => $id,
                'nombre' => $ex['nombre'],
                'tipo_muestra' => $ex['tipo_muestra'],
                'tiempo_respuesta' => $ex['tiempo_respuesta'],
                'preanalitica_cliente' => $ex['preanalitica_cliente'],
                'cant' => $cant,
                'unit' => $unit,
                'sub' => $sub,
            ];
        }

        $montoDesc = round($subtotal * $descExtra / 100, 2);
        return [
            'lineas' => $lineas,
            'perfil' => $perfil,
            'subtotal' => round($subtotal, 2),
            'desc_pct' => $descExtra,
            'desc_monto' => $montoDesc,
            'total' => round($subtotal - $montoDesc, 2),
        ];
    }
}

if (!function_exists('cotExpUrlPublica')) {
    function cotExpUrlPublica(string $token): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $local = preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) === 1;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || !$local;
        return ($https ? 'https' : 'http') . '://' . $host . rtrim((string)BASE_URL, '/') . '/public/cotizacion_express_ver.php?t=' . $token;
    }
}

if (!function_exists('cotExpGuardar')) {
    // Guarda la cotización express con precios congelados. Devuelve id, folio, token y url.
    function cotExpGuardar(PDO $pdo, array $calc, array $meta): array
    {
        $perfil = $calc['perfil'];
        $token = bin2hex(random_bytes(20));
        $vigencia = max(1, min(90, (int)($meta['vigencia'] ?? 7)));
        $venceEn = (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->modify('+' . $vigencia . ' days')->format('Y-m-d');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO cotizaciones_express
                    (token, estado, modo_operativo, tarifa_tipo, tarifa_id, tarifa_nombre, destinatario_nombre, destinatario_telefono,
                     cliente_id, descuento_extra, subtotal, total, vigencia_dias, vence_en, incluir_indicaciones, canales, ultimo_envio_en,
                     creado_por, rol_creador, creado_en)
                 VALUES (?, \'emitida\', ?, ?,                  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $canal = (string)($meta['canal'] ?? '');
            $ahora = (new DateTimeImmutable('now', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s');
            $stmt->execute([
                $token,
                (string)($meta['modo'] ?? 'PARTICULAR'),
                $perfil ? $perfil['tipo'] : 'particular',
                $perfil ? $perfil['id'] : null,
                $perfil ? mb_substr($perfil['nombre'], 0, 150) : null,
                $meta['destinatario'] !== '' ? mb_substr((string)$meta['destinatario'], 0, 150) : null,
                $meta['telefono'] !== '' ? mb_substr((string)$meta['telefono'], 0, 20) : null,
                !empty($meta['cliente_id']) ? (int)$meta['cliente_id'] : null,
                $calc['desc_pct'],
                $calc['subtotal'],
                $calc['total'],
                $vigencia,
                $venceEn,
                !empty($meta['indicaciones']) ? 1 : 0,
                $canal !== '' ? $canal : null,
                $canal !== '' ? $ahora : null,
                (int)($meta['usuario_id'] ?? 0) ?: null,
                (string)($meta['rol'] ?? ''),
                $ahora,
            ]);
            $id = (int)$pdo->lastInsertId();
            $folio = sprintf('EXP-%s-%05d', date('Y'), $id);
            $pdo->prepare('UPDATE cotizaciones_express SET folio = ? WHERE id = ?')->execute([$folio, $id]);

            $stmtIt = $pdo->prepare('INSERT INTO cotizaciones_express_items (express_id, examen_id, nombre_examen, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($calc['lineas'] as $l) {
                $stmtIt->execute([$id, $l['id'], mb_substr($l['nombre'], 0, 255), $l['cant'], $l['unit'], $l['sub']]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['id' => $id, 'folio' => $folio, 'token' => $token, 'url' => cotExpUrlPublica($token), 'vence' => date('d/m/Y', strtotime($venceEn))];
    }
}

if (!function_exists('cotExpEstadoEfectivo')) {
    function cotExpEstadoEfectivo(array $row): string
    {
        $estado = (string)($row['estado'] ?? 'emitida');
        if ($estado === 'emitida' && !empty($row['vence_en']) && $row['vence_en'] < date('Y-m-d')) {
            return 'vencida';
        }
        return $estado;
    }
}

if (!function_exists('cotExpLuminancia')) {
    function cotExpLuminancia(string $hex): float
    {
        $hex = ltrim($hex, '#');
        return (0.2126 * hexdec(substr($hex, 0, 2)) + 0.7152 * hexdec(substr($hex, 2, 2)) + 0.0722 * hexdec(substr($hex, 4, 2))) / 255;
    }
}

if (!function_exists('cotExpMezclarConBlanco')) {
    // $pct = 0..1 de color; el resto es blanco (genera un tono suave del color de la empresa).
    function cotExpMezclarConBlanco(string $hex, float $pct): string
    {
        $hex = ltrim($hex, '#');
        $mix = static fn(string $c): int => (int)round(hexdec($c) * $pct + 255 * (1 - $pct));
        return sprintf('#%02x%02x%02x', $mix(substr($hex, 0, 2)), $mix(substr($hex, 2, 2)), $mix(substr($hex, 4, 2)));
    }
}

if (!function_exists('cotExpPaleta')) {
    // Colores del PDF según la empresa configurada (config_empresa). El acento debe ser lo bastante
    // oscuro para que el texto blanco del encabezado de la tabla se lea; si el color principal es claro
    // (p. ej. blanco) se usa el de botones o el del pie, y como último recurso se oscurece el principal.
    function cotExpPaleta(PDO $pdo, ?array $config = null): array
    {
        $tema = ui_theme_get_active($pdo, $config);
        $acento = '';
        foreach ([$tema['primary'], $tema['button'], $tema['footer']] as $candidato) {
            if (ui_theme_is_hex_color((string)$candidato) && cotExpLuminancia($candidato) <= 0.55) {
                $acento = strtolower($candidato);
                break;
            }
        }
        if ($acento === '') {
            // Ningún color es lo bastante oscuro: se oscurece el primer color que no sea casi blanco
            // (conserva el tono de la empresa); si todos son blancos se usa un verde azulado neutro.
            $base = '';
            foreach ([$tema['primary'], $tema['button'], $tema['footer']] as $candidato) {
                if (ui_theme_is_hex_color((string)$candidato) && cotExpLuminancia($candidato) < 0.93) {
                    $base = strtolower($candidato);
                    break;
                }
            }
            $acento = $base !== '' ? $base : '#0f766e';
            for ($i = 0; $i < 12 && cotExpLuminancia($acento) > 0.45; $i++) {
                $acento = ui_theme_adjust_brightness($acento, -20);
            }
        }
        $texto = ui_theme_normalize_hex((string)$tema['text'], '#1f2937');
        if (cotExpLuminancia($texto) > 0.45) {
            $texto = '#1f2937';
        }
        return [
            'acento' => $acento,
            'acento_suave' => cotExpMezclarConBlanco($acento, 0.12),
            'texto' => $texto,
        ];
    }
}

if (!function_exists('cotExpPdfHtml')) {
    // $d: con_precios, lineas, subtotal, desc_pct, desc_monto, total, destinatario, tarifa_texto, vence, folio, incluir_indicaciones
    function cotExpPdfHtml(PDO $pdo, array $d): string
    {
        $h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $conPrecios = !empty($d['con_precios']);
        $cfgMoneda = currency_get_config($pdo);
        $money = static fn(float $v): string => money_format_local($v, $cfgMoneda);
        $empresa = cotExpEmpresa($pdo);
        $logo = cotExpLogoDataUri($empresa['logo']);

        $filas = '';
        $n = 0;
        foreach ($d['lineas'] as $l) {
            $n++;
            $nota = [];
            if ($l['tipo_muestra'] !== '') {
                $nota[] = 'Muestra: ' . $l['tipo_muestra'];
            }
            if ($l['tiempo_respuesta'] !== '') {
                $nota[] = 'Entrega: ' . $l['tiempo_respuesta'];
            }
            if (!empty($d['incluir_indicaciones']) && $l['preanalitica_cliente'] !== '') {
                $nota[] = 'Indicaciones: ' . $l['preanalitica_cliente'];
            }
            $detalle = '<b>' . $h($l['nombre']) . '</b>' . (empty($nota) ? '' : '<br><span class="nota">' . $h(implode(' · ', $nota)) . '</span>');
            if ($conPrecios) {
                $filas .= '<tr><td class="c">' . $n . '</td><td>' . $detalle . '</td><td class="c">' . (int)$l['cant'] . '</td><td class="r">' . $h($money((float)$l['unit'])) . '</td><td class="r">' . $h($money((float)$l['sub'])) . '</td></tr>';
            } else {
                $filas .= '<tr><td class="c">' . $n . '</td><td>' . $detalle . '</td></tr>';
            }
        }

        $totalHtml = '';
        if ($conPrecios) {
            if ($d['desc_pct'] > 0) {
                $totalHtml .= '<tr><td colspan="4" class="r">Subtotal</td><td class="r">' . $h($money((float)$d['subtotal'])) . '</td></tr>';
                $totalHtml .= '<tr><td colspan="4" class="r">Descuento (' . $h(rtrim(rtrim(number_format((float)$d['desc_pct'], 2), '0'), '.')) . '%)</td><td class="r">- ' . $h($money((float)$d['desc_monto'])) . '</td></tr>';
            }
            $totalHtml .= '<tr class="tot"><td colspan="4" class="r">TOTAL</td><td class="r">' . $h($money((float)$d['total'])) . '</td></tr>';
        }

        $cabecera = $conPrecios
            ? '<tr><th width="5%">#</th><th>Examen</th><th width="8%">Cant.</th><th width="16%">P. unit.</th><th width="16%">Subtotal</th></tr>'
            : '<tr><th width="6%">#</th><th>Examen e indicaciones</th></tr>';

        $telefonos = implode(' / ', array_filter([$empresa['telefono'], $empresa['celular']]));
        $contacto = array_filter([$empresa['direccion'], $telefonos !== '' ? 'Tel: ' . $telefonos : '', $empresa['email']]);
        $vence = (string)($d['vence'] ?? '');
        $pal = cotExpPaleta($pdo);

        return '<style>
body{font-family:sans-serif;font-size:10pt;color:' . $pal['texto'] . '}
h1{font-size:16pt;margin:0;color:' . $pal['acento'] . '}
table.t{width:100%;border-collapse:collapse;margin-top:10px}
table.t th{background:' . $pal['acento'] . ';color:#ffffff;padding:6px;text-align:left;font-size:9pt}
table.t td{border-bottom:1px solid #e5e7eb;padding:6px;vertical-align:top}
.c{text-align:center}.r{text-align:right}
.nota{font-size:8pt;color:#6b7280}
.tot td{font-size:12pt;font-weight:bold;background:' . $pal['acento_suave'] . ';color:' . $pal['acento'] . '}
.pie{margin-top:14px;font-size:8.5pt;color:#6b7280}
</style>
<table width="100%"><tr>
<td width="35%">' . ($logo !== '' ? '<img src="' . $logo . '" height="115">' : '') . '</td>
<td align="right"><h1>' . ($conPrecios ? 'COTIZACIÓN' : 'INFORMACIÓN DE EXÁMENES') . '</h1>'
            . (!empty($d['folio']) ? '<b>N° ' . $h($d['folio']) . '</b><br>' : '')
            . '<b>' . $h($empresa['nombre']) . '</b>' . ($empresa['ruc'] !== '' ? ' · RUC ' . $h($empresa['ruc']) : '') . '<br>'
            . $h(implode(' · ', $contacto)) . '</td></tr></table>
<p>' . (!empty($d['destinatario']) ? '<b>Para:</b> ' . $h($d['destinatario']) . '<br>' : '')
            . (!empty($d['tarifa_texto']) ? '<b>Tarifa:</b> ' . $h($d['tarifa_texto']) . '<br>' : '')
            . '<b>Fecha:</b> ' . $h($d['fecha'] ?? date('d/m/Y')) . ($conPrecios && $vence !== '' ? ' &nbsp; <b>Vigencia:</b> hasta el ' . $h($vence) : '') . '</p>
<table class="t">' . $cabecera . $filas . $totalHtml . '</table>
<p class="pie">' . ($conPrecios
                ? 'Cotización referencial sujeta a confirmación al momento de la atención.' . ($vence !== '' ? ' Válida hasta el ' . $h($vence) . '.' : '')
                : 'Información referencial para la preparación del paciente. Consulte en recepción cualquier duda.') . '</p>';
    }
}

if (!function_exists('cotExpPdfSalida')) {
    function cotExpPdfSalida(string $html, string $archivo): void
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($archivo, 'I');
    }
}
