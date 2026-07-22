function createTypeSelect() {
  const select = document.createElement('select');
  select.className = 'form-select form-select-sm type-select';
  ['Parámetro', 'Título', 'Subtítulo', 'Texto Largo'].forEach(type => {
    const opt = document.createElement('option');
    opt.value = type;
    opt.innerText = type;
    select.appendChild(opt);
  });
  return select;
}

const REF_ALERT_DEFAULT_MODE = 'both';
const REF_ALERT_DEFAULT_COLOR = '#c62828';

function normalizeRefAlertMode(mode) {
  const raw = String(mode || '').trim().toLowerCase();
  if (['none', 'asterisk', 'color', 'both'].includes(raw)) {
    return raw;
  }
  return REF_ALERT_DEFAULT_MODE;
}

function normalizeRefAlertColor(color) {
  const raw = String(color || '').trim();
  return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(raw) ? raw : REF_ALERT_DEFAULT_COLOR;
}

function normalizeRefAlertTexts(value) {
  if (Array.isArray(value)) {
    return value.map(v => String(v || '').trim()).filter(Boolean).join(', ');
  }
  return String(value || '').trim();
}

function normalizeRefAlertTextColors(value) {
  return String(value || '').trim();
}

function splitRefTextList(value) {
  return String(value || '')
    .split(/[|,;\/]+/)
    .map(v => String(v || '').trim())
    .filter(Boolean);
}

function parseRefAlertTextColorMap(value) {
  const out = {};
  const src = String(value || '').trim();
  if (!src) return out;
  src.split(/[;,]+/).forEach((part) => {
    const token = String(part || '').trim();
    if (!token) return;
    const idxEq = token.indexOf('=');
    const idxColon = token.indexOf(':');
    const sep = idxEq >= 0 ? idxEq : idxColon;
    if (sep <= 0) return;
    const key = String(token.slice(0, sep)).trim();
    const color = normalizeRefAlertColor(token.slice(sep + 1));
    if (key) out[key] = color;
  });
  return out;
}

function serializeRefAlertTextColorMap(mapObj) {
  if (!mapObj || typeof mapObj !== 'object') return '';
  return Object.keys(mapObj)
    .map((k) => String(k || '').trim())
    .filter(Boolean)
    .map((k) => `${k}=${normalizeRefAlertColor(mapObj[k])}`)
    .join(', ');
}

function splitLegacyRowOptions(tr) {
  if (!tr) return [];
  const raw = tr.querySelector('.opciones-input')?.value || '';
  return raw.split(',').map(v => String(v || '').trim()).filter(Boolean);
}

function syncLegacyAlertVisualEditor(group) {
  if (!group) return;
  const tr = group.closest('tr');
  if (!tr) return;

  const alertModeEl = group.querySelector('.alerta-modo-ref');
  const defaultColor = normalizeRefAlertColor(group.querySelector('.alerta-color-ref')?.value || REF_ALERT_DEFAULT_COLOR);
  const hiddenTexts = group.querySelector('.alerta-textos-ref');
  const hiddenMap = group.querySelector('.alerta-colores-texto-ref');
  const listWrap = group.querySelector('.alerta-text-color-list');
  if (!hiddenTexts || !hiddenMap || !listWrap) return;

  const selectedTokens = new Set(splitRefTextList(hiddenTexts.value));
  const map = parseRefAlertTextColorMap(hiddenMap.value);
  const fromOptions = splitLegacyRowOptions(tr);
  const merged = Array.from(new Set([...fromOptions, ...Object.keys(map), ...Array.from(selectedTokens)]));

  listWrap.innerHTML = '';
  if (merged.length === 0) {
    listWrap.innerHTML = '<div class="small text-muted">Primero define opciones en la columna Resultado.</div>';
    return;
  }

  const mode = normalizeRefAlertMode(alertModeEl?.value || REF_ALERT_DEFAULT_MODE);
  const showColorControls = mode === 'color' || mode === 'both';

  merged.forEach((txt) => {
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 mb-1';
    const checked = selectedTokens.has(txt) || Object.prototype.hasOwnProperty.call(map, txt);
    const color = normalizeRefAlertColor(map[txt] || defaultColor);
    row.innerHTML = `
      <label class="form-check mb-0" style="min-width:18px;">
        <input type="checkbox" class="form-check-input alerta-text-toggle" ${checked ? 'checked' : ''}>
      </label>
      <input type="text" class="form-control form-control-sm alerta-text-label" value="${txt.replace(/"/g, '&quot;')}" readonly style="max-width:190px;">
      <input type="color" class="form-control form-control-sm alerta-text-color" value="${color}" ${showColorControls ? '' : 'disabled'}>
    `;
    const toggle = row.querySelector('.alerta-text-toggle');
    const colorInput = row.querySelector('.alerta-text-color');
    const rebuildHidden = () => {
      const rows = Array.from(listWrap.querySelectorAll('.d-flex'));
      const nextTexts = [];
      const nextMap = {};
      rows.forEach((r) => {
        const t = String(r.querySelector('.alerta-text-label')?.value || '').trim();
        const on = !!r.querySelector('.alerta-text-toggle')?.checked;
        const c = normalizeRefAlertColor(r.querySelector('.alerta-text-color')?.value || defaultColor);
        if (!t || !on) return;
        nextTexts.push(t);
        nextMap[t] = c;
      });
      hiddenTexts.value = nextTexts.join(', ');
      hiddenMap.value = serializeRefAlertTextColorMap(nextMap);
      updatePreview();
    };
    toggle.addEventListener('change', rebuildHidden);
    colorInput.addEventListener('input', rebuildHidden);
    listWrap.appendChild(row);
  });

  // Normalize hidden payload from rendered visual state.
  const rows = Array.from(listWrap.querySelectorAll('.d-flex'));
  const nextTexts = [];
  const nextMap = {};
  rows.forEach((r) => {
    const t = String(r.querySelector('.alerta-text-label')?.value || '').trim();
    const on = !!r.querySelector('.alerta-text-toggle')?.checked;
    const c = normalizeRefAlertColor(r.querySelector('.alerta-text-color')?.value || defaultColor);
    if (!t || !on) return;
    nextTexts.push(t);
    nextMap[t] = c;
  });
  hiddenTexts.value = nextTexts.join(', ');
  hiddenMap.value = serializeRefAlertTextColorMap(nextMap);
}

function createRefGroup(valor = '', desc = '', valor_min = '', valor_max = '', sexo = 'cualquiera', edad_min = '', edad_max = '', alerta_modo = REF_ALERT_DEFAULT_MODE, alerta_color = REF_ALERT_DEFAULT_COLOR, alerta_textos = '', alerta_colores_texto = '') {
  const div = document.createElement('div');
  // Normalizar decimales a punto
  let valorMinStr = valor_min !== '' ? valor_min.toString().replace(',', '.') : '';
  let valorMaxStr = valor_max !== '' ? valor_max.toString().replace(',', '.') : '';
  valorMinStr = valorMinStr !== '' ? parseFloat(valorMinStr).toString() : '';
  valorMaxStr = valorMaxStr !== '' ? parseFloat(valorMaxStr).toString() : '';
  const alertModeNorm = normalizeRefAlertMode(alerta_modo);
  const alertColorNorm = normalizeRefAlertColor(alerta_color);
  const alertTextsNorm = normalizeRefAlertTexts(alerta_textos);
  const alertTextColorsNorm = normalizeRefAlertTextColors(alerta_colores_texto);
  const showAdv = (valorMinStr !== '' || valorMaxStr !== '' || (sexo && sexo !== 'cualquiera') || (edad_min !== '' || edad_max !== '') || alertModeNorm !== REF_ALERT_DEFAULT_MODE || alertColorNorm.toLowerCase() !== REF_ALERT_DEFAULT_COLOR || alertTextsNorm !== '' || alertTextColorsNorm !== '');
  div.className = 'valores-ref-group' + (showAdv ? ' show-advanced' : '');
  div.innerHTML = `
    <div class="ref-header">
      <span class="badge bg-secondary ref-badge">Valor normal</span>
    </div>
    <div class="ref-basic">
      <div class="field">
        <label class="label-sm">Descripción</label>
        <input type="text" class="form-control form-control-sm desc" placeholder="Ej: Normal" value="${desc}">
      </div>
      <div class="field">
        <label class="label-sm">Valor</label>
        <input type="text" class="form-control form-control-sm valor" placeholder="Ej: 70-110" value="${valor}">
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm toggle-advanced" title="Rango por sexo/edad">Rango opcional</button>
      <button type="button" class="btn btn-danger btn-sm remove-ref" title="Eliminar valor">-</button>
    </div>
    <div class="ref-advanced">
      <div class="field">
        <label class="label-sm">Min</label>
        <input type="number" step="any" class="form-control form-control-sm valor-min" placeholder="Min" value="${valorMinStr}">
      </div>
      <div class="field">
        <label class="label-sm">Max</label>
        <input type="number" step="any" class="form-control form-control-sm valor-max" placeholder="Max" value="${valorMaxStr}">
      </div>
      <div class="field">
        <label class="label-sm">Sexo</label>
        <select class="form-select form-select-sm sexo-ref">
          <option value="cualquiera" ${sexo === 'cualquiera' ? 'selected' : ''}>Cualquiera</option>
          <option value="masculino" ${sexo === 'masculino' ? 'selected' : ''}>Masculino</option>
          <option value="femenino" ${sexo === 'femenino' ? 'selected' : ''}>Femenino</option>
        </select>
      </div>
      <div class="field">
        <label class="label-sm">Edad min</label>
        <input type="number" class="form-control form-control-sm edad-min" placeholder="Edad min" value="${edad_min}">
      </div>
      <div class="field">
        <label class="label-sm">Edad max</label>
        <input type="number" class="form-control form-control-sm edad-max" placeholder="Edad max" value="${edad_max}">
      </div>
      <div class="field">
        <label class="label-sm">Alerta</label>
        <select class="form-select form-select-sm alerta-modo-ref">
          <option value="none" ${alertModeNorm === 'none' ? 'selected' : ''}>Sin marca</option>
          <option value="asterisk" ${alertModeNorm === 'asterisk' ? 'selected' : ''}>Solo *</option>
          <option value="color" ${alertModeNorm === 'color' ? 'selected' : ''}>Solo color</option>
          <option value="both" ${alertModeNorm === 'both' ? 'selected' : ''}>* + color</option>
        </select>
      </div>
      <div class="field">
        <label class="label-sm">Color alerta</label>
        <input type="color" class="form-control form-control-sm alerta-color-ref" value="${alertColorNorm}">
      </div>
      <div class="field" style="min-width:220px; flex: 2 1 220px;">
        <label class="label-sm">Textos alerta (coma)</label>
        <input type="hidden" class="alerta-textos-ref" value="${alertTextsNorm}">
        <div class="small text-muted">Se sincroniza automaticamente desde la lista desplegable.</div>
      </div>
      <div class="field" style="min-width:260px; flex: 2 1 260px;">
        <label class="label-sm">Color por texto</label>
        <input type="hidden" class="alerta-colores-texto-ref" value="${alertTextColorsNorm}">
        <div class="alerta-text-color-list border rounded p-2 bg-light"></div>
      </div>
    </div>
  `;
  setTimeout(() => {
    syncLegacyAlertVisualEditor(div);
  }, 0);
  return div;
}





function addRow(data = {}) {
  // ...existing code...
  const tbody = document.querySelector('#formatTable tbody');
  const tr = document.createElement('tr');
  if (data.id_parametro) {
    tr.setAttribute('data-id-parametro', data.id_parametro);
  }
  // Tipo
  const tdType = document.createElement('td');
  tdType.setAttribute('data-label', 'Tipo');
  const selectType = createTypeSelect();
  selectType.value = data.tipo || 'Parámetro';
  tdType.appendChild(selectType);
  tr.appendChild(tdType);
  // Nombre
  const tdNombre = document.createElement('td');
  tdNombre.setAttribute('data-label', 'Nombre');
  tdNombre.innerHTML = `<textarea class="form-control form-control-sm" rows="2">${data.nombre || ''}</textarea>`;
  tr.appendChild(tdNombre);
  // Metodología
  const tdMetod = document.createElement('td');
  tdMetod.className = 'col-metodologia';
  tdMetod.setAttribute('data-label', 'Metodología');
  tdMetod.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.metodologia || ''}">`;
  tr.appendChild(tdMetod);
  // Unidad
  const tdUnidad = document.createElement('td');
  tdUnidad.className = 'col-unidad';
  tdUnidad.setAttribute('data-label', 'Unidad');
  tdUnidad.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.unidad || ''}">`;
  tr.appendChild(tdUnidad);
  // Opciones
  const tdOpciones = document.createElement('td');
  tdOpciones.className = 'col-opciones';
  tdOpciones.setAttribute('data-label', 'Opciones');
  tdOpciones.innerHTML = `<textarea class="form-control form-control-sm opciones-input" rows="2" placeholder="Ej: amarillo, rojizo, ámbar">${data.opciones ? data.opciones.join(', ') : ''}</textarea>`;
  tr.appendChild(tdOpciones);
  // Valor(es) referencia
  const tdRef = document.createElement('td');
  tdRef.className = 'col-referencias';
  tdRef.setAttribute('data-label', 'Referencia');
  const refList = document.createElement('div');
  refList.className = 'valores-ref-list';
  const referencias = data.referencias || [{valor:'', desc:'', valor_min:'', valor_max:'', sexo:'cualquiera', edad_min:'', edad_max:'', alerta_modo:REF_ALERT_DEFAULT_MODE, alerta_color:REF_ALERT_DEFAULT_COLOR, alerta_textos:'', alerta_colores_texto:''}];
  referencias.forEach(ref => {
    refList.appendChild(createRefGroup(ref.valor, ref.desc, ref.valor_min, ref.valor_max, ref.sexo, ref.edad_min, ref.edad_max, ref.alerta_modo, ref.alerta_color, (ref.alerta_textos ?? ref.alert_text_values ?? ref.alerta_valores ?? ''), (ref.alerta_colores_texto ?? ref.alert_text_colors ?? ref.alerta_colores ?? '')));
  });
  const btnAddRef = document.createElement('button');
  btnAddRef.type = 'button';
  btnAddRef.className = 'btn btn-primary btn-sm add-ref mt-1';
  btnAddRef.textContent = '+ Agregar valor';
  tdRef.appendChild(refList);
  tdRef.appendChild(btnAddRef);
  tr.appendChild(tdRef);
  const rowType = String(data.tipo || 'Parámetro');
  const initialTemplateText = String(data.template_text ?? data.template ?? data.valor_template ?? data.valor ?? '');
  const initialFormula = rowType === 'Texto Largo' ? '' : String(data.formula || '');
  // Fórmula / Plantilla
  const tdFormula = document.createElement('td');
  tdFormula.className = 'col-formula';
  tdFormula.setAttribute('data-label', 'Fórmula / Plantilla');
  tdFormula.innerHTML = `
    <input type="text" class="form-control form-control-sm formula-input" value="${initialFormula}" placeholder="Ej: [Hemoglobina]/[Hematocrito]">
    <textarea class="form-control form-control-sm template-text-input d-none mt-1" rows="3" placeholder="Plantilla precargada para captura (Texto Largo)">${initialTemplateText}</textarea>
  `;
  tr.appendChild(tdFormula);
  // Negrita
  const tdBold = document.createElement('td');
  tdBold.className = 'col-neg';
  tdBold.setAttribute('data-label', 'Negrita');
  tdBold.innerHTML = `<input type="checkbox" class="form-check-input" ${data.negrita ? 'checked' : ''}>`;
  tr.appendChild(tdBold);
  // Cursiva
  const tdItalic = document.createElement('td');
  tdItalic.className = 'col-cur';
  tdItalic.setAttribute('data-label', 'Cursiva');
  tdItalic.innerHTML = `<input type="checkbox" class="form-check-input" ${data.cursiva ? 'checked' : ''}>`;
  tr.appendChild(tdItalic);
  // Alineación
  const tdAlign = document.createElement('td');
  tdAlign.setAttribute('data-label', 'Alineación');
  tdAlign.innerHTML = `<select class="form-select form-select-sm align-select">
    <option value="left" ${(data.alineacion === 'left' || !data.alineacion) ? 'selected' : ''}>Izquierda</option>
    <option value="center" ${data.alineacion === 'center' ? 'selected' : ''}>Centro</option>
    <option value="right" ${data.alineacion === 'right' ? 'selected' : ''}>Derecha</option>
  </select>`;
  tr.appendChild(tdAlign);
  // Color texto
  const tdColorText = document.createElement('td');
  tdColorText.className = 'col-color-texto';
  tdColorText.setAttribute('data-label', 'Color txt');
  tdColorText.innerHTML = `<input type="color" class="color-input" value="${data.color_texto || '#000000'}">`;
  tr.appendChild(tdColorText);
  // Color fondo
  const tdColorBg = document.createElement('td');
  tdColorBg.className = 'col-color-fondo';
  tdColorBg.setAttribute('data-label', 'Fondo');
  tdColorBg.innerHTML = `<input type="color" class="color-input" value="${data.color_fondo || '#ffffff'}">`;
  tr.appendChild(tdColorBg);
  // Decimales
  const tdDecimales = document.createElement('td');
  tdDecimales.className = 'col-decimales';
  tdDecimales.setAttribute('data-label', 'Decimales');
  tdDecimales.innerHTML = `<input type="number" class="form-control form-control-sm decimales-input" value="${data.decimales !== undefined ? data.decimales : ''}" min="0" max="6" style="width:50px;">`;
  tr.appendChild(tdDecimales);
  // Filas (para Texto Largo)
  const tdRows = document.createElement('td');
  tdRows.className = 'col-rows';
  tdRows.setAttribute('data-label', 'Filas');
  tdRows.innerHTML = `<input type="number" class="form-control form-control-sm rows-input" value="${data.rows !== undefined ? data.rows : 4}" min="2" max="12" style="width:50px;">`;
  tr.appendChild(tdRows);
  // Orden (solo número, no editable)
  const tdOrden = document.createElement('td');
  tdOrden.className = 'orden-fija';
  tdOrden.setAttribute('data-label', 'Orden');
  tdOrden.textContent = tbody.children.length + 1;
  tr.appendChild(tdOrden);
  // Acciones
  const tdAcc = document.createElement('td');
  tdAcc.className = 'col-acciones';
  tdAcc.setAttribute('data-label', 'Acciones');
  tdAcc.innerHTML = `
    <button type="button" class="btn btn-secondary btn-sm btn-icon move-up" title="Subir"><i class="bi bi-arrow-up"></i></button>
    <button type="button" class="btn btn-secondary btn-sm btn-icon move-down" title="Bajar"><i class="bi bi-arrow-down"></i></button>
    <button type="button" class="btn btn-danger btn-sm btn-icon remove-row" title="Eliminar"><i class="bi bi-trash"></i></button>
  `;
  tr.appendChild(tdAcc);
  tbody.appendChild(tr);
  attachRowListeners(tr);
  // Numerar referencias de la fila
  renumerarReferenciasEnFila(tr);
  refreshLegacyAlertVisualEditors(tr);
  // Ajuste visual según tipo
  selectType.addEventListener('change', () => updateRowUI(tr));
  updateRowUI(tr);
  actualizarOrdenFilas();
  updatePreview();
function actualizarOrdenFilas() {
  const filas = document.querySelectorAll('#formatTable tbody tr');
  filas.forEach((tr, idx) => {
    const tdOrden = tr.querySelector('.orden-fija');
    if (tdOrden) tdOrden.textContent = idx + 1;
  });
}
}
// Eliminar fila de la tabla y mover filas
document.addEventListener('click', function(e) {
  const btnRemove = e.target.closest('.remove-row');
  if (btnRemove) {
    e.preventDefault();
    e.stopPropagation();
    btnRemove.closest('tr').remove();
    actualizarOrdenFilas();
    updatePreview();
    return;
  }

  // Mover fuera de addRow para que esté disponible globalmente
  function actualizarOrdenFilas() {
    const filas = document.querySelectorAll('#formatTable tbody tr');
    filas.forEach((tr, idx) => {
      const tdOrden = tr.querySelector('.orden-fija');
      if (tdOrden) tdOrden.textContent = idx + 1;
    });
  }
  const btnUp = e.target.closest('.move-up');
  if (btnUp) {
    e.preventDefault();
    e.stopPropagation();
    const tr = btnUp.closest('tr');
    const prev = tr.previousElementSibling;
    if (prev) {
      tr.parentNode.insertBefore(tr, prev);
      actualizarOrdenFilas();
      updatePreview();
    }
    return;
  }
  const btnDown = e.target.closest('.move-down');
  if (btnDown) {
    e.preventDefault();
    e.stopPropagation();
    const tr = btnDown.closest('tr');
    const next = tr.nextElementSibling;
    if (next) {
      tr.parentNode.insertBefore(next, tr);
      actualizarOrdenFilas();
      updatePreview();
    }
  }
});

// Agregar nueva fila
document.getElementById('addRow').addEventListener('click', function() {
  addRow();
});

document.addEventListener('DOMContentLoaded', function() {
  const mobileAddRow = document.getElementById('mobileAddRow');
  const mobileToggleCompact = document.getElementById('mobileToggleCompact');
  const desktopAddRow = document.getElementById('addRow');
  const compactToggle = document.getElementById('toggleCompact');

  if (mobileAddRow && desktopAddRow) {
    mobileAddRow.addEventListener('click', function() {
      desktopAddRow.click();
    });
  }

  if (mobileToggleCompact && compactToggle) {
    mobileToggleCompact.addEventListener('click', function() {
      compactToggle.checked = !compactToggle.checked;
      compactToggle.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }
});



// Agregar valor de referencia en una fila
document.addEventListener('click', function(e) {
  const btnAdd = e.target.closest('.add-ref');
  if (btnAdd) {
    const refList = btnAdd.parentElement.querySelector('.valores-ref-list');
    const newGroup = createRefGroup();
    refList.appendChild(newGroup);
    const tr = btnAdd.closest('tr');
    attachRowListeners(tr);
    renumerarReferenciasEnFila(tr);
    refreshLegacyAlertVisualEditors(tr);
    updatePreview();
    return;
  }
  const btnToggleAdv = e.target.closest('.toggle-advanced');
  if (btnToggleAdv) {
    const group = btnToggleAdv.closest('.valores-ref-group');
    group.classList.toggle('show-advanced');
    updatePreview();
  }
});




// Eliminar valor de referencia específico
document.addEventListener('click', function(e) {
  const btnRemRef = e.target.closest('.remove-ref');
  if (btnRemRef) {
    const group = btnRemRef.closest('.valores-ref-group');
    if (group) group.remove();
    const tr = btnRemRef.closest('tr');
    renumerarReferenciasEnFila(tr);
    refreshLegacyAlertVisualEditors(tr);
    updatePreview();
  }
});

// Resaltar grupo activo y numerar referencias
document.addEventListener('focusin', function(e) {
  const group = e.target.closest('.valores-ref-group');
  if (group) {
    document.querySelectorAll('.valores-ref-group.active').forEach(g => g.classList.remove('active'));
    group.classList.add('active');
  }
});
document.addEventListener('focusout', function(e) {
  const group = e.target.closest('.valores-ref-group');
  if (group) {
    setTimeout(() => {
      if (!group.contains(document.activeElement)) {
        group.classList.remove('active');
      }
    }, 0);
  }
});

function renumerarReferenciasEnFila(tr) {
  if (!tr) return;
  const groups = tr.querySelectorAll('.valores-ref-list .valores-ref-group');
  groups.forEach((g, idx) => {
    const badge = g.querySelector('.ref-badge');
    if (badge) badge.textContent = `Valor normal ${idx + 1}`;
  });
}

function refreshLegacyAlertVisualEditors(tr) {
  if (!tr) return;
  tr.querySelectorAll('.valores-ref-group').forEach((group) => {
    syncLegacyAlertVisualEditor(group);
  });
}

function getV2RowSelectableTexts(row) {
  const out = [];
  if (!row || !row.select_options || typeof row.select_options !== 'object') return out;
  Object.keys(row.select_options).forEach((cid) => {
    const arr = row.select_options[cid];
    if (!Array.isArray(arr)) return;
    arr.forEach((v) => {
      const txt = String(v || '').trim();
      if (txt) out.push(txt);
    });
  });
  return Array.from(new Set(out));
}

function syncV2AlertVisualEditor(refItem, row) {
  if (!refItem || !row) return;
  const defaultColor = normalizeRefAlertColor(refItem.querySelector('.v2-ref-alert-color')?.value || REF_ALERT_DEFAULT_COLOR);
  const alertMode = normalizeRefAlertMode(refItem.querySelector('.v2-ref-alert-mode')?.value || REF_ALERT_DEFAULT_MODE);
  const hiddenTexts = refItem.querySelector('.v2-ref-alert-texts');
  const hiddenMap = refItem.querySelector('.v2-ref-alert-text-colors');
  const listWrap = refItem.querySelector('.v2-ref-alert-text-color-list');
  if (!hiddenTexts || !hiddenMap || !listWrap) return;

  const selectedTokens = new Set(splitRefTextList(hiddenTexts.value));
  const map = parseRefAlertTextColorMap(hiddenMap.value);
  const merged = Array.from(new Set([...getV2RowSelectableTexts(row), ...Object.keys(map), ...Array.from(selectedTokens)]));

  listWrap.innerHTML = '';
  if (merged.length === 0) {
    listWrap.innerHTML = '<div class="small text-muted">Define opciones en alguna columna de resultado/lista.</div>';
    return;
  }

  const showColorControls = alertMode === 'color' || alertMode === 'both';
  merged.forEach((txt) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'd-flex align-items-center gap-2 mb-1';
    const checked = selectedTokens.has(txt) || Object.prototype.hasOwnProperty.call(map, txt);
    const color = normalizeRefAlertColor(map[txt] || defaultColor);
    rowEl.innerHTML = `
      <label class="form-check mb-0" style="min-width:18px;">
        <input type="checkbox" class="form-check-input v2-alert-text-toggle" ${checked ? 'checked' : ''}>
      </label>
      <input type="text" class="form-control form-control-sm v2-alert-text-label" value="${txt.replace(/"/g, '&quot;')}" readonly style="max-width:190px;">
      <input type="color" class="form-control form-control-sm v2-alert-text-color" value="${color}" ${showColorControls ? '' : 'disabled'}>
    `;
    const rebuild = () => {
      const rows = Array.from(listWrap.querySelectorAll('.d-flex'));
      const nextTexts = [];
      const nextMap = {};
      rows.forEach((r) => {
        const t = String(r.querySelector('.v2-alert-text-label')?.value || '').trim();
        const on = !!r.querySelector('.v2-alert-text-toggle')?.checked;
        const c = normalizeRefAlertColor(r.querySelector('.v2-alert-text-color')?.value || defaultColor);
        if (!t || !on) return;
        nextTexts.push(t);
        nextMap[t] = c;
      });
      hiddenTexts.value = nextTexts.join(', ');
      hiddenMap.value = serializeRefAlertTextColorMap(nextMap);
      updatePreview();
      renderV2Preview();
    };
    rowEl.querySelector('.v2-alert-text-toggle')?.addEventListener('change', rebuild);
    rowEl.querySelector('.v2-alert-text-color')?.addEventListener('input', rebuild);
    listWrap.appendChild(rowEl);
  });

  const rows = Array.from(listWrap.querySelectorAll('.d-flex'));
  const nextTexts = [];
  const nextMap = {};
  rows.forEach((r) => {
    const t = String(r.querySelector('.v2-alert-text-label')?.value || '').trim();
    const on = !!r.querySelector('.v2-alert-text-toggle')?.checked;
    const c = normalizeRefAlertColor(r.querySelector('.v2-alert-text-color')?.value || defaultColor);
    if (!t || !on) return;
    nextTexts.push(t);
    nextMap[t] = c;
  });
  hiddenTexts.value = nextTexts.join(', ');
  hiddenMap.value = serializeRefAlertTextColorMap(nextMap);
}

function attachRowListeners(tr) {
  if (!tr) return;
  const controls = tr.querySelectorAll('textarea, input, select');
  controls.forEach(el => {
    el.removeEventListener('input', updatePreview);
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', () => {
      if (el.classList.contains('opciones-input') || el.classList.contains('alerta-modo-ref') || el.classList.contains('alerta-color-ref')) {
        refreshLegacyAlertVisualEditors(tr);
      }
    });
  });
}

// Delegation fallback: any input inside the table triggers preview
document.addEventListener('input', function(e) {
  if (e.target && e.target.closest('#formatTable')) {
    const tr = e.target.closest('tr');
    if (tr && (e.target.classList.contains('opciones-input') || e.target.classList.contains('alerta-modo-ref') || e.target.classList.contains('alerta-color-ref'))) {
      refreshLegacyAlertVisualEditors(tr);
    }
    updatePreview();
  }
});



function updatePreview() {
  const tbody = document.querySelector('#formatTable tbody');
  let rows = Array.from(tbody.querySelectorAll('tr'));
  // Solo columnas relevantes en la preview
  let html = '<table class="table table-bordered"><thead><tr>' +
    '<th>Nombre</th><th>Metodología</th><th>Unidad</th><th>Opciones</th><th>Valor(es) Referencia</th><th>Negrita</th><th>Cursiva</th><th>Alineación</th>' +
    '</tr></thead><tbody>';

  const renderOpcionesPreview = (raw) => {
    const opciones = String(raw || '')
      .split(',')
      .map(opcion => opcion.trim())
      .filter(Boolean);
    if (!opciones.length) {
      return '';
    }
    const optionsHtml = opciones.map((opcion, index) => {
      const selected = index === 0 ? ' selected' : '';
      return `<option value="${opcion}"${selected}>${opcion}</option>`;
    }).join('');
    return `<select class="form-select form-select-sm" disabled>${optionsHtml}</select>`;
  };

  const renderReferenciaEjemplo = (refGroups) => {
    const primeraReferencia = Array.from(refGroups).find(refDiv => {
      const valor = refDiv.querySelector('.valor').value.trim();
      const desc = refDiv.querySelector('.desc').value.trim();
      return valor || desc;
    });

    if (!primeraReferencia) {
      return '';
    }

    const valor = primeraReferencia.querySelector('.valor').value.trim();
    const desc = primeraReferencia.querySelector('.desc').value.trim();
    const etiqueta = desc || valor;
    if (!etiqueta) {
      return '';
    }

    return `<div class="small text-muted mt-1">Ejemplo: ${etiqueta}</div>`;
  };

  rows.forEach(tr => {
    const tipo = tr.querySelector('.type-select').value;
    const nombre = tr.children[1].querySelector('textarea').value;
    const metodologia = tr.children[2].querySelector('input').value;
    const unidad = tr.children[3].querySelector('input').value;
    const opciones = tr.children[4].querySelector('.opciones-input').value;
    const templateText = tr.children[6].querySelector('.template-text-input')?.value || '';
    let refHtml = '';
    const refGroups = tr.children[5].querySelectorAll('.valores-ref-group');
    refGroups.forEach(refDiv => {
      const valor = refDiv.querySelector('.valor').value;
      const desc = refDiv.querySelector('.desc').value;
      const vmin = refDiv.querySelector('.valor-min').value;
      const vmax = refDiv.querySelector('.valor-max').value;
      const sexoSel = refDiv.querySelector('.sexo-ref').value;
      const emin = refDiv.querySelector('.edad-min').value;
      const emax = refDiv.querySelector('.edad-max').value;
      let linea = '';
      if (valor || desc) {
        linea = `<div><b>${desc ? desc + ':' : ''}</b> ${valor}`;
      }
      // Mostrar rango si existe
      const partes = [];
      if (vmin || vmax) partes.push(`${vmin || ''}–${vmax || ''}`);
      if (sexoSel && sexoSel !== 'cualquiera') partes.push(sexoSel);
      if (emin || emax) partes.push(`edad ${emin || ''}–${emax || ''}`);
      if (partes.length) {
        linea += ` <span class="text-muted">(${partes.join(', ')})</span>`;
      }
      if (linea) refHtml += linea + '</div>';
    });
    const negrita = tr.children[7].querySelector('input').checked;
    const cursiva = tr.children[8].querySelector('input').checked;
    const alineacion = tr.children[9].querySelector('select').value;
    const colorTexto = tr.children[10].querySelector('input').value;
    const colorFondo = tr.children[11].querySelector('input').value;

    let fontStyle = '';
    if (negrita) fontStyle += 'font-weight:bold;';
    if (cursiva) fontStyle += 'font-style:italic;';
      const tdAcc = document.createElement('td');
      tdAcc.innerHTML = `
        <button class="btn btn-secondary btn-sm move-up">↑</button>
        <button class="btn btn-secondary btn-sm move-down">↓</button>
        <button class="btn btn-danger btn-sm remove-row">Eliminar</button>
      `;
    // Traducción de alineación
    let alineacionCastellano = 'Izquierda';
    if (alineacion === 'center') alineacionCastellano = 'Centro';
    if (alineacion === 'right') alineacionCastellano = 'Derecha';

    if (tipo === 'Título') {
      html += `<tr>
        <td colspan="8" style="background:${colorFondo};color:${colorTexto};${fontStyle}font-size:1.2em;text-align:${alineacion};">
          ${nombre}
        </td>
      </tr>`;
    } else if (tipo === 'Subtítulo') {
      html += `<tr>
        <td colspan="8" style="background:${colorFondo};color:${colorTexto};${fontStyle}font-size:1em;text-align:${alineacion};">
          ${nombre}
        </td>
      </tr>`;
    } else if (tipo === 'Texto Largo') {
      html += `<tr>
        <td colspan="8" style="background:${colorFondo};color:${colorTexto};${fontStyle}text-align:${alineacion};">
          <div><b>${nombre || 'Observación'}</b></div>
          <div style="white-space:pre-wrap;">${templateText ? templateText.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '<span class="text-muted">(bloque de texto largo)</span>'}</div>
        </td>
      </tr>`;
    } else {
      const opcionesPreview = renderOpcionesPreview(opciones);
      const ejemploReferencia = renderReferenciaEjemplo(refGroups);
      html += `<tr>
        <td style="color:${colorTexto};background:${colorFondo};${fontStyle}">${nombre}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${metodologia}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${unidad}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${opcionesPreview}${ejemploReferencia}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${refHtml}</td>
        <td>${negrita ? '✔' : ''}</td>
        <td>${cursiva ? '✔' : ''}</td>
        <td>${alineacionCastellano}</td>
      </tr>`;
    }
  });

  html += '</tbody></table>';
  document.getElementById('preview').innerHTML = html;
}

const v2State = {
  columns: [],
  rows: []
};

function slugifyV2(text) {
  return String(text || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');
}

function ensureUniqueColumnId(baseId, indexToIgnore = -1) {
  let id = slugifyV2(baseId) || 'columna';
  let n = 1;
  while (v2State.columns.some((c, idx) => idx !== indexToIgnore && c.id === id)) {
    id = `${slugifyV2(baseId) || 'columna'}_${n++}`;
  }
  return id;
}

function ensureUniqueRowId(baseId, indexToIgnore = -1) {
  let id = slugifyV2(baseId) || 'fila';
  let n = 1;
  while (v2State.rows.some((r, idx) => idx !== indexToIgnore && r.id === id)) {
    id = `${slugifyV2(baseId) || 'fila'}_${n++}`;
  }
  return id;
}

function addV2Column(col = {}) {
  const defaultLabel = col.label || `Columna ${v2State.columns.length + 1}`;
  const colId = ensureUniqueColumnId(col.id || defaultLabel);
  const newCol = {
    label: defaultLabel,
    id: colId,
    kind: col.kind || 'text',
    editable: !!col.editable,
    visible_capture: col.visible_capture !== false,
    visible_pdf: col.visible_pdf !== false,
    width: col.width || '',
    order: v2State.columns.length + 1
  };
  v2State.columns.push(newCol);
  v2State.rows.forEach(r => {
    if (!r.cells) r.cells = {};
    if (!Object.prototype.hasOwnProperty.call(r.cells, newCol.id)) {
      r.cells[newCol.id] = '';
    }
  });
}

function addV2DataRow(row = {}) {
  const rowId = ensureUniqueRowId(row.id || `fila_${v2State.rows.length + 1}`);
  const rowType = String(row.type || 'data').trim() || 'data';
  const cells = {};
  const formulas = {};
  const selectOptions = {};
  const referenceRanges = {};
  const decimals = {};
  v2State.columns.forEach(c => {
    cells[c.id] = row.cells && Object.prototype.hasOwnProperty.call(row.cells, c.id)
      ? row.cells[c.id]
      : '';
    formulas[c.id] = row.formulas && Object.prototype.hasOwnProperty.call(row.formulas, c.id)
      ? String(row.formulas[c.id] || '')
      : '';
    const rawOpts = row.select_options && Object.prototype.hasOwnProperty.call(row.select_options, c.id)
      ? row.select_options[c.id]
      : [];
    const opts = Array.isArray(rawOpts)
      ? rawOpts.map(o => String(o || '').trim()).filter(Boolean)
      : String(rawOpts || '').split(',').map(o => o.trim()).filter(Boolean);
    selectOptions[c.id] = opts;

    const rawRanges = row.reference_ranges && Object.prototype.hasOwnProperty.call(row.reference_ranges, c.id)
      ? row.reference_ranges[c.id]
      : [];
    const ranges = Array.isArray(rawRanges)
      ? rawRanges.map((r) => ({
          desc: String((r && r.desc) || '').trim(),
          valor: String((r && r.valor) || '').trim(),
          valor_min: String((r && r.valor_min) || '').trim(),
          valor_max: String((r && r.valor_max) || '').trim(),
          sexo: String((r && r.sexo) || 'cualquiera').trim() || 'cualquiera',
          edad_min: String((r && r.edad_min) || '').trim(),
          edad_max: String((r && r.edad_max) || '').trim(),
          alerta_modo: normalizeRefAlertMode((r && (r.alerta_modo ?? r.alert_mode)) || REF_ALERT_DEFAULT_MODE),
          alerta_color: normalizeRefAlertColor((r && (r.alerta_color ?? r.alert_color)) || REF_ALERT_DEFAULT_COLOR),
          alerta_textos: normalizeRefAlertTexts((r && (r.alerta_textos ?? r.alert_text_values ?? r.alerta_valores)) || ''),
          alerta_colores_texto: normalizeRefAlertTextColors((r && (r.alerta_colores_texto ?? r.alert_text_colors ?? r.alerta_colores)) || '')
        })).filter(r => r.desc || r.valor || r.valor_min || r.valor_max || r.edad_min || r.edad_max || (r.sexo && r.sexo !== 'cualquiera') || r.alerta_modo !== REF_ALERT_DEFAULT_MODE || String(r.alerta_color || '').toLowerCase() !== REF_ALERT_DEFAULT_COLOR || r.alerta_textos !== '' || r.alerta_colores_texto !== '')
      : [];
    referenceRanges[c.id] = ranges;
    if (String(c.kind || '').toLowerCase() === 'reference' && ranges.length > 0 && String(cells[c.id] || '').trim() === '') {
      cells[c.id] = buildV2ReferenceSummary(ranges);
    }

    const rawDec = row.decimales && Object.prototype.hasOwnProperty.call(row.decimales, c.id)
      ? row.decimales[c.id]
      : (row.decimals && Object.prototype.hasOwnProperty.call(row.decimals, c.id) ? row.decimals[c.id] : '');
    if (rawDec !== '' && rawDec !== null && rawDec !== undefined && isFinite(parseInt(rawDec, 10))) {
      const parsedDec = parseInt(rawDec, 10);
      if (parsedDec >= 0 && parsedDec <= 6) {
        decimals[c.id] = parsedDec;
      }
    }
  });
  const defaultAlign = (rowType === 'title') ? 'center' : 'left';
  const rawAlign = String(row.alineacion || '').trim().toLowerCase();
  const safeAlign = ['left', 'center', 'right'].includes(rawAlign) ? rawAlign : defaultAlign;
  const defaultTemplateAlign = String(row.template_align || '').trim().toLowerCase();
  const safeTemplateAlign = ['left', 'center', 'right'].includes(defaultTemplateAlign) ? defaultTemplateAlign : 'left';
  const defaultBold = Object.prototype.hasOwnProperty.call(row, 'negrita')
    ? !!row.negrita
    : (rowType === 'title' || rowType === 'subtitle');

  v2State.rows.push({
    id: rowId,
    type: rowType,
    label: row.label || '',
    template_text: String(row.template_text || row.template || '').trim(),
    template_editable: row.template_editable !== false,
    template_visible_pdf: row.template_visible_pdf !== false,
    template_align: safeTemplateAlign,
    color_texto: String(row.color_texto || '').trim(),
    color_fondo: String(row.color_fondo || '').trim(),
    negrita: defaultBold,
    cursiva: !!row.cursiva,
    alineacion: safeAlign,
    cells,
    formulas,
    select_options: selectOptions,
    reference_ranges: referenceRanges,
    decimales: decimals
  });
}

function buildV2ReferenceSummary(ranges) {
  const list = Array.isArray(ranges) ? ranges : [];
  const lines = list.map((r) => {
    const desc = String((r && r.desc) || '').trim();
    const valor = String((r && r.valor) || '').trim();
    const min = String((r && r.valor_min) || '').trim();
    const max = String((r && r.valor_max) || '').trim();

    // Mostrar solo información pública (desc + valor/rango). Edad/sexo se
    // mantienen en reference_ranges para la discriminación interna.
    const rango = (min || max) ? `${min || ''} - ${max || ''}`.trim() : '';
    const visible = valor || rango;

    if (desc && visible) return `${desc} (${visible})`;
    if (desc) return desc;
    if (visible) return visible;
    return '';
  }).map(s => String(s || '').trim()).filter(Boolean);
  return lines.join(' | ');
}

function renderV2Columns() {
  const tbody = document.querySelector('#v2ColumnsTable tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  v2State.columns.forEach((col, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" class="form-control form-control-sm v2-col-label" value="${col.label}"></td>
      <td><input type="text" class="form-control form-control-sm v2-col-id" value="${col.id}"></td>
      <td>
        <select class="form-select form-select-sm v2-col-kind">
          <option value="text" ${col.kind === 'text' ? 'selected' : ''}>Texto</option>
          <option value="result" ${col.kind === 'result' ? 'selected' : ''}>Resultado</option>
          <option value="select" ${col.kind === 'select' ? 'selected' : ''}>Lista desplegable</option>
          <option value="reference" ${col.kind === 'reference' ? 'selected' : ''}>Referencia</option>
          <option value="long_text" ${col.kind === 'long_text' ? 'selected' : ''}>Texto largo</option>
          <option value="number" ${col.kind === 'number' ? 'selected' : ''}>Numero</option>
        </select>
      </td>
      <td class="text-center"><input type="checkbox" class="form-check-input v2-col-editable" ${col.editable ? 'checked' : ''}></td>
      <td class="text-center"><input type="checkbox" class="form-check-input v2-col-capture" ${col.visible_capture ? 'checked' : ''}></td>
      <td class="text-center"><input type="checkbox" class="form-check-input v2-col-pdf" ${col.visible_pdf ? 'checked' : ''}></td>
      <td><input type="text" class="form-control form-control-sm v2-col-width" placeholder="Ej: 20%" value="${col.width}"></td>
      <td>${idx + 1}</td>
      <td>
        <button type="button" class="btn btn-secondary btn-sm v2-col-up">↑</button>
        <button type="button" class="btn btn-secondary btn-sm v2-col-down">↓</button>
        <button type="button" class="btn btn-danger btn-sm v2-col-del">✕</button>
      </td>
    `;

    tr.querySelector('.v2-col-label').addEventListener('input', (e) => {
      col.label = e.target.value;
      if (!col.id || col.id === slugifyV2(col.label)) {
        col.id = ensureUniqueColumnId(col.label || 'columna', idx);
        tr.querySelector('.v2-col-id').value = col.id;
      }
      renderV2Rows();
      renderV2Preview();
    });

    tr.querySelector('.v2-col-id').addEventListener('input', (e) => {
      const oldId = col.id;
      col.id = ensureUniqueColumnId(e.target.value || col.label || 'columna', idx);
      e.target.value = col.id;
      v2State.rows.forEach(r => {
        if (!r.cells) r.cells = {};
        if (Object.prototype.hasOwnProperty.call(r.cells, oldId)) {
          r.cells[col.id] = r.cells[oldId];
          delete r.cells[oldId];
        } else if (!Object.prototype.hasOwnProperty.call(r.cells, col.id)) {
          r.cells[col.id] = '';
        }
        if (!r.select_options) r.select_options = {};
        if (Object.prototype.hasOwnProperty.call(r.select_options, oldId)) {
          r.select_options[col.id] = r.select_options[oldId];
          delete r.select_options[oldId];
        } else if (!Object.prototype.hasOwnProperty.call(r.select_options, col.id)) {
          r.select_options[col.id] = [];
        }
        if (!r.reference_ranges) r.reference_ranges = {};
        if (Object.prototype.hasOwnProperty.call(r.reference_ranges, oldId)) {
          r.reference_ranges[col.id] = r.reference_ranges[oldId];
          delete r.reference_ranges[oldId];
        } else if (!Object.prototype.hasOwnProperty.call(r.reference_ranges, col.id)) {
          r.reference_ranges[col.id] = [];
        }
        if (!r.decimales) r.decimales = {};
        if (Object.prototype.hasOwnProperty.call(r.decimales, oldId)) {
          r.decimales[col.id] = r.decimales[oldId];
          delete r.decimales[oldId];
        }
      });
      renderV2Rows();
      renderV2Preview();
    });

    tr.querySelector('.v2-col-kind').addEventListener('change', (e) => {
      col.kind = e.target.value;
      renderV2Preview();
    });
    tr.querySelector('.v2-col-editable').addEventListener('change', (e) => {
      col.editable = e.target.checked;
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-col-capture').addEventListener('change', (e) => {
      col.visible_capture = e.target.checked;
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-col-pdf').addEventListener('change', (e) => {
      col.visible_pdf = e.target.checked;
      renderV2Preview();
    });
    tr.querySelector('.v2-col-width').addEventListener('input', (e) => {
      col.width = e.target.value;
      renderV2Preview();
    });

    tr.querySelector('.v2-col-up').addEventListener('click', () => {
      if (idx === 0) return;
      const tmp = v2State.columns[idx - 1];
      v2State.columns[idx - 1] = v2State.columns[idx];
      v2State.columns[idx] = tmp;
      renderV2Columns();
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-col-down').addEventListener('click', () => {
      if (idx >= v2State.columns.length - 1) return;
      const tmp = v2State.columns[idx + 1];
      v2State.columns[idx + 1] = v2State.columns[idx];
      v2State.columns[idx] = tmp;
      renderV2Columns();
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-col-del').addEventListener('click', () => {
      const oldId = col.id;
      v2State.columns.splice(idx, 1);
      v2State.rows.forEach(r => {
        if (r.cells && Object.prototype.hasOwnProperty.call(r.cells, oldId)) {
          delete r.cells[oldId];
        }
        if (r.select_options && Object.prototype.hasOwnProperty.call(r.select_options, oldId)) {
          delete r.select_options[oldId];
        }
        if (r.reference_ranges && Object.prototype.hasOwnProperty.call(r.reference_ranges, oldId)) {
          delete r.reference_ranges[oldId];
        }
        if (r.decimales && Object.prototype.hasOwnProperty.call(r.decimales, oldId)) {
          delete r.decimales[oldId];
        }
      });
      renderV2Columns();
      renderV2Rows();
      renderV2Preview();
    });

    tbody.appendChild(tr);
  });
}

function renderV2Rows() {
  const thead = document.querySelector('#v2RowsTable thead');
  const tbody = document.querySelector('#v2RowsTable tbody');
  if (!thead || !tbody) return;

  const visibleCols = v2State.columns.filter(c => c.visible_capture !== false);
  let headHtml = '<tr><th>ID fila</th><th>Tipo</th><th>Etiqueta</th>';
  visibleCols.forEach(c => {
    headHtml += `<th>${c.label || c.id}</th>`;
  });
  headHtml += '<th>Acciones</th></tr>';
  thead.innerHTML = headHtml;

  tbody.innerHTML = '';
  v2State.rows.forEach((row, rowIdx) => {
    const tr = document.createElement('tr');
    const moveActionsHtml = '<button type="button" class="btn btn-secondary btn-sm v2-row-up">↑</button> <button type="button" class="btn btn-secondary btn-sm v2-row-down">↓</button> <button type="button" class="btn btn-danger btn-sm v2-row-del">✕</button>';
    let html = `
      <td><input type="text" class="form-control form-control-sm v2-row-id" value="${row.id}"></td>
      <td>
        <select class="form-select form-select-sm v2-row-type">
          <option value="data" ${row.type === 'data' ? 'selected' : ''}>Data</option>
          <option value="title" ${row.type === 'title' ? 'selected' : ''}>Titulo</option>
          <option value="subtitle" ${row.type === 'subtitle' ? 'selected' : ''}>Subtitulo</option>
          <option value="long_text" ${row.type === 'long_text' ? 'selected' : ''}>Texto largo</option>
        </select>
      </td>
      <td><input type="text" class="form-control form-control-sm v2-row-label" value="${row.label || ''}" placeholder="Titulo opcional de bloque"></td>
    `;
    if (row.type === 'title' || row.type === 'subtitle') {
      const align = ['left', 'center', 'right'].includes(String(row.alineacion || '').toLowerCase())
        ? String(row.alineacion || '').toLowerCase()
        : (row.type === 'title' ? 'center' : 'left');
      const textColor = String(row.color_texto || '').trim() || '#1f2d5c';
      const bgColor = String(row.color_fondo || '').trim() || (row.type === 'title' ? '#eef4ff' : '#f4f7fb');
      html += `<td colspan="${Math.max(1, visibleCols.length)}">
        <div class="row g-2 align-items-end">
          <div class="col-3">
            <label class="small text-muted d-block mb-1">Alineacion</label>
            <select class="form-select form-select-sm v2-row-style-align">
              <option value="left" ${align === 'left' ? 'selected' : ''}>Izquierda</option>
              <option value="center" ${align === 'center' ? 'selected' : ''}>Centro</option>
              <option value="right" ${align === 'right' ? 'selected' : ''}>Derecha</option>
            </select>
          </div>
          <div class="col-3">
            <label class="small text-muted d-block mb-1">Color texto</label>
            <input type="color" class="form-control form-control-sm v2-row-style-text-color" value="${textColor}">
          </div>
          <div class="col-3">
            <label class="small text-muted d-block mb-1">Color fondo</label>
            <input type="color" class="form-control form-control-sm v2-row-style-bg-color" value="${bgColor}">
          </div>
          <div class="col-3">
            <label class="small text-muted d-block mb-1">Estilo</label>
            <div class="d-flex gap-3">
              <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-style-bold" ${row.negrita ? 'checked' : ''}> Negrita</label>
              <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-style-italic" ${row.cursiva ? 'checked' : ''}> Cursiva</label>
            </div>
          </div>
        </div>
      </td>`;
      html += `<td>${moveActionsHtml}</td>`;
      tr.innerHTML = html;

      tr.querySelector('.v2-row-id').addEventListener('input', (e) => {
        row.id = ensureUniqueRowId(e.target.value || 'fila', rowIdx);
        e.target.value = row.id;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-type').addEventListener('change', (e) => {
        row.type = e.target.value;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-label').addEventListener('input', (e) => {
        row.label = e.target.value;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-style-align')?.addEventListener('change', (e) => {
        row.alineacion = String(e.target.value || 'left');
        renderV2Preview();
      });
      tr.querySelector('.v2-row-style-text-color')?.addEventListener('input', (e) => {
        row.color_texto = String(e.target.value || '').trim();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-style-bg-color')?.addEventListener('input', (e) => {
        row.color_fondo = String(e.target.value || '').trim();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-style-bold')?.addEventListener('change', (e) => {
        row.negrita = !!e.target.checked;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-style-italic')?.addEventListener('change', (e) => {
        row.cursiva = !!e.target.checked;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-up')?.addEventListener('click', () => {
        if (rowIdx === 0) return;
        const tmp = v2State.rows[rowIdx - 1];
        v2State.rows[rowIdx - 1] = v2State.rows[rowIdx];
        v2State.rows[rowIdx] = tmp;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-down')?.addEventListener('click', () => {
        if (rowIdx >= v2State.rows.length - 1) return;
        const tmp = v2State.rows[rowIdx + 1];
        v2State.rows[rowIdx + 1] = v2State.rows[rowIdx];
        v2State.rows[rowIdx] = tmp;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-del').addEventListener('click', () => {
        v2State.rows.splice(rowIdx, 1);
        renderV2Rows();
        renderV2Preview();
      });

      tbody.appendChild(tr);
      return;
    }
    if (row.type === 'long_text') {
      const templateText = String(row.template_text || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const templateAlign = ['left', 'center', 'right'].includes(String(row.template_align || '').toLowerCase())
        ? String(row.template_align || '').toLowerCase()
        : 'left';
      const textColor = String(row.color_texto || '').trim() || '#1f2d5c';
      const bgColor = String(row.color_fondo || '').trim() || '#fafcff';
      html += `<td colspan="${Math.max(1, visibleCols.length)}">
        <textarea class="form-control form-control-sm v2-row-template-text" rows="4" placeholder="Plantilla de texto precargada para el formulario">${templateText}</textarea>
        <div class="d-flex gap-3 mt-2">
          <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-template-editable" ${row.template_editable !== false ? 'checked' : ''}> Editable en captura</label>
          <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-template-pdf" ${row.template_visible_pdf !== false ? 'checked' : ''}> Visible en PDF</label>
          <label class="form-check-label">Alineacion
            <select class="form-select form-select-sm d-inline-block ms-1 v2-row-template-align" style="width:auto;">
              <option value="left" ${templateAlign === 'left' ? 'selected' : ''}>Izquierda</option>
              <option value="center" ${templateAlign === 'center' ? 'selected' : ''}>Centro</option>
              <option value="right" ${templateAlign === 'right' ? 'selected' : ''}>Derecha</option>
            </select>
          </label>
        </div>
        <div class="row g-2 mt-2 align-items-end">
          <div class="col-4">
            <label class="small text-muted d-block mb-1">Color texto</label>
            <input type="color" class="form-control form-control-sm v2-row-template-text-color" value="${textColor}">
          </div>
          <div class="col-4">
            <label class="small text-muted d-block mb-1">Color fondo</label>
            <input type="color" class="form-control form-control-sm v2-row-template-bg-color" value="${bgColor}">
          </div>
          <div class="col-4">
            <label class="small text-muted d-block mb-1">Estilo</label>
            <div class="d-flex gap-3">
              <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-template-bold" ${row.negrita ? 'checked' : ''}> Negrita</label>
              <label class="form-check-label"><input type="checkbox" class="form-check-input v2-row-template-italic" ${row.cursiva ? 'checked' : ''}> Cursiva</label>
            </div>
          </div>
        </div>
      </td>`;
      html += `<td>${moveActionsHtml}</td>`;
      tr.innerHTML = html;

      tr.querySelector('.v2-row-id').addEventListener('input', (e) => {
        row.id = ensureUniqueRowId(e.target.value || 'fila', rowIdx);
        e.target.value = row.id;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-type').addEventListener('change', (e) => {
        row.type = e.target.value;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-label').addEventListener('input', (e) => {
        row.label = e.target.value;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-text')?.addEventListener('input', (e) => {
        row.template_text = String(e.target.value || '');
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-editable')?.addEventListener('change', (e) => {
        row.template_editable = !!e.target.checked;
      });
      tr.querySelector('.v2-row-template-pdf')?.addEventListener('change', (e) => {
        row.template_visible_pdf = !!e.target.checked;
      });
      tr.querySelector('.v2-row-template-align')?.addEventListener('change', (e) => {
        row.template_align = String(e.target.value || 'left');
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-text-color')?.addEventListener('input', (e) => {
        row.color_texto = String(e.target.value || '').trim();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-bg-color')?.addEventListener('input', (e) => {
        row.color_fondo = String(e.target.value || '').trim();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-bold')?.addEventListener('change', (e) => {
        row.negrita = !!e.target.checked;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-template-italic')?.addEventListener('change', (e) => {
        row.cursiva = !!e.target.checked;
        renderV2Preview();
      });
      tr.querySelector('.v2-row-up')?.addEventListener('click', () => {
        if (rowIdx === 0) return;
        const tmp = v2State.rows[rowIdx - 1];
        v2State.rows[rowIdx - 1] = v2State.rows[rowIdx];
        v2State.rows[rowIdx] = tmp;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-down')?.addEventListener('click', () => {
        if (rowIdx >= v2State.rows.length - 1) return;
        const tmp = v2State.rows[rowIdx + 1];
        v2State.rows[rowIdx + 1] = v2State.rows[rowIdx];
        v2State.rows[rowIdx] = tmp;
        renderV2Rows();
        renderV2Preview();
      });
      tr.querySelector('.v2-row-del').addEventListener('click', () => {
        v2State.rows.splice(rowIdx, 1);
        renderV2Rows();
        renderV2Preview();
      });

      tbody.appendChild(tr);
      return;
    }
    visibleCols.forEach(c => {
      const cellValue = (row.cells && Object.prototype.hasOwnProperty.call(row.cells, c.id)) ? row.cells[c.id] : '';
      const disabled = row.type !== 'data' || !c.editable;
      const formulaValue = (row.formulas && Object.prototype.hasOwnProperty.call(row.formulas, c.id)) ? row.formulas[c.id] : '';
      const cellOptions = row.select_options && Object.prototype.hasOwnProperty.call(row.select_options, c.id)
        ? row.select_options[c.id]
        : [];
      const rangeValues = row.reference_ranges && Object.prototype.hasOwnProperty.call(row.reference_ranges, c.id)
        ? row.reference_ranges[c.id]
        : [];
      const normalizedOptions = Array.isArray(cellOptions)
        ? cellOptions.map(opt => String(opt || '').trim()).filter(Boolean)
        : [];
      const useStrictSelect = c.kind === 'select';
      const shouldRenderSelect = useStrictSelect && normalizedOptions.length > 0;
      const optionsText = normalizedOptions.join(', ');
      const decimalsValue = (row.decimales && Object.prototype.hasOwnProperty.call(row.decimales, c.id))
        ? row.decimales[c.id]
        : '';
      const isDataRow = row.type === 'data';
      const decimalsAllowed = isDataRow && c.editable && ['result', 'number'].includes(c.kind);
      const showOptionsEditor = isDataRow && c.editable && ['result', 'number', 'select'].includes(c.kind);
      const showFormulaEditor = isDataRow && c.editable && c.kind !== 'select';
      const optionsHtml = normalizedOptions.map((opt) => {
        const selected = String(cellValue) === String(opt) ? ' selected' : '';
        return `<option value="${opt}"${selected}>${opt}</option>`;
      }).join('');
      const normalizedRanges = Array.isArray(rangeValues)
        ? rangeValues
        : [];

      if (c.kind === 'reference' && row.type === 'data') {
        const rangesHtml = normalizedRanges.map((r, i) => {
          return `<div class="v2-ref-item border rounded p-1 mb-1" data-range-index="${i}">
            <div class="row g-1 mb-1">
              <div class="col-6"><input type="text" class="form-control form-control-sm v2-ref-desc" value="${String(r.desc || '')}" placeholder="Etiqueta"></div>
              <div class="col-6"><input type="text" class="form-control form-control-sm v2-ref-valor" value="${String(r.valor || '')}" placeholder="Texto visible"></div>
            </div>
            <div class="row g-1 mb-1">
              <div class="col-3"><input type="number" step="any" class="form-control form-control-sm v2-ref-min" value="${String(r.valor_min || '')}" placeholder="Min"></div>
              <div class="col-3"><input type="number" step="any" class="form-control form-control-sm v2-ref-max" value="${String(r.valor_max || '')}" placeholder="Max"></div>
              <div class="col-3">
                <select class="form-select form-select-sm v2-ref-sexo">
                  <option value="cualquiera" ${(String(r.sexo || 'cualquiera') === 'cualquiera') ? 'selected' : ''}>Cualquiera</option>
                  <option value="masculino" ${(String(r.sexo || '') === 'masculino') ? 'selected' : ''}>Masculino</option>
                  <option value="femenino" ${(String(r.sexo || '') === 'femenino') ? 'selected' : ''}>Femenino</option>
                </select>
              </div>
              <div class="col-2"><input type="number" class="form-control form-control-sm v2-ref-edad-min" value="${String(r.edad_min || '')}" placeholder="Edad min"></div>
              <div class="col-1 d-grid"><button type="button" class="btn btn-danger btn-sm v2-ref-remove">-</button></div>
            </div>
            <div class="row g-1">
              <div class="col-3"><input type="number" class="form-control form-control-sm v2-ref-edad-max" value="${String(r.edad_max || '')}" placeholder="Edad max"></div>
            </div>
            <div class="row g-1 mt-1">
              <div class="col-4">
                <select class="form-select form-select-sm v2-ref-alert-mode">
                  <option value="none" ${(normalizeRefAlertMode(r.alerta_modo || r.alert_mode) === 'none') ? 'selected' : ''}>Sin marca</option>
                  <option value="asterisk" ${(normalizeRefAlertMode(r.alerta_modo || r.alert_mode) === 'asterisk') ? 'selected' : ''}>Solo *</option>
                  <option value="color" ${(normalizeRefAlertMode(r.alerta_modo || r.alert_mode) === 'color') ? 'selected' : ''}>Solo color</option>
                  <option value="both" ${(normalizeRefAlertMode(r.alerta_modo || r.alert_mode) === 'both') ? 'selected' : ''}>* + color</option>
                </select>
              </div>
              <div class="col-3"><input type="color" class="form-control form-control-sm v2-ref-alert-color" value="${normalizeRefAlertColor(r.alerta_color || r.alert_color)}"></div>
              <div class="col-5"><input type="hidden" class="v2-ref-alert-texts" value="${normalizeRefAlertTexts(r.alerta_textos || r.alert_text_values || r.alerta_valores || '')}"><div class="small text-muted">Se sincroniza automaticamente desde opciones.</div></div>
            </div>
            <div class="row g-1 mt-1">
              <div class="col-12"><input type="hidden" class="v2-ref-alert-text-colors" value="${normalizeRefAlertTextColors(r.alerta_colores_texto || r.alert_text_colors || r.alerta_colores || '')}"><div class="v2-ref-alert-text-color-list border rounded p-2 bg-light"></div></div>
            </div>
          </div>`;
        }).join('');
        html += `<td>
          <div class="v2-ref-editor" data-col-id="${c.id}">
            ${rangesHtml}
            <button type="button" class="btn btn-outline-primary btn-sm v2-ref-add" data-col-id="${c.id}">+ Valor normal</button>
          </div>
          <div class="text-muted small mt-1">${buildV2ReferenceSummary(normalizedRanges) || 'Sin referencias dinámicas'}</div>
        </td>`;
      } else if (c.kind === 'long_text') {
        html += `<td>
          <textarea class="form-control form-control-sm v2-cell" data-col-id="${c.id}" rows="2" ${disabled ? 'disabled' : ''}>${cellValue}</textarea>
          ${c.editable ? `<input type="text" class="form-control form-control-sm v2-formula mt-1" data-col-id="${c.id}" placeholder="Formula: [fila:col] o [col]" value="${formulaValue}" ${row.type !== 'data' ? 'disabled' : ''}>` : ''}
        </td>`;
      } else {
        const listId = `v2_opts_${rowIdx}_${c.id}`;
        const dataListHtml = normalizedOptions.length > 0
          ? `<datalist id="${listId}">${normalizedOptions.map((opt) => `<option value="${String(opt).replace(/</g, '&lt;').replace(/>/g, '&gt;')}"></option>`).join('')}</datalist>`
          : '';
        html += `<td>
          ${shouldRenderSelect
            ? `<select class="form-select form-select-sm v2-cell" data-col-id="${c.id}" ${disabled ? 'disabled' : ''}>${optionsHtml}</select>`
            : `<input type="text" class="form-control form-control-sm v2-cell" data-col-id="${c.id}" value="${cellValue}" ${normalizedOptions.length > 0 ? `list="${listId}"` : ''} ${useStrictSelect && normalizedOptions.length === 0 ? 'placeholder="Define opciones abajo"' : ''} ${disabled ? 'disabled' : ''}>${dataListHtml}`
          }
          ${showOptionsEditor ? `<input type="text" class="form-control form-control-sm v2-cell-options mt-1" data-col-id="${c.id}" placeholder="Opciones (coma): amarillo, rojizo" value="${optionsText}" ${row.type !== 'data' ? 'disabled' : ''}>` : ''}
          ${decimalsAllowed ? `<input type="number" min="0" max="6" class="form-control form-control-sm v2-cell-decimales mt-1" data-col-id="${c.id}" placeholder="Decimales (0-6)" value="${decimalsValue}" ${row.type !== 'data' ? 'disabled' : ''}>` : ''}
          ${showFormulaEditor ? `<input type="text" class="form-control form-control-sm v2-formula mt-1" data-col-id="${c.id}" placeholder="Formula: [fila:col] o [col]" value="${formulaValue}" ${row.type !== 'data' ? 'disabled' : ''}>` : ''}
        </td>`;
      }
    });
    html += `<td>${moveActionsHtml}</td>`;
    tr.innerHTML = html;

    tr.querySelectorAll('.v2-ref-item').forEach((refItem) => {
      syncV2AlertVisualEditor(refItem, row);
    });

    tr.querySelector('.v2-row-id').addEventListener('input', (e) => {
      row.id = ensureUniqueRowId(e.target.value || 'fila', rowIdx);
      e.target.value = row.id;
      renderV2Preview();
    });
    tr.querySelector('.v2-row-type').addEventListener('change', (e) => {
      row.type = e.target.value;
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-row-label').addEventListener('input', (e) => {
      row.label = e.target.value;
      renderV2Preview();
    });
    tr.querySelectorAll('.v2-cell').forEach(cellInput => {
      cellInput.addEventListener('input', (e) => {
        const cid = e.target.getAttribute('data-col-id');
        if (!row.cells) row.cells = {};
        row.cells[cid] = e.target.value;
        renderV2Preview();
      });
      cellInput.addEventListener('change', (e) => {
        const cid = e.target.getAttribute('data-col-id');
        if (!row.cells) row.cells = {};
        row.cells[cid] = e.target.value;
        renderV2Preview();
      });
    });
    tr.querySelectorAll('.v2-cell-options').forEach(optionsInput => {
      optionsInput.addEventListener('input', (e) => {
        const cid = e.target.getAttribute('data-col-id');
        if (!row.select_options) row.select_options = {};
        row.select_options[cid] = String(e.target.value || '')
          .split(',')
          .map(opt => opt.trim())
          .filter(Boolean);
        const current = String((row.cells && row.cells[cid]) || '');
        const colCfg = visibleCols.find((vc) => vc.id === cid);
        const useStrictSelectCell = !!(colCfg && colCfg.kind === 'select');
        if (useStrictSelectCell) {
          if (row.select_options[cid].length > 0 && !row.select_options[cid].includes(current)) {
            row.cells[cid] = row.select_options[cid][0];
          }
          if (row.select_options[cid].length === 0) {
            row.cells[cid] = '';
          }
        }
        // Mantener foco al escribir: no re-renderizar toda la tabla en cada tecla.
        // El re-render completo se aplica al confirmar (change) para refrescar controles.
        tr.querySelectorAll('.v2-ref-item').forEach((refItem) => {
          syncV2AlertVisualEditor(refItem, row);
        });
        renderV2Preview();
      });

      optionsInput.addEventListener('change', () => {
        renderV2Rows();
        renderV2Preview();
      });
    });
    tr.querySelectorAll('.v2-cell-decimales').forEach(decInput => {
      decInput.addEventListener('input', (e) => {
        const cid = e.target.getAttribute('data-col-id');
        const raw = String(e.target.value || '').trim();
        if (!row.decimales) row.decimales = {};
        if (raw === '') {
          delete row.decimales[cid];
        } else {
          const parsed = parseInt(raw, 10);
          if (isFinite(parsed) && parsed >= 0 && parsed <= 6) {
            row.decimales[cid] = parsed;
          }
        }
        renderV2Preview();
      });
    });
    const syncRangesForColumn = (cid) => {
      const editor = tr.querySelector(`.v2-ref-editor[data-col-id="${cid}"]`);
      if (!editor) return;
      const items = Array.from(editor.querySelectorAll('.v2-ref-item'));
      if (!row.reference_ranges) row.reference_ranges = {};
      row.reference_ranges[cid] = items.map((it) => ({
        desc: String(it.querySelector('.v2-ref-desc')?.value || '').trim(),
        valor: String(it.querySelector('.v2-ref-valor')?.value || '').trim(),
        valor_min: String(it.querySelector('.v2-ref-min')?.value || '').trim(),
        valor_max: String(it.querySelector('.v2-ref-max')?.value || '').trim(),
        sexo: String(it.querySelector('.v2-ref-sexo')?.value || 'cualquiera').trim() || 'cualquiera',
        edad_min: String(it.querySelector('.v2-ref-edad-min')?.value || '').trim(),
        edad_max: String(it.querySelector('.v2-ref-edad-max')?.value || '').trim(),
        alerta_modo: normalizeRefAlertMode(it.querySelector('.v2-ref-alert-mode')?.value || REF_ALERT_DEFAULT_MODE),
        alerta_color: normalizeRefAlertColor(it.querySelector('.v2-ref-alert-color')?.value || REF_ALERT_DEFAULT_COLOR),
        alerta_textos: normalizeRefAlertTexts(it.querySelector('.v2-ref-alert-texts')?.value || ''),
        alerta_colores_texto: normalizeRefAlertTextColors(it.querySelector('.v2-ref-alert-text-colors')?.value || '')
      })).filter(r => r.desc || r.valor || r.valor_min || r.valor_max || r.edad_min || r.edad_max || (r.sexo && r.sexo !== 'cualquiera') || r.alerta_modo !== REF_ALERT_DEFAULT_MODE || String(r.alerta_color || '').toLowerCase() !== REF_ALERT_DEFAULT_COLOR || r.alerta_textos !== '' || r.alerta_colores_texto !== '');
      if (!row.cells) row.cells = {};
      row.cells[cid] = buildV2ReferenceSummary(row.reference_ranges[cid]);
      renderV2Preview();
    };

    tr.querySelectorAll('.v2-ref-add').forEach((btn) => {
      btn.addEventListener('click', () => {
        const cid = btn.getAttribute('data-col-id');
        if (!row.reference_ranges) row.reference_ranges = {};
        if (!Array.isArray(row.reference_ranges[cid])) row.reference_ranges[cid] = [];
        row.reference_ranges[cid].push({
          desc: '',
          valor: '',
          valor_min: '',
          valor_max: '',
          sexo: 'cualquiera',
          edad_min: '',
          edad_max: '',
          alerta_modo: REF_ALERT_DEFAULT_MODE,
          alerta_color: REF_ALERT_DEFAULT_COLOR,
          alerta_textos: '',
          alerta_colores_texto: ''
        });
        renderV2Rows();
        renderV2Preview();
      });
    });

    tr.querySelectorAll('.v2-ref-remove').forEach((btn) => {
      btn.addEventListener('click', () => {
        const item = btn.closest('.v2-ref-item');
        if (!item) return;
        const editor = btn.closest('.v2-ref-editor');
        if (!editor) return;
        const cid = editor.getAttribute('data-col-id');
        const idx = parseInt(item.getAttribute('data-range-index') || '-1', 10);
        if (!row.reference_ranges || !Array.isArray(row.reference_ranges[cid])) return;
        if (idx >= 0 && idx < row.reference_ranges[cid].length) {
          row.reference_ranges[cid].splice(idx, 1);
        }
        if (!row.cells) row.cells = {};
        row.cells[cid] = buildV2ReferenceSummary(row.reference_ranges[cid]);
        renderV2Rows();
        renderV2Preview();
      });
    });

    tr.querySelectorAll('.v2-ref-item input, .v2-ref-item select').forEach((ctrl) => {
      ctrl.addEventListener('input', () => {
        const refItem = ctrl.closest('.v2-ref-item');
        if (refItem && (ctrl.classList.contains('v2-ref-alert-mode') || ctrl.classList.contains('v2-ref-alert-color'))) {
          syncV2AlertVisualEditor(refItem, row);
        }
        const editor = ctrl.closest('.v2-ref-editor');
        if (!editor) return;
        const cid = editor.getAttribute('data-col-id');
        syncRangesForColumn(cid);
      });
      ctrl.addEventListener('change', () => {
        const refItem = ctrl.closest('.v2-ref-item');
        if (refItem && (ctrl.classList.contains('v2-ref-alert-mode') || ctrl.classList.contains('v2-ref-alert-color'))) {
          syncV2AlertVisualEditor(refItem, row);
        }
        const editor = ctrl.closest('.v2-ref-editor');
        if (!editor) return;
        const cid = editor.getAttribute('data-col-id');
        syncRangesForColumn(cid);
      });
    });

    tr.querySelectorAll('.v2-formula').forEach(formulaInput => {
      formulaInput.addEventListener('input', (e) => {
        const cid = e.target.getAttribute('data-col-id');
        if (!row.formulas) row.formulas = {};
        row.formulas[cid] = e.target.value;
        renderV2Preview();
      });
    });
    tr.querySelector('.v2-row-up')?.addEventListener('click', () => {
      if (rowIdx === 0) return;
      const tmp = v2State.rows[rowIdx - 1];
      v2State.rows[rowIdx - 1] = v2State.rows[rowIdx];
      v2State.rows[rowIdx] = tmp;
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-row-down')?.addEventListener('click', () => {
      if (rowIdx >= v2State.rows.length - 1) return;
      const tmp = v2State.rows[rowIdx + 1];
      v2State.rows[rowIdx + 1] = v2State.rows[rowIdx];
      v2State.rows[rowIdx] = tmp;
      renderV2Rows();
      renderV2Preview();
    });
    tr.querySelector('.v2-row-del').addEventListener('click', () => {
      v2State.rows.splice(rowIdx, 1);
      renderV2Rows();
      renderV2Preview();
    });

    tbody.appendChild(tr);
  });
}

function renderV2Preview() {
  const preview = document.getElementById('previewV2');
  if (!preview) return;

  const cols = v2State.columns.filter(c => c.visible_capture !== false);
  let html = '<table class="table table-bordered table-sm"><thead><tr>';
  cols.forEach(c => {
    html += `<th>${c.label || c.id}</th>`;
  });
  html += '</tr></thead><tbody>';

  v2State.rows.forEach(r => {
    if (String(r.type || '').toLowerCase() === 'long_text') {
      const title = String(r.label || '').trim();
      const body = String(r.template_text || '').trim();
      const tAlign = ['left', 'center', 'right'].includes(String(r.template_align || '').toLowerCase())
        ? String(r.template_align || '').toLowerCase()
        : 'left';
      const textColor = String(r.color_texto || '').trim() || '#1f2d5c';
      const bgColor = String(r.color_fondo || '').trim() || '#fafcff';
      const fw = r.negrita ? 'bold' : 'normal';
      const fs = r.cursiva ? 'italic' : 'normal';
      const safeTitle = title.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const safeBody = body.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      let blockHtml = '';
      if (safeTitle) {
        blockHtml += `<div style="font-weight:${fw}; font-style:${fs}; margin-bottom:4px;">${safeTitle}</div>`;
      }
      if (safeBody) {
        blockHtml += `<div style="white-space:pre-wrap; font-weight:normal; font-style:normal;">${safeBody}</div>`;
      }
      html += `<tr><td colspan="${Math.max(1, cols.length)}" style="background:${bgColor}; color:${textColor}; text-align:${tAlign};">${blockHtml}</td></tr>`;
      return;
    }
    if (r.type === 'title' || r.type === 'subtitle') {
      const textColor = String(r.color_texto || '').trim() || '#1f2d5c';
      const bgColor = String(r.color_fondo || '').trim() || (r.type === 'title' ? '#eef4ff' : '#f4f7fb');
      const align = ['left', 'center', 'right'].includes(String(r.alineacion || '').toLowerCase())
        ? String(r.alineacion || '').toLowerCase()
        : (r.type === 'title' ? 'center' : 'left');
      const fw = r.negrita ? 'bold' : 'normal';
      const fs = r.cursiva ? 'italic' : 'normal';
      html += `<tr><td colspan="${Math.max(1, cols.length)}" style="color:${textColor}; background:${bgColor}; text-align:${align}; font-weight:${fw}; font-style:${fs};">${String(r.label || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td></tr>`;
      return;
    }
    html += '<tr>';
    cols.forEach(c => {
      const val = r.cells && Object.prototype.hasOwnProperty.call(r.cells, c.id) ? r.cells[c.id] : '';
      const fx = r.formulas && Object.prototype.hasOwnProperty.call(r.formulas, c.id) ? String(r.formulas[c.id] || '').trim() : '';
      const opts = r.select_options && Object.prototype.hasOwnProperty.call(r.select_options, c.id)
        ? (Array.isArray(r.select_options[c.id]) ? r.select_options[c.id] : [])
        : [];
      const ranges = r.reference_ranges && Object.prototype.hasOwnProperty.call(r.reference_ranges, c.id)
        ? (Array.isArray(r.reference_ranges[c.id]) ? r.reference_ranges[c.id] : [])
        : [];
      const safeVal = String(val || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const safeFx = fx.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      if (c.kind === 'reference' && ranges.length > 0) {
        const lines = ranges.map((r) => {
          const text = buildV2ReferenceSummary([r]);
          return `<div class="small">${String(text).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>`;
        }).join('');
        html += `<td>${lines}</td>`;
      } else if (c.kind === 'select' && opts.length > 0) {
        const optionsHtml = opts.map((opt) => {
          const selected = String(val) === String(opt) ? ' selected' : '';
          return `<option value="${String(opt).replace(/</g, '&lt;').replace(/>/g, '&gt;')}"${selected}>${String(opt).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</option>`;
        }).join('');
        html += `<td><select class="form-select form-select-sm" disabled>${optionsHtml}</select>${safeFx ? `<div class="text-muted small">fx: ${safeFx}</div>` : ''}</td>`;
      } else if (c.kind === 'select') {
        html += `<td><span class="text-muted">Sin opciones definidas</span>${safeFx ? `<div class="text-muted small">fx: ${safeFx}</div>` : ''}</td>`;
      } else {
        html += `<td>${safeVal}${safeFx ? `<div class="text-muted small">fx: ${safeFx}</div>` : ''}</td>`;
      }
    });
    html += '</tr>';
  });

  html += '</tbody></table>';
  preview.innerHTML = html;
}

function resetV2State() {
  v2State.columns = [];
  v2State.rows = [];
}

function buildLegacyReferenciaText(item) {
  const refs = Array.isArray(item && item.referencias) ? item.referencias : [];
  if (refs.length === 0) return '';
  const parts = refs.map(ref => {
    if (!ref || typeof ref !== 'object') {
      return String(ref || '').trim();
    }
    const desc = String(ref.desc || '').trim();
    const valor = String(ref.valor || '').trim();
    const rango = [];
    if (ref.valor_min !== undefined && ref.valor_min !== '') rango.push(String(ref.valor_min));
    if (ref.valor_max !== undefined && ref.valor_max !== '') rango.push(String(ref.valor_max));
    let extra = '';
    if (rango.length > 0) {
      extra = ` (${rango.join(' - ')})`;
    }
    if (desc && valor) return `${desc}: ${valor}${extra}`;
    return `${valor}${extra}`.trim();
  }).filter(Boolean);
  return parts.join(' | ');
}

function migrateLegacyToV2(legacyItems) {
  const source = Array.isArray(legacyItems) ? legacyItems : [];
  if (source.length === 0) {
    return false;
  }

  resetV2State();
  addV2Column({ label: 'Parametro', id: 'parametro', kind: 'text', editable: false, visible_capture: true, visible_pdf: true });
  addV2Column({ label: 'Metodologia', id: 'metodologia', kind: 'text', editable: false, visible_capture: true, visible_pdf: true });
  addV2Column({ label: 'Resultado', id: 'resultado', kind: 'result', editable: true, visible_capture: true, visible_pdf: true });
  addV2Column({ label: 'Unidades', id: 'unidad', kind: 'text', editable: false, visible_capture: true, visible_pdf: true });
  addV2Column({ label: 'Valores referenciales', id: 'referencia', kind: 'reference', editable: false, visible_capture: true, visible_pdf: true });

  const nameToRowId = new Map();
  const migratedRows = [];

  source.forEach((item, idx) => {
    if (!item || typeof item !== 'object') return;
    const tipo = String(item.tipo || '').trim();
    const nombre = String(item.nombre || '').trim();

    if (tipo === 'Título') {
      addV2DataRow({
        id: `titulo_${idx + 1}`,
        type: 'title',
        label: nombre,
        color_texto: String(item.color_texto || '').trim(),
        color_fondo: String(item.color_fondo || '').trim(),
        negrita: !!item.negrita,
        cursiva: !!item.cursiva,
        alineacion: String(item.alineacion || '').trim()
      });
      return;
    }
    if (tipo === 'Subtítulo') {
      addV2DataRow({
        id: `subtitulo_${idx + 1}`,
        type: 'subtitle',
        label: nombre,
        color_texto: String(item.color_texto || '').trim(),
        color_fondo: String(item.color_fondo || '').trim(),
        negrita: !!item.negrita,
        cursiva: !!item.cursiva,
        alineacion: String(item.alineacion || '').trim()
      });
      return;
    }
    if (tipo === 'Texto Largo') {
      addV2DataRow({
        id: `texto_${idx + 1}`,
        type: 'long_text',
        label: nombre,
        template_text: String(item.template_text ?? item.valor ?? ''),
        template_editable: true,
        template_visible_pdf: true,
        template_align: String(item.alineacion || 'left').trim().toLowerCase(),
        color_texto: String(item.color_texto || '').trim(),
        color_fondo: String(item.color_fondo || '').trim(),
        negrita: !!item.negrita,
        cursiva: !!item.cursiva
      });
      return;
    }

    if (!['Parámetro', 'Campo'].includes(tipo)) {
      return;
    }

    const rowIdBase = item.id_parametro ? String(item.id_parametro) : `fila_${idx + 1}`;
    const referenciaTxt = buildLegacyReferenciaText(item);
    const rowData = {
      id: rowIdBase,
      type: 'data',
      cells: {
        parametro: nombre,
        metodologia: String(item.metodologia || ''),
        resultado: '',
        unidad: String(item.unidad || ''),
        referencia: referenciaTxt
      },
      select_options: {
        resultado: Array.isArray(item.opciones)
          ? item.opciones.map(o => String(o || '').trim()).filter(Boolean)
          : []
      },
      reference_ranges: {
        referencia: Array.isArray(item.referencias)
          ? item.referencias.map((ref) => ({
              desc: String((ref && ref.desc) || '').trim(),
              valor: String((ref && ref.valor) || '').trim(),
              valor_min: String((ref && ref.valor_min) || '').trim(),
              valor_max: String((ref && ref.valor_max) || '').trim(),
              sexo: String((ref && ref.sexo) || 'cualquiera').trim() || 'cualquiera',
              edad_min: String((ref && ref.edad_min) || '').trim(),
              edad_max: String((ref && ref.edad_max) || '').trim(),
              alerta_modo: normalizeRefAlertMode((ref && (ref.alerta_modo ?? ref.alert_mode)) || REF_ALERT_DEFAULT_MODE),
              alerta_color: normalizeRefAlertColor((ref && (ref.alerta_color ?? ref.alert_color)) || REF_ALERT_DEFAULT_COLOR),
              alerta_textos: normalizeRefAlertTexts((ref && (ref.alerta_textos ?? ref.alert_text_values ?? ref.alerta_valores)) || ''),
              alerta_colores_texto: normalizeRefAlertTextColors((ref && (ref.alerta_colores_texto ?? ref.alert_text_colors ?? ref.alerta_colores)) || '')
            })).filter(r => r.desc || r.valor || r.valor_min || r.valor_max || r.edad_min || r.edad_max || (r.sexo && r.sexo !== 'cualquiera') || r.alerta_modo !== REF_ALERT_DEFAULT_MODE || String(r.alerta_color || '').toLowerCase() !== REF_ALERT_DEFAULT_COLOR || r.alerta_textos !== '' || r.alerta_colores_texto !== '')
          : []
      },
      formulas: {
        resultado: String(item.formula || '').trim()
      },
      decimales: {
        resultado: (item.decimales !== '' && item.decimales !== null && item.decimales !== undefined && isFinite(parseInt(item.decimales, 10)))
          ? parseInt(item.decimales, 10)
          : undefined
      }
    };
    if (Array.isArray(rowData.select_options.resultado) && rowData.select_options.resultado.length > 0) {
      rowData.cells.resultado = rowData.select_options.resultado[0];
    }
    addV2DataRow(rowData);
    migratedRows.push({ legacy: item, rowId: rowIdBase, nombre });
    if (nombre) {
      nameToRowId.set(nombre.toLowerCase(), rowIdBase);
    }
  });

  // Convertir formulas legacy [Nombre] -> [rowId:resultado] en filas migradas.
  v2State.rows.forEach((row) => {
    if (!row || !row.formulas || typeof row.formulas !== 'object') return;
    const currentFormula = String(row.formulas.resultado || '').trim();
    if (!currentFormula) return;
    const converted = currentFormula.replace(/\[([^\]]+)\]/g, (_, tokenRaw) => {
      const token = String(tokenRaw || '').trim();
      const rid = nameToRowId.get(token.toLowerCase());
      if (!rid) return `[${token}]`;
      return `[${rid}:resultado]`;
    });
    row.formulas.resultado = converted;
  });

  renderV2Columns();
  renderV2Rows();
  renderV2Preview();
  clearV2ValidationAlert();
  return true;
}

function buildV2Payload() {
  const cols = v2State.columns.map((c, idx) => ({
    id: String(c.id || '').trim(),
    label: String(c.label || '').trim() || String(c.id || '').trim(),
    kind: String(c.kind || 'text').trim(),
    editable: !!c.editable,
    visible_capture: c.visible_capture !== false,
    visible_pdf: c.visible_pdf !== false,
    width: String(c.width || '').trim(),
    order: idx + 1
  })).filter(c => c.id !== '');

  const rows = v2State.rows.map(r => {
    const outCells = {};
    const outFormulas = {};
    const outSelectOptions = {};
    const outReferenceRanges = {};
    const outDecimales = {};
    cols.forEach(c => {
      const val = (r.cells && Object.prototype.hasOwnProperty.call(r.cells, c.id)) ? r.cells[c.id] : '';
      outCells[c.id] = val;

      const fx = (r.formulas && Object.prototype.hasOwnProperty.call(r.formulas, c.id))
        ? String(r.formulas[c.id] || '').trim()
        : '';
      if (fx !== '') {
        outFormulas[c.id] = fx;
      }

      const rawOpts = (r.select_options && Object.prototype.hasOwnProperty.call(r.select_options, c.id))
        ? r.select_options[c.id]
        : [];
      const opts = Array.isArray(rawOpts)
        ? rawOpts.map(o => String(o || '').trim()).filter(Boolean)
        : [];
      if (opts.length > 0) {
        outSelectOptions[c.id] = opts;
      }

      const rawRanges = (r.reference_ranges && Object.prototype.hasOwnProperty.call(r.reference_ranges, c.id))
        ? r.reference_ranges[c.id]
        : [];
      const ranges = Array.isArray(rawRanges)
        ? rawRanges.map((rr) => ({
            desc: String((rr && rr.desc) || '').trim(),
            valor: String((rr && rr.valor) || '').trim(),
            valor_min: String((rr && rr.valor_min) || '').trim(),
            valor_max: String((rr && rr.valor_max) || '').trim(),
            sexo: String((rr && rr.sexo) || 'cualquiera').trim() || 'cualquiera',
            edad_min: String((rr && rr.edad_min) || '').trim(),
            edad_max: String((rr && rr.edad_max) || '').trim(),
            alerta_modo: normalizeRefAlertMode((rr && (rr.alerta_modo ?? rr.alert_mode)) || REF_ALERT_DEFAULT_MODE),
            alerta_color: normalizeRefAlertColor((rr && (rr.alerta_color ?? rr.alert_color)) || REF_ALERT_DEFAULT_COLOR),
            alerta_textos: normalizeRefAlertTexts((rr && (rr.alerta_textos ?? rr.alert_text_values ?? rr.alerta_valores)) || ''),
            alerta_colores_texto: normalizeRefAlertTextColors((rr && (rr.alerta_colores_texto ?? rr.alert_text_colors ?? rr.alerta_colores)) || '')
          })).filter(rr => rr.desc || rr.valor || rr.valor_min || rr.valor_max || rr.edad_min || rr.edad_max || (rr.sexo && rr.sexo !== 'cualquiera') || rr.alerta_modo !== REF_ALERT_DEFAULT_MODE || String(rr.alerta_color || '').toLowerCase() !== REF_ALERT_DEFAULT_COLOR || rr.alerta_textos !== '' || rr.alerta_colores_texto !== '')
        : [];
      if (ranges.length > 0) {
        outReferenceRanges[c.id] = ranges;
      }

      const rawDec = (r.decimales && Object.prototype.hasOwnProperty.call(r.decimales, c.id))
        ? r.decimales[c.id]
        : null;
      if (rawDec !== '' && rawDec !== null && rawDec !== undefined && isFinite(parseInt(rawDec, 10))) {
        const parsedDec = parseInt(rawDec, 10);
        if (parsedDec >= 0 && parsedDec <= 6) {
          outDecimales[c.id] = parsedDec;
        }
      }
    });
    return {
      id: String(r.id || '').trim(),
      type: String(r.type || 'data').trim(),
      label: String(r.label || ''),
      template_text: String(r.template_text || ''),
      template_editable: r.template_editable !== false,
      template_visible_pdf: r.template_visible_pdf !== false,
      template_align: ['left', 'center', 'right'].includes(String(r.template_align || '').toLowerCase()) ? String(r.template_align || '').toLowerCase() : 'left',
      color_texto: String(r.color_texto || '').trim(),
      color_fondo: String(r.color_fondo || '').trim(),
      negrita: !!r.negrita,
      cursiva: !!r.cursiva,
      alineacion: ['left', 'center', 'right'].includes(String(r.alineacion || '').toLowerCase()) ? String(r.alineacion || '').toLowerCase() : (String(r.type || '') === 'title' ? 'center' : 'left'),
      cells: outCells,
      formulas: outFormulas,
      select_options: outSelectOptions,
      reference_ranges: outReferenceRanges,
      decimales: outDecimales
    };
  }).filter(r => r.id !== '' || r.type !== 'data');

  return {
    schema_version: 2,
    layout: {
      columns: cols,
      rows
    }
  };
}

function setBuilderMode(isV2) {
  const legacy = document.getElementById('legacyBuilderSection');
  const v2 = document.getElementById('v2BuilderSection');
  if (legacy) legacy.style.display = isV2 ? 'none' : '';
  if (v2) v2.style.display = isV2 ? '' : 'none';
  if (!isV2) {
    clearV2ValidationAlert();
  }
}

function clearV2ValidationAlert() {
  const alertBox = document.getElementById('v2ValidationAlert');
  if (!alertBox) return;
  alertBox.style.display = 'none';
  alertBox.innerHTML = '';
}

function showV2ValidationAlert(errors) {
  const alertBox = document.getElementById('v2ValidationAlert');
  if (!alertBox) return;
  const items = Array.isArray(errors) ? errors : [String(errors || 'Error de validacion')];
  const list = items.map(e => `<li>${String(e).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</li>`).join('');
  alertBox.innerHTML = `<strong>Corrige el formato dinamico antes de guardar:</strong><ul class="mb-0">${list}</ul>`;
  alertBox.style.display = '';
  alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function validateV2Payload(payload) {
  const errors = [];
  if (!payload || typeof payload !== 'object') {
    return ['No se pudo construir el formato v2.'];
  }

  if (Number(payload.schema_version || 0) < 2) {
    errors.push('La version de esquema v2 es invalida.');
  }

  const layout = payload.layout || {};
  const columns = Array.isArray(layout.columns) ? layout.columns : [];
  const rows = Array.isArray(layout.rows) ? layout.rows : [];

  if (columns.length === 0) {
    errors.push('Debes registrar al menos una columna.');
  }

  const seenIds = new Set();
  let visibleCaptureCount = 0;
  let editableCount = 0;
  columns.forEach((c, idx) => {
    const cid = String(c.id || '').trim();
    const label = String(c.label || '').trim();
    if (!cid) {
      errors.push(`La columna #${idx + 1} no tiene ID.`);
    }
    if (!label) {
      errors.push(`La columna #${idx + 1} no tiene etiqueta.`);
    }
    if (cid) {
      if (seenIds.has(cid)) {
        errors.push(`El ID de columna '${cid}' esta duplicado.`);
      }
      seenIds.add(cid);
    }
    if (c.visible_capture !== false) visibleCaptureCount++;
    if (c.editable === true) editableCount++;
  });

  if (visibleCaptureCount === 0) {
    errors.push('Debe existir al menos una columna visible en captura.');
  }

  if (rows.length === 0) {
    errors.push('Debes registrar al menos una fila.');
  }

  let dataRows = 0;
  let editableLongTextRows = 0;
  rows.forEach((r, idx) => {
    const rid = String(r.id || '').trim();
    const type = String(r.type || 'data').trim();
    const cells = r.cells && typeof r.cells === 'object' ? r.cells : {};

    if (type === 'data') {
      dataRows++;
      if (!rid) {
        errors.push(`La fila de datos #${idx + 1} no tiene ID.`);
      }
    }

    if ((type === 'title' || type === 'subtitle') && !String(r.label || '').trim()) {
      errors.push(`La fila #${idx + 1} de tipo ${type} debe tener etiqueta.`);
    }

    if (type === 'long_text' && !String(r.id || '').trim()) {
      errors.push(`La fila #${idx + 1} de tipo long_text debe tener ID.`);
    }
    if (type === 'long_text') {
      const editableTemplate = !Object.prototype.hasOwnProperty.call(r, 'template_editable') || !!r.template_editable;
      if (editableTemplate) {
        editableLongTextRows++;
      }
    }

    Object.keys(cells).forEach(cellColId => {
      if (!seenIds.has(cellColId)) {
        errors.push(`La fila #${idx + 1} contiene una celda con columna inexistente: '${cellColId}'.`);
      }
    });

    const formulas = r.formulas && typeof r.formulas === 'object' ? r.formulas : {};
    Object.entries(formulas).forEach(([colId, expr]) => {
      const fx = String(expr || '').trim();
      if (!fx) return;
      if (!seenIds.has(colId)) {
        errors.push(`La fila #${idx + 1} tiene formula para columna inexistente: '${colId}'.`);
        return;
      }
      const open = (fx.match(/\[/g) || []).length;
      const close = (fx.match(/\]/g) || []).length;
      if (open !== close) {
        errors.push(`La fila #${idx + 1} tiene formula invalida (corchetes desbalanceados) en columna '${colId}'.`);
      }
      const hasToken = /\[[^\]]+\]/.test(fx);
      if (!hasToken) {
        errors.push(`La fila #${idx + 1} en columna '${colId}' debe referenciar al menos un token tipo [fila:col] o [col].`);
      }
    });

    const decimales = r.decimales && typeof r.decimales === 'object' ? r.decimales : {};
    Object.entries(decimales).forEach(([colId, rawDec]) => {
      if (!seenIds.has(colId)) {
        errors.push(`La fila #${idx + 1} tiene decimales para columna inexistente: '${colId}'.`);
        return;
      }
      if (rawDec === '' || rawDec === null || rawDec === undefined) {
        return;
      }
      const parsed = parseInt(rawDec, 10);
      if (!isFinite(parsed) || parsed < 0 || parsed > 6) {
        errors.push(`La fila #${idx + 1} en columna '${colId}' tiene decimales invalidos. Usa un entero entre 0 y 6.`);
      }
    });
  });

  if (dataRows === 0) {
    errors.push('Debes tener al menos una fila de tipo data.');
  }

  if (editableCount === 0 && editableLongTextRows === 0) {
    errors.push('Debe existir al menos un campo editable para ingresar resultados.');
  }

  return errors;
}

window.initExamFormatBuilder = function(datosAdicionales) {
  const modeSwitch = document.getElementById('formatModeV2');
  const btnAddCol = document.getElementById('v2AddColumn');
  const btnAddRowV2 = document.getElementById('v2AddRow');
  const btnMigrateLegacy = document.getElementById('v2MigrateLegacy');

  const hasLegacySource = Array.isArray(datosAdicionales) && datosAdicionales.length > 0;

  if (btnAddCol) {
    btnAddCol.addEventListener('click', () => {
      addV2Column();
      renderV2Columns();
      renderV2Rows();
      renderV2Preview();
    });
  }
  if (btnAddRowV2) {
    btnAddRowV2.addEventListener('click', () => {
      addV2DataRow();
      renderV2Rows();
      renderV2Preview();
    });
  }

  if (btnMigrateLegacy) {
    btnMigrateLegacy.style.display = hasLegacySource ? '' : 'none';
    btnMigrateLegacy.addEventListener('click', () => {
      const ok = migrateLegacyToV2(datosAdicionales);
      if (ok && modeSwitch) {
        modeSwitch.checked = true;
        setBuilderMode(true);
      }
    });
  }

  const isV2Data = !!(datosAdicionales && !Array.isArray(datosAdicionales) && Number(datosAdicionales.schema_version || 0) >= 2 && datosAdicionales.layout);
  if (isV2Data) {
    const layout = datosAdicionales.layout || {};
    const cols = Array.isArray(layout.columns) ? [...layout.columns] : [];
    cols.sort((a, b) => (Number(a.order || 0) - Number(b.order || 0)));
    cols.forEach(c => addV2Column(c));

    const rows = Array.isArray(layout.rows) ? layout.rows : [];
    rows.forEach(r => addV2DataRow(r));

    if (modeSwitch) modeSwitch.checked = true;
  } else if (Array.isArray(datosAdicionales) && datosAdicionales.length > 0) {
    datosAdicionales.forEach(parametro => addRow(parametro));
  } else {
    addRow();
    addV2Column({ label: 'Parámetro', id: 'parametro', kind: 'text', editable: false });
    addV2Column({ label: 'Resultado', id: 'resultado', kind: 'result', editable: true });
    addV2Column({ label: 'Unidad', id: 'unidad', kind: 'text', editable: false });
    addV2DataRow({
      id: 'fila_1',
      type: 'data',
      cells: { parametro: '', resultado: '', unidad: '' }
    });
  }

  renderV2Columns();
  renderV2Rows();
  renderV2Preview();
  setBuilderMode(modeSwitch ? modeSwitch.checked : false);

  if (modeSwitch) {
    modeSwitch.addEventListener('change', function() {
      setBuilderMode(this.checked);
    });
  }
};

// Serialización del formato antes de enviar el formulario
document.getElementById('form-examen').addEventListener('submit', function(e) {
  const modeSwitch = document.getElementById('formatModeV2');
  if (modeSwitch && modeSwitch.checked) {
    const payload = buildV2Payload();
    const errors = validateV2Payload(payload);
    if (errors.length > 0) {
      e.preventDefault();
      showV2ValidationAlert(errors);
      return;
    }
    clearV2ValidationAlert();
    document.getElementById('adicional').value = JSON.stringify(payload);
    return;
  }

  const tbody = document.querySelector('#formatTable tbody');
  let rows = Array.from(tbody.querySelectorAll('tr'));
  let formato = rows.map(tr => {
  const tipoFila = tr.querySelector('.type-select').value;
    let referencias = [];
    const refGroups = tr.children[5].querySelectorAll('.valores-ref-group');
    refGroups.forEach(refDiv => {
      const valor = refDiv.querySelector('.valor').value;
      const desc = refDiv.querySelector('.desc').value;
      // Normalizar decimales con coma a punto al serializar
      const valor_min = refDiv.querySelector('.valor-min').value.replace(',', '.');
      const valor_max = refDiv.querySelector('.valor-max').value.replace(',', '.');
      const sexo = refDiv.querySelector('.sexo-ref').value;
      const edad_min = refDiv.querySelector('.edad-min').value;
      const edad_max = refDiv.querySelector('.edad-max').value;
      const alerta_modo = normalizeRefAlertMode(refDiv.querySelector('.alerta-modo-ref')?.value || REF_ALERT_DEFAULT_MODE);
      const alerta_color = normalizeRefAlertColor(refDiv.querySelector('.alerta-color-ref')?.value || REF_ALERT_DEFAULT_COLOR);
      const alerta_textos = normalizeRefAlertTexts(refDiv.querySelector('.alerta-textos-ref')?.value || '');
      const alerta_colores_texto = normalizeRefAlertTextColors(refDiv.querySelector('.alerta-colores-texto-ref')?.value || '');
      if (valor || desc || valor_min || valor_max || edad_min || edad_max || (sexo && sexo !== 'cualquiera') || alerta_modo !== REF_ALERT_DEFAULT_MODE || String(alerta_color || '').toLowerCase() !== REF_ALERT_DEFAULT_COLOR || alerta_textos !== '' || alerta_colores_texto !== '') {
        referencias.push({ valor, desc, valor_min, valor_max, sexo, edad_min, edad_max, alerta_modo, alerta_color, alerta_textos, alerta_colores_texto });
      }
    });
    let opciones = tr.children[4].querySelector('.opciones-input').value
      .split(',')
      .map(o => o.trim())
      .filter(o => o);

    // Generar id_parametro único
    let id_parametro = tr.getAttribute('data-id-parametro');
    if (!id_parametro) {
      id_parametro = 'param_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
      tr.setAttribute('data-id-parametro', id_parametro);
    }

    const formulaRaw = tr.children[6].querySelector('.formula-input')?.value || '';
    const templateTextRaw = tr.children[6].querySelector('.template-text-input')?.value || '';

    return {
      id_parametro: id_parametro,
      tipo: tipoFila,
      nombre: tr.children[1].querySelector('textarea').value,
      metodologia: tr.children[2].querySelector('input').value,
      unidad: tr.children[3].querySelector('input').value,
      opciones: opciones,
      referencias: referencias,
      formula: tipoFila === 'Texto Largo' ? '' : formulaRaw,
      template_text: tipoFila === 'Texto Largo' ? templateTextRaw : '',
      negrita: tr.children[7].querySelector('input').checked,
      cursiva: tr.children[8].querySelector('input').checked,
      alineacion: tr.children[9].querySelector('select').value,
      color_texto: tr.children[10].querySelector('input').value,
      color_fondo: tr.children[11].querySelector('input').value,
      decimales: tr.children[12].querySelector('input').value !== '' ? parseInt(tr.children[12].querySelector('input').value) : undefined,
      rows: tr.children[13].querySelector('input') ? parseInt(tr.children[13].querySelector('input').value) : undefined,
      orden: parseInt(tr.querySelector('.orden-fija').textContent) || 0
    };
  });

  document.getElementById('adicional').value = JSON.stringify(formato);
  // El formulario se enviará normalmente
});
// Mantener columnas visibles y deshabilitar las que no aplican para 'Texto Largo'
function updateRowUI(tr) {
  const tipo = tr.querySelector('.type-select').value;
  const isLongText = tipo === 'Texto Largo';
  // Índices: 0 Tipo,1 Nombre,2 Metod,3 Unidad,4 Opciones,5 Referencias,6 Fórmula,7 Negrita,8 Cursiva,9 Alineación,10 Color texto,11 Color fondo,12 Decimales,13 Filas,14 Orden,15 Acciones
  const indicesParaDeshabilitar = [2, 3, 4, 5, 12];
  indicesParaDeshabilitar.forEach(idx => {
    const td = tr.children[idx];
    if (!td) return;
    td.style.display = ''; // nunca ocultar
    td.querySelectorAll('input,textarea,select,button').forEach(ctrl => {
      ctrl.disabled = isLongText;
      // Para inputs de texto, mostrar pista cuando están deshabilitados
      if (isLongText && ctrl.tagName === 'INPUT' && ctrl.type === 'text') {
        ctrl.placeholder = ctrl.placeholder || 'No aplica';
      }
    });
  });
  // Filas visible y habilitado solo para Texto Largo
  const tdRows = tr.children[13];
  if (tdRows) {
    tdRows.style.display = '';
    tdRows.querySelectorAll('input').forEach(ctrl => ctrl.disabled = !isLongText);
  }

  // En Texto Largo se usa plantilla precargada (textarea) en vez de fórmula.
  const formulaInput = tr.querySelector('.formula-input');
  const templateInput = tr.querySelector('.template-text-input');
  if (formulaInput && templateInput) {
    if (isLongText) {
      formulaInput.classList.add('d-none');
      formulaInput.disabled = true;
      templateInput.classList.remove('d-none');
      templateInput.disabled = false;
    } else {
      formulaInput.classList.remove('d-none');
      formulaInput.disabled = false;
      templateInput.classList.add('d-none');
      templateInput.disabled = true;
    }
  }
}
// Panel flotante para seleccionar parámetros y operadores en la fórmula
function createFormulaPanel(paramNames, formulaInput) {
  let oldPanel = document.getElementById('formula-panel');
  if (oldPanel) oldPanel.remove();

  const panel = document.createElement('div');
  panel.id = 'formula-panel';

  // Botones de parámetros
  paramNames.forEach(name => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-secondary btn-sm';
    btn.textContent = name;
    btn.onmousedown = function(ev) {
      ev.preventDefault();
      insertAtCaret(formulaInput, `[${name}]`);
    };
    panel.appendChild(btn);
  });

  // Botones de operadores
  ['+', '-', '*', '/','^', '(', ')'].forEach(op => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-primary btn-sm';
    btn.textContent = op;
    btn.onmousedown = function(ev) {
      ev.preventDefault();
      insertAtCaret(formulaInput, op);
    };
    panel.appendChild(btn);
  });

  // Botón cerrar
  const btnClose = document.createElement('button');
  btnClose.type = 'button';
  btnClose.className = 'btn btn-danger btn-sm ms-auto';
  btnClose.textContent = 'Cerrar';
  btnClose.onclick = () => panel.remove();
  panel.appendChild(btnClose);

  document.body.appendChild(panel);

  // Posiciona el panel junto al input
  const rect = formulaInput.getBoundingClientRect();
  panel.style.top = `${window.scrollY + rect.bottom + 2}px`;
  panel.style.left = `${window.scrollX + rect.left}px`;
}

// Utilidad para insertar texto en el input donde está el cursor
function insertAtCaret(input, text) {
  const start = input.selectionStart;
  const end = input.selectionEnd;
  const value = input.value;
  input.value = value.substring(0, start) + text + value.substring(end);
  input.selectionStart = input.selectionEnd = start + text.length;
  input.focus();
  updatePreview();
}

// Evento para mostrar el panel al enfocar el input de fórmula
document.addEventListener('focusin', function(e) {
  if (
    e.target &&
    e.target.classList.contains('formula-input')
  ) {
    const tbody = document.querySelector('#formatTable tbody');
    let paramNames = [];
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
      const tipo = tr.querySelector('.type-select').value;
      const nombre = tr.children[1].querySelector('textarea').value;
      if (tipo === 'Parámetro' && nombre) paramNames.push(nombre);
    });
    createFormulaPanel(paramNames, e.target);
  } else {
    let oldPanel = document.getElementById('formula-panel');
    if (oldPanel) oldPanel.remove();
  }
});
