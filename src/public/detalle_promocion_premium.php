<?php
$promoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE id = ?");
$stmt->execute([$promoId]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

$toPublicAsset = static function (string $path) use ($siteBasePath): string {
    $p = trim($path);
    if ($p === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $p) || strpos($p, 'data:') === 0) {
        return $p;
    }
    $normalized = str_replace('\\', '/', $p);
    $normalized = preg_replace('#^\./+#', '', $normalized);
    $normalized = preg_replace('#^\.\./+#', '', $normalized);
    $normalized = ltrim($normalized, '/');
    return ($siteBasePath === '' ? '' : $siteBasePath) . '/' . $normalized;
};

$promoImageUrl = static function ($promoRow) use ($toPublicAsset): string {
    $raw = trim((string)($promoRow['imagen'] ?? ''));
    if ($raw === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $raw) || strpos($raw, 'data:') === 0) {
        return $raw;
    }
    if (strpos($raw, '/') === false && strpos($raw, '\\') === false) {
        return rtrim((string)BASE_URL, '/\\') . '/promociones/assets/' . rawurlencode($raw);
    }
    return $toPublicAsset($raw);
};

$formatPromoDate = static function ($raw): string {
    $txt = trim((string)$raw);
    if ($txt === '') {
        return '';
    }
    $ts = strtotime($txt);
    if ($ts === false || $ts <= 0) {
        return '';
    }
    return date('d/m/Y', $ts);
};

$btnTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_botones)
    : '#ffffff';
$navTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_principal)
    : '#ffffff';
$footerTextContrast = function_exists('ui_theme_text_for_bg')
    ? ui_theme_text_for_bg((string)$color_footer)
    : '#ffffff';

$whatsAppHref = '';
if (!empty($redes_sociales) && is_array($redes_sociales)) {
    foreach ($redes_sociales as $red) {
        if (!is_array($red)) {
            continue;
        }
        $nombreRed = strtolower(trim((string)($red['nombre'] ?? '')));
        $urlRed = trim((string)($red['url'] ?? ''));
        if ($urlRed === '') {
            continue;
        }
        $esWhatsApp = (strpos($nombreRed, 'whatsapp') !== false)
            || (stripos($urlRed, 'wa.me') !== false)
            || (stripos($urlRed, 'whatsapp.com') !== false)
            || (stripos($urlRed, 'api.whatsapp.com') !== false);
        if (!$esWhatsApp) {
            continue;
        }
        if (preg_match('~^https?://~i', $urlRed)) {
            $whatsAppHref = $urlRed;
        } else {
            $numeroRed = preg_replace('/\D+/', '', $urlRed);
            if ($numeroRed !== '') {
                $whatsAppHref = 'https://wa.me/' . $numeroRed;
            }
        }
        if ($whatsAppHref !== '') {
            break;
        }
    }
}
if ($whatsAppHref === '') {
    $whatsappNumero = preg_replace('/\D+/', '', (string)($config_empresa['celular'] ?? ''));
    if ($whatsappNumero !== '') {
        $whatsAppHref = 'https://wa.me/' . $whatsappNumero;
    }
}

$pwaBasePath = isset($siteBasePath) ? rtrim((string)$siteBasePath, '/\\') : '';
if ($pwaBasePath === '.' || $pwaBasePath === '/') {
    $pwaBasePath = '';
}
$pwaManifestHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/manifest.php';
$pwaServiceWorkerHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/sw.js';
$pwaScope = $pwaBasePath === '' ? '/' : ($pwaBasePath . '/');
$pwaRegisterScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-register.js';
$pwaInstallScriptHref = ($pwaBasePath === '' ? '' : $pwaBasePath) . '/src/pwa/pwa-install.js';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title><?= htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8') ?> | Detalle Promocion</title>
    <meta name="theme-color" content="<?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="manifest" href="<?= htmlspecialchars($pwaManifestHref, ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --p-primary: <?= htmlspecialchars($color_principal, ENT_QUOTES, 'UTF-8') ?>;
            --p-secondary: <?= htmlspecialchars($color_secundario, ENT_QUOTES, 'UTF-8') ?>;
            --p-footer: <?= htmlspecialchars($color_footer, ENT_QUOTES, 'UTF-8') ?>;
            --p-button: <?= htmlspecialchars($color_botones, ENT_QUOTES, 'UTF-8') ?>;
            --p-text: <?= htmlspecialchars($color_texto, ENT_QUOTES, 'UTF-8') ?>;
            --p-btn-text: <?= htmlspecialchars($btnTextContrast, ENT_QUOTES, 'UTF-8') ?>;
            --p-nav-text: <?= htmlspecialchars($navTextContrast, ENT_QUOTES, 'UTF-8') ?>;
            --p-footer-text: <?= htmlspecialchars($footerTextContrast, ENT_QUOTES, 'UTF-8') ?>;
        }

        body {
            background: linear-gradient(160deg, var(--p-secondary) 0%, #ffffff 42%, var(--p-secondary) 100%);
            color: var(--p-text);
            font-size: <?= htmlspecialchars($tamano_letra, ENT_QUOTES, 'UTF-8') ?>;
        }

        .navbar {
            background: var(--p-primary) !important;
        }

        .navbar .nav-link,
        .navbar .navbar-brand {
            color: var(--p-nav-text) !important;
        }

        .detail-wrap {
            padding: 2rem 0 2.8rem;
        }

        .detail-card {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }

        .detail-media {
            width: 100%;
            height: 340px;
            object-fit: contain;
            background: #ffffff;
            padding: 8px;
            display: block;
        }

        .btn-premium {
            background: var(--p-button);
            color: var(--p-btn-text);
            border: none;
            border-radius: 999px;
            padding: 0.68rem 1.25rem;
            font-weight: 700;
        }

        .btn-premium:hover {
            color: var(--p-btn-text);
            filter: brightness(0.92);
        }

        .cart-fab {
            position: fixed;
            right: 20px;
            bottom: 86px;
            z-index: 9999;
            min-width: 56px;
            height: 56px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 22px rgba(0,0,0,.24);
            background: var(--p-button);
            color: var(--p-btn-text);
            border: 0;
            font-weight: 700;
            padding: 0 14px;
            text-decoration: none;
        }

        .cart-fab .badge {
            margin-left: 8px;
            background: #0f172a;
            color: #ffffff;
        }

        footer {
            background: var(--p-footer);
            color: var(--p-footer-text);
            margin-top: 2.5rem;
        }

        @media (max-width: 767.98px) {
            .detail-media {
                height: 220px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../nav.php'; ?>

    <section class="detail-wrap">
        <div class="container">
            <?php if ($promo): ?>
                <article class="detail-card mx-auto" style="max-width: 860px;">
                    <?php $img = $promoImageUrl($promo); ?>
                    <?php if ($img !== ''): ?>
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="Promocion" class="detail-media" onerror="this.style.display='none';">
                    <?php endif; ?>
                    <div class="p-4 p-md-5">
                        <h1 class="h2 fw-bold mb-2"><?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="text-muted mb-3"><?= nl2br(htmlspecialchars((string)($promo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if ((float)($promo['precio_promocional'] ?? 0) > 0): ?>
                                <span class="badge text-bg-warning">Precio: S/ <?= number_format((float)$promo['precio_promocional'], 2) ?></span>
                            <?php endif; ?>
                            <?php if ((float)($promo['descuento'] ?? 0) > 0): ?>
                                <span class="badge text-bg-success">Descuento: <?= (float)$promo['descuento'] ?>%</span>
                            <?php endif; ?>
                            <?php
                                $fi = $formatPromoDate($promo['fecha_inicio'] ?? '');
                                $ff = $formatPromoDate($promo['fecha_fin'] ?? '');
                            ?>
                            <?php if ($fi !== '' || $ff !== ''): ?>
                                <span class="badge text-bg-info">Vigencia: <?= htmlspecialchars(trim($fi . ($ff !== '' ? ' al ' . $ff : '')), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>

                        <a href="index.php#promociones" class="btn btn-premium">
                            <i class="bi bi-arrow-left"></i> Volver a promociones
                        </a>
                        <button
                            type="button"
                            class="btn btn-outline-success ms-2"
                            data-add-promo="1"
                            data-promo-id="<?= (int)($promo['id'] ?? 0) ?>"
                            data-promo-titulo="<?= htmlspecialchars((string)($promo['titulo'] ?? 'Promocion especial'), ENT_QUOTES, 'UTF-8') ?>"
                            data-promo-precio="<?= isset($promo['precio_promocional']) ? (float)$promo['precio_promocional'] : 0 ?>"
                            data-promo-vigencia="<?= htmlspecialchars(trim(($fi ?? '') . (($ff ?? '') !== '' ? ' al ' . ($ff ?? '') : '')), ENT_QUOTES, 'UTF-8') ?>"
                        >Agregar al carrito</button>
                    </div>
                </article>
            <?php else: ?>
                <div class="alert alert-warning mx-auto" style="max-width: 860px;">Promocion no encontrada.</div>
                <div class="text-center">
                    <a href="index.php#promociones" class="btn btn-premium">Volver</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="py-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <span>© <?= date('Y') ?> <?= htmlspecialchars((string)$nombre_empresa, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </footer>

    <button type="button" class="cart-fab" data-bs-toggle="modal" data-bs-target="#promoCartModalDA" aria-label="Abrir carrito de promociones">
        <i class="bi bi-cart3"></i>
        <span class="badge" id="promoCartCountDA">0</span>
    </button>

    <div class="modal fade" id="promoCartModalDA" tabindex="-1" aria-labelledby="promoCartModalDALabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promoCartModalDALabel">Carrito de promociones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="promoCartEmptyDA" class="alert alert-light border">Aun no agregaste promociones.</div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="promoCartTableDA">
                            <thead>
                                <tr>
                                    <th>Promocion</th>
                                    <th>Vigencia</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <strong>Total: <span id="promoCartTotalDA">S/ 0.00</span></strong>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-6"><input type="text" class="form-control" id="cotNombreDA" placeholder="Nombre del paciente"></div>
                        <div class="col-12 col-md-6"><input type="text" class="form-control" id="cotTelefonoDA" placeholder="Telefono de contacto"></div>
                        <div class="col-12 col-md-6">
                            <select class="form-select" id="cotModalidadDA">
                                <option value="laboratorio">En laboratorio</option>
                                <option value="domicilio">A domicilio</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6"><input type="date" class="form-control" id="cotFechaDA"></div>
                        <div class="col-12" id="cotDireccionWrapDA" style="display:none;"><input type="text" class="form-control" id="cotDireccionDA" placeholder="Direccion para toma a domicilio"></div>
                        <div class="col-12"><textarea class="form-control" id="cotObsDA" rows="2" placeholder="Observaciones"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="promoCartClearDA">Vaciar carrito</button>
                    <button type="button" class="btn btn-premium" id="promoQuoteWhatsappDA">Cotizar por WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($whatsAppHref !== ''): ?>
        <a href="<?= htmlspecialchars($whatsAppHref, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="position:fixed;right:20px;bottom:20px;z-index:9999;width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#25d366;color:#fff;font-size:1.9rem;box-shadow:0 8px 22px rgba(0,0,0,.24);text-decoration:none;" aria-label="WhatsApp">
            <i class="bi bi-whatsapp"></i>
        </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const CART_KEY = 'promo_cart_v1';
            const WA_BASE = <?= json_encode($whatsAppHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const EMPRESA = <?= json_encode((string)$nombre_empresa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            const countEl = document.getElementById('promoCartCountDA');
            const table = document.getElementById('promoCartTableDA');
            const tbody = table ? table.querySelector('tbody') : null;
            const emptyEl = document.getElementById('promoCartEmptyDA');
            const totalEl = document.getElementById('promoCartTotalDA');
            const modalidadEl = document.getElementById('cotModalidadDA');
            const direccionWrapEl = document.getElementById('cotDireccionWrapDA');

            function getCart() { try { const raw = localStorage.getItem(CART_KEY); const p = raw ? JSON.parse(raw) : []; return Array.isArray(p) ? p : []; } catch (e) { return []; } }
            function setCart(items) { localStorage.setItem(CART_KEY, JSON.stringify(items)); }
            function formatMoney(value) { return 'S/ ' + (Number(value) || 0).toFixed(2); }
            function escapeHtml(str) { return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }

            function render() {
                const items = getCart();
                const qty = items.reduce((a, it) => a + (Number(it.qty) || 0), 0);
                if (countEl) countEl.textContent = String(qty);
                if (!tbody || !emptyEl || !totalEl) return;
                tbody.innerHTML = '';
                let total = 0;
                items.forEach((it) => {
                    const price = Number(it.price) || 0;
                    const rowSubtotal = price * (Number(it.qty) || 0);
                    total += rowSubtotal;
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + escapeHtml(it.title || 'Promocion') + '</td><td>' + escapeHtml(it.vigencia || '-') + '</td><td>' + (price > 0 ? formatMoney(price) : 'Por confirmar') + '</td><td>' + String(Number(it.qty) || 0) + '</td><td>' + formatMoney(rowSubtotal) + '</td><td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-id="' + String(it.id || '') + '">Quitar</button></td>';
                    tbody.appendChild(tr);
                });
                emptyEl.style.display = items.length ? 'none' : 'block';
                table.style.display = items.length ? '' : 'none';
                totalEl.textContent = formatMoney(total);
            }

            function addPromo(payload) {
                const items = getCart();
                const promoId = String(payload.id || '');
                if (!promoId) return;
                const idx = items.findIndex((it) => String(it.id) === promoId);
                if (idx >= 0) {
                    items[idx].qty = (Number(items[idx].qty) || 0) + 1;
                } else {
                    items.push({ id: promoId, title: payload.title || 'Promocion', price: Number(payload.price) || 0, vigencia: payload.vigencia || '', qty: 1 });
                }
                setCart(items);
                render();
            }

            document.querySelectorAll('[data-add-promo="1"]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    addPromo({ id: this.getAttribute('data-promo-id') || '', title: this.getAttribute('data-promo-titulo') || 'Promocion', price: this.getAttribute('data-promo-precio') || '0', vigencia: this.getAttribute('data-promo-vigencia') || '' });
                });
            });

            if (tbody) {
                tbody.addEventListener('click', function (ev) {
                    const target = ev.target;
                    if (!(target instanceof HTMLElement)) return;
                    const removeId = target.getAttribute('data-remove-id');
                    if (!removeId) return;
                    setCart(getCart().filter((it) => String(it.id) !== String(removeId)));
                    render();
                });
            }

            const clearBtn = document.getElementById('promoCartClearDA');
            if (clearBtn) clearBtn.addEventListener('click', function () { setCart([]); render(); });

            if (modalidadEl && direccionWrapEl) {
                const toggleDireccion = function () { direccionWrapEl.style.display = modalidadEl.value === 'domicilio' ? '' : 'none'; };
                modalidadEl.addEventListener('change', toggleDireccion);
                toggleDireccion();
            }

            const quoteBtn = document.getElementById('promoQuoteWhatsappDA');
            if (quoteBtn) {
                quoteBtn.addEventListener('click', function () {
                    const items = getCart();
                    if (!items.length) { alert('Agrega al menos una promocion al carrito.'); return; }
                    if (!WA_BASE) { alert('No hay WhatsApp configurado en el sistema.'); return; }

                    const nombre = (document.getElementById('cotNombreDA') || {}).value || '';
                    const telefono = (document.getElementById('cotTelefonoDA') || {}).value || '';
                    const modalidad = (document.getElementById('cotModalidadDA') || {}).value || 'laboratorio';
                    const fecha = (document.getElementById('cotFechaDA') || {}).value || '';
                    const direccion = (document.getElementById('cotDireccionDA') || {}).value || '';
                    const obs = (document.getElementById('cotObsDA') || {}).value || '';

                    let total = 0;
                    const lines = items.map((it, idx) => {
                        const price = Number(it.price) || 0;
                        const rowSubtotal = price * (Number(it.qty) || 0);
                        total += rowSubtotal;
                        let line = (idx + 1) + '. ' + (it.title || 'Promocion') + ' x' + (Number(it.qty) || 0);
                        line += price > 0 ? (' - ' + formatMoney(rowSubtotal)) : ' - Precio por confirmar';
                        if (it.vigencia) line += ' | Vigencia: ' + it.vigencia;
                        return line;
                    });

                    const parts = [
                        'Hola, deseo cotizar las siguientes promociones en ' + EMPRESA + ':',
                        '',
                        'Promociones:',
                        lines.join('\n'),
                        '',
                        'Total referencial: ' + formatMoney(total),
                        '',
                        'Datos para agendar toma de muestra:',
                        'Paciente: ' + (nombre || 'No indicado'),
                        'Telefono: ' + (telefono || 'No indicado'),
                        'Modalidad: ' + (modalidad === 'domicilio' ? 'A domicilio' : 'En laboratorio'),
                        'Fecha tentativa: ' + (fecha || 'No indicada')
                    ];
                    if (modalidad === 'domicilio') parts.push('Direccion: ' + (direccion || 'No indicada'));
                    if (obs) parts.push('Observaciones: ' + obs);
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

            render();
        })();
    </script>
    <script>
        window.APP_PWA = {
            manifestUrl: <?= json_encode($pwaManifestHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            swUrl: <?= json_encode($pwaServiceWorkerHref, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            scope: <?= json_encode($pwaScope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
    <script src="<?= htmlspecialchars($pwaRegisterScriptHref, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($pwaInstallScriptHref, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
