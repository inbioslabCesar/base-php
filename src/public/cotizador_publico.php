<?php
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'No se pudo inicializar el cotizador publico.';
    return;
}

if (!function_exists('cotPubHasColumn')) {
    function cotPubHasColumn(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }

        return $cache[$key];
    }
}

if (!function_exists('cotPubClasificarCategoria')) {
    function cotPubClasificarCategoria(string $nombre): string
    {
        $txt = strtolower(trim($nombre));
        if ($txt === '') {
            return 'analisis';
        }

        if (strpos($txt, 'perfil') !== false || strpos($txt, 'panel') !== false) {
            return 'perfiles';
        }

        if (strpos($txt, 'vacuna') !== false || strpos($txt, 'inmun') !== false) {
            return 'vacunacion';
        }

        return 'analisis';
    }
}

if (!function_exists('cotPubWhatsAppBase')) {
    function cotPubWhatsAppBase(array $configEmpresa, array $redes): string
    {
        $normalize = static function (string $value): string {
            return preg_replace('/\D+/', '', trim($value)) ?? '';
        };

        foreach ($redes as $red) {
            if (!is_array($red)) {
                continue;
            }
            $nombre = strtolower(trim((string)($red['nombre'] ?? '')));
            $url = trim((string)($red['url'] ?? ''));
            if ($nombre !== 'whatsapp' && strpos($url, 'wa.me') === false) {
                continue;
            }
            if ($url === '') {
                continue;
            }
            if (preg_match('~^https?://~i', $url)) {
                return $url;
            }
            $digits = $normalize($url);
            if (strlen($digits) >= 7) {
                return 'https://wa.me/' . $digits;
            }
        }

        $fallback = $normalize((string)($configEmpresa['celular'] ?? ''));
        if (strlen($fallback) >= 7) {
            return 'https://wa.me/' . $fallback;
        }

        return '';
    }
}

$hasMetodologia = cotPubHasColumn($pdo, 'examenes', 'metodologia');
$hasTipoTubo = cotPubHasColumn($pdo, 'examenes', 'tipo_tubo');
$hasTipoMuestra = cotPubHasColumn($pdo, 'examenes', 'tipo_muestra');
$hasPreRef = cotPubHasColumn($pdo, 'examenes', 'preanalitica_referencias');

$sqlCatalogo = "SELECT
        id,
        COALESCE(codigo, '') AS codigo,
        COALESCE(nombre, '') AS nombre,
        COALESCE(tiempo_respuesta, '') AS tiempo_respuesta,
        " . ($hasMetodologia ? "COALESCE(metodologia, '')" : "''") . " AS metodologia,
        COALESCE(preanalitica_cliente, '') AS preanalitica_cliente,
        " . ($hasPreRef ? "COALESCE(preanalitica_referencias, '')" : "''") . " AS preanalitica_referencias,
        " . ($hasTipoMuestra ? "COALESCE(tipo_muestra, '')" : "''") . " AS tipo_muestra,
        " . ($hasTipoTubo ? "COALESCE(tipo_tubo, '')" : "''") . " AS tipo_tubo,
        COALESCE(observaciones, '') AS observaciones,
        COALESCE(precio_publico, 0) AS precio_publico
    FROM examenes
    WHERE vigente = 1
    ORDER BY nombre ASC";

$stmtCatalogo = $pdo->query($sqlCatalogo);
$catalogo = $stmtCatalogo ? $stmtCatalogo->fetchAll(PDO::FETCH_ASSOC) : [];

$logoCot = isset($logoPublicPath) ? (string)$logoPublicPath : '';
if ($logoCot === '') {
    $logoCot = (string)($logo ?? 'uploads/empresa/logo_empresa.png');
}
$logoCot = str_replace('\\', '/', $logoCot);
$logoCot = preg_replace('#^\.\./+#', '', $logoCot);
if (strpos($logoCot, '/') !== 0) {
    $logoCot = '/' . ltrim($logoCot, '/');
}

$empresaNombre = trim((string)($nombre_empresa ?? 'Laboratorio'));
$empresaNombre = $empresaNombre !== '' ? $empresaNombre : 'Laboratorio';

$redesLista = is_array($redes_sociales ?? null) ? $redes_sociales : [];
$waBaseCotizador = cotPubWhatsAppBase((array)$config_empresa, $redesLista);

$colorPri = (string)($color_principal ?? '#0d6efd');
$colorSec = (string)($color_secundario ?? '#f4f7fb');
$colorTxt = (string)($color_texto ?? '#1f2937');
$colorBtn = (string)($color_botones ?? '#198754');
$colorBtnTxt = (string)($color_boton_texto ?? '#ffffff');

$analisisFrecuentes = is_array($analisisFrecuentesPublicos ?? null) ? $analisisFrecuentesPublicos : [];
$prefillExamenId = isset($_GET['examen_id']) ? (int)$_GET['examen_id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($shareTitle ?? ($empresaNombre . ' | Laboratorio Clinico'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_PE">
    <meta property="og:title" content="<?= htmlspecialchars($shareTitle ?? ($empresaNombre . ' | Laboratorio Clinico'), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($shareDescription ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($shareImage ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($empresaNombre, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($shareTitle ?? ($empresaNombre . ' | Laboratorio Clinico'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($shareDescription ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($shareImage ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" href="<?= htmlspecialchars($faviconDynamicHref ?? '', ENT_QUOTES, 'UTF-8') ?>" type="image/png" sizes="48x48">
    <link rel="icon" href="<?= htmlspecialchars($logoCot, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --cp-primary: <?= htmlspecialchars($colorPri, ENT_QUOTES, 'UTF-8') ?>;
            --cp-secondary: <?= htmlspecialchars($colorSec, ENT_QUOTES, 'UTF-8') ?>;
            --cp-text: <?= htmlspecialchars($colorTxt, ENT_QUOTES, 'UTF-8') ?>;
            --cp-button: <?= htmlspecialchars($colorBtn, ENT_QUOTES, 'UTF-8') ?>;
            --cp-button-text: <?= htmlspecialchars($colorBtnTxt, ENT_QUOTES, 'UTF-8') ?>;
        }

        body {
            color: var(--cp-text);
            background: linear-gradient(160deg, var(--cp-secondary) 0%, #ffffff 48%, var(--cp-secondary) 100%);
        }

        .cot-nav {
            background: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.07);
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(8px);
        }

        .cot-brand-logo {
            width: 88px;
            height: 88px;
            object-fit: contain;
            border-radius: 12px;
            background: #fff;
            padding: 3px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .cot-hero {
            padding: 34px 0 18px;
        }

        .cot-hero-wrap {
            border-radius: 18px;
            padding: 1.4rem;
            background: linear-gradient(130deg, rgba(255, 255, 255, 0.9), rgba(245, 249, 255, 0.95));
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 16px 34px rgba(7, 18, 38, 0.08);
        }

        .cot-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            letter-spacing: -0.02em;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .cot-sub {
            color: #4b5563;
            margin-bottom: 0;
        }

        .cot-chipbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cot-chip {
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            background: #fff;
            padding: 0.4rem 0.9rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
        }

        .cot-chip.active {
            border-color: var(--cp-primary);
            color: var(--cp-primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .cot-search {
            border-radius: 12px;
            border: 1px solid #d0dae7;
            padding: 0.78rem 0.95rem;
        }

        .cot-panel {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .exam-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 14px;
            padding: 0.9rem;
            background: #fff;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .exam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }

        .exam-card.pinned {
            border-color: var(--cp-primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
        }

        .exam-name {
            font-weight: 700;
            margin-bottom: 0.1rem;
        }

        .exam-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .exam-price {
            font-weight: 800;
            font-size: 1.6rem;
            color: #0f172a;
            line-height: 1;
        }

        .btn-cotizar-exam {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid var(--cp-primary);
            color: var(--cp-primary);
            font-weight: 700;
            padding: 0.42rem 0.92rem;
            background: #fff;
            box-shadow: 0 6px 14px rgba(13, 110, 253, 0.12);
            transition: all 0.2s ease;
        }

        .btn-cotizar-exam:hover {
            background: var(--cp-primary);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(13, 110, 253, 0.24);
        }

        .btn-cotizar-exam .btn-cotizar-icon {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 110, 253, 0.12);
            font-size: 0.82rem;
            line-height: 1;
        }

        .btn-cotizar-exam:hover .btn-cotizar-icon {
            background: rgba(255, 255, 255, 0.18);
        }

        .btn-cot-main {
            background: var(--cp-button);
            color: var(--cp-button-text);
            border: none;
            border-radius: 10px;
            font-weight: 700;
        }

        .btn-cot-main:hover {
            color: var(--cp-button-text);
            filter: brightness(0.95);
        }

        .quick-link {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 0.8rem 0.9rem;
            background: #fff;
            text-decoration: none;
            color: inherit;
        }

        .quick-link:hover {
            border-color: var(--cp-primary);
        }

        .cart-fab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 58px;
            height: 58px;
            border-radius: 999px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--cp-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);
            z-index: 1040;
        }

        .cart-fab .badge {
            position: absolute;
            top: -4px;
            right: -4px;
        }

        .cot-pagination-wrap {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }

        .cot-pagination {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }

        .cot-page-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            border-radius: 9px;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            font-weight: 600;
        }

        .cot-page-btn.active {
            border-color: var(--cp-primary);
            background: var(--cp-primary);
            color: #fff;
        }

        .cot-page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 767.98px) {
            .cot-brand-logo {
                width: 70px;
                height: 70px;
            }

            .exam-price {
                font-size: 1.35rem;
            }
        }
    </style>
</head>
<body>
    <header class="cot-nav">
        <div class="container py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <img src="<?= htmlspecialchars($logoCot, ENT_QUOTES, 'UTF-8') ?>" class="cot-brand-logo" alt="Logo">
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($empresaNombre, ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted">Cotizador público</small>
                </div>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house"></i> Inicio</a>
                <a href="index.php#servicios" class="btn btn-outline-secondary btn-sm">Servicios</a>
                <a href="src/auth/login.php" class="btn btn-cot-main btn-sm">Acceso Clientes</a>
            </div>
        </div>
    </header>

    <main class="container pb-5">
        <section class="cot-hero">
            <div class="cot-hero-wrap">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg-8">
                        <h1 class="cot-title">Cotiza tus análisis con precio público</h1>
                        <p class="cot-sub">Selecciona exámenes, arma tu carrito y envía la solicitud por WhatsApp con los datos para agendar.</p>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="p-3 rounded-3 border bg-white">
                            <div class="small text-muted">Catálogo vigente</div>
                            <div class="h4 mb-1"><?= count($catalogo) ?> exámenes</div>
                            <div class="small text-muted">Precio referencial mostrado en público.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-3">
            <div class="cot-panel p-3 p-md-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg-8">
                        <input type="text" id="cotizadorPublicoSearch" class="form-control cot-search" placeholder="Busca por nombre o código de examen...">
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="small text-muted" id="cotizadorPublicoCounter">Resultados encontrados (0)</div>
                    </div>
                </div>
                <div class="cot-chipbar mt-3" id="cotizadorPublicoCategorias">
                    <button type="button" class="cot-chip active" data-cat="all">Todos</button>
                    <button type="button" class="cot-chip" data-cat="analisis">Análisis</button>
                    <button type="button" class="cot-chip" data-cat="perfiles">Perfiles</button>
                </div>
            </div>
        </section>

        <section class="row g-3" id="cotizadorPublicoListado">
            <?php foreach ($catalogo as $ex): ?>
                <?php
                $idExam = (int)($ex['id'] ?? 0);
                $nombreExam = trim((string)($ex['nombre'] ?? ''));
                $codigoExam = trim((string)($ex['codigo'] ?? ''));
                $tiempoResp = trim((string)($ex['tiempo_respuesta'] ?? ''));
                $metodologia = trim((string)($ex['metodologia'] ?? ''));
                $prePaciente = trim((string)($ex['preanalitica_cliente'] ?? ''));
                $preRef = trim((string)($ex['preanalitica_referencias'] ?? ''));
                $tipoMuestra = trim((string)($ex['tipo_muestra'] ?? ''));
                $tipoTubo = trim((string)($ex['tipo_tubo'] ?? ''));
                $obsExam = trim((string)($ex['observaciones'] ?? ''));
                $precioPub = (float)($ex['precio_publico'] ?? 0);
                $catExam = cotPubClasificarCategoria($nombreExam);

                $payloadDetalle = [
                    'id' => $idExam,
                    'codigo' => $codigoExam,
                    'nombre' => $nombreExam,
                    'tiempo_respuesta' => $tiempoResp,
                    'metodologia' => $metodologia,
                    'preanalitica_cliente' => $prePaciente,
                    'preanalitica_referencias' => $preRef,
                    'tipo_muestra' => $tipoMuestra,
                    'tipo_tubo' => $tipoTubo,
                    'observaciones' => $obsExam,
                    'precio_publico' => $precioPub,
                ];
                ?>
                <div class="col-12 cot-exam-item" data-exam-id="<?= $idExam ?>" data-cat="<?= htmlspecialchars($catExam, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($nombreExam . ' ' . $codigoExam), ENT_QUOTES, 'UTF-8') ?>">
                    <article class="exam-card">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-lg-8">
                                <div class="exam-name"><?= htmlspecialchars($nombreExam !== '' ? $nombreExam : ('Examen #' . $idExam), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="exam-meta">Código: <?= htmlspecialchars($codigoExam !== '' ? $codigoExam : '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="mt-1 d-flex gap-1 flex-wrap">
                                    <span class="badge text-bg-light border">Laboratorio</span>
                                    <span class="badge text-bg-light border">A domicilio</span>
                                    <?php if ($tiempoResp !== ''): ?><span class="badge text-bg-light border">Tiempo: <?= htmlspecialchars($tiempoResp, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2">
                                    <div class="exam-price">S/ <?= number_format($precioPub, 2) ?></div>
                                    <button type="button" class="btn-cotizar-exam" data-add-exam="1" data-exam='<?= htmlspecialchars(json_encode($payloadDetalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>
                                        <span class="btn-cotizar-icon"><i class="bi bi-bag-plus-fill"></i></span> Cotizar
                                    </button>
                                </div>
                                <div class="text-end mt-1">
                                    <button type="button" class="btn btn-link btn-sm p-0" data-show-detail="1" data-exam='<?= htmlspecialchars(json_encode($payloadDetalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>Ver detalles <i class="bi bi-arrow-up-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </section>
        <div class="cot-pagination-wrap">
            <div class="cot-pagination" id="cotizadorPublicoPaginacion" aria-label="Paginacion de examenes"></div>
        </div>
    </main>

    <button type="button" class="cart-fab" data-bs-toggle="modal" data-bs-target="#examCartModal" aria-label="Abrir carrito de exámenes">
        <i class="bi bi-cart3"></i>
        <span class="badge bg-danger" id="examCartCount">0</span>
    </button>

    <div class="modal fade" id="examCartModal" tabindex="-1" aria-labelledby="examCartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="examCartModalLabel">Carrito de cotización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="examCartEmpty" class="alert alert-light border">Aun no agregaste items para cotizar.</div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="examCartTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Tipo</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-end mb-3">
                        <div class="small text-muted">Subtotal: <span id="examCartSubtotal">S/ 0.00</span></div>
                        <div class="small text-success" id="examCartDiscountWrap" style="display:none;">Descuento web: <span id="examCartDiscount">-S/ 0.00</span></div>
                        <strong>Total referencial: <span id="examCartTotal">S/ 0.00</span></strong>
                    </div>

                    <h6 class="mb-2">Datos para cotización</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre del paciente</label>
                            <input type="text" class="form-control" id="examCotNombre" placeholder="Nombres y apellidos">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Teléfono de contacto</label>
                            <input type="text" class="form-control" id="examCotTelefono" placeholder="9XXXXXXXX">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">¿Dónde deseas la toma de muestra?</label>
                            <select class="form-select" id="examCotModalidad">
                                <option value="laboratorio">En laboratorio</option>
                                <option value="domicilio">A domicilio</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha deseada de atención</label>
                            <input type="date" class="form-control" id="examCotFecha">
                            <small class="text-muted" id="examCotFechaHint">Selecciona la fecha en la que deseas asistir al laboratorio.</small>
                        </div>
                        <div class="col-12" id="examCotDireccionWrap" style="display:none;">
                            <label class="form-label">Dirección para toma a domicilio</label>
                            <input type="text" class="form-control" id="examCotDireccion" placeholder="Dirección exacta y referencia">
                        </div>
                        <div class="col-12">
                            <label class="form-label">¿Tienes alguna duda o indicación para el laboratorio?</label>
                            <textarea class="form-control" id="examCotObs" rows="2" placeholder="Ej: ¿Debo ir en ayunas?, ¿puedo tomar agua?, horario ideal, otra consulta"></textarea>
                            <small class="text-muted">Escribe aquí cualquier consulta para el laboratorio antes de agendar.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="examCartClear">Vaciar carrito</button>
                    <button type="button" class="btn btn-cot-main" id="examQuoteWhatsapp">Cotizar por WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="examDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de examen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="examDetailBody"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const CART_KEY = 'quote_items_v1';
            const LEGACY_PROMO_KEY = 'promo_cart_v1';
            const LEGACY_EXAM_KEY = 'exam_cart_v1';
            const LEGACY_MIGRATED_FLAG_KEY = 'quote_items_legacy_migrated_v1';
            const FORM_DRAFT_KEY = 'quote_contact_draft_v1';
            const WA_BASE = <?= json_encode($waBaseCotizador, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const EMPRESA = <?= json_encode($empresaNombre, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const PREFILL_EXAMEN_ID = <?= (int)$prefillExamenId ?>;
            const DISCOUNT_ACTIVE = <?= !empty($promoWebActiva) ? 'true' : 'false' ?>;
            const DISCOUNT_PERCENT = <?= json_encode((float)($promoWebPorcentaje ?? 0), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const DISCOUNT_APPLY = <?= !empty($promoWebAplicarCarrito) ? 'true' : 'false' ?>;

            const listItems = Array.from(document.querySelectorAll('.cot-exam-item'));
            const searchInput = document.getElementById('cotizadorPublicoSearch');
            const counter = document.getElementById('cotizadorPublicoCounter');
            const categoryWrap = document.getElementById('cotizadorPublicoCategorias');
            const paginationEl = document.getElementById('cotizadorPublicoPaginacion');

            const countEl = document.getElementById('examCartCount');
            const table = document.getElementById('examCartTable');
            const tbody = table ? table.querySelector('tbody') : null;
            const emptyEl = document.getElementById('examCartEmpty');
            const subtotalEl = document.getElementById('examCartSubtotal');
            const discountWrapEl = document.getElementById('examCartDiscountWrap');
            const discountEl = document.getElementById('examCartDiscount');
            const totalEl = document.getElementById('examCartTotal');
            const modalidadEl = document.getElementById('examCotModalidad');
            const direccionWrapEl = document.getElementById('examCotDireccionWrap');
            const fechaHintEl = document.getElementById('examCotFechaHint');
            const nombreEl = document.getElementById('examCotNombre');
            const telefonoEl = document.getElementById('examCotTelefono');
            const fechaEl = document.getElementById('examCotFecha');
            const direccionEl = document.getElementById('examCotDireccion');
            const obsEl = document.getElementById('examCotObs');

            const detailModalEl = document.getElementById('examDetailModal');
            const detailBody = document.getElementById('examDetailBody');
            const detailModal = detailModalEl ? bootstrap.Modal.getOrCreateInstance(detailModalEl) : null;

            let activeCategory = 'all';
            let currentPage = 1;
            const pageSize = 12;

            function getFilteredItems() {
                const query = String(searchInput ? searchInput.value : '').trim().toLowerCase();
                return listItems.filter((item) => {
                    const cat = item.getAttribute('data-cat') || 'analisis';
                    const searchable = item.getAttribute('data-search') || '';
                    const passCat = activeCategory === 'all' || cat === activeCategory;
                    const passText = query === '' || searchable.indexOf(query) >= 0;
                    return passCat && passText;
                });
            }

            function renderPagination(totalItems) {
                if (!paginationEl) {
                    return;
                }

                const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                paginationEl.innerHTML = '';
                if (totalItems <= pageSize) {
                    return;
                }

                const addBtn = function (label, page, disabled, active) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'cot-page-btn' + (active ? ' active' : '');
                    btn.textContent = label;
                    btn.disabled = !!disabled;
                    btn.addEventListener('click', function () {
                        currentPage = page;
                        applyFilters();
                    });
                    paginationEl.appendChild(btn);
                };

                addBtn('Anterior', Math.max(1, currentPage - 1), currentPage === 1, false);

                const maxVisible = 7;
                let startPage = Math.max(1, currentPage - 3);
                let endPage = Math.min(totalPages, startPage + maxVisible - 1);
                if ((endPage - startPage + 1) < maxVisible) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }

                if (startPage > 1) {
                    addBtn('1', 1, false, currentPage === 1);
                    if (startPage > 2) {
                        const dots = document.createElement('span');
                        dots.textContent = '...';
                        dots.className = 'px-1 text-muted';
                        paginationEl.appendChild(dots);
                    }
                }

                for (let p = startPage; p <= endPage; p++) {
                    addBtn(String(p), p, false, p === currentPage);
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        const dots = document.createElement('span');
                        dots.textContent = '...';
                        dots.className = 'px-1 text-muted';
                        paginationEl.appendChild(dots);
                    }
                    addBtn(String(totalPages), totalPages, false, currentPage === totalPages);
                }

                addBtn('Siguiente', Math.min(totalPages, currentPage + 1), currentPage === totalPages, false);
            }

            function getCart() {
                try {
                    const raw = localStorage.getItem(CART_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return normalizeCart(Array.isArray(parsed) ? parsed : []);
                } catch (e) {
                    return [];
                }
            }

            function normalizeCart(items) {
                const map = new Map();
                (Array.isArray(items) ? items : []).forEach((it) => {
                    const rawType = String((it && it.type) || '').toLowerCase();
                    const type = rawType === 'promocion' || rawType === 'promo' ? 'promocion' : 'examen';
                    const key = String((it && it.key) || '').trim() || (type + ':' + String((it && it.id) || '').trim());
                    if (!key || map.has(key)) {
                        return;
                    }
                    map.set(key, {
                        key: key,
                        type: type,
                        id: String((it && it.id) || ''),
                        title: String((it && it.title) || (type === 'promocion' ? 'Promocion' : 'Examen')),
                        price: Number((it && it.price) || 0),
                        qty: Math.max(1, Number((it && it.qty) || 1)),
                        codigo: String((it && it.codigo) || ''),
                        tiempo_respuesta: String((it && it.tiempo_respuesta) || ''),
                        tipo_tubo: String((it && it.tipo_tubo) || ''),
                        observaciones: String((it && it.observaciones) || ''),
                        vigencia: String((it && it.vigencia) || '')
                    });
                });
                return Array.from(map.values());
            }

            function setCart(items) {
                localStorage.setItem(CART_KEY, JSON.stringify(normalizeCart(items)));
            }

            function migrateLegacyCart() {
                if (localStorage.getItem(LEGACY_MIGRATED_FLAG_KEY) === '1') {
                    return;
                }

                const existing = getCart();
                if (existing.length > 0) {
                    localStorage.setItem(LEGACY_MIGRATED_FLAG_KEY, '1');
                    return;
                }

                let merged = [];
                try {
                    const legacyPromoRaw = localStorage.getItem(LEGACY_PROMO_KEY);
                    const legacyPromo = legacyPromoRaw ? JSON.parse(legacyPromoRaw) : [];
                    if (Array.isArray(legacyPromo)) {
                        merged = merged.concat(legacyPromo.map((it) => ({
                            key: 'promocion:' + String((it && it.id) || ''),
                            type: 'promocion',
                            id: String((it && it.id) || ''),
                            title: String((it && it.title) || 'Promocion'),
                            price: Number((it && it.price) || 0),
                            qty: Math.max(1, Number((it && it.qty) || 1)),
                            vigencia: String((it && it.vigencia) || '')
                        })));
                    }
                } catch (e) {}

                try {
                    const legacyExamRaw = localStorage.getItem(LEGACY_EXAM_KEY);
                    const legacyExam = legacyExamRaw ? JSON.parse(legacyExamRaw) : [];
                    if (Array.isArray(legacyExam)) {
                        merged = merged.concat(legacyExam.map((it) => ({
                            key: 'examen:' + String((it && it.id) || ''),
                            type: 'examen',
                            id: String((it && it.id) || ''),
                            title: String((it && it.title) || 'Examen'),
                            price: Number((it && it.price) || 0),
                            qty: 1,
                            codigo: String((it && it.codigo) || ''),
                            tiempo_respuesta: String((it && it.tiempo_respuesta) || ''),
                            tipo_tubo: String((it && it.tipo_tubo) || ''),
                            observaciones: String((it && it.observaciones) || '')
                        })));
                    }
                } catch (e) {}

                if (merged.length) {
                    setCart(merged);
                }

                localStorage.removeItem(LEGACY_PROMO_KEY);
                localStorage.removeItem(LEGACY_EXAM_KEY);
                localStorage.setItem(LEGACY_MIGRATED_FLAG_KEY, '1');
            }

            function getDraft() {
                try {
                    const raw = localStorage.getItem(FORM_DRAFT_KEY);
                    const parsed = raw ? JSON.parse(raw) : {};
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (e) {
                    return {};
                }
            }

            function saveDraft(data) {
                const current = getDraft();
                const next = Object.assign({}, current, data || {});
                localStorage.setItem(FORM_DRAFT_KEY, JSON.stringify(next));
            }

            function readFormData() {
                return {
                    nombre: (nombreEl || {}).value || '',
                    telefono: (telefonoEl || {}).value || '',
                    modalidad: (modalidadEl || {}).value || 'laboratorio',
                    fecha: (fechaEl || {}).value || '',
                    direccion: (direccionEl || {}).value || '',
                    obs: (obsEl || {}).value || ''
                };
            }

            function applyDraft() {
                const draft = getDraft();
                if (nombreEl && draft.nombre) nombreEl.value = draft.nombre;
                if (telefonoEl && draft.telefono) telefonoEl.value = draft.telefono;
                if (modalidadEl && draft.modalidad) modalidadEl.value = draft.modalidad;
                if (fechaEl && draft.fecha) fechaEl.value = draft.fecha;
                if (direccionEl && draft.direccion) direccionEl.value = draft.direccion;
                if (obsEl && draft.obs) obsEl.value = draft.obs;
            }

            function formatMoney(value) {
                return 'S/ ' + (Number(value) || 0).toFixed(2);
            }

            function computeDiscount(subtotal) {
                if (!(DISCOUNT_ACTIVE && DISCOUNT_APPLY && DISCOUNT_PERCENT > 0)) {
                    return 0;
                }
                const raw = Number(subtotal || 0) * (Number(DISCOUNT_PERCENT) / 100);
                return Math.max(0, Number(raw.toFixed(2)));
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderCounter(visible) {
                if (counter) {
                    counter.textContent = 'Resultados encontrados (' + visible + ')';
                }
            }

            function applyFilters() {
                const filtered = getFilteredItems();
                const visibleCount = filtered.length;
                const totalPages = Math.max(1, Math.ceil(visibleCount / pageSize));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                listItems.forEach((item) => {
                    item.style.display = 'none';
                });

                const from = (currentPage - 1) * pageSize;
                const to = from + pageSize;
                filtered.slice(from, to).forEach((item) => {
                    item.style.display = '';
                });

                renderCounter(visibleCount);
                renderPagination(visibleCount);
            }

            function renderCart() {
                const items = getCart();
                const qty = items.reduce((acc, it) => acc + (Number(it.qty) || 0), 0);
                if (countEl) {
                    countEl.textContent = String(qty);
                }

                if (!tbody || !emptyEl || !totalEl) {
                    return;
                }

                tbody.innerHTML = '';
                let total = 0;

                items.forEach((it) => {
                    const price = Number(it.price) || 0;
                    const units = Math.max(1, Number(it.qty) || 1);
                    const rowSubtotal = price * units;
                    total += rowSubtotal;
                    const isPromo = it.type === 'promocion';
                    const tipoLabel = isPromo ? 'Promoción' : 'Examen';
                    const tipoChip = '<span class="badge ' + (isPromo ? 'text-bg-warning' : 'text-bg-info') + '">' + tipoLabel + '</span>';

                    const tr = document.createElement('tr');
                    tr.innerHTML = '' +
                        '<td>' + escapeHtml(it.title || 'Item') + '</td>' +
                        '<td>' + tipoChip + '</td>' +
                        '<td>' + formatMoney(price) + '</td>' +
                        '<td>' + String(units) + '</td>' +
                        '<td>' + formatMoney(rowSubtotal) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-key="' + escapeHtml(String(it.key || '')) + '">Quitar</button></td>';
                    tbody.appendChild(tr);
                });

                emptyEl.style.display = items.length ? 'none' : 'block';
                table.style.display = items.length ? '' : 'none';
                const discount = computeDiscount(total);
                const finalTotal = Math.max(0, total - discount);
                if (subtotalEl) {
                    subtotalEl.textContent = formatMoney(total);
                }
                if (discountWrapEl && discountEl) {
                    if (discount > 0) {
                        discountWrapEl.style.display = '';
                        discountEl.textContent = '-' + formatMoney(discount);
                    } else {
                        discountWrapEl.style.display = 'none';
                        discountEl.textContent = '-' + formatMoney(0);
                    }
                }
                totalEl.textContent = formatMoney(finalTotal);
            }

            function addExamToCart(payload) {
                const items = getCart();
                const examId = String(payload.id || '');
                if (!examId) {
                    return;
                }

                const examKey = 'examen:' + examId;
                const idx = items.findIndex((it) => String(it.key) === examKey);
                if (idx >= 0) {
                    return;
                }

                items.push({
                    key: examKey,
                    type: 'examen',
                    id: examId,
                    title: payload.nombre || 'Examen',
                    price: Number(payload.precio_publico) || 0,
                    qty: 1,
                    codigo: payload.codigo || '',
                    tiempo_respuesta: payload.tiempo_respuesta || '',
                    tipo_tubo: payload.tipo_tubo || '',
                    observaciones: payload.observaciones || ''
                });

                setCart(items);
                renderCart();
            }

            function buildDetailHtml(ex) {
                return [
                    '<div class="row g-3">',
                        '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                            '<h6 class="mb-2 text-primary">Información general</h6>',
                            '<div><strong>Código:</strong> ' + escapeHtml(ex.codigo || '-') + '</div>',
                            '<div><strong>Nombre:</strong> ' + escapeHtml(ex.nombre || '-') + '</div>',
                            '<div><strong>Precio público:</strong> ' + formatMoney(Number(ex.precio_publico || 0)) + '</div>',
                        '</div></div>',
                        '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                            '<h6 class="mb-2 text-primary">Proceso</h6>',
                            '<div><strong>Tiempo de proceso:</strong> ' + escapeHtml(ex.tiempo_respuesta || '-') + '</div>',
                            '<div><strong>Metodología:</strong> ' + escapeHtml(ex.metodologia || '-') + '</div>',
                            '<div><strong>Tipo de muestra:</strong> ' + escapeHtml(ex.tipo_muestra || '-') + '</div>',
                            '<div><strong>Tipo de tubo:</strong> ' + escapeHtml(ex.tipo_tubo || '-') + '</div>',
                        '</div></div>',
                        '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                            '<h6 class="mb-2 text-primary">Indicaciones para paciente</h6>',
                            '<div>' + escapeHtml(ex.preanalitica_cliente || '-') + '</div>',
                        '</div></div>',
                        '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                            '<h6 class="mb-2 text-primary">Referencia técnica</h6>',
                            '<div>' + escapeHtml(ex.preanalitica_referencias || '-') + '</div>',
                        '</div></div>',
                        '<div class="col-12"><div class="border rounded p-3">',
                            '<h6 class="mb-2 text-primary">Observación</h6>',
                            '<div>' + escapeHtml(ex.observaciones || '-') + '</div>',
                        '</div></div>',
                    '</div>'
                ].join('');
            }

            document.querySelectorAll('[data-add-exam="1"]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    let payload = null;
                    try {
                        payload = JSON.parse(this.getAttribute('data-exam') || '{}');
                    } catch (e) {
                        payload = null;
                    }
                    if (!payload || !payload.id) {
                        return;
                    }
                    addExamToCart(payload);
                });
            });

            document.querySelectorAll('[data-show-detail="1"]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    let payload = null;
                    try {
                        payload = JSON.parse(this.getAttribute('data-exam') || '{}');
                    } catch (e) {
                        payload = null;
                    }
                    if (!payload || !detailBody || !detailModal) {
                        return;
                    }
                    detailBody.innerHTML = buildDetailHtml(payload);
                    detailModal.show();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    currentPage = 1;
                    applyFilters();
                });
            }

            if (categoryWrap) {
                categoryWrap.addEventListener('click', function (ev) {
                    const target = ev.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    const button = target.closest('[data-cat]');
                    if (!(button instanceof HTMLElement)) {
                        return;
                    }
                    activeCategory = button.getAttribute('data-cat') || 'all';
                    currentPage = 1;
                    categoryWrap.querySelectorAll('[data-cat]').forEach((chip) => {
                        chip.classList.remove('active');
                    });
                    button.classList.add('active');
                    applyFilters();
                });
            }

            if (tbody) {
                tbody.addEventListener('click', function (ev) {
                    const target = ev.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    const removeKey = target.getAttribute('data-remove-key');
                    if (!removeKey) {
                        return;
                    }
                    const next = getCart().filter((it) => String(it.key) !== String(removeKey));
                    setCart(next);
                    renderCart();
                });
            }

            const clearBtn = document.getElementById('examCartClear');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    setCart([]);
                    renderCart();
                });
            }

            if (modalidadEl && direccionWrapEl) {
                const toggleDireccion = function () {
                    direccionWrapEl.style.display = modalidadEl.value === 'domicilio' ? '' : 'none';
                    if (fechaHintEl) {
                        fechaHintEl.textContent = modalidadEl.value === 'domicilio'
                            ? 'Selecciona la fecha en la que deseas la toma de muestra a domicilio.'
                            : 'Selecciona la fecha en la que deseas asistir al laboratorio.';
                    }
                };
                modalidadEl.addEventListener('change', toggleDireccion);
                toggleDireccion();
            }

            [nombreEl, telefonoEl, modalidadEl, fechaEl, direccionEl, obsEl].forEach((el) => {
                if (!el) {
                    return;
                }
                const evt = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(evt, function () {
                    saveDraft(readFormData());
                });
            });

            const quoteBtn = document.getElementById('examQuoteWhatsapp');
            if (quoteBtn) {
                quoteBtn.addEventListener('click', function () {
                    const items = getCart();
                    if (!items.length) {
                        alert('Agrega al menos un examen al carrito.');
                        return;
                    }
                    if (!WA_BASE) {
                        alert('No hay WhatsApp configurado en el sistema.');
                        return;
                    }

                    const formData = readFormData();
                    const nombre = formData.nombre;
                    const telefono = formData.telefono;
                    const modalidad = formData.modalidad;
                    const fecha = formData.fecha;
                    const direccion = formData.direccion;
                    const obs = formData.obs;
                    saveDraft(formData);

                    let total = 0;
                    const promoItems = items.filter((it) => it.type === 'promocion');
                    const examItems = items.filter((it) => it.type !== 'promocion');

                    const promoLines = promoItems.map((it, idx) => {
                        const price = Number(it.price) || 0;
                        const rowSubtotal = price * (Number(it.qty) || 0);
                        total += rowSubtotal;
                        let line = (idx + 1) + '. ' + (it.title || 'Promocion') + ' x' + (Number(it.qty) || 0);
                        line += price > 0 ? (' - ' + formatMoney(rowSubtotal)) : ' - Precio por confirmar';
                        if (it.vigencia) {
                            line += ' | Vigencia: ' + it.vigencia;
                        }
                        return line;
                    });

                    const examLines = examItems.map((it, idx) => {
                        const price = Number(it.price) || 0;
                        const rowSubtotal = price * (Number(it.qty) || 1);
                        total += rowSubtotal;

                        const extras = [];
                        if (it.codigo) extras.push('Código: ' + it.codigo);
                        if (it.tiempo_respuesta) extras.push('Tiempo: ' + it.tiempo_respuesta);
                        if (it.tipo_tubo) extras.push('Tubo: ' + it.tipo_tubo);

                        let line = (idx + 1) + '. ' + (it.title || 'Examen') + ' - ' + formatMoney(rowSubtotal);
                        if (extras.length) {
                            line += ' | ' + extras.join(' | ');
                        }
                        return line;
                    });

                    const parts = [
                        'Hola, deseo cotizar en ' + EMPRESA + ':',
                        '',
                    ];

                    if (promoLines.length) {
                        parts.push('Promociones:', promoLines.join('\n'), '');
                    }
                    if (examLines.length) {
                        parts.push('Exámenes:', examLines.join('\n'), '');
                    }

                    const discount = computeDiscount(total);
                    const finalTotal = Math.max(0, total - discount);

                    parts.push(
                        'Subtotal referencial: ' + formatMoney(total),
                        (discount > 0 ? 'Descuento web (' + String(Number(DISCOUNT_PERCENT)) + '%): -' + formatMoney(discount) : 'Descuento web: No aplica'),
                        'Total referencial: ' + formatMoney(finalTotal),
                        '',
                        'Datos para coordinar la atención:',
                        'Paciente: ' + (nombre || 'No indicado'),
                        'Teléfono: ' + (telefono || 'No indicado'),
                        'Modalidad: ' + (modalidad === 'domicilio' ? 'A domicilio' : 'En laboratorio'),
                        'Fecha deseada: ' + (fecha || 'No indicada')
                    );

                    if (modalidad === 'domicilio') {
                        parts.push('Dirección: ' + (direccion || 'No indicada'));
                    }
                    if (obs) {
                        parts.push('Duda o indicación: ' + obs);
                    }
                    parts.push('', 'Por favor, me brindan referencia para agendar.');

                    const message = parts.join('\n');
                    let link = '';
                    try {
                        const u = new URL(WA_BASE, window.location.href);
                        u.searchParams.set('text', message);
                        link = u.toString();
                    } catch (e) {
                        link = WA_BASE + (WA_BASE.indexOf('?') >= 0 ? '&' : '?') + 'text=' + encodeURIComponent(message);
                    }

                    window.open(link, '_blank');
                });
            }

            function pinPrefilledExam() {
                if (PREFILL_EXAMEN_ID <= 0) {
                    return;
                }
                const node = document.querySelector('.cot-exam-item[data-exam-id="' + String(PREFILL_EXAMEN_ID) + '"]');
                if (!node) {
                    return;
                }
                const query = String(searchInput ? searchInput.value : '').trim().toLowerCase();
                const matchesCurrentFilter = (function () {
                    const cat = node.getAttribute('data-cat') || 'analisis';
                    const searchable = node.getAttribute('data-search') || '';
                    const passCat = activeCategory === 'all' || cat === activeCategory;
                    const passText = query === '' || searchable.indexOf(query) >= 0;
                    return passCat && passText;
                })();

                if (matchesCurrentFilter) {
                    const filtered = getFilteredItems();
                    const idx = filtered.indexOf(node);
                    if (idx >= 0) {
                        currentPage = Math.floor(idx / pageSize) + 1;
                    }
                }

                applyFilters();

                const card = node.querySelector('.exam-card');
                if (card) {
                    card.classList.add('pinned');
                }
                node.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            applyFilters();
            migrateLegacyCart();
            applyDraft();
            renderCart();
            pinPrefilledExam();
        })();
    </script>
</body>
</html>
