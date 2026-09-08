<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/resultados_pdf_datos.php';
require_once __DIR__ . '/qr_verificacion_utils.php';

$token = trim((string)($_GET['t'] ?? ''));

$empresa = obtenerDatosEmpresa($pdo);
$validation = qr_verificacion_validar_token($token, is_array($empresa) ? $empresa : []);

$status = 'error';
$title = 'No se pudo verificar este QR';
$message = 'El enlace de verificacion no es valido o fue alterado.';
$details = [];

if (!empty($validation['ok']) && !empty($validation['payload'])) {
    $payload = $validation['payload'];
    $cotizacionId = (int)($payload['cid'] ?? 0);
    $expectedFingerprint = trim((string)($payload['fp'] ?? ''));

    $rows = $cotizacionId > 0 ? obtenerResultadosExamenes($pdo, $cotizacionId) : [];
    if (is_array($rows) && count($rows) > 0) {
        $currentFingerprint = qr_verificacion_resultados_fingerprint($cotizacionId, $rows);
        $matches = hash_equals($expectedFingerprint, $currentFingerprint);

        $row0 = $rows[0];
        $nombre = trim((string)($row0['nombre'] ?? '') . ' ' . (string)($row0['apellido'] ?? ''));
        $codigo = trim((string)($row0['codigo_cliente'] ?? ''));
        $dniMasked = qr_verificacion_mask_dni((string)($row0['dni'] ?? ''));

        $fechaProceso = '';
        $fechaValidacion = '';
        $seccionesImpresas = 0;

        foreach ($rows as $row) {
            $resultadosTmp = [];
            $raw = (string)($row['resultados'] ?? '');
            if ($raw !== '') {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    $resultadosTmp = $dec;
                }
            }
            $imprimir = !isset($resultadosTmp['imprimir_examen']) || (int)$resultadosTmp['imprimir_examen'] === 1;
            if (!$imprimir) {
                continue;
            }
            $seccionesImpresas++;

            if ($fechaProceso === '') {
                $fechaProceso = trim((string)($row['fecha_proceso_en'] ?? ''));
                if ($fechaProceso === '') {
                    $fechaProceso = trim((string)($row['fecha_ingreso'] ?? ''));
                }
            }

            $fv = trim((string)($row['fecha_validacion_en'] ?? ''));
            if ($fv !== '' && ($fechaValidacion === '' || strtotime($fv) > strtotime($fechaValidacion))) {
                $fechaValidacion = $fv;
            }
        }

        if ($matches) {
            $status = 'ok';
            $title = 'Documento validado';
            $message = 'El QR coincide con el contenido actual del resultado emitido.';
        } else {
            $status = 'warn';
            $title = 'Documento no vigente';
            $message = 'El QR es autentico, pero el contenido actual fue modificado despues de la emision.';
        }

        $details = [
            'Cotizacion' => (string)$cotizacionId,
            'Paciente' => $nombre !== '' ? $nombre : 'No disponible',
            'Codigo paciente' => $codigo !== '' ? $codigo : 'No disponible',
            'Documento' => $dniMasked,
            'Secciones impresas' => (string)$seccionesImpresas,
            'Fecha de proceso' => $fechaProceso !== '' ? date('d/m/Y H:i', strtotime($fechaProceso)) : 'No disponible',
            'Fecha de validacion' => $fechaValidacion !== '' ? date('d/m/Y H:i', strtotime($fechaValidacion)) : 'No disponible',
            'Emitido (token)' => !empty($payload['iat']) ? date('d/m/Y H:i', (int)$payload['iat']) : 'No disponible',
        ];
    } else {
        $status = 'warn';
        $title = 'Resultado no encontrado';
        $message = 'No hay resultados disponibles para la cotizacion asociada al QR.';
    }
}

$statusClass = 'danger';
$icon = 'bi-x-octagon-fill';
if ($status === 'ok') {
    $statusClass = 'success';
    $icon = 'bi-patch-check-fill';
} elseif ($status === 'warn') {
    $statusClass = 'warning';
    $icon = 'bi-exclamation-triangle-fill';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificacion de resultados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #eef6ff 0%, #dbeaff 100%);
            min-height: 100vh;
        }
        .verify-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }
        .verify-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card verify-card">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h1 class="h4 mb-0">Verificacion de resultado</h1>
                        <span class="verify-chip bg-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>-subtle text-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <p class="text-muted mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

                    <?php if (!empty($details)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                <?php foreach ($details as $label => $value): ?>
                                    <tr>
                                        <th style="width: 220px;"><?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?></th>
                                        <td><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 small text-muted">
                        Este verificador confirma autenticidad del QR y vigencia del contenido del resultado.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
