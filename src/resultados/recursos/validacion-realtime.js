// resultados/recursos/validacion-realtime.js
// Validacion en tiempo real de resultados vs valores de referencia

function validarCampoConReferencias(target) {
  if (!target || !target.getAttribute) return;
  const rawRefs = target.getAttribute('data-referencias');
  if (!rawRefs) return;

  const parseNullableFloat = (value) => {
    if (value === null || value === undefined) return null;
    const normalized = String(value).trim().replace(/,/g, '');
    if (normalized === '') return null;
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : null;
  };

  let referencias = [];
  try {
    const parsed = JSON.parse(rawRefs || '[]');
    if (Array.isArray(parsed)) referencias = parsed;
  } catch (err) {
    referencias = [];
  }
  const edad = parseNullableFloat(document.getElementById('edad-paciente')?.value ?? target.getAttribute('data-edad'));
  const sexo = (document.getElementById('sexo-paciente')?.value ?? target.getAttribute('data-sexo') ?? '').toLowerCase();
  let referencia_aplicada = null;

  if (referencias.length > 0) {
    referencias.forEach(ref => {
      const ref_sexo = (ref.sexo || '').toLowerCase();
      const ref_edad_min = parseNullableFloat(ref.edad_min);
      const ref_edad_max = parseNullableFloat(ref.edad_max);
      const sexo_match = (ref_sexo === '' || ref_sexo === 'cualquiera' || ref_sexo === sexo);
      const edad_match = (edad === null) || ((ref_edad_min === null || edad >= ref_edad_min) && (ref_edad_max === null || edad <= ref_edad_max));
      if (sexo_match && edad_match && !referencia_aplicada) referencia_aplicada = ref;
    });
    if (!referencia_aplicada && referencias.length > 0) {
      referencia_aplicada = referencias[0];
    }
  }

  let fuera_rango = false;
  const valor = parseNullableFloat(target.value);
  if (referencia_aplicada && valor !== null && target.value !== '') {
    const min = parseNullableFloat(referencia_aplicada.valor_min);
    const max = parseNullableFloat(referencia_aplicada.valor_max);
    if (min !== null && valor < min) fuera_rango = true;
    if (max !== null && valor > max) fuera_rango = true;
  }
  target.classList.toggle('is-invalid', fuera_rango);
  target.classList.toggle('is-valid', !fuera_rango && target.value !== '' && valor !== null);
}

document.addEventListener('input', function(e) {
  validarCampoConReferencias(e.target);
});

document.addEventListener('change', function(e) {
  validarCampoConReferencias(e.target);
});

// Puedes agregar los inputs ocultos en el formulario para edad y sexo:
// <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad']) ?>">
// <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo']) ?>">

// Y en cada input de parámetro, agrega:
// data-referencias='<?= json_encode($item['referencias']) ?>' data-edad='<?= htmlspecialchars($datos_paciente['edad']) ?>' data-sexo='<?= htmlspecialchars($datos_paciente['sexo']) ?>'
