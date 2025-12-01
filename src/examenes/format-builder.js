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

function createRefGroup(valor = '', desc = '', valor_min = '', valor_max = '', sexo = 'cualquiera', edad_min = '', edad_max = '') {
  const div = document.createElement('div');
  div.className = 'valores-ref-group';
  // Normalizar decimales a punto
  // Reemplazar coma por punto si el usuario ingresa con coma
  let valorMinStr = valor_min !== '' ? valor_min.toString().replace(',', '.') : '';
  let valorMaxStr = valor_max !== '' ? valor_max.toString().replace(',', '.') : '';
  // Convertir a número y devolver como string con punto
  valorMinStr = valorMinStr !== '' ? parseFloat(valorMinStr).toString() : '';
  valorMaxStr = valorMaxStr !== '' ? parseFloat(valorMaxStr).toString() : '';
  div.innerHTML = `
    <input type="text" class="form-control form-control-sm valor" placeholder="Valor" value="${valor}">
    <input type="text" class="form-control form-control-sm desc" placeholder="Descripción" value="${desc}">
    <input type="number" step="0.01" class="form-control form-control-sm valor-min" placeholder="Min" value="${valorMinStr}" style="width:90px;display:inline-block;">
    <input type="number" step="0.01" class="form-control form-control-sm valor-max" placeholder="Max" value="${valorMaxStr}" style="width:90px;display:inline-block;">
    <select class="form-select form-select-sm sexo-ref" style="width:120px;display:inline-block;">
      <option value="cualquiera" ${sexo === 'cualquiera' ? 'selected' : ''}>Cualquiera</option>
      <option value="masculino" ${sexo === 'masculino' ? 'selected' : ''}>Masculino</option>
      <option value="femenino" ${sexo === 'femenino' ? 'selected' : ''}>Femenino</option>
    </select>
    <input type="number" class="form-control form-control-sm edad-min" placeholder="Edad min" value="${edad_min}" style="width:90px;display:inline-block;">
    <input type="number" class="form-control form-control-sm edad-max" placeholder="Edad max" value="${edad_max}" style="width:90px;display:inline-block;">
    <button type="button" class="btn btn-danger btn-sm remove-ref">-</button>
  `;
  return div;
}





function addRow(data = {}) {
  // ...existing code...
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
  const referencias = data.referencias || [{valor:'', desc:'', valor_min:'', valor_max:'', sexo:'', edad_min:'', edad_max:''}];
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
  tdFormula.innerHTML = `<input type="text" class="form-control form-control-sm formula-input" value="${data.formula || ''}" placeholder="Ej: [Hemoglobina]/[Hematocrito]">`;
  tr.appendChild(tdFormula);
  // Negrita
  const tdBold = document.createElement('td');
  tdBold.innerHTML = `<input type="checkbox" class="form-check-input" ${data.negrita ? 'checked' : ''}>`;
  tr.appendChild(tdBold);
  // Cursiva
  const tdItalic = document.createElement('td');
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
  tdColorText.innerHTML = `<input type="color" class="color-input" value="${data.color_texto || '#000000'}">`;
  tr.appendChild(tdColorText);
  // Color fondo
  const tdColorBg = document.createElement('td');
  tdColorBg.innerHTML = `<input type="color" class="color-input" value="${data.color_fondo || '#ffffff'}">`;
  tr.appendChild(tdColorBg);
  // Decimales
  const tdDecimales = document.createElement('td');
  tdDecimales.innerHTML = `<input type="number" class="form-control form-control-sm decimales-input" value="${data.decimales !== undefined ? data.decimales : ''}" min="0" max="6" style="width:70px;">`;
  tr.appendChild(tdDecimales);
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
      if (valor || desc) {
        refHtml += `<div><b>${desc ? desc + ':' : ''}</b> ${valor}</div>`;
      }
    });
    const negrita = tr.children[7].querySelector('input').checked;
    const cursiva = tr.children[8].querySelector('input').checked;
    const alineacion = tr.children[9].querySelector('select').value;
    const colorTexto = tr.children[10].querySelector('input').value;
    const colorFondo = tr.children[11].querySelector('input').value;

    let fontStyle = '';
    if (negrita) fontStyle += 'font-weight:bold;';
    if (cursiva) fontStyle += 'font-style:italic;';

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
      orden: parseInt(tr.children[13].querySelector('input').value) || 0
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
