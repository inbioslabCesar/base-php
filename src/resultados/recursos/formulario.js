
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
