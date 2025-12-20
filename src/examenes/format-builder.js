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

function createRefGroup(valor = '', desc = '', valor_min = '', valor_max = '', sexo = 'cualquiera', edad_min = '', edad_max = '') {
  const div = document.createElement('div');
  // Normalizar decimales a punto
  let valorMinStr = valor_min !== '' ? valor_min.toString().replace(',', '.') : '';
  let valorMaxStr = valor_max !== '' ? valor_max.toString().replace(',', '.') : '';
  valorMinStr = valorMinStr !== '' ? parseFloat(valorMinStr).toString() : '';
  valorMaxStr = valorMaxStr !== '' ? parseFloat(valorMaxStr).toString() : '';
  const showAdv = (valorMinStr !== '' || valorMaxStr !== '' || (sexo && sexo !== 'cualquiera') || (edad_min !== '' || edad_max !== ''));
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
        <input type="number" step="0.01" class="form-control form-control-sm valor-min" placeholder="Min" value="${valorMinStr}">
      </div>
      <div class="field">
        <label class="label-sm">Max</label>
        <input type="number" step="0.01" class="form-control form-control-sm valor-max" placeholder="Max" value="${valorMaxStr}">
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
    </div>
  `;
  return div;
}





function addRow(data = {}) {
  // ...existing code...
  const tbody = document.querySelector('#formatTable tbody');
  const tr = document.createElement('tr');
  // Tipo
  const tdType = document.createElement('td');
  const selectType = createTypeSelect();
  selectType.value = data.tipo || 'Parámetro';
  tdType.appendChild(selectType);
  tr.appendChild(tdType);
  // Nombre
  const tdNombre = document.createElement('td');
  tdNombre.innerHTML = `<textarea class="form-control form-control-sm" rows="2">${data.nombre || ''}</textarea>`;
  tr.appendChild(tdNombre);
  // Metodología
  const tdMetod = document.createElement('td');
  tdMetod.className = 'col-metodologia';
  tdMetod.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.metodologia || ''}">`;
  tr.appendChild(tdMetod);
  // Unidad
  const tdUnidad = document.createElement('td');
  tdUnidad.className = 'col-unidad';
  tdUnidad.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.unidad || ''}">`;
  tr.appendChild(tdUnidad);
  // Opciones
  const tdOpciones = document.createElement('td');
  tdOpciones.className = 'col-opciones';
  tdOpciones.innerHTML = `<textarea class="form-control form-control-sm opciones-input" rows="2" placeholder="Ej: amarillo, rojizo, ámbar">${data.opciones ? data.opciones.join(', ') : ''}</textarea>`;
  tr.appendChild(tdOpciones);
  // Valor(es) referencia
  const tdRef = document.createElement('td');
  tdRef.className = 'col-referencias';
  const refList = document.createElement('div');
  refList.className = 'valores-ref-list';
  const referencias = data.referencias || [{valor:'', desc:'', valor_min:'', valor_max:'', sexo:'cualquiera', edad_min:'', edad_max:''}];
  referencias.forEach(ref => {
    refList.appendChild(createRefGroup(ref.valor, ref.desc, ref.valor_min, ref.valor_max, ref.sexo, ref.edad_min, ref.edad_max));
  });
  const btnAddRef = document.createElement('button');
  btnAddRef.type = 'button';
  btnAddRef.className = 'btn btn-primary btn-sm add-ref mt-1';
  btnAddRef.textContent = '+ Agregar valor';
  tdRef.appendChild(refList);
  tdRef.appendChild(btnAddRef);
  tr.appendChild(tdRef);
  // Fórmula
  const tdFormula = document.createElement('td');
  tdFormula.className = 'col-formula';
  tdFormula.innerHTML = `<input type="text" class="form-control form-control-sm formula-input" value="${data.formula || ''}" placeholder="Ej: [Hemoglobina]/[Hematocrito]">`;
  tr.appendChild(tdFormula);
  // Negrita
  const tdBold = document.createElement('td');
  tdBold.className = 'col-neg';
  tdBold.innerHTML = `<input type="checkbox" class="form-check-input" ${data.negrita ? 'checked' : ''}>`;
  tr.appendChild(tdBold);
  // Cursiva
  const tdItalic = document.createElement('td');
  tdItalic.className = 'col-cur';
  tdItalic.innerHTML = `<input type="checkbox" class="form-check-input" ${data.cursiva ? 'checked' : ''}>`;
  tr.appendChild(tdItalic);
  // Alineación
  const tdAlign = document.createElement('td');
  tdAlign.innerHTML = `<select class="form-select form-select-sm align-select">
    <option value="left" ${(data.alineacion === 'left' || !data.alineacion) ? 'selected' : ''}>Izquierda</option>
    <option value="center" ${data.alineacion === 'center' ? 'selected' : ''}>Centro</option>
    <option value="right" ${data.alineacion === 'right' ? 'selected' : ''}>Derecha</option>
  </select>`;
  tr.appendChild(tdAlign);
  // Color texto
  const tdColorText = document.createElement('td');
  tdColorText.className = 'col-color-texto';
  tdColorText.innerHTML = `<input type="color" class="color-input" value="${data.color_texto || '#000000'}">`;
  tr.appendChild(tdColorText);
  // Color fondo
  const tdColorBg = document.createElement('td');
  tdColorBg.className = 'col-color-fondo';
  tdColorBg.innerHTML = `<input type="color" class="color-input" value="${data.color_fondo || '#ffffff'}">`;
  tr.appendChild(tdColorBg);
  // Decimales
  const tdDecimales = document.createElement('td');
  tdDecimales.className = 'col-decimales';
  tdDecimales.innerHTML = `<input type="number" class="form-control form-control-sm decimales-input" value="${data.decimales !== undefined ? data.decimales : ''}" min="0" max="6" style="width:50px;">`;
  tr.appendChild(tdDecimales);
  // Filas (para Texto Largo)
  const tdRows = document.createElement('td');
  tdRows.className = 'col-rows';
  tdRows.innerHTML = `<input type="number" class="form-control form-control-sm rows-input" value="${data.rows !== undefined ? data.rows : 4}" min="2" max="12" style="width:50px;">`;
  tr.appendChild(tdRows);
  // Orden (solo número, no editable)
  const tdOrden = document.createElement('td');
  tdOrden.className = 'orden-fija';
  tdOrden.textContent = tbody.children.length + 1;
  tr.appendChild(tdOrden);
  // Acciones
  const tdAcc = document.createElement('td');
  tdAcc.className = 'col-acciones';
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

function attachRowListeners(tr) {
  if (!tr) return;
  const controls = tr.querySelectorAll('textarea, input, select');
  controls.forEach(el => {
    el.removeEventListener('input', updatePreview);
    el.addEventListener('input', updatePreview);
  });
}

// Delegation fallback: any input inside the table triggers preview
document.addEventListener('input', function(e) {
  if (e.target && e.target.closest('#formatTable')) {
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

  rows.forEach(tr => {
    const tipo = tr.querySelector('.type-select').value;
    const nombre = tr.children[1].querySelector('textarea').value;
    const metodologia = tr.children[2].querySelector('input').value;
    const unidad = tr.children[3].querySelector('input').value;
    const opciones = tr.children[4].querySelector('.opciones-input').value;
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
          <div class="text-muted">(bloque de texto largo)</div>
        </td>
      </tr>`;
    } else {
      html += `<tr>
        <td style="color:${colorTexto};background:${colorFondo};${fontStyle}">${nombre}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${metodologia}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${unidad}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${opciones}</td>
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

// Serialización del formato antes de enviar el formulario
document.getElementById('form-examen').addEventListener('submit', function(e) {
  const tbody = document.querySelector('#formatTable tbody');
  let rows = Array.from(tbody.querySelectorAll('tr'));
  let formato = rows.map(tr => {
    let referencias = [];
    const refGroups = tr.children[5].querySelectorAll('.valores-ref-group');
    refGroups.forEach(refDiv => {
      const valor = refDiv.querySelector('.valor').value;
      const desc = refDiv.querySelector('.desc').value;
      const valor_min = refDiv.querySelector('.valor-min').value;
      const valor_max = refDiv.querySelector('.valor-max').value;
      const sexo = refDiv.querySelector('.sexo-ref').value;
      const edad_min = refDiv.querySelector('.edad-min').value;
      const edad_max = refDiv.querySelector('.edad-max').value;
      if (valor || desc || valor_min || valor_max) {
        referencias.push({ valor, desc, valor_min, valor_max, sexo, edad_min, edad_max });
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

    return {
      id_parametro: id_parametro,
      tipo: tr.querySelector('.type-select').value,
      nombre: tr.children[1].querySelector('textarea').value,
      metodologia: tr.children[2].querySelector('input').value,
      unidad: tr.children[3].querySelector('input').value,
      opciones: opciones,
      referencias: referencias,
      formula: tr.children[6].querySelector('input').value,
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
  const indicesParaDeshabilitar = [2, 3, 4, 5, 6, 12];
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
