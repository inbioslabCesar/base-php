
// JS personalizado para formulario de resultados
document.addEventListener('DOMContentLoaded', function () {
    const buildScrollStateKey = () => {
        const params = new URLSearchParams(window.location.search || '');
        const cotizacionId = String(params.get('cotizacion_id') || '').trim();
        return cotizacionId ? `resultados_form_scroll_${cotizacionId}` : 'resultados_form_scroll';
    };

    const scrollStateKey = buildScrollStateKey();
    const savedScrollRaw = sessionStorage.getItem(scrollStateKey);
    if (savedScrollRaw) {
        try {
            const data = JSON.parse(savedScrollRaw);
            const y = Number(data && data.y);
            const at = Number(data && data.at);
            const isRecent = Number.isFinite(at) && (Date.now() - at) < 5 * 60 * 1000;
            if (Number.isFinite(y) && y >= 0 && isRecent) {
                window.scrollTo({ top: y, left: 0, behavior: 'auto' });
            }
        } catch (error) {
            // Ignorar datos corruptos de sesion.
        }
        sessionStorage.removeItem(scrollStateKey);
    }

    // Animación de aparición escalonada para las cards
    const cards = document.querySelectorAll('.exam-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    const examContainer = document.getElementById('examCardsContainer');
    const orderInputsContainer = document.getElementById('examOrderInputs');
    const formGuardar = document.querySelector('form[action="dashboard.php?action=guardar"]');
    const actionsDock = document.getElementById('resultsActionsDock');
    const dockModeToggle = document.getElementById('resultsDockModeToggle');
    const dockModeStorageKey = 'resultados_actions_dock_mode';
    const canUseDrag = window.matchMedia && window.matchMedia('(pointer: fine)').matches;

    const getExamCards = () => {
        if (!examContainer) return [];
        return Array.from(examContainer.querySelectorAll('.exam-card[data-id-resultado]'));
    };

    const syncExamOrderInputs = () => {
        if (!orderInputsContainer) return;
        orderInputsContainer.innerHTML = '';
        getExamCards().forEach((card) => {
            const idResultado = String(card.getAttribute('data-id-resultado') || '').trim();
            if (!idResultado) return;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'exam_order[]';
            hidden.value = idResultado;
            orderInputsContainer.appendChild(hidden);
        });
    };

    if (examContainer) {
        let draggedCard = null;
        let dragArmedCard = null;

        const moveCard = (card, direction) => {
            if (!card) return;

            let moved = false;
            if (direction === 'up') {
                const prev = card.previousElementSibling;
                if (prev && prev.classList.contains('exam-card')) {
                    examContainer.insertBefore(card, prev);
                    moved = true;
                }
            } else if (direction === 'down') {
                const next = card.nextElementSibling;
                if (next && next.classList.contains('exam-card')) {
                    examContainer.insertBefore(next, card);
                    moved = true;
                }
            }

            if (!moved) {
                return;
            }

            syncExamOrderInputs();
        };

        if (canUseDrag) {
            getExamCards().forEach((card) => {
                card.draggable = true;
            });

            examContainer.addEventListener('pointerdown', (event) => {
                const handle = event.target.closest('.js-exam-drag-handle');
                if (!handle) {
                    dragArmedCard = null;
                    return;
                }
                dragArmedCard = handle.closest('.exam-card');
            });

            examContainer.addEventListener('dragstart', (event) => {
                const card = event.target.closest('.exam-card');
                if (!card) {
                    event.preventDefault();
                    return;
                }

                const startedFromHandle = !!event.target.closest('.js-exam-drag-handle');
                const isArmedCard = dragArmedCard && dragArmedCard === card;
                if (!startedFromHandle && !isArmedCard) {
                    event.preventDefault();
                    return;
                }

                draggedCard = card;
                draggedCard.classList.add('dragging');
                dragArmedCard = null;
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', card.getAttribute('data-id-resultado') || '');
                }
            });

            examContainer.addEventListener('dragover', (event) => {
                if (!draggedCard) return;
                const overCard = event.target.closest('.exam-card');
                if (!overCard || overCard === draggedCard) return;

                event.preventDefault();
                const rect = overCard.getBoundingClientRect();
                const placeAfter = event.clientY > (rect.top + rect.height / 2);
                if (placeAfter) {
                    if (overCard.nextElementSibling !== draggedCard) {
                        examContainer.insertBefore(draggedCard, overCard.nextElementSibling);
                    }
                } else if (overCard !== draggedCard.nextElementSibling) {
                    examContainer.insertBefore(draggedCard, overCard);
                }
            });

            examContainer.addEventListener('drop', (event) => {
                if (!draggedCard) return;
                event.preventDefault();
                syncExamOrderInputs();
            });

            examContainer.addEventListener('dragend', () => {
                if (draggedCard) {
                    draggedCard.classList.remove('dragging');
                    draggedCard = null;
                    syncExamOrderInputs();
                }
                dragArmedCard = null;
            });
        }

        examContainer.addEventListener('click', (event) => {
            if (event.defaultPrevented) return;
            const upBtn = event.target.closest('.js-exam-move-up');
            const downBtn = event.target.closest('.js-exam-move-down');
            if (!upBtn && !downBtn) return;

            event.preventDefault();

            const card = event.target.closest('.exam-card');
            if (!card) return;

            moveCard(card, upBtn ? 'up' : 'down');
        });

        // Fallback directo: algunos navegadores moviles/tactiles pueden no disparar
        // de forma confiable el delegado sobre contenedores con controles complejos.
        getExamCards().forEach((card) => {
            const upBtn = card.querySelector('.js-exam-move-up');
            const downBtn = card.querySelector('.js-exam-move-down');

            if (upBtn) {
                upBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    moveCard(card, 'up');
                });
            }

            if (downBtn) {
                downBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    moveCard(card, 'down');
                });
            }
        });

        syncExamOrderInputs();
    }

    if (formGuardar) {
        formGuardar.addEventListener('submit', () => {
            syncExamOrderInputs();
        });
    }

    if (actionsDock) {
        let ticking = false;
        const isMobileViewport = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
        const storedDockMode = localStorage.getItem(dockModeStorageKey);
        let dockMode;
        if (storedDockMode === 'always' || storedDockMode === 'auto') {
            dockMode = storedDockMode;
        } else {
            dockMode = isMobileViewport ? 'always' : 'auto';
        }

        const updateDockModeToggle = () => {
            if (!dockModeToggle) return;
            const pinned = dockMode === 'always';
            dockModeToggle.classList.toggle('is-pinned', pinned);
            dockModeToggle.setAttribute('aria-pressed', pinned ? 'true' : 'false');
            dockModeToggle.innerHTML = pinned
                ? '<i class="bi bi-pin-angle-fill me-1"></i>Siempre visible'
                : '<i class="bi bi-pin-angle me-1"></i>Fijar';
            dockModeToggle.title = pinned
                ? 'La barra esta siempre visible. Clic para volver a modo automatico'
                : 'La barra aparece al hacer scroll. Clic para dejarla siempre visible';
        };

        const updateDockVisibility = () => {
            const shouldShow = dockMode === 'always' || window.scrollY > 220;
            actionsDock.classList.toggle('is-visible', shouldShow);
            ticking = false;
        };

        const onScroll = () => {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(updateDockVisibility);
        };

        updateDockModeToggle();
        updateDockVisibility();

        if (dockModeToggle) {
            dockModeToggle.addEventListener('click', () => {
                dockMode = dockMode === 'always' ? 'auto' : 'always';
                localStorage.setItem(dockModeStorageKey, dockMode);
                updateDockModeToggle();
                updateDockVisibility();
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
    }

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
    const calculatedFields = [];
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

        function calcular(force = false) {
            if (calculado.dataset.calculating === '1') return;
            calculado.dataset.calculating = '1';

            const currentValue = String(calculado.value ?? '').trim();
            if (!force && currentValue !== '') {
                calculado.dataset.calculating = '0';
                return;
            }

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
                if (prevValue !== calculado.value) {
                    // Propaga cambios a campos calculados dependientes y a validación en tiempo real.
                    calculado.dispatchEvent(new Event('input', { bubbles: true }));
                }
                // Efecto visual cuando se calcula
                calculado.style.background = 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)';
                setTimeout(() => {
                    calculado.style.background = 'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)';
                }, 300);

                // Evitar cascadas recursivas entre campos calculados.
            } catch (e) {
                calculado.value = '';
            } finally {
                calculado.dataset.calculating = '0';
            }
        }
        variables.forEach(function(variable) {
            let nombre = variable.replace(/[\[\]]/g, '');
            const input = getInputsMap().get(normalizeKey(nombre));
            if (input) {
                input.addEventListener('input', function() {
                    calcular(true);
                });
            }
        });
        calculatedFields.push({ el: calculado, calcular });
    });

    // Recalcula en cadena al cargar para evitar valores guardados obsoletos
    // cuando hay fórmulas dependientes (A usa B y B también es calculado).
    const maxFormulaPasses = Math.max(1, calculatedFields.length + 1);
    for (let pass = 0; pass < maxFormulaPasses; pass++) {
        let changedAny = false;
        calculatedFields.forEach(({ el, calcular }) => {
            const before = String(el.value ?? '');
            calcular(true);
            if (String(el.value ?? '') !== before) {
                changedAny = true;
            }
        });
        if (!changedAny) {
            break;
        }
    }

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

    const normalizarValor = (v) => {
        if (v === null || v === undefined) return '';
        return String(v).trim();
    };

    const examenTieneCambio = (card) => {
        const campos = card.querySelectorAll('[name*="[resultados]["]');
        for (const campo of campos) {
            const inicial = normalizarValor(campo.getAttribute('data-initial-value') || '');
            const actual = normalizarValor(campo.value);
            if (inicial !== actual) {
                return true;
            }
        }
        return false;
    };

        const showDecisionModal = (examenNombre) => {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.inset = '0';
                overlay.style.background = 'rgba(0,0,0,0.45)';
                overlay.style.zIndex = '9999';
                overlay.style.display = 'flex';
                overlay.style.alignItems = 'center';
                overlay.style.justifyContent = 'center';
                overlay.innerHTML = `
                    <div class="card shadow" style="width:min(92vw,520px);border-radius:12px;">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Cambio detectado</h5>
                            <p class="card-text mb-3">Se detectó cambio en <strong>${examenNombre}</strong>.<br>¿Cómo deseas registrarlo?</p>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="applyDecisionToAll">
                                <label class="form-check-label" for="applyDecisionToAll">
                                    Aplicar esta decisión a los demás exámenes modificados
                                </label>
                            </div>
                            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary" data-choice="correction">No, solo corrección</button>
                                <button type="button" class="btn btn-primary" data-choice="repeat">Sí, prueba repetida</button>
                                <button type="button" class="btn btn-outline-danger" data-choice="cancel">Cancelar guardado</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);

                const checkboxApply = overlay.querySelector('#applyDecisionToAll');

                const close = (choice) => {
                    overlay.remove();
                    resolve({
                        choice,
                        applyAll: checkboxApply ? checkboxApply.checked : false,
                    });
                };

                overlay.querySelectorAll('button[data-choice]').forEach((btn) => {
                    btn.addEventListener('click', () => close(btn.getAttribute('data-choice')));
                });

                overlay.addEventListener('click', (ev) => {
                    if (ev.target === overlay) {
                        close('cancel');
                    }
                });
            });
        };

        const showReasonModal = (examenNombre) => {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.style.position = 'fixed';
                overlay.style.inset = '0';
                overlay.style.background = 'rgba(0,0,0,0.45)';
                overlay.style.zIndex = '10000';
                overlay.style.display = 'flex';
                overlay.style.alignItems = 'center';
                overlay.style.justifyContent = 'center';
                overlay.innerHTML = `
                    <div class="card shadow" style="width:min(92vw,560px);border-radius:12px;">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Motivo de repetición</h5>
                            <p class="card-text mb-3">Indica el motivo para registrar consumo adicional en <strong>${examenNombre}</strong>.</p>
                            <textarea class="form-control mb-3" rows="3" placeholder="Ej: Control por resultado fuera de rango"></textarea>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="applyReasonToAll">
                                <label class="form-check-label" for="applyReasonToAll">
                                    Reutilizar este motivo para los demás exámenes repetidos
                                </label>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary" data-action="cancel">Cancelar</button>
                                <button type="button" class="btn btn-primary" data-action="ok">Confirmar repetición</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
                const textarea = overlay.querySelector('textarea');
                const checkboxApply = overlay.querySelector('#applyReasonToAll');
                textarea.focus();

                const close = (value) => {
                    overlay.remove();
                    resolve(value);
                };

                overlay.querySelector('[data-action="cancel"]').addEventListener('click', () => close(null));
                overlay.querySelector('[data-action="ok"]').addEventListener('click', () => {
                    const motivo = normalizarValor(textarea.value);
                    if (!motivo) {
                        window.alert('Debe ingresar motivo para registrar consumo por repetición.');
                        textarea.focus();
                        return;
                    }
                    close({
                        motivo,
                        applyAll: checkboxApply ? checkboxApply.checked : false,
                    });
                });

                overlay.addEventListener('click', (ev) => {
                    if (ev.target === overlay) {
                        close(null);
                    }
                });
            });
        };

    // Validación del formulario antes de enviar
    const form = document.querySelector('form[action="dashboard.php?action=guardar"]');
    if (form) {
        form.addEventListener('submit', async function(e) {
            if (form.dataset.forceNativeSubmit === '1') {
                return;
            }
            if (form.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }
            e.preventDefault();

            let decisionGlobal = null;
            let motivoGlobalRepeticion = null;

            const cards = Array.from(document.querySelectorAll('.exam-card'));
            for (const card of cards) {
                const hasReceta = card.getAttribute('data-has-receta') === '1';
                const teniaPrevio = card.getAttribute('data-tenia-previo') === '1';
                if (!hasReceta || !teniaPrevio) {
                    continue;
                }

                const cambio = examenTieneCambio(card);
                if (!cambio) {
                    continue;
                }

                const examenNombre = card.getAttribute('data-examen-nombre') || 'este examen';
                const confirmadaInput = card.querySelector('.js-repeticion-confirmada');
                const motivoInput = card.querySelector('.js-repeticion-motivo');
                if (!confirmadaInput || !motivoInput) {
                    continue;
                }

                let choiceData = null;
                if (decisionGlobal !== null) {
                    choiceData = { choice: decisionGlobal, applyAll: true };
                } else {
                    choiceData = await showDecisionModal(examenNombre);
                }

                const choice = choiceData ? choiceData.choice : 'cancel';
                if (choice === 'repeat') {
                    let motivoTxt = motivoGlobalRepeticion;
                    if (!motivoTxt) {
                        const reasonData = await showReasonModal(examenNombre);
                        if (!reasonData || !reasonData.motivo) {
                            return false;
                        }
                        motivoTxt = reasonData.motivo;
                        if (reasonData.applyAll) {
                            motivoGlobalRepeticion = motivoTxt;
                        }
                    }

                    if (!motivoTxt) {
                        return false;
                    }

                    if (choiceData && choiceData.applyAll) {
                        decisionGlobal = 'repeat';
                    }
                    confirmadaInput.value = '1';
                    motivoInput.value = motivoTxt;
                } else if (choice === 'correction') {
                    if (choiceData && choiceData.applyAll) {
                        decisionGlobal = 'correction';
                    }
                    confirmadaInput.value = '0';
                    motivoInput.value = '';
                } else {
                    return false;
                }
            }

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
                alert('Por favor, completa todos los campos obligatorios.');
                return false;
            }

            const submitButtons = Array.from(document.querySelectorAll('.js-save-submit'));

            const setSavingState = () => {
                submitButtons.forEach((btn) => {
                    if (!btn.dataset.originalHtml) {
                        btn.dataset.originalHtml = btn.innerHTML;
                    }
                    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Guardando...';
                    btn.disabled = true;
                    setTimeout(() => {
                        btn.style.transform = 'scale(0.95)';
                    }, 100);
                });
            };

            const restoreButtons = () => {
                submitButtons.forEach((btn) => {
                    if (btn.dataset.originalHtml) {
                        btn.innerHTML = btn.dataset.originalHtml;
                    }
                    btn.disabled = false;
                    btn.style.transform = '';
                });
            };

            const submitNativeFallback = () => {
                sessionStorage.setItem(scrollStateKey, JSON.stringify({ y: window.scrollY, at: Date.now() }));
                form.dataset.submitting = '1';
                form.dataset.forceNativeSubmit = '1';
                HTMLFormElement.prototype.submit.call(form);
            };

            setSavingState();
            form.dataset.submitting = '1';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                if (!response.ok || contentType.indexOf('application/json') === -1) {
                    submitNativeFallback();
                    return;
                }

                const payload = await response.json();
                if (!payload || payload.success !== true) {
                    restoreButtons();
                    form.dataset.submitting = '0';
                    const msgError = payload && payload.message ? payload.message : 'No se pudo guardar resultados.';
                    alert(msgError);
                    return false;
                }

                const msgOk = payload.message || 'Resultados guardados correctamente.';
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: msgOk,
                        showConfirmButton: false,
                        timer: 2600,
                        timerProgressBar: true,
                    });
                } else {
                    alert(msgOk);
                }

                restoreButtons();
                form.dataset.submitting = '0';
            } catch (error) {
                submitNativeFallback();
            }
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
