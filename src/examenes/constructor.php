<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';
?>

<h2>Constructor de Exámenes</h2>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['exito'])): ?>
    <div class="alert alert-success"><?= $_SESSION['exito']; unset($_SESSION['exito']); ?></div>
<?php endif; ?>

<form action="dashboard.php?action=guardar_constructor" method="POST">
  <label>Nombre del examen o perfil:</label>
  <input type="text" name="nombre" required><br>
  
  <label>Área:</label>
  <input type="text" name="area" required><br>
  
  <label>Metodología:</label>
  <input type="text" name="metodologia" required><br>
  
  <h4>Parámetros</h4>
  <div id="parametros"></div>
  <button type="button" onclick="agregarParametro()">Agregar parámetro</button><br><br>
  
  <button type="submit" class="btn btn-success">Guardar examen/perfil</button>
</form>

<script>
let contador = 0;
let nombresParametros = [];

function agregarParametro() {
    const idx = contador;
    const div = document.createElement('div');
    div.className = 'parametro';
    div.innerHTML = `
      <input type="text" name="parametros[${idx}][nombre]" placeholder="Nombre parámetro" required oninput="actualizarNombres()">
      <input type="text" name="parametros[${idx}][unidad]" placeholder="Unidad" required>
      <input type="text" name="parametros[${idx}][referencia]" placeholder="Valor de referencia" required>
      <label>
        <input type="checkbox" name="parametros[${idx}][calculado]" value="1" onchange="toggleFormula(this, ${idx})">
        Calculado
      </label>
      <input type="text" name="parametros[${idx}][formula]" id="formula_${idx}" placeholder="Fórmula (si es calculado)" style="display:none;">
      <span id="ayuda_formula_${idx}" class="text-secondary" style="display:none; font-size:0.9em;"></span>
      <button type="button" onclick="this.parentNode.remove(); actualizarNombres();">Quitar</button>
      <br>
    `;
    document.getElementById('parametros').appendChild(div);
    contador++;
    actualizarNombres();
}

function toggleFormula(checkbox, idx) {
    const formulaInput = document.getElementById('formula_' + idx);
    const ayuda = document.getElementById('ayuda_formula_' + idx);
    if (checkbox.checked) {
        formulaInput.style.display = 'inline-block';
        ayuda.style.display = 'inline-block';
        actualizarAyudaFormula(idx);
        formulaInput.required = true;
    } else {
        formulaInput.style.display = 'none';
        ayuda.style.display = 'none';
        formulaInput.required = false;
        formulaInput.value = '';
    }
}

function actualizarNombres() {
    // Recolecta los nombres de todos los parámetros no calculados y actualiza la ayuda en cada campo de fórmula
    nombresParametros = [];
    const divs = document.querySelectorAll('#parametros .parametro');
    divs.forEach((div, i) => {
        const inputNombre = div.querySelector('input[name^="parametros"][name$="[nombre]"]');
        const inputCalculado = div.querySelector('input[type="checkbox"][name^="parametros"][name$="[calculado]"]');
        if (inputNombre && inputNombre.value.trim() !== "") {
            nombresParametros.push(inputNombre.value.trim());
        }
        if (inputCalculado && inputCalculado.checked) {
            const idx = inputCalculado.name.match(/\d+/)[0];
            actualizarAyudaFormula(idx);
        }
    });
}

function actualizarAyudaFormula(idx) {
    const ayuda = document.getElementById('ayuda_formula_' + idx);
    if (ayuda) {
        ayuda.innerHTML = 'Puedes usar: ' + nombresParametros.map(n => `<span class="badge bg-info text-dark" style="cursor:pointer;" onclick="insertarEnFormula(${idx}, '${n.replace(/'/g,"\\'")}')">${n}</span>`).join(' ');
    }
}

function insertarEnFormula(idx, nombre) {
    const formulaInput = document.getElementById('formula_' + idx);
    if (formulaInput) {
        // Inserta el nombre del parámetro en la posición actual del cursor
        const start = formulaInput.selectionStart || 0;
        const end = formulaInput.selectionEnd || 0;
        const value = formulaInput.value;
        formulaInput.value = value.substring(0, start) + nombre + value.substring(end);
        formulaInput.focus();
        formulaInput.selectionStart = formulaInput.selectionEnd = start + nombre.length;
    }
}
</script>
