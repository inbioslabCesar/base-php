// resultados/recursos/validacion-realtime.js
// Validacion en tiempo real de resultados vs valores de referencia

function validarCampoConReferencias(target) {
  if (!target || !target.getAttribute) return;
  const rawRefs = target.getAttribute('data-referencias');
  if (!rawRefs) return;

  const normalizeText = (value) => {
    if (value === null || value === undefined) return '';
    let out = String(value).trim().toLowerCase();
    if (!out) return '';
    if (typeof out.normalize === 'function') {
      out = out.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    out = out.replace(/\s+/g, ' ');
    return out;
  };

  const splitExpectedTextValues = (raw) => {
    const src = String(raw ?? '').trim();
    if (!src) return [];
    return src
      .split(/[|,;\/]+/)
      .map((part) => normalizeText(part))
      .filter(Boolean);
  };

  const resolveQualitativeStatus = (token) => {
    const t = normalizeText(token);
    if (!t) return '';

    // Priorizar frases negativas compuestas para evitar falsos matches con "reactivo".
    if (/\bno\s+reactiv[oa]?\b/.test(t)) return 'no_reactivo';
    if (/\bno\s+detectad[oa]\b/.test(t)) return 'no_detectado';
    if (/\bincompatibl[ea]s?\b/.test(t)) return 'incompatible';
    if (/\bindeterminad[oa]\b/.test(t)) return 'indeterminado';
    if (/\breactiv[oa]?\b/.test(t)) return 'reactivo';
    if (/\bpositiv[oa]?\b/.test(t)) return 'positivo';
    if (/\bnegativ[oa]?\b/.test(t)) return 'negativo';
    if (/\bcompatible\b/.test(t)) return 'compatible';
    return '';
  };

  const matchesExpectedText = (targetToken, expectedTokens) => {
    if (!targetToken || !Array.isArray(expectedTokens) || expectedTokens.length === 0) return false;
    if (expectedTokens.includes(targetToken)) return true;

    const targetStatus = resolveQualitativeStatus(targetToken);
    if (!targetStatus) return false;

    return expectedTokens.some((expectedToken) => resolveQualitativeStatus(expectedToken) === targetStatus);
  };

  const splitAlertTextValues = (raw) => {
    const src = String(raw ?? '').trim();
    if (!src) return [];
    return src
      .split(/[|,;\/]+/)
      .map((part) => normalizeText(part))
      .filter(Boolean);
  };

  const parseAlertTextColorMap = (raw) => {
    const src = String(raw ?? '').trim();
    const out = {};
    if (!src) return out;
    src.split(/[;,]+/).forEach((part) => {
      const token = String(part || '').trim();
      if (!token) return;
      const idxEq = token.indexOf('=');
      const idxColon = token.indexOf(':');
      const sepIdx = (idxEq >= 0) ? idxEq : idxColon;
      if (sepIdx <= 0) return;
      const label = normalizeText(token.slice(0, sepIdx));
      const color = normalizeAlertColor(token.slice(sepIdx + 1));
      if (label) {
        out[label] = color;
      }
    });
    return out;
  };

  const parseNullableFloat = (value, rangeContext) => {
    if (value === null || value === undefined) return null;
    const normalized = String(value)
      .trim()
      .replace(/\u00A0/g, ' ')
      .replace(/\s+/g, '');
    if (normalized === '') return null;

    const candidates = [];
    const seen = new Set();
    const addCandidate = (num, score) => {
      if (!Number.isFinite(num)) return;
      const key = String(num);
      if (seen.has(key)) return;
      seen.add(key);
      candidates.push({ num, score });
    };

    const hasComma = normalized.indexOf(',') !== -1;
    const hasDot = normalized.indexOf('.') !== -1;

    if (hasComma && hasDot) {
      const lastComma = normalized.lastIndexOf(',');
      const lastDot = normalized.lastIndexOf('.');
      if (lastComma > lastDot) {
        // Formato tipo 1.234,56
        const v = parseFloat(normalized.replace(/\./g, '').replace(',', '.'));
        addCandidate(v, 5);
      } else {
        // Formato tipo 1,234.56
        const v = parseFloat(normalized.replace(/,/g, ''));
        addCandidate(v, 5);
      }
    } else if (hasComma) {
      // Puede ser miles (150,000) o decimal (150,5).
      const commaThousandsPattern = /^[-+]?\d{1,3}(,\d{3})+$/;
      const asThousands = parseFloat(normalized.replace(/,/g, ''));
      const asDecimal = parseFloat(normalized.replace(',', '.'));
      addCandidate(asThousands, commaThousandsPattern.test(normalized) ? 5 : 2);
      addCandidate(asDecimal, commaThousandsPattern.test(normalized) ? 1 : 4);
    } else if (hasDot) {
      // Puede ser miles (150.000) o decimal (150.5).
      const dotThousandsPattern = /^[-+]?\d{1,3}(\.\d{3})+$/;
      const asThousands = parseFloat(normalized.replace(/\./g, ''));
      const asDecimal = parseFloat(normalized);
      addCandidate(asThousands, dotThousandsPattern.test(normalized) ? 5 : 2);
      addCandidate(asDecimal, dotThousandsPattern.test(normalized) ? 1 : 4);
    } else {
      addCandidate(parseFloat(normalized), 4);
    }

    if (candidates.length === 0) return null;

    const minCtx = Number.isFinite(rangeContext?.min) ? Number(rangeContext.min) : null;
    const maxCtx = Number.isFinite(rangeContext?.max) ? Number(rangeContext.max) : null;
    if (minCtx !== null || maxCtx !== null) {
      const inRange = candidates.filter(({ num }) => {
        if (minCtx !== null && num < minCtx) return false;
        if (maxCtx !== null && num > maxCtx) return false;
        return true;
      });
      if (inRange.length > 0) {
        inRange.sort((a, b) => b.score - a.score);
        return inRange[0].num;
      }
    }

    candidates.sort((a, b) => b.score - a.score);
    return candidates[0].num;
  };

  const normalizeAlertMode = (mode) => {
    const raw = String(mode || '').trim().toLowerCase();
    return ['none', 'asterisk', 'color', 'both'].includes(raw) ? raw : 'both';
  };

  const normalizeAlertColor = (color) => {
    const raw = String(color || '').trim();
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(raw) ? raw : '#c62828';
  };

  const ensureAsteriskMarker = (el, show, color) => {
    if (!el || !el.parentNode) return;
    let marker = null;
    const next = el.nextElementSibling;
    if (next && next.classList && next.classList.contains('range-alert-marker')) {
      marker = next;
    }
    if (!marker && show) {
      marker = document.createElement('span');
      marker.className = 'range-alert-marker';
      marker.setAttribute('aria-hidden', 'true');
      marker.style.marginLeft = '6px';
      marker.style.fontWeight = '700';
      marker.style.fontSize = '1rem';
      marker.textContent = '*';
      el.insertAdjacentElement('afterend', marker);
    }
    if (marker) {
      if (show) {
        marker.style.display = 'inline-block';
        marker.style.color = color;
      } else {
        marker.style.display = 'none';
      }
    }
  };

  const clearAlertStyles = (el) => {
    if (!el || !el.style) return;
    el.style.borderColor = '';
    el.style.color = '';
    el.style.boxShadow = '';
  };

  const applyColorAlertStyles = (el, color) => {
    if (!el || !el.style) return;
    el.style.borderColor = color;
    el.style.color = color;
    el.style.boxShadow = `0 0 0 0.2rem ${color}33`;
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
  let textRuleEvaluated = false;
  let alertMode = 'both';
  let alertColor = '#c62828';
  let valor = parseNullableFloat(target.value);
  const targetTextRaw = String(target.value ?? '').trim();

  if (referencia_aplicada && targetTextRaw !== '') {
    alertMode = normalizeAlertMode(referencia_aplicada.alerta_modo ?? referencia_aplicada.alert_mode ?? 'both');
    alertColor = normalizeAlertColor(referencia_aplicada.alerta_color ?? referencia_aplicada.alert_color ?? '#c62828');
    const min = parseNullableFloat(referencia_aplicada.valor_min);
    const max = parseNullableFloat(referencia_aplicada.valor_max);
    const hasNumericRule = (min !== null || max !== null);

    if (hasNumericRule) {
      valor = parseNullableFloat(target.value, { min, max });
      if (valor !== null) {
        if (min !== null && valor < min) fuera_rango = true;
        if (max !== null && valor > max) fuera_rango = true;
      }
    } else {
      const targetToken = normalizeText(targetTextRaw);
      const alertTextColorMap = parseAlertTextColorMap(referencia_aplicada.alerta_colores_texto ?? referencia_aplicada.alert_text_colors ?? referencia_aplicada.alerta_colores ?? '');
      const hasExplicitColorRules = Object.keys(alertTextColorMap).length > 0;
      const alertTextsRaw = referencia_aplicada.alerta_textos ?? referencia_aplicada.alert_text_values ?? referencia_aplicada.alerta_valores ?? '';
      const explicitAlertTokens = splitAlertTextValues(alertTextsRaw);
      const hasExplicitValueRules = explicitAlertTokens.length > 0;
      const hasExplicitRules = hasExplicitColorRules || hasExplicitValueRules;

      if (targetToken !== '' && Object.prototype.hasOwnProperty.call(alertTextColorMap, targetToken)) {
        textRuleEvaluated = true;
        fuera_rango = true;
        alertColor = normalizeAlertColor(alertTextColorMap[targetToken]);
      } else {
      if (hasExplicitValueRules && targetToken !== '') {
        textRuleEvaluated = true;
        if (explicitAlertTokens.includes(targetToken)) {
          fuera_rango = true;
        }
      } else if (hasExplicitRules && targetToken !== '') {
        // Si hay reglas explícitas configuradas, no aplicar mismatch general.
        textRuleEvaluated = true;
      } else {
      const expectedRaw = String(referencia_aplicada.valor ?? '').trim();
      const expectedTokens = splitExpectedTextValues(expectedRaw);
      if (expectedTokens.length > 0 && targetToken !== '') {
        textRuleEvaluated = true;
        if (!matchesExpectedText(targetToken, expectedTokens)) {
          fuera_rango = true;
        }
      }
      }
      }
    }
  }
  const showColor = fuera_rango && (alertMode === 'color' || alertMode === 'both');
  const showAsterisk = fuera_rango && (alertMode === 'asterisk' || alertMode === 'both');

  target.classList.toggle('is-invalid', showColor);
  const isValidByNumeric = (targetTextRaw !== '' && valor !== null);
  const isValidByText = (targetTextRaw !== '' && textRuleEvaluated && !fuera_rango);
  target.classList.toggle('is-valid', !fuera_rango && (isValidByNumeric || isValidByText));

  if (showColor) {
    applyColorAlertStyles(target, alertColor);
  } else {
    clearAlertStyles(target);
  }
  ensureAsteriskMarker(target, showAsterisk, alertColor);
}

document.addEventListener('input', function(e) {
  validarCampoConReferencias(e.target);
});

document.addEventListener('change', function(e) {
  validarCampoConReferencias(e.target);
});

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-referencias]').forEach((el) => {
    validarCampoConReferencias(el);
  });
});

// Puedes agregar los inputs ocultos en el formulario para edad y sexo:
// <input type="hidden" id="edad-paciente" value="<?= htmlspecialchars($datos_paciente['edad']) ?>">
// <input type="hidden" id="sexo-paciente" value="<?= htmlspecialchars($datos_paciente['sexo']) ?>">

// Y en cada input de parámetro, agrega:
// data-referencias='<?= json_encode($item['referencias']) ?>' data-edad='<?= htmlspecialchars($datos_paciente['edad']) ?>' data-sexo='<?= htmlspecialchars($datos_paciente['sexo']) ?>'
