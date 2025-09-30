<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conexion/conexion.php';

$cotizacion_id = $_GET['cotizacion_id'] ?? null;
$examenes = [];
$referencia_personalizada = '';

if ($cotizacion_id) {
    // Obtener todos los exámenes y resultados asociados a la cotización
    $sql = "SELECT re.id as id_resultado, re.resultados, e.adicional, e.nombre as nombre_examen
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = :cotizacion_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cotizacion_id' => $cotizacion_id]);
    $examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener la referencia personalizada existente si hay una
    $sql_ref = "SELECT referencia_personalizada FROM cotizaciones WHERE id = :cotizacion_id";
    $stmt_ref = $pdo->prepare($sql_ref);
    $stmt_ref->execute(['cotizacion_id' => $cotizacion_id]);
    $cotizacion_data = $stmt_ref->fetch(PDO::FETCH_ASSOC);
    $referencia_personalizada = $cotizacion_data['referencia_personalizada'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Exámenes - InbiosLab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header-container {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: var(--card-shadow);
        }

        .header-title {
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin: 0;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
        }

        .exam-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .exam-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-3px);
        }

        .exam-card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.2rem 1.5rem;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .exam-card-body {
            padding: 1.5rem;
        }

        .pdf-config-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            border-left: 5px solid #ffc107;
        }

        .pdf-config-header {
            background: var(--warning-gradient);
            color: white;
            padding: 1.2rem 1.5rem;
            border: none;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .parameter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .parameter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reference-info {
            background: var(--info-gradient);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #495057;
        }

        .methodology-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #1976d2;
        }

        .title-section {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .subtitle-section {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
            padding: 0.8rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(79, 172, 254, 0.3);
        }

        .save-btn {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }

        .save-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
            color: white;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .alert-custom {
            background: var(--info-gradient);
            border: none;
            border-radius: 15px;
            color: #495057;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .input-icon {
            position: relative;
        }

        .input-icon .form-control {
            padding-left: 3rem;
        }

        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            z-index: 10;
        }

        .calculated-field {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            border-color: #e17055;
        }

        .form-text-custom {
            background: rgba(102, 126, 234, 0.1);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            border-left: 4px solid #667eea;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .exam-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        .exam-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .exam-card:nth-child(odd) {
            animation-delay: 0.2s;
        }
    </style>
</head>
<body>
<div class="header-container">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="dashboard.php?vista=cotizaciones" class="back-btn">
                <i class="bi bi-arrow-left"></i>
                Volver a Cotizaciones
            </a>
            <h1 class="header-title">
                <i class="bi bi-clipboard-data me-3"></i>
                Resultados de Exámenes
            </h1>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?php if (!empty($examenes)): ?>
        <form method="post" action="dashboard.php?action=guardar">
            <input type="hidden" name="cotizacion_id" value="<?= htmlspecialchars($cotizacion_id) ?>">
            
            <?php foreach ($examenes as $index => $examen): 
                $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];
                $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
            ?>
                <div class="exam-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="exam-card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clipboard-pulse me-2"></i>
                            <span><?= htmlspecialchars($examen['nombre_examen']) ?></span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   name="examenes[<?= $examen['id_resultado'] ?>][imprimir_examen]" 
                                   id="imprimir_examen_<?= $examen['id_resultado'] ?>" 
                                   value="1"
                                   <?= (!isset($resultados['imprimir_examen']) || $resultados['imprimir_examen']) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="imprimir_examen_<?= $examen['id_resultado'] ?>">
                                <i class="bi bi-printer me-1"></i>
                                Imprimir
                            </label>
                        </div>
                    </div>
                    <div class="exam-card-body">
                        <input type="hidden" name="examenes[<?= $examen['id_resultado'] ?>][id_resultado]" 
                               value="<?= htmlspecialchars($examen['id_resultado']) ?>">
                        
                        <?php foreach ($adicional as $item): ?>
                            <?php if ($item['tipo'] === 'Título'): ?>
                                <div class="title-section" style="background: <?= $item['color_fondo'] ?? 'var(--primary-gradient)' ?>; color: <?= $item['color_texto'] ?? 'white' ?>;">
                                    <i class="bi bi-bookmark-star me-2"></i>
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </div>
                            <?php elseif ($item['tipo'] === 'Subtítulo'): ?>
                                <div class="subtitle-section" style="background: <?= $item['color_fondo'] ?? 'var(--success-gradient)' ?>; color: <?= $item['color_texto'] ?? 'white' ?>;">
                                    <i class="bi bi-bookmark me-2"></i>
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </div>
                            <?php elseif ($item['tipo'] === 'Campo'): ?>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                        value="<?= htmlspecialchars($resultados[$item['nombre']] ?? '') ?>"
                                        placeholder="Ingrese <?= htmlspecialchars($item['nombre']) ?>">
                                </div>
                            <?php elseif ($item['tipo'] === 'Parámetro'): ?>
                                <div class="parameter-section">
                                    <label class="parameter-label">
                                        <i class="bi bi-graph-up me-1"></i>
                                        <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                        <?php if (!empty($item['unidad'])): ?>
                                            <span class="badge bg-info ms-2"><?= htmlspecialchars($item['unidad']) ?></span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php if (!empty($item['opciones'])): ?>
                                        <select name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]" 
                                                class="form-control">
                                            <option value="">Seleccione una opción...</option>
                                            <?php foreach ($item['opciones'] as $opcion): ?>
                                                <option value="<?= htmlspecialchars($opcion) ?>"
                                                    <?= (isset($resultados[$item['nombre']]) && $resultados[$item['nombre']] == $opcion) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($opcion) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <div class="input-icon">
                                            <?php if (!empty($item['formula'])): ?>
                                                <i class="bi bi-calculator"></i>
                                            <?php else: ?>
                                                <i class="bi bi-123"></i>
                                            <?php endif; ?>
                                            <input
                                                type="text"
                                                name="examenes[<?= $examen['id_resultado'] ?>][resultados][<?= htmlspecialchars($item['nombre']) ?>]"
                                                class="form-control<?= !empty($item['formula']) ? ' campo-calculado calculated-field' : '' ?>"
                                                value="<?= isset($resultados[$item['nombre']]) ? htmlspecialchars($resultados[$item['nombre']]) : '' ?>"
                                                placeholder="<?= !empty($item['formula']) ? 'Valor calculado automáticamente' : 'Ingrese el valor' ?>"
                                                <?= !empty($item['formula']) ? 'data-formula="' . htmlspecialchars($item['formula']) . '" readonly' : '' ?>
                                            >
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['referencias'])): ?>
                                        <div class="reference-info">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Valores de Referencia:</strong>
                                            <?php foreach ($item['referencias'] as $ref): ?>
                                                <span class="badge bg-primary ms-1"><?= htmlspecialchars($ref['desc'] . ' ' . $ref['valor']) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['metodologia'])): ?>
                                        <div class="methodology-info">
                                            <i class="bi bi-gear me-1"></i>
                                            <strong>Metodología:</strong> <?= htmlspecialchars($item['metodologia']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Campo para referencia personalizada en PDF -->
            <div class="pdf-config-card">
                <div class="pdf-config-header">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-file-earmark-pdf me-2"></i>
                        Configuración para Impresión PDF
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="referencia_personalizada" class="form-label">
                            <i class="bi bi-tag me-2"></i>
                            <strong>Referencia Personalizada para PDF</strong>
                        </label>
                        <div class="input-icon">
                            <i class="bi bi-pencil-square"></i>
                            <input type="text" 
                                   id="referencia_personalizada" 
                                   name="referencia_personalizada" 
                                   class="form-control" 
                                   placeholder="Ej: Particular, Examen Médico, Empresa ABC..."
                                   value="<?= htmlspecialchars($referencia_personalizada) ?>"
                                   maxlength="100">
                        </div>
                        <div class="form-text-custom">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>¿Para qué sirve este campo?</strong><br>
                            Permite cambiar la referencia que aparece en el PDF de resultados. En lugar de mostrar 
                            el nombre real de la empresa o convenio, aparecerá el texto que escribas aquí. 
                            <strong>Déjalo vacío</strong> si quieres que aparezca la referencia original.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="save-btn">
                    <i class="bi bi-save me-2"></i>
                    Guardar Resultados
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert-custom text-center">
            <i class="bi bi-info-circle-fill display-4 mb-3"></i>
            <h4>No hay exámenes asociados</h4>
            <p class="mb-0">No se encontraron exámenes para esta cotización.</p>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

    // Lógica de cálculos automáticos mejorada
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
            const submitBtn = document.querySelector('.save-btn');
            
            // Animación del botón de envío
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Guardando...';
            submitBtn.disabled = true;
            
            // Efecto de loading
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
</script>
</body>
</html>