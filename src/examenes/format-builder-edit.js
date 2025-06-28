// Asume que tienes datos iniciales cargados desde PHP como window.parametrosData
let parametros = [];
try {
    parametros = window.parametrosData || [];
} catch(e) {
    parametros = [];
}

function renderTable() {
    const tbody = document.getElementById('formatTableBody');
    tbody.innerHTML = '';
    parametros.forEach((param, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" class="form-control tipo-input" value="${param.tipo || ''}" required></td>
            <td><input type="text" class="form-control nombre-input" value="${param.nombre || ''}"></td>
            <td><input type="text" class="form-control metodologia-input" value="${param.metodologia || ''}"></td>
            <td><input type="text" class="form-control unidad-input" value="${param.unidad || ''}"></td>
            <td><textarea class="form-control opciones-input" rows="2">${Array.isArray(param.opciones) ? param.opciones.join(', ') : (param.opciones || '')}</textarea></td>
            <td><input type="text" class="form-control valores-referencia-input" value="${param.valores_referencia || ''}"></td>
            <td><input type="text" class="form-control formula-input" value="${param.formula || ''}"></td>
            <td><input type="checkbox" class="form-check-input negrita-input" ${param.negrita ? 'checked' : ''}></td>
            <td><input type="color" class="form-control form-control-color color-texto-input" value="${param.color_texto || '#000000'}"></td>
            <td><input type="color" class="form-control form-control-color color-fondo-input" value="${param.color_fondo || '#ffffff'}"></td>
            <td><input type="number" class="form-control orden-input" value="${param.orden || idx+1}"></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(${idx})">Eliminar</button></td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('addRow').addEventListener('click', function() {
    parametros.push({
        tipo: '', nombre: '', metodologia: '', unidad: '', opciones: '',
        valores_referencia: '', formula: '', negrita: false,
        color_texto: '#000000', color_fondo: '#ffffff', orden: parametros.length+1
    });
    renderTable();
});

window.eliminarFila = function(idx) {
    parametros.splice(idx, 1);
    renderTable();
};

// Validación flexible: "tipo" siempre es obligatorio. Si tipo ≠ "título"/"subtítulo", también "nombre" y "opciones" son obligatorios.
document.getElementById('form-editar-formato').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#formatTableBody tr');
    const nuevosParametros = [];
    let valid = true;

    rows.forEach((row, idx) => {
        const tipo = row.querySelector('.tipo-input').value.trim().toLowerCase();
        const nombre = row.querySelector('.nombre-input').value.trim();
        const metodologia = row.querySelector('.metodologia-input').value.trim();
        const unidad = row.querySelector('.unidad-input').value.trim();
        const opcionesStr = row.querySelector('.opciones-input').value.trim();
        const opciones = opcionesStr ? opcionesStr.split(',').map(op => op.trim()).filter(Boolean) : [];
        const valores_referencia = row.querySelector('.valores-referencia-input').value.trim();
        const formula = row.querySelector('.formula-input').value.trim();
        const negrita = row.querySelector('.negrita-input').checked;
        const color_texto = row.querySelector('.color-texto-input').value;
        const color_fondo = row.querySelector('.color-fondo-input').value;
        const orden = parseInt(row.querySelector('.orden-input').value, 10) || idx+1;

        // Validación según tipo
        if (!tipo) valid = false;
        if (tipo !== 'título' && tipo !== 'subtítulo') {
            if (!nombre || !opcionesStr) valid = false;
        }

        nuevosParametros.push({
            tipo, nombre, metodologia, unidad, opciones,
            valores_referencia, formula, negrita,
            color_texto, color_fondo, orden
        });
    });

    if (!valid) {
        e.preventDefault();
        alert('Por favor, completa los campos obligatorios según el tipo seleccionado.');
        return;
    }
    document.getElementById('adicional').value = JSON.stringify(nuevosParametros);
});

renderTable();
