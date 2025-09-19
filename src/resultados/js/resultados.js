document.addEventListener('DOMContentLoaded', function() {

    // Evalúa expresiones matemáticas de forma segura
    function safeEval(expr) {
        try {
            let resultado = eval(expr);
            if (!isFinite(resultado) || isNaN(resultado)) {
                return '';
            }
            return parseFloat(resultado).toFixed(2);
        } catch (e) {
            return '';
        }
    }

    // Calcula los campos dependientes en cascada
    function calcularCampos() {
        let valores = {};
        // Lee todos los campos base y calculados
        document.querySelectorAll('.campo-base, .campo-calculado').forEach(function(input) {
            let nombre = input.name.replace('parametros[','').replace(']','');
            valores[nombre] = parseFloat(input.value) || 0;
        });

        // Varias pasadas para dependencias en cascada
        for (let i = 0; i < 3; i++) {
            document.querySelectorAll('.campo-calculado').forEach(function(input) {
                let formula = input.getAttribute('data-formula');
                let nombreCampo = input.name.replace('parametros[','').replace(']','');
                if (formula) {
                    let expresion = formula;
                    Object.keys(valores).forEach(function(nombre) {
                        let regex = new RegExp('\\[' + nombre + '\\]', 'g');
                        expresion = expresion.replace(regex, valores[nombre]);
                    });
                    let resultado = safeEval(expresion);
                    input.value = resultado;
                    valores[nombreCampo] = resultado === '' ? 0 : parseFloat(resultado);
                }
            });
        }
    }

    // Escucha cambios en los campos base y recalcula
    document.querySelectorAll('.campo-base').forEach(function(input) {
        input.addEventListener('input', calcularCampos);
    });

    // Si hay campos calculados al cargar, calcula una vez
    calcularCampos();
});
$(document).ready(function() {
  $.ajax({
    url: 'resultados.php',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
      // Mostrar datos del paciente
      $('#datos-paciente').html(
        `<strong>Nombre:</strong> ${data.paciente.nombre}   
         <strong>Edad:</strong> ${data.paciente.edad}   
         <strong>Sexo:</strong> ${data.paciente.sexo}   
         <strong>Fecha:</strong> ${data.paciente.fecha}   
         <strong>ID:</strong> ${data.paciente.id}`
      );
      // Llenar la tabla de resultados
      let filas = '';
      data.resultados.forEach(function(r) {
        filas += `<tr>
          <td>${r.prueba}</td>
          <td>${r.metodologia}</td>
          <td>${r.resultado}</td>
          <td>${r.unidades}</td>
          <td>${r.referencia}</td>
        </tr>`;
      });
      $('#tabla-resultados').html(filas);
    }
  });
});
