<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$mensaje_error = $_SESSION['mensaje_error'] ?? '';
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Constructor de Exámenes</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                    <?php endif; ?>

                    <form action="dashboard.php?action=guardar_constructor" method="POST" id="formulario-examen">
                        <div class="mb-3">
                            <label class="form-label">Nombre del examen:</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área:</label>
                            <input type="text" name="area" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metodología:</label>
                            <input type="text" name="metodologia" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <h5>Parámetros del examen</h5>
                            <div id="parametros"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="agregarParametro()">Agregar parámetro</button>
                        </div>

                        <button type="submit" class="btn btn-success">Guardar examen</button>
                        <a href="dashboard.php?vista=constructor" class="btn btn-secondary">Limpiar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let nombresParametros = [];

// Función para agregar parámetros dinámicamente
function agregarParametro(parametro = '', unidad = '', valor = '', calculado = false, formula = '') {
    const div = document.createElement('div');
    div.className = 'row parametro-row align-items-end';

    const index = nombresParametros.length;
    nombresParametros.push(parametro);

    div.innerHTML = `
        <div class="col-md-3 mb-1">
            <input type="text" name="parametros[parametro][]" class="form-control nombre-parametro" placeholder="Nombre" value="${parametro}" required oninput="actualizarNombresParametros()">
        </div>
        <div class="col-md-2 mb-1">
            <input type="text" name="parametros[unidad][]" class="form-control" placeholder="Unidad" value="${unidad}">
        </div>
        <div class="col-md-3 mb-1">
            <input type="text" name="parametros[valor][]" class="form-control" placeholder="Valor de referencia" value="${valor}">
        </div>
        <div class="col-md-2 mb-1">
            <select name="parametros[calculado][]" class="form-select" onchange="toggleFormula(this, ${index})">
                <option value="0"${calculado ? '' : ' selected'}>Procesado</option>
                <option value="1"${calculado ? ' selected' : ''}>Calculado</option>
            </select>
        </div>
        <div class="col-md-2 mb-1 formula-group" style="position:relative;${calculado ? '' : 'display:none;'}">
            <input type="text" name="parametros[formula][]" class="form-control formula-input" placeholder="Fórmula" value="${formula}">
            <div class="btn-group mt-1 nombres-btns" style="flex-wrap:wrap;"></div>
        </div>
        <div class="col-12 mb-2">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.parametro-row').remove(); actualizarNombresParametros();">Eliminar</button>
        </div>
    `;
    document.getElementById('parametros').appendChild(div);
    actualizarNombresParametros();
}

// Mostrar u ocultar campo fórmula y botones de nombres según tipo de parámetro
function toggleFormula(select, index) {
    const formulaGroup = select.closest('.parametro-row').querySelector('.formula-group');
    if (select.value === "1") {
        formulaGroup.style.display = "";
        actualizarNombresParametros();
    } else {
        formulaGroup.style.display = "none";
        formulaGroup.querySelector('.formula-input').value = "";
    }
}

// Actualiza la lista de nombres de parámetros y los botones para insertar en la fórmula
function actualizarNombresParametros() {
    // Obtiene todos los nombres actuales
    let nombres = Array.from(document.querySelectorAll('.nombre-parametro')).map(input => input.value).filter(Boolean);

    // Actualiza todos los grupos de botones de nombres para cada parámetro calculado
    document.querySelectorAll('.parametro-row').forEach(row => {
        const select = row.querySelector('select[name^="parametros[calculado]"]');
        const formulaGroup = row.querySelector('.formula-group');
        const formulaInput = row.querySelector('.formula-input');
        const btnsDiv = row.querySelector('.nombres-btns');
        if (select && select.value === "1" && btnsDiv) {
            btnsDiv.innerHTML = '';
            nombres.forEach(nombre => {
                // Evita que se agregue su propio nombre como botón
                if (row.querySelector('.nombre-parametro').value !== nombre) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-secondary btn-sm me-1 mb-1';
                    btn.textContent = nombre;
                    btn.onclick = () => {
                        insertarEnFormula(formulaInput, nombre);
                    };
                    btnsDiv.appendChild(btn);
                }
            });
        }
    });
}

// Inserta el nombre del parámetro en la posición del cursor en la fórmula
function insertarEnFormula(input, nombre) {
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const texto = input.value;
    input.value = texto.substring(0, start) + nombre + texto.substring(end);
    input.focus();
    input.selectionStart = input.selectionEnd = start + nombre.length;
}

</script>
