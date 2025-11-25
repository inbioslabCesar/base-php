// resultados/recursos/validacion-realtime.js
// Validación en tiempo real de resultados vs valores de referencia

document.addEventListener('input', function(e) {
  if (e.target.classList.contains('form-control') && e.target.closest('.parameter-section')) {
    const input = e.target;
    const section = input.closest('.parameter-section');
    // Log para depuración
    console.log('Validando input:', input.name, 'valor:', input.value);
    // Obtener referencias y datos del paciente desde atributos data
    const referencias = JSON.parse(input.getAttribute('data-referencias') || '[]');
    const edad = parseFloat(document.getElementById('edad-paciente')?.value || input.getAttribute('data-edad') || 0);
    const sexo = (document.getElementById('sexo-paciente')?.value || input.getAttribute('data-sexo') || '').toLowerCase();
    let referencia_aplicada = null;
    referencias.forEach(ref => {
      const ref_sexo = (ref.sexo || '').toLowerCase();
      const ref_edad_min = parseFloat(ref.edad_min || 0);
      const ref_edad_max = parseFloat(ref.edad_max || 999);
      const sexo_match = (ref_sexo === 'cualquiera' || ref_sexo === sexo);
      const edad_match = (edad >= ref_edad_min && edad <= ref_edad_max);
      if (sexo_match && edad_match && !referencia_aplicada) referencia_aplicada = ref;
    });
    let fuera_rango = false;
    const valor = parseFloat(input.value);
    if (referencia_aplicada && !isNaN(valor)) {
      const min = parseFloat(referencia_aplicada.valor_min);
      const max = parseFloat(referencia_aplicada.valor_max);
      if ((min && valor < min) || (max && valor > max)) fuera_rango = true;
    }
    input.classList.toggle('is-invalid', fuera_rango);
    input.classList.toggle('is-valid', !fuera_rango && input.value !== '');
  }
});

// Puedes agregar los inputs ocultos en el formulario para edad y sexo:
// <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad']) ?>">
// <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo']) ?>">

// Y en cada input de parámetro, agrega:
// data-referencias='<?= json_encode($item['referencias']) ?>' data-edad='<?= htmlspecialchars($datos_paciente['edad']) ?>' data-sexo='<?= htmlspecialchars($datos_paciente['sexo']) ?>'
