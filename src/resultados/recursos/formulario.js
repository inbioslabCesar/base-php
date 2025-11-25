
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

        function calcular() {
            let expr = formula;
            variables.forEach(function(variable) {
                let nombre = variable.replace(/[\[\]]/g, '').trim();
                let input = document.querySelector(`[name="examenes[${idResultado}][resultados][${nombre}]"]`);
                let val = input && input.value ? parseFloat(input.value) : 0;
                expr = expr.replaceAll(variable, val);
            });
            try {
                let resultado = eval(expr);
                calculado.value = (!isFinite(resultado) || isNaN(resultado)) ? '' : resultado.toFixed(1);
                // Efecto visual cuando se calcula
                calculado.style.background = 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)';
                setTimeout(() => {
                    calculado.style.background = 'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)';
                }, 300);
            } catch (e) {
                calculado.value = '';
            }
        }
        variables.forEach(function(variable) {
            let nombre = variable.replace(/[\[\]]/g, '').trim();
            let input = document.querySelector(`[name="examenes[${idResultado}][resultados][${nombre}]"]`);
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
