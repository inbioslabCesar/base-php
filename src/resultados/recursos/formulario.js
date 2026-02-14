
// JS personalizado para formulario de resultados
document.addEventListener('DOMContentLoaded', function () {
    // Animación de aparición escalonada para las cards
    const cards = document.querySelectorAll('.exam-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Efecto de enfoque mejorado para inputs
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Lógica de cálculos automáticos para campos con fórmula
    document.querySelectorAll('.campo-calculado[data-formula]').forEach(function(calculado) {
        let formula = calculado.getAttribute('data-formula');
        let variables = formula.match(/\[([^\]]+)\]/g) || [];
        let nameParts = calculado.name.match(/examenes\[(\d+)\]\[resultados\]\[([^\]]+)\]/);
        let idResultado = nameParts ? nameParts[1] : null;

        const normalizeKey = (s) => {
            return String(s ?? '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
        };

        const getInputsMap = () => {
            // Mapea nombre de parámetro -> input dentro del mismo examen
            const card = calculado.closest('.exam-card') || document;
            const inputs = card.querySelectorAll(`[name^="examenes[${idResultado}][resultados]["]`);
            const map = new Map();
            inputs.forEach((el) => {
                const m = el.name.match(/examenes\[\d+\]\[resultados\]\[([^\]]+)\]/);
                if (!m) return;
                const key = normalizeKey(m[1]);
                if (!map.has(key)) map.set(key, el);
            });
            return map;
        };

        function calcular() {
            if (calculado.dataset.calculating === '1') return;
            calculado.dataset.calculating = '1';

            const inputsMap = getInputsMap();

            let expr = formula;
            variables.forEach(function(variable) {
                let nombre = variable.replace(/[\[\]]/g, '');
                const input = inputsMap.get(normalizeKey(nombre));
                let val = input && input.value ? parseFloat(input.value.replace(/,/g, '')) : 0;
                // Si el campo es porcentaje y el valor es menor a 1 pero mayor a 0, lo convertimos a entero
                if (input && input.getAttribute('unidad') === '%' && val > 0 && val < 1) {
                    val = val * 100;
                }
                expr = expr.replaceAll(variable, val);
            });
            try {
                // Soporta exponente usando '^' convirtiéndolo a '**' (JS)
                if (expr.indexOf('^') !== -1) {
                    expr = expr.replace(/\^/g, '**');
                }

                // Soporta multiplicación implícita: 2(3+4) o (2+3)4
                expr = expr.replace(/([0-9\.]|\))\s*\(/g, '$1*(');
                expr = expr.replace(/\)\s*([0-9\.-])/g, ')*$1');

                let resultado = eval(expr);
                const prevValue = calculado.value;
                if (!isFinite(resultado) || isNaN(resultado)) {
                    calculado.value = '';
                } else {
                    const decAttr = calculado.getAttribute('data-decimales');
                    const dec = (decAttr !== null && decAttr !== '') ? parseInt(decAttr, 10) : null;
                    // Formateo natural: si hay decimales definidos, respetarlos;
                    // si no, enteros sin ".0" y fracciones tal cual.
                    if (dec !== null && !isNaN(dec)) {
                        calculado.value = Number(resultado).toFixed(dec);
                    } else if (Number.isInteger(resultado)) {
                        calculado.value = String(Math.trunc(resultado));
                    } else {
                        calculado.value = String(resultado);
                    }
                }
                // Efecto visual cuando se calcula
                calculado.style.background = 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)';
                setTimeout(() => {
                    calculado.style.background = 'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)';
                }, 300);

                // Importante: si este valor cambió, notificar para recalcular fórmulas dependientes
                if (calculado.value !== prevValue) {
                    calculado.dispatchEvent(new Event('input', { bubbles: true }));
                }
            } catch (e) {
                calculado.value = '';
                calculado.dispatchEvent(new Event('input', { bubbles: true }));
            } finally {
                calculado.dataset.calculating = '0';
            }
        }
        variables.forEach(function(variable) {
            let nombre = variable.replace(/[\[\]]/g, '');
            const input = getInputsMap().get(normalizeKey(nombre));
            if (input) {
                input.addEventListener('input', calcular);
            }
        });
        calcular();
    });

    // Cabeceras por paciente (se insertan en el snapshot al guardar)
    document.querySelectorAll('.header-builder').forEach((builder) => {
        const titleSelect = builder.querySelector('.header-title-select');
        const titleCustom = builder.querySelector('.header-title-custom');
        const colorInput = builder.querySelector('.header-color-new');
        const beforeSelect = builder.querySelector('.header-insert-before');
        const addBtn = builder.querySelector('.add-header-btn');
        const preview = builder.querySelector('.header-preview-list');
        const hidden = builder.querySelector('.headers-hidden');

        if (!hidden) return;

        const getNextIndex = () => {
            const n = parseInt(hidden.dataset.nextIndex || '0', 10);
            hidden.dataset.nextIndex = String(n + 1);
            return n;
        };

        const getTitle = () => {
            const v = titleSelect ? titleSelect.value : '';
            if (v === '__custom__') {
                return (titleCustom && titleCustom.value ? titleCustom.value : '').trim();
            }
            return (v || '').trim();
        };

        if (titleSelect && titleCustom) {
            titleSelect.addEventListener('change', () => {
                const isCustom = titleSelect.value === '__custom__';
                titleCustom.classList.toggle('d-none', !isCustom);
                if (!isCustom) {
                    titleCustom.value = '';
                } else {
                    titleCustom.focus();
                }
            });
        }

        const examId = builder.dataset.examId;
        if (!examId || !addBtn || !preview) return;

        addBtn.addEventListener('click', () => {
            const titulo = getTitle();
            const color = (colorInput && colorInput.value ? colorInput.value : '#0923E1').trim();
            const before = (beforeSelect && beforeSelect.value ? beforeSelect.value : '__END__').trim();
            if (!titulo) {
                alert('Escribe o selecciona un nombre de cabecera.');
                return;
            }

            const idx = getNextIndex();

            const makeHidden = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                input.dataset.hdrIdx = String(idx);
                hidden.appendChild(input);
            };

            makeHidden(`examenes[${examId}][cabeceras_nuevas][${idx}][titulo]`, titulo);
            makeHidden(`examenes[${examId}][cabeceras_nuevas][${idx}][color]`, color);
            makeHidden(`examenes[${examId}][cabeceras_nuevas][${idx}][before]`, before);

            const row = document.createElement('div');
            row.className = 'header-preview-item';
            row.dataset.hdrIdx = String(idx);
            row.innerHTML = `
                <span class="header-preview-dot" style="background:${color}"></span>
                <span class="header-preview-text">${titulo}</span>
                <button type="button" class="btn btn-sm btn-link header-preview-remove">Quitar</button>
            `;
            row.querySelector('.header-preview-remove').addEventListener('click', () => {
                const key = row.dataset.hdrIdx;
                row.remove();
                hidden.querySelectorAll(`input[data-hdr-idx="${key}"]`).forEach((el) => el.remove());
            });
            preview.appendChild(row);

            // reset simple
            if (titleSelect) titleSelect.value = '';
            if (titleCustom) {
                titleCustom.value = '';
                titleCustom.classList.add('d-none');
            }
        });
    });

    // Validación del formulario antes de enviar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            // Ejemplo: marcar campos obligatorios
            form.querySelectorAll('.form-control[required]').forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
                return false;
            }
            const submitBtn = document.querySelector('.save-btn');
            // Animación del botón de envío
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Guardando...';
            submitBtn.disabled = true;
            setTimeout(() => {
                submitBtn.style.transform = 'scale(0.95)';
            }, 100);
        });
    }

    // Tooltip para campos calculados
    document.querySelectorAll('.calculated-field').forEach(field => {
        field.setAttribute('title', 'Este campo se calcula automáticamente basado en otros valores');
        field.style.cursor = 'help';
    });

    // Efecto hover mejorado para las cards
    document.querySelectorAll('.exam-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.01)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
