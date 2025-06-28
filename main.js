function createTypeSelect() {
  const select = document.createElement('select');
  select.className = 'form-select form-select-sm type-select';
  ['Parámetro', 'Título', 'Subtítulo'].forEach(type => {
    const opt = document.createElement('option');
    opt.value = type;
    opt.innerText = type;
    select.appendChild(opt);
  });
  return select;
}

function createRefGroup(valor = '', desc = '') {
  const div = document.createElement('div');
  div.className = 'valores-ref-group';
  div.innerHTML = `
    <input type="text" class="form-control form-control-sm valor" placeholder="Valor" value="${valor}">
    <input type="text" class="form-control form-control-sm desc" placeholder="Descripción" value="${desc}">
    <button type="button" class="btn btn-danger btn-sm remove-ref">-</button>
  `;
  return div;
}





function addRow(data = {}) {
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
  tdMetod.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.metodologia || ''}">`;
  tr.appendChild(tdMetod);
  // Unidad
  const tdUnidad = document.createElement('td');
  tdUnidad.innerHTML = `<input type="text" class="form-control form-control-sm" value="${data.unidad || ''}">`;
  tr.appendChild(tdUnidad);
  // Opciones
  const tdOpciones = document.createElement('td');
  tdOpciones.innerHTML = `<textarea class="form-control form-control-sm opciones-input" rows="2" placeholder="Ej: amarillo, rojizo, ámbar">${data.opciones ? data.opciones.join(', ') : ''}</textarea>`;
  tr.appendChild(tdOpciones);
  // Valor(es) referencia
  const tdRef = document.createElement('td');
  const refList = document.createElement('div');
  refList.className = 'valores-ref-list';
  const referencias = data.referencias || [{valor:'', desc:''}];
  referencias.forEach(ref => {
    refList.appendChild(createRefGroup(ref.valor, ref.desc));
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
  tdFormula.innerHTML = `<input type="text" class="form-control form-control-sm formula-input" value="${data.formula || ''}" placeholder="Ej: [Hemoglobina]/[Hematocrito]">`;
  tr.appendChild(tdFormula);
  // Negrita
  const tdBold = document.createElement('td');
  tdBold.innerHTML = `<input type="checkbox" class="form-check-input" ${data.negrita ? 'checked' : ''}>`;
  tr.appendChild(tdBold);
  // Color texto
  const tdColorText = document.createElement('td');
  tdColorText.innerHTML = `<input type="color" class="color-input" value="${data.color_texto || '#000000'}">`;
  tr.appendChild(tdColorText);
  // Color fondo
  const tdColorBg = document.createElement('td');
  tdColorBg.innerHTML = `<input type="color" class="color-input" value="${data.color_fondo || '#ffffff'}">`;
  tr.appendChild(tdColorBg);
  // Orden
  const tdOrden = document.createElement('td');
  tdOrden.innerHTML = `<input type="number" class="form-control form-control-sm" value="${data.orden || (tbody.children.length + 1)}" min="1" style="width:70px;">`;
  tr.appendChild(tdOrden);
  // Acciones
  const tdAcc = document.createElement('td');
  tdAcc.innerHTML = `<button class="btn btn-danger btn-sm remove-row">Eliminar</button>`;
  tr.appendChild(tdAcc);
  tbody.appendChild(tr);
  updatePreview();
}
// Eliminar fila de la tabla
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('remove-row')) {
    e.target.closest('tr').remove();
    updatePreview();
  }
});

// Agregar nueva fila
document.getElementById('addRow').addEventListener('click', function() {
  addRow();
});



// Agregar valor de referencia en una fila
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('add-ref')) {
    const refList = e.target.parentElement.querySelector('.valores-ref-list');
    refList.appendChild(createRefGroup());
    updatePreview();
  }
});




// Eliminar valor de referencia específico
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('remove-ref')) {
    e.target.parentElement.remove();
    updatePreview();
  }
});

// Actualizar vista previa en tiempo real
document.getElementById('formatTable').addEventListener('input', updatePreview);
document.getElementById('formatTable').addEventListener('change', updatePreview);

function updatePreview() {
  const tbody = document.querySelector('#formatTable tbody');
  let rows = Array.from(tbody.querySelectorAll('tr'));
  rows.sort((a, b) => {
    let aOrden = parseInt(a.children[10].querySelector('input').value) || 0;
    let bOrden = parseInt(b.children[10].querySelector('input').value) || 0;
    return aOrden - bOrden;
  });

  // Solo columnas relevantes en la preview
  let html = '<table class="table table-bordered"><thead><tr>' +
    '<th>Nombre</th><th>Metodología</th><th>Unidad</th><th>Opciones</th><th>Valor(es) Referencia</th>' +
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
      if (valor || desc) {
        refHtml += `<div><b>${desc ? desc + ':' : ''}</b> ${valor}</div>`;
      }
    });
    const negrita = tr.children[7].querySelector('input').checked;
    const colorTexto = tr.children[8].querySelector('input').value;
    const colorFondo = tr.children[9].querySelector('input').value;

    if (tipo === 'Título') {
      html += `<tr>
        <td colspan="5" style="background:${colorFondo};color:${colorTexto};${negrita ? 'font-weight:bold;' : ''};font-size:1.2em;text-align:center;">
          ${nombre}
        </td>
      </tr>`;
    } else if (tipo === 'Subtítulo') {
      html += `<tr>
        <td colspan="5" style="background:${colorFondo};color:${colorTexto};${negrita ? 'font-weight:bold;' : ''};font-size:1em;text-align:left;">
          ${nombre}
        </td>
      </tr>`;
    } else {
      html += `<tr>
        <td style="color:${colorTexto};background:${colorFondo};${negrita ? 'font-weight:bold;' : ''};">${nombre}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${metodologia}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${unidad}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${opciones}</td>
        <td style="color:${colorTexto};background:${colorFondo};">${refHtml}</td>
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
  rows.sort((a, b) => {
    let aOrden = parseInt(a.children[10].querySelector('input').value) || 0;
    let bOrden = parseInt(b.children[10].querySelector('input').value) || 0;
    return aOrden - bOrden;
  });

  let formato = rows.map(tr => {
    let referencias = [];
    const refGroups = tr.children[5].querySelectorAll('.valores-ref-group');
    refGroups.forEach(refDiv => {
      const valor = refDiv.querySelector('.valor').value;
      const desc = refDiv.querySelector('.desc').value;
      if (valor || desc) {
        referencias.push({ valor, desc });
      }
    });
    let opciones = tr.children[4].querySelector('.opciones-input').value
      .split(',')
      .map(o => o.trim())
      .filter(o => o);

    return {
      tipo: tr.querySelector('.type-select').value,
      nombre: tr.children[1].querySelector('textarea').value,
      metodologia: tr.children[2].querySelector('input').value,
      unidad: tr.children[3].querySelector('input').value,
      opciones: opciones,
      referencias: referencias,
      formula: tr.children[6].querySelector('input').value,
      negrita: tr.children[7].querySelector('input').checked,
      color_texto: tr.children[8].querySelector('input').value,
      color_fondo: tr.children[9].querySelector('input').value,
      orden: parseInt(tr.children[10].querySelector('input').value) || 0
    };
  });

  document.getElementById('adicional').value = JSON.stringify(formato);
  // El formulario se enviará normalmente
});
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
  ['+', '-', '*', '/', '(', ')'].forEach(op => {
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
