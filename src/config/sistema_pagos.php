<?php
// Control de pagos del sistema (mensualidad / alojamiento anual).
// Tolera que las tablas aun no existan: en ese caso no hay aviso ni bloqueo.
require_once __DIR__ . '/operacion_context.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/ui_theme.php';

const SP_ROLES_AVISO = ['admin', 'recepcionista', 'laboratorista'];
const SP_TZ = 'America/Lima';
const SP_MAX_CICLOS = 600;

if (!function_exists('sp_tz')) {
    function sp_tz(): DateTimeZone
    {
        return new DateTimeZone(SP_TZ);
    }
}

if (!function_exists('sp_ahora')) {
    function sp_ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', sp_tz());
    }
}

if (!function_exists('sp_tablas_ok')) {
    function sp_tablas_ok(PDO $pdo): bool
    {
        static $ok = null;
        if ($ok === null) {
            $ok = app_database_has_table($pdo, 'sistema_suscripcion')
                && app_database_has_table($pdo, 'sistema_suscripcion_pagos');
        }
        return $ok;
    }
}

if (!function_exists('sp_empresa_actual_id')) {
    function sp_empresa_actual_id(PDO $pdo): int
    {
        $cfg = ui_theme_fetch_company_config($pdo);
        return (int)($cfg['id'] ?? 0);
    }
}

if (!function_exists('sp_obtener_suscripcion')) {
    function sp_obtener_suscripcion(PDO $pdo, int $empresaId): ?array
    {
        if ($empresaId <= 0 || !sp_tablas_ok($pdo)) {
            return null;
        }
        try {
            $st = $pdo->prepare('SELECT * FROM sistema_suscripcion WHERE empresa_cfg_id = ? LIMIT 1');
            $st->execute([$empresaId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('sp_fecha_clamp')) {
    // Fecha con el dia pedido o, si el mes es mas corto, su ultimo dia (31 en febrero -> 28/29).
    function sp_fecha_clamp(int $anio, int $mes, int $dia): DateTimeImmutable
    {
        $ultimo = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes), sp_tz()))->format('t');
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d 23:59:59', $anio, $mes, min(max($dia, 1), $ultimo)), sp_tz());
    }
}

if (!function_exists('sp_ciclo')) {
    // Ciclo n-esimo: ['periodo' => 'YYYY-MM'|'YYYY', 'vence' => DateTimeImmutable (23:59:59)]
    function sp_ciclo(array $sus, int $n): ?array
    {
        $base = (string)($sus['primer_vencimiento'] ?? '');
        if ($base === '' || $base === '0000-00-00') {
            return null;
        }
        try {
            $b = new DateTimeImmutable($base, sp_tz());
        } catch (Throwable $e) {
            return null;
        }
        $modalidad = (string)($sus['modalidad'] ?? 'EXENTO');
        if ($modalidad === 'MENSUAL') {
            $total = ((int)$b->format('Y')) * 12 + ((int)$b->format('n') - 1) + $n;
            $anio = intdiv($total, 12);
            $mes = $total % 12 + 1;
            $dia = max(1, min(31, (int)($sus['dia_cobro'] ?? 1)));
            return ['periodo' => sprintf('%04d-%02d', $anio, $mes), 'vence' => sp_fecha_clamp($anio, $mes, $dia)];
        }
        if ($modalidad === 'ANUAL') {
            $anio = (int)$b->format('Y') + $n;
            return ['periodo' => sprintf('%04d', $anio), 'vence' => sp_fecha_clamp($anio, (int)$b->format('n'), (int)$b->format('j'))];
        }
        return null;
    }
}

if (!function_exists('sp_periodos_pagados')) {
    function sp_periodos_pagados(PDO $pdo, int $suscripcionId): array
    {
        try {
            $st = $pdo->prepare('SELECT periodo FROM sistema_suscripcion_pagos WHERE suscripcion_id = ? AND anulado = 0 AND periodo IS NOT NULL');
            $st->execute([$suscripcionId]);
            return array_fill_keys($st->fetchAll(PDO::FETCH_COLUMN) ?: [], true);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('sp_dias_aviso')) {
    function sp_dias_aviso(array $sus): int
    {
        if (isset($sus['dias_aviso']) && $sus['dias_aviso'] !== null && $sus['dias_aviso'] !== '') {
            return max(0, (int)$sus['dias_aviso']);
        }
        return ($sus['modalidad'] ?? '') === 'ANUAL' ? 30 : 5;
    }
}

if (!function_exists('sp_proximos_ciclos')) {
    // Primeros $cantidad ciclos desde el primero impago (para el panel del ingeniero).
    function sp_proximos_ciclos(PDO $pdo, array $sus, int $cantidad = 4): array
    {
        $pagados = sp_periodos_pagados($pdo, (int)$sus['id']);
        $out = [];
        for ($n = 0; $n < SP_MAX_CICLOS && count($out) < $cantidad; $n++) {
            $c = sp_ciclo($sus, $n);
            if ($c === null) {
                break;
            }
            if (isset($pagados[$c['periodo']])) {
                continue;
            }
            $out[] = $c;
        }
        return $out;
    }
}

if (!function_exists('sp_calcular_estado')) {
    function sp_calcular_estado(PDO $pdo, ?int $empresaId = null, ?DateTimeImmutable $ahora = null): array
    {
        $vacio = ['estado' => 'SIN_CONFIG', 'mostrar' => false, 'bloqueado' => false];
        $empresaId = $empresaId ?? sp_empresa_actual_id($pdo);
        $sus = sp_obtener_suscripcion($pdo, $empresaId);
        if (!$sus) {
            return $vacio;
        }
        if (($sus['modalidad'] ?? 'EXENTO') === 'EXENTO' || sp_ciclo($sus, 0) === null) {
            return ['estado' => 'EXENTO', 'mostrar' => false, 'bloqueado' => false] + ['suscripcion' => $sus];
        }

        $ahora = $ahora ?? sp_ahora();
        $pagados = sp_periodos_pagados($pdo, (int)$sus['id']);
        $diasAviso = sp_dias_aviso($sus);

        $pendiente = null;
        $vencidos = 0;
        for ($n = 0; $n < SP_MAX_CICLOS; $n++) {
            $c = sp_ciclo($sus, $n);
            if ($c === null) {
                break;
            }
            $inicioAviso = $c['vence']->setTime(0, 0, 0)->modify('-' . $diasAviso . ' days');
            if ($inicioAviso > $ahora) {
                break;
            }
            if (isset($pagados[$c['periodo']])) {
                continue;
            }
            if ($pendiente === null) {
                $pendiente = $c;
            }
            if ($c['vence'] < $ahora) {
                $vencidos++;
                continue;
            }
            break;
        }

        if ($pendiente === null) {
            return ['estado' => 'OK', 'mostrar' => false, 'bloqueado' => false, 'suscripcion' => $sus];
        }

        $vence = $pendiente['vence'];
        $seg = $vence->getTimestamp() - $ahora->getTimestamp();
        $hoy = $ahora->setTime(0, 0, 0);
        $diasCal = (int)$hoy->diff($vence->setTime(0, 0, 0))->format('%r%a');

        if ($seg < 0) {
            $estado = 'VENCIDO';
        } elseif ($diasCal === 0) {
            $estado = 'HOY';
        } elseif ($seg < 48 * 3600) {
            $estado = 'URGENTE';
        } else {
            $estado = 'AVISO';
        }

        $gracia = ($sus['dias_gracia'] !== null && $sus['dias_gracia'] !== '') ? max(0, (int)$sus['dias_gracia']) : null;
        $bloqueoTs = $gracia !== null ? $vence->modify('+' . $gracia . ' days')->getTimestamp() : null;
        $bloqueado = $bloqueoTs !== null && $ahora->getTimestamp() > $bloqueoTs;
        if ($bloqueado) {
            $estado = 'SUSPENDIDO';
        }

        return [
            'estado' => $estado,
            'mostrar' => true,
            'bloqueado' => $bloqueado,
            'modalidad' => (string)$sus['modalidad'],
            'concepto' => $sus['modalidad'] === 'ANUAL' ? 'ALOJAMIENTO' : 'MENSUALIDAD',
            'periodo' => $pendiente['periodo'],
            'monto' => (float)$sus['monto'],
            'plan' => (string)($sus['plan'] ?? ''),
            'venc_ts' => $vence->getTimestamp(),
            'venc_fecha' => $vence->format('d/m/Y'),
            'dias_restantes' => $diasCal,
            'dias_vencido' => $seg < 0 ? max(1, -$diasCal) : 0,
            'ciclos_vencidos' => $vencidos,
            'bloqueo_ts' => $bloqueoTs,
            'ahora_ts' => $ahora->getTimestamp(),
            'medios_pago' => (string)($sus['medios_pago'] ?? ''),
            'mensaje_extra' => (string)($sus['mensaje_extra'] ?? ''),
            'suscripcion' => $sus,
        ];
    }
}

if (!function_exists('sp_estado_para_rol')) {
    function sp_estado_para_rol(PDO $pdo, string $rol): ?array
    {
        static $cache = [];
        $rol = strtolower(trim($rol));
        if (!in_array($rol, SP_ROLES_AVISO, true) || !sp_tablas_ok($pdo)) {
            return null;
        }
        if (!array_key_exists('estado', $cache)) {
            try {
                $cache['estado'] = sp_calcular_estado($pdo);
            } catch (Throwable $e) {
                $cache['estado'] = null;
            }
        }
        $e = $cache['estado'];
        return (is_array($e) && !empty($e['mostrar'])) ? $e : null;
    }
}

// ---------- Escritura (solo engineer; el control de rol lo hace el llamador) ----------

if (!function_exists('sp_guardar_config')) {
    function sp_guardar_config(PDO $pdo, int $empresaId, array $d): void
    {
        $modalidad = in_array($d['modalidad'] ?? '', ['MENSUAL', 'ANUAL', 'EXENTO'], true) ? $d['modalidad'] : 'EXENTO';
        $primer = trim((string)($d['primer_vencimiento'] ?? ''));
        $fecha = ($primer !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $primer)) ? $primer : null;
        $dia = max(1, min(31, (int)($d['dia_cobro'] ?? 1)));
        if ($modalidad === 'MENSUAL' && $fecha !== null) {
            // El primer ciclo es el mes de la fecha indicada, con el dia de cobro elegido.
            $b = new DateTimeImmutable($fecha, sp_tz());
            $fecha = sp_fecha_clamp((int)$b->format('Y'), (int)$b->format('n'), $dia)->format('Y-m-d');
        }
        $avisoRaw = trim((string)($d['dias_aviso'] ?? ''));
        $graciaRaw = trim((string)($d['dias_gracia'] ?? ''));
        $params = [
            $empresaId,
            $modalidad,
            mb_substr(trim((string)($d['plan'] ?? '')), 0, 60) ?: null,
            max(0, round((float)($d['monto'] ?? 0), 2)),
            $dia,
            $fecha,
            $avisoRaw === '' ? null : max(0, min(365, (int)$avisoRaw)),
            $graciaRaw === '' ? null : max(0, min(365, (int)$graciaRaw)),
            trim((string)($d['medios_pago'] ?? '')) ?: null,
            mb_substr(trim((string)($d['mensaje_extra'] ?? '')), 0, 255) ?: null,
        ];
        $sql = 'INSERT INTO sistema_suscripcion (empresa_cfg_id, modalidad, plan, monto, dia_cobro, primer_vencimiento, dias_aviso, dias_gracia, medios_pago, mensaje_extra)
                VALUES (?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE modalidad=VALUES(modalidad), plan=VALUES(plan), monto=VALUES(monto), dia_cobro=VALUES(dia_cobro),
                primer_vencimiento=VALUES(primer_vencimiento), dias_aviso=VALUES(dias_aviso), dias_gracia=VALUES(dias_gracia),
                medios_pago=VALUES(medios_pago), mensaje_extra=VALUES(mensaje_extra)';
        $pdo->prepare($sql)->execute($params);
    }
}

if (!function_exists('sp_registrar_pago')) {
    // Registra el pago de los N primeros ciclos impagos (MENSUALIDAD/ALOJAMIENTO) o un pago suelto (IMPLEMENTACION/OTRO).
    // Devuelve la cantidad de pagos insertados. Lanza RuntimeException con mensaje apto para el usuario.
    function sp_registrar_pago(PDO $pdo, array $sus, string $concepto, int $periodos, array $d, string $por): int
    {
        $fecha = trim((string)($d['fecha_pago'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('Fecha de pago invalida.');
        }
        $monto = round((float)($d['monto'] ?? 0), 2);
        if ($monto < 0) {
            throw new RuntimeException('Monto invalido.');
        }
        $metodo = mb_substr(trim((string)($d['metodo'] ?? '')), 0, 40) ?: null;
        $ref = mb_substr(trim((string)($d['referencia'] ?? '')), 0, 120) ?: null;
        $nota = mb_substr(trim((string)($d['nota'] ?? '')), 0, 255) ?: null;
        $por = mb_substr($por, 0, 120);

        $ins = $pdo->prepare('INSERT INTO sistema_suscripcion_pagos (suscripcion_id, concepto, periodo, periodo_activo, monto, fecha_pago, metodo, referencia, nota, registrado_por) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $subId = (int)$sus['id'];

        if (in_array($concepto, ['IMPLEMENTACION', 'OTRO'], true)) {
            $ins->execute([$subId, $concepto, null, null, $monto, $fecha, $metodo, $ref, $nota, $por]);
            return 1;
        }

        if (!in_array($sus['modalidad'] ?? '', ['MENSUAL', 'ANUAL'], true)) {
            throw new RuntimeException('Configure primero la modalidad MENSUAL o ANUAL.');
        }
        $concepto = $sus['modalidad'] === 'ANUAL' ? 'ALOJAMIENTO' : 'MENSUALIDAD';
        $periodos = max(1, min(60, $periodos));
        $ciclos = sp_proximos_ciclos($pdo, $sus, $periodos);
        if (count($ciclos) < $periodos) {
            throw new RuntimeException('No hay suficientes ciclos configurados. Revise el primer vencimiento.');
        }

        $pdo->beginTransaction();
        try {
            foreach ($ciclos as $c) {
                $ins->execute([$subId, $concepto, $c['periodo'], $c['periodo'], $monto, $fecha, $metodo, $ref, $nota, $por]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('No se pudo registrar el pago (posible ciclo ya pagado).');
        }
        return count($ciclos);
    }
}

if (!function_exists('sp_anular_pago')) {
    function sp_anular_pago(PDO $pdo, int $pagoId, int $suscripcionId, string $motivo): bool
    {
        $st = $pdo->prepare('UPDATE sistema_suscripcion_pagos SET anulado = 1, periodo_activo = NULL, anulado_en = NOW(), anulado_motivo = ? WHERE id = ? AND suscripcion_id = ? AND anulado = 0');
        $st->execute([mb_substr(trim($motivo), 0, 255) ?: null, $pagoId, $suscripcionId]);
        return $st->rowCount() > 0;
    }
}

if (!function_exists('sp_csrf_token')) {
    function sp_csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['sp_csrf'])) {
            $_SESSION['sp_csrf'] = bin2hex(random_bytes(16));
        }
        return (string)$_SESSION['sp_csrf'];
    }
}

if (!function_exists('sp_csrf_ok')) {
    function sp_csrf_ok(string $token): bool
    {
        return $token !== '' && isset($_SESSION['sp_csrf']) && hash_equals((string)$_SESSION['sp_csrf'], $token);
    }
}

// ---------- Presentacion ----------

if (!function_exists('sp_h')) {
    function sp_h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sp_texto_aviso')) {
    // [prefijo, modo, objetivo_ts, sufijo]. modo: 'count' (cuenta regresiva viva) | 'static'
    function sp_texto_aviso(array $e): array
    {
        $dias = (int)$e['dias_restantes'];
        switch ($e['estado']) {
            case 'AVISO':
                return ['Pago del sistema vence el ' . substr($e['venc_fecha'], 0, 5) . ' · ' . $dias . ($dias === 1 ? ' día' : ' días'), 'static', 0, ''];
            case 'URGENTE':
                return ['Pago del sistema vence en ', 'count', (int)$e['venc_ts'], ''];
            case 'HOY':
                return ['Pago del sistema vence hoy · ', 'count', (int)$e['venc_ts'], ''];
            default:
                $n = (int)$e['dias_vencido'];
                $txt = 'Pago del sistema vencido hace ' . $n . ($n === 1 ? ' día' : ' días');
                if ((int)$e['ciclos_vencidos'] > 1) {
                    $txt .= ' (' . (int)$e['ciclos_vencidos'] . ' ciclos)';
                }
                if (!empty($e['bloqueo_ts'])) {
                    return [$txt . ' · suspensión en ', 'count', (int)$e['bloqueo_ts'], ''];
                }
                return [$txt, 'static', 0, ''];
        }
    }
}

if (!function_exists('sp_render_chip')) {
    function sp_render_chip(array $e): string
    {
        [$prefijo, $modo, $target] = sp_texto_aviso($e);
        $clase = ['AVISO' => 'sp-aviso', 'URGENTE' => 'sp-urgente', 'HOY' => 'sp-critico', 'VENCIDO' => 'sp-critico'][$e['estado']] ?? 'sp-aviso';
        $icono = $e['estado'] === 'AVISO' ? 'bi-bell-fill' : 'bi-exclamation-triangle-fill';
        $pulso = in_array($e['estado'], ['HOY', 'VENCIDO'], true) ? ' sp-pulso' : '';
        ob_start();
        ?>
        <button type="button" id="spChip" class="sp-chip <?= $clase . $pulso ?>" data-bs-toggle="modal" data-bs-target="#spModal"
            data-sp-modo="<?= sp_h($modo) ?>" data-sp-target="<?= (int)$target * 1000 ?>" data-sp-now="<?= (int)$e['ahora_ts'] * 1000 ?>"
            data-sp-prefijo="<?= sp_h($prefijo) ?>" aria-label="Pago del sistema pendiente">
            <i class="bi <?= $icono ?>"></i>
            <span id="spChipTexto"><?= sp_h($prefijo) ?></span>
        </button>
        <style>
            .sp-chip { border: 0; border-radius: 999px; padding: .28rem .7rem; font-size: .78rem; font-weight: 600; line-height: 1.2; white-space: nowrap; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.25); max-width: 100%; }
            .sp-chip i { margin-right: .3rem; }
            .sp-aviso { background: #ffc107; color: #3b2f00; }
            .sp-urgente { background: #fd7e14; color: #fff; }
            .sp-critico { background: #dc3545; color: #fff; }
            .sp-pulso { animation: spPulso 1.8s ease-in-out infinite; }
            @keyframes spPulso { 0%,100% { box-shadow: 0 2px 8px rgba(0,0,0,.25); } 50% { box-shadow: 0 0 0 5px rgba(220,53,69,.35); } }
            @media (max-width: 767.98px) { .sp-chip { font-size: .68rem; padding: .22rem .5rem; } }
        </style>
        <script>
            (function () {
                var chip = document.getElementById('spChip');
                if (!chip) return;
                var texto = document.getElementById('spChipTexto');
                var modo = chip.getAttribute('data-sp-modo');
                var target = parseInt(chip.getAttribute('data-sp-target'), 10);
                var offset = parseInt(chip.getAttribute('data-sp-now'), 10) - Date.now();
                var prefijo = chip.getAttribute('data-sp-prefijo');
                function pad(n) { return (n < 10 ? '0' : '') + n; }
                function tick() {
                    var modal = document.getElementById('spModalCuenta');
                    var ms = target - (Date.now() + offset);
                    if (ms <= 0) {
                        texto.textContent = 'Plazo cumplido. Actualice la página';
                        if (modal) modal.textContent = '00d : 00h : 00min';
                        return false;
                    }
                    var s = Math.floor(ms / 1000), d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60), seg = s % 60;
                    var cuenta = (d === 0 && h === 0) ? pad(m) + 'min : ' + pad(seg) + 's' : pad(d) + 'd : ' + pad(h) + 'h : ' + pad(m) + 'min';
                    texto.textContent = prefijo + cuenta;
                    if (modal) modal.textContent = cuenta;
                    return true;
                }
                if (modo === 'count' && tick()) {
                    setInterval(tick, 1000);
                }
            })();
        </script>
        <?php
        return (string)ob_get_clean();
    }
}

if (!function_exists('sp_render_modal')) {
    function sp_render_modal(array $e): string
    {
        $tituloConcepto = $e['concepto'] === 'ALOJAMIENTO' ? 'Alojamiento anual del sistema' : 'Mensualidad del sistema';
        $estadoTxt = [
            'AVISO' => 'Próximo a vencer',
            'URGENTE' => 'Vence en menos de 48 horas',
            'HOY' => 'Vence hoy',
            'VENCIDO' => 'Vencido',
        ][$e['estado']] ?? '';
        [, $modo] = sp_texto_aviso($e);
        ob_start();
        ?>
        <div class="modal fade" id="spModal" tabindex="-1" aria-labelledby="spModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="spModalLabel"><i class="bi bi-credit-card-2-front me-2"></i><?= sp_h($tituloConcepto) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2"><strong>Estado:</strong> <?= sp_h($estadoTxt) ?></p>
                        <p class="mb-2"><strong>Fecha límite de pago:</strong> <?= sp_h($e['venc_fecha']) ?></p>
                        <?php if ($e['monto'] > 0): ?>
                            <p class="mb-2"><strong>Monto:</strong> <?= sp_h(money_format_local((float)$e['monto'])) ?></p>
                        <?php endif; ?>
                        <?php if ($modo === 'count'): ?>
                            <p class="mb-2"><strong><?= $e['estado'] === 'VENCIDO' ? 'Suspensión en' : 'Tiempo restante' ?>:</strong> <span id="spModalCuenta" class="fw-bold">--</span></p>
                        <?php endif; ?>
                        <?php if ((int)$e['ciclos_vencidos'] > 1): ?>
                            <p class="mb-2 text-danger"><strong>Ciclos vencidos sin pagar:</strong> <?= (int)$e['ciclos_vencidos'] ?></p>
                        <?php endif; ?>
                        <?php if (trim($e['mensaje_extra']) !== ''): ?>
                            <p class="mb-2"><?= sp_h($e['mensaje_extra']) ?></p>
                        <?php endif; ?>
                        <?php if (trim($e['medios_pago']) !== ''): ?>
                            <hr>
                            <p class="mb-1"><strong>Medios de pago</strong></p>
                            <div class="small"><?= nl2br(sp_h($e['medios_pago'])) ?></div>
                        <?php endif; ?>
                        <p class="text-muted small mt-3 mb-0">Este aviso desaparece cuando se registra el pago.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}

if (!function_exists('sp_render_suspension')) {
    function sp_render_suspension(PDO $pdo, array $e): void
    {
        if (!headers_sent()) {
            http_response_code(402);
        }
        $cfg = ui_theme_fetch_company_config($pdo);
        $nombre = trim((string)($cfg['nombre'] ?? 'Sistema'));
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio suspendido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#7f1d1d 100%);padding:1rem}</style>
</head>
<body>
    <div class="card shadow-lg border-0" style="max-width:560px;width:100%">
        <div class="card-body p-4 text-center">
            <i class="bi bi-lock-fill text-danger" style="font-size:3rem"></i>
            <h3 class="mt-2">Servicio suspendido por falta de pago</h3>
            <p class="text-muted mb-3"><?= sp_h($nombre) ?> — el pago de <?= $e['concepto'] === 'ALOJAMIENTO' ? 'alojamiento' : 'mensualidad' ?> del sistema venció el <strong><?= sp_h($e['venc_fecha']) ?></strong>.</p>
            <?php if ($e['monto'] > 0): ?>
                <p class="mb-2"><strong>Monto:</strong> <?= sp_h(money_format_local((float)$e['monto'])) ?></p>
            <?php endif; ?>
            <?php if (trim($e['mensaje_extra']) !== ''): ?>
                <p class="mb-2"><?= sp_h($e['mensaje_extra']) ?></p>
            <?php endif; ?>
            <?php if (trim($e['medios_pago']) !== ''): ?>
                <div class="alert alert-light border text-start small"><strong>Medios de pago</strong><br><?= nl2br(sp_h($e['medios_pago'])) ?></div>
            <?php endif; ?>
            <p class="small text-muted">Sus datos están seguros. El servicio se reactiva apenas se registre el pago.</p>
            <a class="btn btn-outline-secondary btn-sm" href="<?= sp_h(rtrim((string)BASE_URL, '/') . '/auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }
}
