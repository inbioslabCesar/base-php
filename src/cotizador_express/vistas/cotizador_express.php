<?php
require_once __DIR__ . '/../funciones/cotizador_express_servicio.php';

$ctxExpress = cotExpContexto($pdo);
$conPrecios = $ctxExpress['muestra_precios'];
$empresaExpress = cotExpEmpresa($pdo);
$esAdminExpress = strtolower((string)($_SESSION['rol'] ?? '')) === 'admin';
$historialExpress = $conPrecios && cotExpHistorialDisponible($pdo);

$telefonosLab = implode(' / ', array_filter([$empresaExpress['telefono'], $empresaExpress['celular']]));

// "Reutilizar": precarga el carrito desde una cotización express anterior.
$prefillExpress = null;
if ($historialExpress && !empty($_GET['reutilizar'])) {
    $stmtRe = $pdo->prepare('SELECT * FROM cotizaciones_express WHERE id = ? LIMIT 1');
    $stmtRe->execute([(int)$_GET['reutilizar']]);
    $rowRe = $stmtRe->fetch(PDO::FETCH_ASSOC);
    if ($rowRe) {
        $stmtReIt = $pdo->prepare('SELECT examen_id AS id, cantidad AS cant FROM cotizaciones_express_items WHERE express_id = ? ORDER BY id');
        $stmtReIt->execute([(int)$rowRe['id']]);
        $prefillExpress = [
            'items' => array_map(static fn($i) => ['id' => (int)$i['id'], 'cant' => (int)$i['cant']], $stmtReIt->fetchAll(PDO::FETCH_ASSOC)),
            'perfil' => $rowRe['tarifa_tipo'] === 'particular' ? 'particular' : $rowRe['tarifa_tipo'] . ':' . (int)$rowRe['tarifa_id'],
            'desc' => (float)$rowRe['descuento_extra'],
            'vigencia' => (int)$rowRe['vigencia_dias'],
            'nombre' => (string)$rowRe['destinatario_nombre'],
            'telefono' => (string)$rowRe['destinatario_telefono'],
        ];
    }
}

$payloadExpress = [
    'precios' => $conPrecios,
    'catalogo' => cotExpCatalogo($pdo, $conPrecios),
    'perfiles' => $conPrecios ? cotExpPerfiles($pdo) : [],
    'moneda' => currency_get_config($pdo),
    'lab' => [
        'nombre' => $empresaExpress['nombre'],
        'direccion' => $empresaExpress['direccion'],
        'telefonos' => $telefonosLab,
        'logo_ticket' => cotExpLogoTicketDataUri($empresaExpress['logo']),
    ],
    'plantilla' => cotExpPlantillaActual($pdo, $conPrecios ? 'precios' : 'info'),
    'historial' => $historialExpress,
    'esAdmin' => $esAdminExpress,
    'prefill' => $prefillExpress,
];
$jsonExpress = json_encode($payloadExpress, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<style>
.cx-card { border-radius: 14px; }
.cx-result { cursor: pointer; }
.cx-result:hover, .cx-result.cx-sel { background: #e7f5f3; color: #1f2937; }
.cx-result.cx-sel { border-left: 4px solid #0f766e; }
#cxMensaje { font-size: .92rem; min-height: 320px; }
.cx-scroll { max-height: 320px; overflow-y: auto; }
</style>

<div class="container-fluid mt-4" id="cxApp">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="mb-0"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Cotizador Express</h3>
            <small class="text-muted">
                <?= $conPrecios
                    ? 'Cotiza al instante sin registrar al paciente. No genera deuda ni atención.'
                    : 'Modo informativo (SIS): muestra indicaciones de los exámenes, sin precios.' ?>
            </small>
        </div>
        <div class="d-flex gap-2">
            <?php if ($historialExpress): ?>
                <a href="dashboard.php?vista=cotizador_express_historial" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i> Historial</a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary" id="cxLimpiar"><i class="bi bi-eraser"></i> Nueva cotización</button>
        </div>
    </div>

    <?php if (!$conPrecios): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> En modo <strong><?= htmlspecialchars($ctxExpress['modo'], ENT_QUOTES, 'UTF-8') ?></strong> no se muestran ni se envían precios. Solo información preanalítica, muestra y tiempo de entrega.</div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card cx-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted">1. Buscar exámenes</h6>
                    <input type="search" id="cxBuscar" class="form-control form-control-lg mb-2" placeholder="Nombre, código o área (Enter agrega el primero)" autocomplete="off">
                    <div id="cxResultados" class="list-group cx-scroll"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card cx-card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted">2. Detalle</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead>
                                <tr>
                                    <th>Examen</th>
                                    <th style="width:90px">Cant.</th>
                                    <?php if ($conPrecios): ?><th class="text-end" style="width:100px">P. unit.</th><th class="text-end" style="width:110px">Subtotal</th><?php endif; ?>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="cxCarrito"></tbody>
                            <?php if ($conPrecios): ?>
                            <tfoot id="cxTotales"></tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div id="cxVacio" class="text-muted text-center py-3">Aún no agregaste exámenes.</div>

                    <div class="row g-2 mt-1">
                        <?php if ($conPrecios): ?>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Tarifa</label>
                            <select id="cxPerfil" class="form-select">
                                <option value="particular">Particular (precio público)</option>
                                <optgroup label="Convenios" id="cxOptConvenios"></optgroup>
                                <optgroup label="Empresas" id="cxOptEmpresas"></optgroup>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Descuento extra %</label>
                            <input type="number" id="cxDesc" class="form-control" min="0" max="100" step="0.5" value="0">
                        </div>
                        <?php endif; ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Vigencia (días)</label>
                            <input type="number" id="cxVigencia" class="form-control" min="1" max="90" value="7">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card cx-card shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted">3. Destinatario y envío</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6 position-relative">
                            <label class="form-label small mb-1">Nombre (opcional) / buscar paciente registrado</label>
                            <input type="text" id="cxNombre" class="form-control" placeholder="Ej. María Pérez" autocomplete="off" maxlength="120">
                            <div id="cxSugerencias" class="list-group position-absolute w-100 shadow" style="z-index:20"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Celular WhatsApp</label>
                            <input type="tel" id="cxTelefono" class="form-control" placeholder="945 123 456" maxlength="20">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="cxIncEntrega" checked><label class="form-check-label" for="cxIncEntrega">Tiempo de entrega</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="cxIncIndic" checked><label class="form-check-label" for="cxIncIndic">Indicaciones (ayuno, etc.)</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="cxIncContacto" checked><label class="form-check-label" for="cxIncContacto">Dirección y teléfonos</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="cxSinEmoji"><label class="form-check-label" for="cxSinEmoji">Quitar emojis (si en WhatsApp salen como �)</label></div>
                    </div>
                    <textarea id="cxMensaje" class="form-control" placeholder="El mensaje aparecerá aquí..."></textarea>
                    <div class="small text-muted mt-1 d-none" id="cxEditado">Editaste el mensaje manualmente. <a href="#" id="cxRegenerar">Regenerar</a></div>
                    <?php if ($historialExpress): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="cxGuardar" checked>
                        <label class="form-check-label" for="cxGuardar">Guardar en historial y agregar folio + enlace al PDF <span class="text-muted">(no genera deuda)</span></label>
                    </div>
                    <?php endif; ?>
                    <?php if ($esAdminExpress): ?>
                    <div class="mt-2"><a href="#" id="cxPlantillaAbrir" class="small"><i class="bi bi-pencil-square"></i> Editar plantilla del mensaje</a></div>
                    <div class="border rounded p-2 mt-2 d-none" id="cxPlantillaPanel">
                        <div class="small text-muted mb-1">Variables: <code>{nombre_saludo}</code> <code>{laboratorio}</code> <code>{folio}</code> <code>{detalle}</code> <?= $conPrecios ? '<code>{total}</code> <code>{entrega}</code> <code>{indicaciones}</code> <code>{vigencia}</code> <code>{enlace}</code> ' : '' ?><code>{contacto}</code>. Las líneas con variables vacías se omiten.</div>
                        <textarea id="cxPlantillaTxt" class="form-control font-monospace" rows="12"></textarea>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary" id="cxPlantillaGuardar">Guardar plantilla</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cxPlantillaRestaurar">Restaurar la original</button>
                            <button type="button" class="btn btn-sm btn-link" id="cxPlantillaCerrar">Cerrar</button>
                        </div>
                        <div class="small mt-1" id="cxPlantillaAviso"></div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-primary" id="cxCopiar"><i class="bi bi-clipboard"></i> Copiar mensaje</button>
                        <button type="button" class="btn btn-success" id="cxWhatsapp"><i class="bi bi-whatsapp"></i> <span id="cxWhatsappTxt">Enviar por WhatsApp</span></button>
                        <select id="cxWaModo" class="form-select w-auto" aria-label="Modo de envío por WhatsApp" title="Elige cómo se envía el mensaje a WhatsApp">
                            <option value="web">WhatsApp Web (mensaje ya escrito)</option>
                            <option value="enlace">App de WhatsApp (mensaje ya escrito)</option>
                            <option value="pegar">Copiar y pegar con Ctrl+V</option>
                        </select>
                        <button type="button" class="btn btn-outline-dark" id="cxTicket"><i class="bi bi-printer"></i> Ticket</button>
                        <button type="button" class="btn btn-outline-danger" id="cxPdf"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                    </div>
                    <div class="small mt-2" id="cxAviso" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="cxPdfForm" method="post" action="dashboard.php?action=cotizador_express_pdf" target="_blank" class="d-none">
    <input type="hidden" name="items">
    <input type="hidden" name="perfil">
    <input type="hidden" name="desc_extra">
    <input type="hidden" name="vigencia">
    <input type="hidden" name="destinatario">
    <input type="hidden" name="indicaciones">
    <input type="hidden" name="folio">
</form>

<script>
(function () {
    const D = <?= $jsonExpress ?>;
    const $ = (id) => document.getElementById(id);
    const norm = (s) => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const porId = new Map(D.catalogo.map((e) => [e.id, e]));
    const carrito = new Map();
    let mensajeEditado = false;
    let telefonoManual = false;
    let timerBusqueda = null;

    function fmt(n) {
        const m = D.moneda;
        const partes = Number(n).toFixed(m.decimals).split('.');
        partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, m.thousands_separator);
        const num = partes.join(m.decimal_separator);
        return m.position === 'suffix' ? num + ' ' + m.symbol : m.symbol + ' ' + num;
    }

    // Misma regla que el sistema: precio convenio si aplica; si no, precio público con descuento del perfil.
    function perfilActual() {
        if (!D.precios) return null;
        const v = $('cxPerfil').value;
        if (v === 'particular') return null;
        const [tipo, id] = v.split(':');
        return D.perfiles.find((p) => p.tipo === tipo && String(p.id) === id) || null;
    }
    function precioUnit(ex) {
        const p = perfilActual();
        if (!p) return ex.precio_publico;
        if (p.usar && ex.precio_convenio > 0) return ex.precio_convenio;
        if (p.descuento > 0) return Math.round(ex.precio_publico * (1 - p.descuento / 100) * 100) / 100;
        return ex.precio_publico;
    }
    function descExtra() {
        return D.precios ? Math.min(100, Math.max(0, parseFloat($('cxDesc').value) || 0)) : 0;
    }
    function vigencia() {
        return Math.min(90, Math.max(1, parseInt($('cxVigencia').value, 10) || 7));
    }
    function totales() {
        let sub = 0;
        carrito.forEach((cant, id) => { sub += Math.round(precioUnit(porId.get(id)) * cant * 100) / 100; });
        const d = descExtra();
        const monto = Math.round(sub * d) / 100;
        return { sub, d, monto, total: Math.round((sub - monto) * 100) / 100 };
    }
    function venceEl() {
        const f = new Date();
        f.setDate(f.getDate() + vigencia());
        return f.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    function telefonoWa(raw) {
        const d = String(raw || '').replace(/\D+/g, '');
        if (d.length === 9 && d[0] === '9') return '51' + d;
        return d.length >= 10 ? d : '';
    }

    function aviso(txt, tipo) {
        const el = $('cxAviso');
        el.className = 'small mt-2 text-' + (tipo || 'success');
        el.textContent = txt;
    }

    /* ---------- Catálogo ---------- */
    function renderResultados() {
        const q = norm($('cxBuscar').value.trim());
        const cont = $('cxResultados');
        cont.textContent = '';
        if (q.length < 1) {
            const p = document.createElement('div');
            p.className = 'text-muted small p-2';
            p.textContent = 'Escribe para buscar exámenes.';
            cont.appendChild(p);
            return;
        }
        const hallados = D.catalogo.filter((e) => norm(e.nombre + ' ' + e.codigo + ' ' + e.area).includes(q)).slice(0, 40);
        if (!hallados.length) {
            const p = document.createElement('div');
            p.className = 'text-muted small p-2';
            p.textContent = 'Sin resultados.';
            cont.appendChild(p);
            return;
        }
        hallados.forEach((e, i) => {
            const row = document.createElement('div');
            row.className = 'list-group-item cx-result d-flex justify-content-between align-items-center' + (i === 0 ? ' cx-sel' : '');
            row.dataset.id = e.id;
            const izq = document.createElement('div');
            const t = document.createElement('div');
            t.className = 'fw-semibold';
            t.textContent = e.nombre;
            const s = document.createElement('small');
            s.className = 'text-muted';
            s.textContent = [e.codigo, e.area].filter(Boolean).join(' · ');
            izq.append(t, s);
            row.appendChild(izq);
            if (D.precios) {
                const b = document.createElement('span');
                b.className = 'badge bg-success';
                b.textContent = fmt(precioUnit(e));
                row.appendChild(b);
            }
            row.addEventListener('click', () => agregar(e.id));
            cont.appendChild(row);
        });
    }

    function agregar(id) {
        carrito.set(id, (carrito.get(id) || 0) + 1);
        refrescar();
    }

    /* ---------- Carrito ---------- */
    function renderCarrito() {
        const tb = $('cxCarrito');
        tb.textContent = '';
        carrito.forEach((cant, id) => {
            const e = porId.get(id);
            const tr = document.createElement('tr');
            const tdN = document.createElement('td');
            tdN.textContent = e.nombre;
            const tdC = document.createElement('td');
            const inp = document.createElement('input');
            inp.type = 'number';
            inp.min = 1;
            inp.max = 99;
            inp.value = cant;
            inp.className = 'form-control form-control-sm';
            inp.addEventListener('change', () => {
                carrito.set(id, Math.min(99, Math.max(1, parseInt(inp.value, 10) || 1)));
                refrescar();
            });
            tdC.appendChild(inp);
            tr.append(tdN, tdC);
            if (D.precios) {
                const u = precioUnit(e);
                const tdU = document.createElement('td');
                tdU.className = 'text-end';
                tdU.textContent = fmt(u);
                const tdS = document.createElement('td');
                tdS.className = 'text-end';
                tdS.textContent = fmt(Math.round(u * cant * 100) / 100);
                tr.append(tdU, tdS);
            }
            const tdX = document.createElement('td');
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'btn btn-sm btn-outline-danger';
            x.setAttribute('aria-label', 'Quitar ' + e.nombre);
            x.innerHTML = '<i class="bi bi-x-lg"></i>';
            x.addEventListener('click', () => { carrito.delete(id); refrescar(); });
            tdX.appendChild(x);
            tr.appendChild(tdX);
            tb.appendChild(tr);
        });
        $('cxVacio').classList.toggle('d-none', carrito.size > 0);

        if (D.precios) {
            const tf = $('cxTotales');
            tf.textContent = '';
            if (carrito.size) {
                const t = totales();
                const fila = (txt, val, fuerte) => {
                    const tr = document.createElement('tr');
                    const a = document.createElement('td');
                    a.colSpan = 3;
                    a.className = 'text-end' + (fuerte ? ' fw-bold fs-5' : '');
                    a.textContent = txt;
                    const b = document.createElement('td');
                    b.className = 'text-end' + (fuerte ? ' fw-bold fs-5' : '');
                    b.textContent = val;
                    tr.append(a, b, document.createElement('td'));
                    tf.appendChild(tr);
                };
                if (t.d > 0) {
                    fila('Subtotal', fmt(t.sub));
                    fila('Descuento ' + t.d + '%', '- ' + fmt(t.monto));
                }
                fila('TOTAL', fmt(t.total), true);
            }
        }
    }

    /* ---------- Mensaje (plantilla editable) ---------- */
    let plantilla = D.plantilla.plantilla;
    let guardado = null; // { id, folio, url, hash } de la última cotización guardada
    let clienteId = 0;
    let clienteNombre = '';

    function bloques() {
        const nombre = $('cxNombre').value.trim();
        const incEntrega = $('cxIncEntrega').checked;
        const incIndic = $('cxIncIndic').checked;
        const detalle = [];
        const entregas = [];
        const indicaciones = [];
        let i = 0;
        carrito.forEach((cant, id) => {
            const e = porId.get(id);
            i++;
            if (D.precios) {
                const u = precioUnit(e);
                const sub = Math.round(u * cant * 100) / 100;
                detalle.push(i + '. ' + e.nombre + (cant > 1 ? ' (' + cant + ' x ' + fmt(u) + ')' : '') + ' — ' + fmt(sub));
            } else {
                detalle.push(i + '. *' + e.nombre + '*');
                if (e.tipo_muestra) detalle.push('   🧫 Muestra: ' + e.tipo_muestra);
                if (incEntrega && e.tiempo_respuesta) detalle.push('   ⏱ Entrega: ' + e.tiempo_respuesta);
                if (incIndic && e.preanalitica_cliente) detalle.push('   📌 Indicaciones: ' + e.preanalitica_cliente);
            }
            if (e.tiempo_respuesta) entregas.push('• ' + e.nombre + ': ' + e.tiempo_respuesta);
            if (e.preanalitica_cliente) indicaciones.push('• ' + e.nombre + ': ' + e.preanalitica_cliente);
        });

        const total = [];
        if (D.precios) {
            const t = totales();
            if (t.d > 0) {
                total.push('Subtotal: ' + fmt(t.sub));
                total.push('Descuento (' + t.d + '%): - ' + fmt(t.monto));
            }
            total.push('💰 *TOTAL: ' + fmt(t.total) + '*');
        }

        const contacto = [];
        if ($('cxIncContacto').checked) {
            if (D.lab.direccion) contacto.push('📍 ' + D.lab.direccion);
            if (D.lab.telefonos) contacto.push('📞 ' + D.lab.telefonos);
        }

        return {
            nombre_saludo: nombre ? ' *' + nombre + '*' : '',
            laboratorio: D.lab.nombre,
            folio: guardado ? ' N° ' + guardado.folio : '',
            detalle: detalle.join('\n'),
            total: total.join('\n'),
            entrega: incEntrega && entregas.length ? '⏱ *Tiempo de entrega*\n' + entregas.join('\n') : '',
            indicaciones: incIndic && indicaciones.length ? '📌 *Indicaciones para su toma de muestra*\n' + indicaciones.join('\n') : '',
            vigencia: venceEl(),
            enlace: guardado ? '📄 Vea o descargue su cotización aquí:\n' + guardado.url : '',
            contacto: contacto.join('\n'),
        };
    }

    function construirMensaje() {
        if (!carrito.size) return '';
        const b = bloques();
        // Las líneas que solo contienen variables vacías se omiten.
        const lineas = plantilla.replace(/\r\n/g, '\n').split('\n').filter((ln) => {
            const vars = ln.match(/\{(\w+)\}/g);
            if (!vars) return true;
            const sinVars = ln.replace(/\{\w+\}/g, '').trim();
            return !(sinVars === '' && vars.every((v) => !b[v.slice(1, -1)]));
        });
        let texto = lineas.join('\n')
            .replace(/\{(\w+)\}/g, (m, k) => (k in b ? b[k] : m))
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
        if ($('cxSinEmoji').checked) {
            texto = texto
                .replace(/[\u{1F000}-\u{1FAFF}\u{2190}-\u{21FF}\u{2300}-\u{23FF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\uFE0F\u200D]/gu, '')
                .replace(/^ (?=\S)/gm, '')
                .replace(/ +([,.:;!])/g, '$1')
                .replace(/[ \t]+\n/g, '\n')
                .trim();
        }
        return texto;
    }

    function actualizarMensaje() {
        if (mensajeEditado) return;
        $('cxMensaje').value = construirMensaje();
    }

    function refrescar() {
        renderCarrito();
        renderResultados();
        actualizarMensaje();
    }

    /* ---------- Guardado en historial ---------- */
    function estadoCarrito() {
        const items = [];
        carrito.forEach((cant, id) => items.push({ id, cant }));
        return {
            items: JSON.stringify(items),
            perfil: D.precios ? $('cxPerfil').value : 'particular',
            desc_extra: descExtra(),
            vigencia: vigencia(),
            destinatario: $('cxNombre').value.trim(),
            telefono: $('cxTelefono').value.replace(/\D+/g, ''),
            cliente_id: clienteId,
            indicaciones: $('cxIncIndic').checked ? '1' : '0',
        };
    }

    async function postApi(params) {
        const r = await fetch('dashboard.php?action=cotizador_express_api', { method: 'POST', body: new URLSearchParams(params), credentials: 'same-origin' });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(data.error || 'Error');
        return data;
    }

    // Guarda (o reutiliza lo ya guardado si nada cambió) y registra el canal usado.
    async function asegurarGuardado(canal) {
        if (!D.historial || !$('cxGuardar') || !$('cxGuardar').checked) return;
        const est = estadoCarrito();
        const hash = JSON.stringify(est);
        try {
            if (guardado && guardado.hash === hash) {
                await postApi({ op: 'canal', id: guardado.id, canal });
                return;
            }
            const res = await postApi({ op: 'guardar', canal, ...est });
            guardado = { id: res.id, folio: res.folio, url: res.url, hash };
            if (!mensajeEditado) {
                actualizarMensaje();
            } else if (!$('cxMensaje').value.includes(res.url)) {
                $('cxMensaje').value += '\n\n📄 Cotización N° ' + res.folio + ': ' + res.url;
            }
            aviso('Guardada en historial como ' + res.folio + '.');
        } catch (err) {
            aviso('No se pudo guardar en el historial (' + err.message + '). Puedes continuar sin enlace.', 'warning');
        }
    }

    /* ---------- Acciones ---------- */
    function requerirItems() {
        if (!carrito.size) {
            aviso('Agrega al menos un examen.', 'danger');
            return false;
        }
        return true;
    }

    async function copiarTexto(txt) {
        const ta = $('cxMensaje');
        try {
            // Si el portapapeles no responde en 2 s, se usa el método clásico.
            await Promise.race([
                navigator.clipboard.writeText(txt),
                new Promise((_, rej) => setTimeout(() => rej(new Error('timeout')), 2000)),
            ]);
        } catch (err) {
            const previo = ta.value;
            ta.value = txt;
            ta.focus();
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            ta.value = previo;
        }
    }

    async function copiar() {
        if (!requerirItems()) return;
        await asegurarGuardado('copiar');
        await copiarTexto($('cxMensaje').value);
        aviso('Mensaje copiado. Pégalo en WhatsApp o donde necesites.');
    }

    function avisoConEnlace(txt, url) {
        const el = $('cxAviso');
        el.className = 'small mt-2 text-success';
        el.textContent = txt + ' ';
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener';
        a.textContent = 'Si WhatsApp no se abrió, haz clic aquí.';
        el.appendChild(a);
    }

    // Modos: "web" abre WhatsApp Web con el texto ya escrito; "enlace" usa wa.me (app o web);
    // "pegar" copia el mensaje y abre el chat vacío para pegarlo con Ctrl+V.
    // No se abre ninguna pestaña antes de guardar/copiar para no perder el foco ni dejar pestañas en blanco.
    async function enviarWhatsapp() {
        if (!requerirItems()) return;
        const btn = $('cxWhatsapp');
        btn.disabled = true;
        try {
            await asegurarGuardado('whatsapp');
            const tel = telefonoWa($('cxTelefono').value);
            const modo = $('cxWaModo').value;
            const msg = $('cxMensaje').value;
            if (modo === 'pegar') await copiarTexto(msg);
            const texto = encodeURIComponent(msg);
            let url;
            if (modo === 'web') {
                url = 'https://web.whatsapp.com/send?' + (tel ? 'phone=' + tel + '&' : '') + 'text=' + texto;
            } else {
                url = 'https://wa.me/' + tel + (modo === 'pegar' ? '' : '?text=' + texto);
            }
            const w = window.open(url, '_blank');
            if (w) w.opener = null;
            const aviso1 = modo === 'pegar'
                ? 'Mensaje copiado. En el chat de WhatsApp pégalo con Ctrl+V y envía.' + (tel ? '' : ' Elige primero el contacto.')
                : 'Abriendo WhatsApp con el mensaje escrito; solo presiona enviar.' + (tel ? '' : ' Elige el contacto.');
            avisoConEnlace(aviso1, url);
        } catch (err) {
            aviso('No se pudo abrir WhatsApp: ' + err.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    }

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function imprimirTicket() {
        if (!requerirItems()) return;
        await asegurarGuardado('ticket');
        let filas = '';
        carrito.forEach((cant, id) => {
            const e = porId.get(id);
            if (D.precios) {
                const u = precioUnit(e);
                filas += '<div class="it"><div>' + esc(e.nombre) + '</div><div class="r">' + cant + ' x ' + esc(fmt(u)) + ' = ' + esc(fmt(Math.round(u * cant * 100) / 100)) + '</div></div>';
            } else {
                filas += '<div class="it"><div><b>' + esc(e.nombre) + '</b></div>'
                    + (e.tipo_muestra ? '<div class="s">Muestra: ' + esc(e.tipo_muestra) + '</div>' : '')
                    + ($('cxIncEntrega').checked && e.tiempo_respuesta ? '<div class="s">Entrega: ' + esc(e.tiempo_respuesta) + '</div>' : '')
                    + ($('cxIncIndic').checked && e.preanalitica_cliente ? '<div class="s">Indic.: ' + esc(e.preanalitica_cliente) + '</div>' : '')
                    + '</div>';
            }
        });
        let pie = '';
        if (D.precios) {
            const t = totales();
            if (t.d > 0) pie += '<div class="r">Subtotal: ' + esc(fmt(t.sub)) + '</div><div class="r">Desc. ' + t.d + '%: -' + esc(fmt(t.monto)) + '</div>';
            pie += '<div class="r tot">TOTAL: ' + esc(fmt(t.total)) + '</div><div class="s c">Cotización referencial. Vigente hasta ' + esc(venceEl()) + '</div>';
        }
        const nombre = $('cxNombre').value.trim();
        const html = '<html><head><meta charset="utf-8"><title>Ticket</title><style>'
            + '@page{size:80mm auto;margin:3mm}body{font-family:monospace;font-size:12px;width:72mm;margin:0}'
            + '.c{text-align:center}.r{text-align:right}.s{font-size:10px;color:#444}.it{border-bottom:1px dashed #999;padding:3px 0}.tot{font-size:15px;font-weight:bold;margin-top:4px}'
            + '</style></head><body>'
            + (D.lab.logo_ticket ? '<div class="c"><img src="' + D.lab.logo_ticket + '" style="width:36mm;max-width:100%;height:auto"></div>' : '')
            + '<div class="c"><b>' + esc(D.lab.nombre) + '</b><br>' + esc(D.lab.direccion) + '<br>' + esc(D.lab.telefonos) + '</div>'
            + '<div class="c"><b>' + (D.precios ? 'COTIZACIÓN' : 'INFORMACIÓN DE EXÁMENES') + '</b>' + (guardado ? '<br>N° ' + esc(guardado.folio) : '') + '</div>'
            + '<div class="s">' + esc(new Date().toLocaleString('es-PE')) + (nombre ? '<br>Para: ' + esc(nombre) : '') + '</div><hr>'
            + filas + pie
            + '</body></html>';
        // Se imprime desde un iframe oculto: no depende de ventanas emergentes.
        const fr = document.createElement('iframe');
        fr.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
        document.body.appendChild(fr);
        const doc = fr.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        // Se espera a que cargue el logo antes de imprimir.
        let impreso = false;
        const imprimir = () => {
            if (impreso) return;
            impreso = true;
            fr.contentWindow.focus();
            fr.contentWindow.print();
            setTimeout(() => fr.remove(), 2000);
        };
        const logo = doc.querySelector('img');
        if (logo && !logo.complete) {
            logo.onload = imprimir;
            logo.onerror = imprimir;
            setTimeout(imprimir, 1500);
        } else {
            setTimeout(imprimir, 200);
        }
    }

    async function descargarPdf() {
        if (!requerirItems()) return;
        await asegurarGuardado('pdf');
        const f = $('cxPdfForm');
        const items = [];
        carrito.forEach((cant, id) => items.push({ id, cant }));
        const setF = (name, val) => { f.querySelector('[name="' + name + '"]').value = val; };
        f.target = '_blank';
        setF('items', JSON.stringify(items));
        setF('perfil', D.precios ? $('cxPerfil').value : 'particular');
        setF('desc_extra', descExtra());
        setF('vigencia', vigencia());
        setF('destinatario', $('cxNombre').value.trim());
        setF('indicaciones', $('cxIncIndic').checked ? '1' : '0');
        setF('folio', guardado ? guardado.folio : '');
        f.submit();
    }

    function limpiar() {
        carrito.clear();
        $('cxBuscar').value = '';
        $('cxNombre').value = '';
        $('cxTelefono').value = '';
        $('cxAviso').textContent = '';
        if (D.precios) {
            $('cxPerfil').value = 'particular';
            $('cxDesc').value = 0;
        }
        mensajeEditado = false;
        telefonoManual = false;
        guardado = null;
        clienteId = 0;
        $('cxEditado').classList.add('d-none');
        refrescar();
        $('cxBuscar').focus();
    }

    /* ---------- Paciente registrado (solo lectura) ---------- */
    function buscarPaciente() {
        const q = $('cxNombre').value.trim();
        const cont = $('cxSugerencias');
        cont.textContent = '';
        if (q.length < 2) return;
        fetch('dashboard.php?action=cotizador_express_api&op=buscar_paciente&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : { items: [] }))
            .then((data) => {
                if ($('cxNombre').value.trim() !== q) return;
                cont.textContent = '';
                (data.items || []).forEach((p) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'list-group-item list-group-item-action small';
                    b.textContent = p.nombre + (p.dni ? ' · ' + p.dni : '') + (p.telefono ? ' · 📱 ' + p.telefono.slice(2) : ' · sin celular');
                    b.addEventListener('click', () => {
                        $('cxNombre').value = p.nombre;
                        clienteId = p.id;
                        clienteNombre = p.nombre;
                        if (p.telefono) {
                            $('cxTelefono').value = p.telefono.slice(2);
                            telefonoManual = false;
                        }
                        cont.textContent = '';
                        actualizarMensaje();
                    });
                    cont.appendChild(b);
                });
            })
            .catch(() => { cont.textContent = ''; });
    }

    /* ---------- Inicio ---------- */
    if (D.precios) {
        const optC = $('cxOptConvenios');
        const optE = $('cxOptEmpresas');
        D.perfiles.forEach((p) => {
            const o = document.createElement('option');
            o.value = p.tipo + ':' + p.id;
            o.textContent = p.nombre;
            (p.tipo === 'convenio' ? optC : optE).appendChild(o);
        });
        if (!optC.children.length) optC.remove();
        if (!optE.children.length) optE.remove();

        $('cxPerfil').addEventListener('change', () => {
            const p = perfilActual();
            if (p) {
                if (!$('cxNombre').value.trim()) $('cxNombre').value = p.nombre;
                if (p.telefono && !telefonoManual) $('cxTelefono').value = p.telefono.slice(2);
            }
            refrescar();
        });
        $('cxDesc').addEventListener('input', refrescar);
    }

    $('cxBuscar').addEventListener('input', renderResultados);
    $('cxBuscar').addEventListener('keydown', (ev) => {
        if (ev.key !== 'Enter') return;
        ev.preventDefault();
        const primero = $('cxResultados').querySelector('.cx-result');
        if (primero) {
            agregar(parseInt(primero.dataset.id, 10));
            $('cxBuscar').select();
        }
    });
    $('cxVigencia').addEventListener('input', actualizarMensaje);
    ['cxIncEntrega', 'cxIncIndic', 'cxIncContacto', 'cxSinEmoji'].forEach((id) => $(id).addEventListener('change', actualizarMensaje));
    $('cxNombre').addEventListener('input', () => {
        if ($('cxNombre').value.trim() !== clienteNombre) clienteId = 0;
        actualizarMensaje();
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(buscarPaciente, 300);
    });
    $('cxNombre').addEventListener('blur', () => setTimeout(() => { $('cxSugerencias').textContent = ''; }, 200));
    $('cxTelefono').addEventListener('input', () => { telefonoManual = true; });
    $('cxMensaje').addEventListener('input', () => {
        mensajeEditado = true;
        $('cxEditado').classList.remove('d-none');
    });
    $('cxRegenerar').addEventListener('click', (ev) => {
        ev.preventDefault();
        mensajeEditado = false;
        $('cxEditado').classList.add('d-none');
        actualizarMensaje();
    });
    $('cxCopiar').addEventListener('click', copiar);
    $('cxWhatsapp').addEventListener('click', enviarWhatsapp);
    try {
        const modoGuardado = localStorage.getItem('cxWaModo2');
        if (['web', 'enlace', 'pegar'].includes(modoGuardado)) $('cxWaModo').value = modoGuardado;
        $('cxSinEmoji').checked = localStorage.getItem('cxSinEmoji') === '1';
    } catch (e) {}
    $('cxWaModo').addEventListener('change', () => {
        try { localStorage.setItem('cxWaModo2', $('cxWaModo').value); } catch (e) {}
    });
    $('cxSinEmoji').addEventListener('change', () => {
        try { localStorage.setItem('cxSinEmoji', $('cxSinEmoji').checked ? '1' : '0'); } catch (e) {}
    });
    $('cxTicket').addEventListener('click', imprimirTicket);
    $('cxPdf').addEventListener('click', descargarPdf);
    $('cxLimpiar').addEventListener('click', limpiar);

    /* ---------- Plantilla editable (solo admin) ---------- */
    if (D.esAdmin && $('cxPlantillaAbrir')) {
        const avisoPl = (txt, tipo) => {
            $('cxPlantillaAviso').className = 'small mt-1 text-' + (tipo || 'success');
            $('cxPlantillaAviso').textContent = txt;
        };
        $('cxPlantillaAbrir').addEventListener('click', (ev) => {
            ev.preventDefault();
            if (!D.plantilla.editable) {
                aviso('Para editar la plantilla falta ejecutar la migración de BD (sql/2026_10_14_cotizador_express).', 'warning');
                return;
            }
            $('cxPlantillaTxt').value = plantilla;
            $('cxPlantillaPanel').classList.remove('d-none');
        });
        $('cxPlantillaCerrar').addEventListener('click', () => $('cxPlantillaPanel').classList.add('d-none'));
        $('cxPlantillaGuardar').addEventListener('click', async () => {
            try {
                await postApi({ op: 'plantilla_guardar', plantilla: $('cxPlantillaTxt').value });
                plantilla = $('cxPlantillaTxt').value;
                mensajeEditado = false;
                $('cxEditado').classList.add('d-none');
                actualizarMensaje();
                avisoPl('Plantilla guardada.');
            } catch (err) {
                avisoPl(err.message, 'danger');
            }
        });
        $('cxPlantillaRestaurar').addEventListener('click', async () => {
            if (!window.confirm('¿Volver a la plantilla original?')) return;
            try {
                const res = await postApi({ op: 'plantilla_restaurar' });
                plantilla = res.plantilla;
                $('cxPlantillaTxt').value = plantilla;
                mensajeEditado = false;
                $('cxEditado').classList.add('d-none');
                actualizarMensaje();
                avisoPl('Plantilla original restaurada.');
            } catch (err) {
                avisoPl(err.message, 'danger');
            }
        });
    }

    /* ---------- Reutilizar una cotización anterior ---------- */
    if (D.prefill) {
        D.prefill.items.forEach((it) => { if (porId.has(it.id)) carrito.set(it.id, Math.min(99, Math.max(1, it.cant))); });
        if (D.precios) {
            if ([...$('cxPerfil').options].some((o) => o.value === D.prefill.perfil)) $('cxPerfil').value = D.prefill.perfil;
            $('cxDesc').value = D.prefill.desc;
        }
        $('cxVigencia').value = D.prefill.vigencia;
        $('cxNombre').value = D.prefill.nombre;
        if (D.prefill.telefono) $('cxTelefono').value = D.prefill.telefono.replace(/^51(?=9\d{8}$)/, '');
    }

    refrescar();
    $('cxBuscar').focus();
})();
</script>
