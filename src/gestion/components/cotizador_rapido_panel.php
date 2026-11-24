<?php
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}

require_once __DIR__ . '/../../config/currency.php';
require_once __DIR__ . '/../../auth/empresa_config.php';

if (!function_exists('cotizadorRapidoTableHasColumn')) {
    function cotizadorRapidoTableHasColumn(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        return $cache[$key];
    }
}

if (!function_exists('cotizadorRapidoApplyDiscount')) {
    function cotizadorRapidoApplyDiscount(float $price, float $discount): float
    {
        if ($price <= 0 || $discount <= 0) {
            return round($price, 2);
        }
        return round($price * (1 - ($discount / 100)), 2);
    }
}

if (!function_exists('cotizadorRapidoResolveLogoPublicPath')) {
    function cotizadorRapidoResolveLogoPublicPath(string $logoRaw): string
    {
        if ($logoRaw === '' || preg_match('/^data:image\//i', $logoRaw)) {
            $logoRaw = '../uploads/empresa/logo_empresa.png';
        }

        $logoPath = str_replace('\\', '/', $logoRaw);
        $logoPath = preg_replace('#/+#', '/', $logoPath) ?: $logoPath;
        $logoPath = preg_replace('#^(\./|\.\./)+#', '', $logoPath) ?: $logoPath;

        if (strpos($logoPath, 'src/images/empresa/') === 0) {
            $logoPath = 'uploads/empresa/' . substr($logoPath, strlen('src/images/empresa/'));
        } elseif (strpos($logoPath, 'images/empresa/') === 0) {
            $logoPath = 'uploads/empresa/' . substr($logoPath, strlen('images/empresa/'));
        } elseif (strpos($logoPath, 'uploads/empresa/') !== 0) {
            $logoPath = 'uploads/empresa/logo_empresa.png';
        }

        return ltrim($logoPath, '/');
    }
}

if (!function_exists('cotizadorRapidoLogoDataUri')) {
    function cotizadorRapidoLogoDataUri(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return '';
        }

        $imageInfo = @getimagesize($absolutePath);
        if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
            return '';
        }

        $mime = strtolower((string)$imageInfo['mime']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
            return '';
        }

        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }
}

$contextType = strtolower((string)($cotizadorContextType ?? ($_SESSION['rol'] ?? '')));
if (!in_array($contextType, ['empresa', 'convenio', 'cliente'], true)) {
    return;
}

$contextId = 0;
if ($contextType === 'empresa') {
    $contextId = (int)($_SESSION['empresa_id'] ?? 0);
} elseif ($contextType === 'convenio') {
    $contextId = (int)($_SESSION['convenio_id'] ?? 0);
} elseif ($contextType === 'cliente') {
    $contextId = (int)($_SESSION['cliente_id'] ?? 0);
}

if ($contextId <= 0) {
    return;
}

$currencyCfg = currency_get_config($pdo);
$formatMoney = static function (float $amount) use ($currencyCfg): string {
    if (function_exists('money_format_local')) {
        return (string)money_format_local($amount, $currencyCfg);
    }
    return 'S/ ' . number_format($amount, 2);
};

$entityTable = $contextType === 'empresa'
    ? 'empresas'
    : ($contextType === 'convenio' ? 'convenios' : 'clientes');
$entityLabel = $contextType === 'empresa'
    ? 'Empresa'
    : ($contextType === 'convenio' ? 'Convenio' : 'Cliente');
$hasEntityDiscount = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'descuento');
$hasEntityUseConvenioPrice = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'usar_precio_convenio');
$hasEntityEmail = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'email');
$hasEntityDni = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'dni');
$hasEntityRuc = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'ruc');
$hasEntityAddress = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'direccion');
$hasEntityPhone = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'telefono');
$hasEntityMobile = cotizadorRapidoTableHasColumn($pdo, $entityTable, 'celular');

if ($contextType === 'empresa') {
    $nameExpr = "COALESCE(NULLIF(TRIM(nombre_comercial), ''), razon_social) AS nombre_reporte";
} elseif ($contextType === 'convenio') {
    $nameExpr = "COALESCE(NULLIF(TRIM(nombre), ''), CONCAT('Convenio #', id)) AS nombre_reporte";
} else {
    $nameExpr = "TRIM(CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellido, ''))) AS nombre_reporte";
}

$entitySql = "SELECT {$nameExpr}, "
    . ($hasEntityDiscount ? 'descuento' : '0 AS descuento') . ', '
    . ($hasEntityUseConvenioPrice ? 'usar_precio_convenio' : '0 AS usar_precio_convenio') . ', '
    . ($hasEntityEmail ? 'email' : "'' AS email") . ', '
    . ($hasEntityDni ? 'dni' : "'' AS dni") . ', '
    . ($hasEntityRuc ? 'ruc' : "'' AS ruc") . ', '
    . ($hasEntityAddress ? 'direccion' : "'' AS direccion") . ', '
    . ($hasEntityPhone ? 'telefono' : "'' AS telefono") . ', '
    . ($hasEntityMobile ? 'celular' : "'' AS celular") . "\n"
    . "FROM {$entityTable} WHERE id = ? LIMIT 1";

$stmtEntity = $pdo->prepare($entitySql);
$stmtEntity->execute([$contextId]);
$entity = $stmtEntity->fetch(PDO::FETCH_ASSOC) ?: [];

$discount = (float)($entity['descuento'] ?? 0);
$useConvenioPrice = (int)($entity['usar_precio_convenio'] ?? 0) === 1;
$forcePublicPricing = !empty($cotizadorForzarPrecioPublico) || $contextType === 'cliente';

$hasExPrecioConvenio = cotizadorRapidoTableHasColumn($pdo, 'examenes', 'precio_convenio');
$hasPreanaliticaRef = cotizadorRapidoTableHasColumn($pdo, 'examenes', 'preanalitica_referencias');
$hasTipoMuestra = cotizadorRapidoTableHasColumn($pdo, 'examenes', 'tipo_muestra');
$hasTipoTubo = cotizadorRapidoTableHasColumn($pdo, 'examenes', 'tipo_tubo');
$hasMetodologia = cotizadorRapidoTableHasColumn($pdo, 'examenes', 'metodologia');

$catalogSql = "SELECT\n"
    . "  id,\n"
    . "  COALESCE(codigo, '') AS codigo,\n"
    . "  COALESCE(nombre, '') AS nombre,\n"
    . "  COALESCE(tiempo_respuesta, '') AS tiempo_respuesta,\n"
    . "  " . ($hasMetodologia ? "COALESCE(metodologia, '')" : "''") . " AS metodologia,\n"
    . "  COALESCE(preanalitica_cliente, '') AS preanalitica_cliente,\n"
    . "  " . ($hasPreanaliticaRef ? "COALESCE(preanalitica_referencias, '')" : "''") . " AS preanalitica_referencias,\n"
    . "  " . ($hasTipoMuestra ? "COALESCE(tipo_muestra, '')" : "''") . " AS tipo_muestra,\n"
    . "  " . ($hasTipoTubo ? "COALESCE(tipo_tubo, '')" : "''") . " AS tipo_tubo,\n"
    . "  COALESCE(observaciones, '') AS observaciones,\n"
    . "  COALESCE(precio_publico, 0) AS precio_publico,\n"
    . "  " . ($hasExPrecioConvenio ? "COALESCE(precio_convenio, 0)" : "0") . " AS precio_convenio\n"
    . "FROM examenes\n"
    . "WHERE vigente = 1\n"
    . "ORDER BY nombre ASC";

$stmtExam = $pdo->query($catalogSql);
$catalogRows = $stmtExam ? $stmtExam->fetchAll(PDO::FETCH_ASSOC) : [];

$catalog = [];
foreach ($catalogRows as $row) {
    $precioPublico = (float)($row['precio_publico'] ?? 0);
    $precioConvenio = (float)($row['precio_convenio'] ?? 0);

    $precioFuente = 'publico';
    $precioFinal = $precioPublico;

    if ($forcePublicPricing) {
        $precioFinal = $precioPublico;
        $precioFuente = 'publico';
    } elseif ($useConvenioPrice && $precioConvenio > 0) {
        $precioFinal = $precioConvenio;
        $precioFuente = 'convenio';
    } elseif ($discount > 0) {
        $precioFinal = cotizadorRapidoApplyDiscount($precioPublico, $discount);
        $precioFuente = 'descuento';
    }

    $catalog[] = [
        'id' => (int)($row['id'] ?? 0),
        'codigo' => trim((string)($row['codigo'] ?? '')),
        'nombre' => trim((string)($row['nombre'] ?? '')),
        'metodologia' => trim((string)($row['metodologia'] ?? '')),
        'tiempo_respuesta' => trim((string)($row['tiempo_respuesta'] ?? '')),
        'preanalitica_cliente' => trim((string)($row['preanalitica_cliente'] ?? '')),
        'preanalitica_referencias' => trim((string)($row['preanalitica_referencias'] ?? '')),
        'tipo_muestra' => trim((string)($row['tipo_muestra'] ?? '')),
        'tipo_tubo' => trim((string)($row['tipo_tubo'] ?? '')),
        'observaciones' => trim((string)($row['observaciones'] ?? '')),
        'precio_publico' => round($precioPublico, 2),
        'precio_convenio' => round($precioConvenio, 2),
        'precio_efectivo' => round($precioFinal, 2),
        'precio_fuente' => $precioFuente,
        'precio_efectivo_texto' => $formatMoney((float)$precioFinal),
    ];
}

$companyName = trim((string)($config['nombre'] ?? 'FARLAB'));
$companyRuc = trim((string)($config['ruc'] ?? ''));
$companyAddress = trim((string)($config['direccion'] ?? ''));
$companyPhone = trim((string)($config['telefono'] ?? ($config['celular'] ?? '')));
$companyLogoRaw = trim((string)($config['logo'] ?? '../uploads/empresa/logo_empresa.png'));

$logoRelativePath = cotizadorRapidoResolveLogoPublicPath($companyLogoRaw);
$logoAbsolutePath = dirname(__DIR__, 3) . '/' . ltrim($logoRelativePath, '/');
$logoPublicUrl = rtrim((string)BASE_URL, '/\\') . '/../' . ltrim($logoRelativePath, '/');
$logoDataUri = cotizadorRapidoLogoDataUri($logoAbsolutePath);

$entityName = trim((string)($entity['nombre_reporte'] ?? ($entityLabel . ' #' . $contextId)));
if ($entityName === '') {
    $entityName = $entityLabel . ' #' . $contextId;
}
$entityDoc = trim((string)($entity['ruc'] ?? ''));
if ($entityDoc === '') {
    $entityDoc = trim((string)($entity['dni'] ?? ''));
}
$entityEmail = trim((string)($entity['email'] ?? ''));
$entityAddress = trim((string)($entity['direccion'] ?? ''));
$entityPhone = trim((string)($entity['telefono'] ?? ($entity['celular'] ?? '')));

$tableId = $contextType === 'empresa'
    ? 'tablaCotizadorRapidoEmpresa'
    : ($contextType === 'convenio' ? 'tablaCotizadorRapidoConvenio' : 'tablaCotizadorRapidoCliente');
$exportTitle = 'Lista de Precios - ' . $entityLabel;
$exportFileBase = 'Lista de Precios inbioslab - ' . $entityLabel;
$policyText = $forcePublicPricing
    ? 'Modo de precio: PUBLICO'
    : ($useConvenioPrice ? 'Precio convenio por defecto: ACTIVADO' : 'Precio convenio por defecto: DESACTIVADO');
$discountText = 'Descuento aplicado: ' . number_format($forcePublicPricing ? 0 : $discount, 2) . '%';
$generatedAt = date('Y-m-d H:i:s');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
.cotizador-rapido-card {
    border: 1px solid #dce8f8;
    border-radius: 16px;
    box-shadow: 0 12px 25px rgba(26, 61, 105, 0.09);
}

.cotizador-rapido-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.cotizador-rapido-title {
    margin-bottom: 0.2rem;
    color: #1f3f60;
    font-weight: 700;
}

.cotizador-rapido-meta {
    font-size: 0.88rem;
    color: #5f7891;
    line-height: 1.3;
}

.cotizador-rapido-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(120px, 1fr));
    gap: 8px;
}

.cotizador-rapido-actions .btn {
    border-radius: 10px;
    font-weight: 600;
}

#<?= htmlspecialchars($tableId) ?> thead th {
    background: #23405e;
    color: #fff;
    border-color: #23405e;
    font-weight: 600;
}

#<?= htmlspecialchars($tableId) ?> td {
    vertical-align: middle;
}

#<?= htmlspecialchars($tableId) ?> .btn-detalle-rapido,
#<?= htmlspecialchars($tableId) ?> .btn-cotizar-rapido,
#<?= htmlspecialchars($tableId) ?> .btn-agregar-rapido {
    min-width: 96px;
}

#<?= htmlspecialchars($tableId) ?> .acciones-rapidas {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.cotizador-rapido-carrito {
    margin-top: 14px;
    border: 1px solid #dce8f8;
    border-radius: 12px;
    background: #f8fbff;
    padding: 12px;
}

.cotizador-rapido-carrito .carrito-head {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.cotizador-rapido-carrito .carrito-total {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1f3f60;
}

.cotizador-rapido-carrito .carrito-items {
    max-height: 220px;
    overflow-y: auto;
}

.cotizador-rapido-carrito .carrito-item {
    border: 1px solid #e2ebf7;
    border-radius: 10px;
    background: #fff;
    padding: 8px;
    margin-bottom: 8px;
}

.cotizador-rapido-carrito .carrito-item:last-child {
    margin-bottom: 0;
}

.cotizador-rapido-carrito .carrito-empty {
    font-size: 0.92rem;
    color: #607890;
    background: #fff;
    border: 1px dashed #c9d9ec;
    border-radius: 10px;
    padding: 12px;
    text-align: center;
}

.cotizador-rapido-leyenda {
    font-size: 0.83rem;
    color: #607890;
}

#<?= htmlspecialchars($tableId) ?>_wrapper .dataTables_filter input,
#<?= htmlspecialchars($tableId) ?>_wrapper .dataTables_length select {
    border-radius: 8px;
    border: 1px solid #c9d9ec;
}

#<?= htmlspecialchars($tableId) ?>_wrapper .dataTables_info {
    color: #5a7188;
}

@media (max-width: 767.98px) {
    .cotizador-rapido-actions {
        grid-template-columns: 1fr;
        width: 100%;
    }

    #<?= htmlspecialchars($tableId) ?> .acciones-rapidas .btn {
        flex: 1 1 calc(50% - 8px);
    }
}
</style>

<div class="card cotizador-rapido-card mt-4">
    <div class="card-body">
        <div class="cotizador-rapido-head mb-3">
            <div>
                <h5 class="cotizador-rapido-title">Cotizador rapido</h5>
                <div class="cotizador-rapido-meta"><?= htmlspecialchars($entityLabel) ?>: <?= htmlspecialchars($entityName) ?></div>
                <div class="cotizador-rapido-meta"><?= htmlspecialchars($policyText) ?> | <?= htmlspecialchars($discountText) ?></div>
            </div>
            <div class="cotizador-rapido-actions">
                <button type="button" class="btn btn-success btn-sm" id="<?= htmlspecialchars($tableId) ?>BtnExcel">
                    <i class="bi bi-file-earmark-excel"></i> Excel precios
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="<?= htmlspecialchars($tableId) ?>BtnPdf">
                    <i class="bi bi-file-earmark-pdf"></i> PDF precios
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="<?= htmlspecialchars($tableId) ?>BtnPrint">
                    <i class="bi bi-printer"></i> Imprimir precios
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle" id="<?= htmlspecialchars($tableId) ?>" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:80px;">N#</th>
                        <th>Examen</th>
                        <th style="width:170px;">Precio configurado</th>
                        <th style="width:190px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalog as $idx => $ex): ?>
                        <?php
                        $examenJson = htmlspecialchars(json_encode($ex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td><?= (int)($idx + 1) ?></td>
                            <td
                                data-tiempo-proceso="<?= htmlspecialchars($ex['tiempo_respuesta'] !== '' ? $ex['tiempo_respuesta'] : '-', ENT_QUOTES, 'UTF-8') ?>"
                                data-tipo-tubo="<?= htmlspecialchars($ex['tipo_tubo'] !== '' ? $ex['tipo_tubo'] : '-', ENT_QUOTES, 'UTF-8') ?>"
                                data-observacion="<?= htmlspecialchars($ex['observaciones'] !== '' ? $ex['observaciones'] : '-', ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <div class="fw-semibold"><?= htmlspecialchars($ex['nombre']) ?></div>
                                <div class="small text-muted">Codigo: <?= htmlspecialchars($ex['codigo'] !== '' ? $ex['codigo'] : '-') ?></div>
                            </td>
                            <td data-order="<?= number_format((float)$ex['precio_efectivo'], 2, '.', '') ?>"><?= htmlspecialchars($ex['precio_efectivo_texto']) ?></td>
                            <td>
                                <div class="acciones-rapidas">
                                    <button type="button" class="btn btn-outline-info btn-sm btn-detalle-rapido" data-examen="<?= $examenJson ?>">
                                        <i class="bi bi-eye"></i> Ver detalle
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success btn-agregar-rapido" data-examen="<?= $examenJson ?>">
                                        <i class="bi bi-cart-plus-fill"></i> Agregar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cotizador-rapido-carrito" id="<?= htmlspecialchars($tableId) ?>CarritoWrap">
            <div class="carrito-head">
                <div>
                    <div class="fw-semibold text-primary">Lista rápida de exámenes</div>
                    <div class="cotizador-rapido-leyenda">Arma la cotización sin registrar paciente.</div>
                </div>
                <div class="carrito-total" id="<?= htmlspecialchars($tableId) ?>CarritoTotal">Total: <?= htmlspecialchars($formatMoney(0)) ?></div>
            </div>
            <div class="carrito-items mb-2" id="<?= htmlspecialchars($tableId) ?>CarritoItems"></div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="<?= htmlspecialchars($tableId) ?>CarritoLimpiar">
                    <i class="bi bi-trash"></i> Limpiar
                </button>
                <button type="button" class="btn btn-success btn-sm" id="<?= htmlspecialchars($tableId) ?>CarritoCotizarRapido">
                    <i class="bi bi-receipt"></i> Cotización rápida (sin paciente)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="<?= htmlspecialchars($tableId) ?>ModalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="<?= htmlspecialchars($tableId) ?>DetalleBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="<?= htmlspecialchars($tableId) ?>ModalResumenRapido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cotización rápida sin registro de paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted">
                    <?= htmlspecialchars($entityLabel) ?>: <?= htmlspecialchars($entityName) ?><br>
                    <?= htmlspecialchars($policyText) ?> | <?= htmlspecialchars($discountText) ?>
                </div>
                <div id="<?= htmlspecialchars($tableId) ?>ResumenRapidoBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary btn-sm" id="<?= htmlspecialchars($tableId) ?>ResumenCopiar">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="<?= htmlspecialchars($tableId) ?>ResumenImprimir">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
(function() {
    const tableId = <?= json_encode($tableId) ?>;
    const exportTitle = <?= json_encode($exportTitle) ?>;
    const exportFileBase = <?= json_encode($exportFileBase) ?>;
    const currencyCfg = <?= json_encode($currencyCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const reportMeta = {
        companyName: <?= json_encode($companyName) ?>,
        companyRuc: <?= json_encode($companyRuc) ?>,
        companyAddress: <?= json_encode($companyAddress) ?>,
        companyPhone: <?= json_encode($companyPhone) ?>,
        companyLogoUrl: <?= json_encode($logoPublicUrl) ?>,
        companyLogoDataUri: <?= json_encode($logoDataUri) ?>,
        entityLabel: <?= json_encode($entityLabel) ?>,
        entityName: <?= json_encode($entityName) ?>,
        entityDoc: <?= json_encode($entityDoc) ?>,
        entityEmail: <?= json_encode($entityEmail) ?>,
        entityAddress: <?= json_encode($entityAddress) ?>,
        entityPhone: <?= json_encode($entityPhone) ?>,
        policyText: <?= json_encode($policyText) ?>,
        discountText: <?= json_encode($discountText) ?>,
        generatedAt: <?= json_encode($generatedAt) ?>
    };
    const carritoItemsEl = document.getElementById(tableId + 'CarritoItems');
    const carritoTotalEl = document.getElementById(tableId + 'CarritoTotal');
    const resumenBodyEl = document.getElementById(tableId + 'ResumenRapidoBody');
    const modalResumenEl = document.getElementById(tableId + 'ModalResumenRapido');
    const carrito = new Map();
    const cartStorageKey = 'cotizador_rapido_carrito_' + <?= json_encode($contextType) ?> + '_' + <?= (int)$contextId ?>;

    function formatMoney(value) {
        const amount = Number(value || 0);
        const decimals = Number(currencyCfg.decimals ?? 2);
        const decimalSeparator = currencyCfg.decimal_separator ?? '.';
        const thousandsSeparator = currencyCfg.thousands_separator ?? ',';
        const symbol = currencyCfg.symbol ?? 'S/';
        const position = currencyCfg.position === 'suffix' ? 'suffix' : 'prefix';
        const fixed = amount.toFixed(decimals);
        const parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        const numberText = decimals > 0 ? parts.join(decimalSeparator) : parts[0];
        return position === 'suffix' ? (numberText + ' ' + symbol) : (symbol + ' ' + numberText);
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function carritoToArray() {
        return Array.from(carrito.values()).sort(function(a, b) {
            return String(a.nombre || '').localeCompare(String(b.nombre || ''));
        });
    }

    function saveCarritoToStorage() {
        if (!window.localStorage) {
            return;
        }
        const payload = carritoToArray().map(function(item) {
            return {
                id: Number(item.id || 0),
                codigo: String(item.codigo || ''),
                nombre: String(item.nombre || ''),
                precio_efectivo: Number(item.precio_efectivo || 0)
            };
        }).filter(function(item) {
            return item.id > 0 && item.nombre !== '';
        });

        if (payload.length === 0) {
            window.localStorage.removeItem(cartStorageKey);
            return;
        }

        window.localStorage.setItem(cartStorageKey, JSON.stringify(payload));
    }

    function loadCarritoFromStorage() {
        if (!window.localStorage) {
            return;
        }
        const raw = window.localStorage.getItem(cartStorageKey);
        if (!raw) {
            return;
        }
        let parsed = [];
        try {
            parsed = JSON.parse(raw);
        } catch (e) {
            parsed = [];
        }
        if (!Array.isArray(parsed)) {
            return;
        }
        parsed.forEach(function(item) {
            const id = Number(item && item.id ? item.id : 0);
            if (id <= 0) {
                return;
            }
            const price = Number(item.precio_efectivo || 0);
            carrito.set(id, {
                id: id,
                codigo: String(item.codigo || ''),
                nombre: String(item.nombre || ''),
                precio_efectivo: isNaN(price) ? 0 : price
            });
        });
    }

    function calcularTotal() {
        let total = 0;
        carrito.forEach(function(item) {
            total += Number(item.precio_efectivo || 0);
        });
        return total;
    }

    function renderCarrito() {
        if (!carritoItemsEl || !carritoTotalEl) {
            return;
        }
        const items = carritoToArray();
        if (items.length === 0) {
            carritoItemsEl.innerHTML = '<div class="carrito-empty">Aún no agregaste exámenes al carrito.</div>';
            carritoTotalEl.textContent = 'Total: ' + formatMoney(0);
            saveCarritoToStorage();
            return;
        }

        let html = '';
        items.forEach(function(item) {
            const unit = Number(item.precio_efectivo || 0);
            html += '' +
                '<div class="carrito-item">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2">' +
                        '<div>' +
                            '<div class="fw-semibold">' + escapeHtml(item.nombre || '-') + '</div>' +
                            '<div class="small text-muted">Código: ' + escapeHtml(item.codigo || '-') + '</div>' +
                            '<div class="small text-muted">Precio unitario: ' + escapeHtml(formatMoney(unit)) + '</div>' +
                        '</div>' +
                        '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-cart" data-examen-id="' + Number(item.id) + '">' +
                            '<i class="bi bi-x-lg"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="d-flex justify-content-between align-items-center mt-2">' +
                        '<div class="small text-muted">Cada examen se agrega una sola vez.</div>' +
                        '<div class="fw-semibold text-primary">Subtotal: ' + escapeHtml(formatMoney(unit)) + '</div>' +
                    '</div>' +
                '</div>';
        });

        carritoItemsEl.innerHTML = html;
        carritoTotalEl.textContent = 'Total: ' + formatMoney(calcularTotal());
        saveCarritoToStorage();
    }

    function addToCarrito(ex) {
        const examId = Number(ex.id || 0);
        if (examId <= 0) {
            return;
        }
        const existing = carrito.get(examId);
        if (existing) {
            renderCarrito();
            return;
        } else {
            carrito.set(examId, {
                id: examId,
                codigo: String(ex.codigo || ''),
                nombre: String(ex.nombre || ''),
                precio_efectivo: Number(ex.precio_efectivo || 0)
            });
        }
        renderCarrito();
    }

    function buildResumenRapido() {
        const items = carritoToArray();
        if (items.length === 0) {
            return {
                html: '<div class="alert alert-warning mb-0">Agrega al menos un examen al carrito para cotizar.</div>',
                text: ''
            };
        }

        let linesHtml = '<div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Examen</th><th style="width:140px;">P. unitario</th><th style="width:150px;">Subtotal</th></tr></thead><tbody>';
        const linesText = [];
        items.forEach(function(item) {
            const unit = Number(item.precio_efectivo || 0);
            const sub = unit;
            linesHtml += '<tr>' +
                '<td><strong>' + escapeHtml(item.nombre || '-') + '</strong><br><small class="text-muted">Código: ' + escapeHtml(item.codigo || '-') + '</small></td>' +
                '<td>' + escapeHtml(formatMoney(unit)) + '</td>' +
                '<td>' + escapeHtml(formatMoney(sub)) + '</td>' +
            '</tr>';
            linesText.push('- ' + (item.nombre || '-') + ' (' + formatMoney(sub) + ')');
        });
        linesHtml += '</tbody></table></div>';
        const total = formatMoney(calcularTotal());
        const remitente = reportMeta.entityName || reportMeta.companyName || 'Inbioslab';
        const introHtml = '' +
            '<div class="border rounded p-2 mb-2 bg-light">' +
                '<div>Hola 👋</div>' +
                '<div>Le saluda <strong>' + escapeHtml(remitente) + '</strong> 🧪, su laboratorio clínico de confianza.</div>' +
                '<div>Con gusto le compartimos su cotización:</div>' +
            '</div>';
        const bodyHtml = introHtml + linesHtml + '<div class="fw-semibold text-end text-primary">Total: ' + escapeHtml(total) + '</div>';
        const bodyText = [
            'Hola 👋',
            'Le saluda *' + remitente + '* 🧪, su laboratorio clínico de confianza.',
            'Con gusto le compartimos su cotización:',
            reportMeta.policyText + ' | ' + reportMeta.discountText,
            '',
            'Detalle:',
            linesText.join('\n'),
            '',
            'Total: ' + total
        ].join('\n');

        return { html: bodyHtml, text: bodyText };
    }

    renderCarrito();

    const reportSubtitleLines = [
        reportMeta.entityLabel + ': ' + reportMeta.entityName,
        (reportMeta.entityDoc ? ('Documento: ' + reportMeta.entityDoc) : ''),
        (reportMeta.entityEmail ? ('Email: ' + reportMeta.entityEmail) : ''),
        (reportMeta.entityAddress ? ('Direccion: ' + reportMeta.entityAddress) : ''),
        (reportMeta.entityPhone ? ('Telefono: ' + reportMeta.entityPhone) : ''),
        reportMeta.policyText,
        reportMeta.discountText,
        'Generado: ' + reportMeta.generatedAt
    ].filter(Boolean).join('\n');

    function stripHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return $('<div>').html(String(value).replace(/<br\s*\/?>/gi, '\n')).text().replace(/\u00a0/g, ' ').trim();
    }

    function buildDetailHtml(ex) {
        return [
            '<div class="row g-3">',
                '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                    '<h6 class="mb-2 text-primary">Informacion general</h6>',
                    '<div><strong>Codigo:</strong> ' + (ex.codigo || '-') + '</div>',
                    '<div><strong>Nombre:</strong> ' + (ex.nombre || '-') + '</div>',
                    '<div><strong>Precio configurado:</strong> ' + (ex.precio_efectivo_texto || '-') + '</div>',
                '</div></div>',
                '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                    '<h6 class="mb-2 text-primary">Proceso</h6>',
                    '<div><strong>Tiempo de respuesta:</strong> ' + (ex.tiempo_respuesta || '-') + '</div>',
                    '<div><strong>Metodologia:</strong> ' + (ex.metodologia || '-') + '</div>',
                    '<div><strong>Tipo de muestra:</strong> ' + (ex.tipo_muestra || '-') + '</div>',
                    '<div><strong>Tipo de tubo:</strong> ' + (ex.tipo_tubo || '-') + '</div>',
                '</div></div>',
                '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                    '<h6 class="mb-2 text-primary">Condiciones</h6>',
                    '<div><strong>Para paciente:</strong><br>' + (ex.preanalitica_cliente || '-') + '</div>',
                '</div></div>',
                '<div class="col-md-6"><div class="border rounded p-3 h-100">',
                    '<h6 class="mb-2 text-primary">Condiciones referencia</h6>',
                    '<div><strong>Para convenio/empresa:</strong><br>' + (ex.preanalitica_referencias || '-') + '</div>',
                '</div></div>',
                '<div class="col-12"><div class="border rounded p-3">',
                    '<h6 class="mb-2 text-primary">Observaciones</h6>',
                    '<div>' + (ex.observaciones || '-') + '</div>',
                '</div></div>',
            '</div>'
        ].join('');
    }

    const dt = $('#' + tableId).DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
        dom: 'Bfrtip',
        initComplete: function() {
            $('#' + tableId + '_wrapper .dt-buttons').hide();
        },
        buttons: [
            {
                extend: 'excelHtml5',
                title: exportTitle,
                filename: exportFileBase,
                messageTop: reportSubtitleLines,
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function(data) {
                            return stripHtml(data);
                        }
                    }
                }
            },
            {
                extend: 'pdfHtml5',
                title: exportTitle,
                filename: exportFileBase,
                messageTop: '',
                orientation: 'portrait',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function(data, row, column, node) {
                            const baseText = stripHtml(data);
                            if (column === 1 && node) {
                                const tiempo = (node.getAttribute('data-tiempo-proceso') || '-').trim();
                                const tipoTubo = (node.getAttribute('data-tipo-tubo') || '-').trim();
                                const observacion = (node.getAttribute('data-observacion') || '-').trim();
                                return [
                                    baseText,
                                    'Tiempo de proceso: ' + (tiempo || '-'),
                                    'Tipo de tubo: ' + (tipoTubo || '-'),
                                    'Observacion: ' + (observacion || '-')
                                ].join('\n');
                            }
                            return baseText;
                        }
                    }
                },
                customize: function(doc) {
                    doc.pageMargins = [24, 24, 24, 34];
                    doc.defaultStyle.fontSize = 9;
                    doc.styles.title = { fontSize: 16, bold: true, alignment: 'center', margin: [0, 0, 0, 10] };
                    doc.styles.tableHeader = {
                        fillColor: '#23405e',
                        color: '#ffffff',
                        bold: true,
                        fontSize: 10,
                        alignment: 'left'
                    };

                    const infoLines = [
                        reportMeta.entityLabel + ': ' + reportMeta.entityName,
                        (reportMeta.entityDoc ? ('Documento: ' + reportMeta.entityDoc) : ''),
                        (reportMeta.entityEmail ? ('Email: ' + reportMeta.entityEmail) : ''),
                        (reportMeta.entityAddress ? ('Direccion: ' + reportMeta.entityAddress) : ''),
                        (reportMeta.entityPhone ? ('Telefono: ' + reportMeta.entityPhone) : ''),
                        reportMeta.policyText,
                        reportMeta.discountText,
                        'Generado: ' + reportMeta.generatedAt
                    ].filter(Boolean).join('\n');

                    const topBlock = [];
                    if (reportMeta.companyLogoDataUri) {
                        topBlock.push({ image: reportMeta.companyLogoDataUri, width: 64, margin: [0, 0, 0, 6] });
                    }
                    topBlock.push({ text: reportMeta.companyName || 'FARLAB', bold: true, fontSize: 13, margin: [0, 0, 0, 6] });
                    if (reportMeta.companyRuc) {
                        topBlock.push({ text: 'RUC: ' + reportMeta.companyRuc, fontSize: 9, margin: [0, 0, 0, 2] });
                    }
                    if (reportMeta.companyAddress) {
                        topBlock.push({ text: 'Direccion: ' + reportMeta.companyAddress, fontSize: 9, margin: [0, 0, 0, 2] });
                    }
                    if (reportMeta.companyPhone) {
                        topBlock.push({ text: 'Telefono: ' + reportMeta.companyPhone, fontSize: 9, margin: [0, 0, 0, 6] });
                    }
                    topBlock.push({
                        margin: [0, 0, 0, 10],
                        columns: [
                            {
                                width: '*',
                                text: infoLines,
                                fontSize: 9,
                                color: '#1f3f60'
                            }
                        ]
                    });

                    doc.content.splice(0, 0, { stack: topBlock, margin: [0, 0, 0, 6] });

                    const tableNode = doc.content.find(function(node) {
                        return node && node.table;
                    });

                    if (tableNode && tableNode.table) {
                        tableNode.layout = {
                            hLineColor: function() { return '#d7e3f1'; },
                            vLineColor: function() { return '#d7e3f1'; },
                            hLineWidth: function() { return 0.8; },
                            vLineWidth: function() { return 0.8; },
                            paddingLeft: function() { return 6; },
                            paddingRight: function() { return 6; },
                            paddingTop: function() { return 4; },
                            paddingBottom: function() { return 4; },
                            fillColor: function(rowIndex) {
                                if (rowIndex === 0) {
                                    return '#23405e';
                                }
                                return rowIndex % 2 === 0 ? '#f7fbff' : null;
                            }
                        };
                        tableNode.table.widths = [42, '*', 110];
                        tableNode.table.headerRows = 1;
                    }

                    doc.footer = function(currentPage, pageCount) {
                        return {
                            margin: [24, 0, 24, 8],
                            columns: [
                                { text: 'Generado: ' + reportMeta.generatedAt, alignment: 'left', fontSize: 8 },
                                { text: 'Pagina ' + currentPage + ' de ' + pageCount, alignment: 'right', fontSize: 8 }
                            ]
                        };
                    };
                }
            },
            {
                extend: 'print',
                title: exportTitle,
                messageTop: reportSubtitleLines,
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function(data) {
                            return stripHtml(data);
                        }
                    }
                },
                customize: function(win) {
                    const logoHtml = reportMeta.companyLogoUrl
                        ? '<img src="' + reportMeta.companyLogoUrl + '" style="height:55px;margin-bottom:8px;" alt="Logo">'
                        : '';
                    $(win.document.body).prepend(
                        '<div style="margin-bottom:12px;">' +
                            logoHtml +
                            '<div style="font-size:16px;font-weight:700;">' + (reportMeta.companyName || 'FARLAB') + '</div>' +
                            (reportMeta.companyRuc ? '<div>RUC: ' + reportMeta.companyRuc + '</div>' : '') +
                            (reportMeta.companyAddress ? '<div>Direccion: ' + reportMeta.companyAddress + '</div>' : '') +
                            (reportMeta.companyPhone ? '<div>Telefono: ' + reportMeta.companyPhone + '</div>' : '') +
                        '</div>'
                    );
                    $(win.document.body).css('font-size', '10px');
                    $(win.document.body).find('table').addClass('compact').css('font-size', '10px');
                }
            }
        ]
    });

    $('#' + tableId + 'BtnExcel').on('click', function() {
        dt.button(0).trigger();
    });
    $('#' + tableId + 'BtnPdf').on('click', function() {
        dt.button(1).trigger();
    });
    $('#' + tableId + 'BtnPrint').on('click', function() {
        dt.button(2).trigger();
    });

    const modalEl = document.getElementById(tableId + 'ModalDetalle');
    const detailBody = document.getElementById(tableId + 'DetalleBody');

    $(document).on('click', '#' + tableId + ' .btn-detalle-rapido', function() {
        const raw = $(this).attr('data-examen') || '{}';
        let ex = {};
        try {
            ex = JSON.parse(raw);
        } catch (e) {
            ex = {};
        }

        if (detailBody) {
            detailBody.innerHTML = buildDetailHtml(ex);
        }
        if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
    });

    $(document).on('click', '#' + tableId + ' .btn-agregar-rapido', function() {
        const raw = $(this).attr('data-examen') || '{}';
        let ex = {};
        try {
            ex = JSON.parse(raw);
        } catch (e) {
            ex = {};
        }
        addToCarrito(ex);
    });

    $(document).on('click', '#' + tableId + ' .btn-remove-cart', function() {
        const examId = Number($(this).attr('data-examen-id') || 0);
        carrito.delete(examId);
        renderCarrito();
    });

    $('#' + tableId + 'CarritoLimpiar').on('click', function() {
        carrito.clear();
        renderCarrito();
    });

    $('#' + tableId + 'CarritoCotizarRapido').on('click', function() {
        const resumen = buildResumenRapido();
        if (resumenBodyEl) {
            resumenBodyEl.innerHTML = resumen.html;
            resumenBodyEl.setAttribute('data-copy-text', resumen.text);
        }
        if (modalResumenEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalResumenEl);
            modalInstance.show();
        }
    });

    $('#' + tableId + 'ResumenCopiar').on('click', function() {
        if (!resumenBodyEl) {
            return;
        }
        const text = resumenBodyEl.getAttribute('data-copy-text') || '';
        if (!text) {
            return;
        }
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text);
        }
    });

    $('#' + tableId + 'ResumenImprimir').on('click', function() {
        if (!resumenBodyEl) {
            return;
        }
        const html = resumenBodyEl.innerHTML || '';
        if (!html) {
            return;
        }
        const w = window.open('', '_blank', 'width=900,height=700');
        if (!w) {
            return;
        }
        w.document.open();
        w.document.write(
            '<html><head><title>Cotización rápida</title>' +
            '<style>body{font-family:Arial,sans-serif;padding:18px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #d5dfec;padding:6px;text-align:left}.text-end{text-align:right}.text-primary{color:#1f3f60}</style>' +
            '</head><body>' +
            '<h3>Cotización rápida</h3>' +
            '<div style="margin-bottom:8px;font-size:13px;color:#4a637d">' + escapeHtml(reportMeta.entityLabel + ': ' + reportMeta.entityName) + '</div>' +
            html +
            '</body></html>'
        );
        w.document.close();
        w.focus();
        w.print();
    });
})();
</script>
