// resultados/recursos/validacion-realtime.js
// Validación en tiempo real de resultados vs valores de referencia

document.addEventListener('input', function(e) {
  if (e.target.classList.contains('form-control') && e.target.closest('.parameter-section')) {
    const input = e.target;
    const section = input.closest('.parameter-section');

    const parseNullableFloat = (value) => {
      if (value === null || value === undefined) return null;
      const normalized = String(value).trim().replace(/,/g, '');
      if (normalized === '') return null;
      const parsed = parseFloat(normalized);
      return Number.isFinite(parsed) ? parsed : null;
    };

    // Obtener referencias y datos del paciente desde atributos data
    const referencias = JSON.parse(input.getAttribute('data-referencias') || '[]');
    const edad = parseNullableFloat(document.getElementById('edad-paciente')?.value ?? input.getAttribute('data-edad'));
    const sexo = (document.getElementById('sexo-paciente')?.value ?? input.getAttribute('data-sexo') ?? '').toLowerCase();
    let referencia_aplicada = null;

    if (edad !== null && sexo !== '') {
      referencias.forEach(ref => {
        const ref_sexo = (ref.sexo || '').toLowerCase();
        const ref_edad_min = parseNullableFloat(ref.edad_min);
        const ref_edad_max = parseNullableFloat(ref.edad_max);
        const sexo_match = (ref_sexo === 'cualquiera' || ref_sexo === sexo);
        const edad_match = (ref_edad_min === null || edad >= ref_edad_min) && (ref_edad_max === null || edad <= ref_edad_max);
        if (sexo_match && edad_match && !referencia_aplicada) referencia_aplicada = ref;
      });
    }

    let fuera_rango = false;
    const valor = parseNullableFloat(input.value);
    if (referencia_aplicada && valor !== null && input.value !== '') {
      const min = parseNullableFloat(referencia_aplicada.valor_min);
      const max = parseNullableFloat(referencia_aplicada.valor_max);
      if (min !== null && valor < min) fuera_rango = true;
      if (max !== null && valor > max) fuera_rango = true;
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
