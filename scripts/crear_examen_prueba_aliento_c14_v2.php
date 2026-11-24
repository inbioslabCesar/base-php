<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$codigo = 'TEST-ALIENTO-C14-V2-' . date('YmdHis');
$nombre = 'Test de Aliento C14 (Formato Dinamico v2)';
$descripcion = 'Formato profesional para prueba de aliento C14 con columnas dinamicas y referencia clinica por rangos.';
$area = 'Gastroenterologia';
$metodologia = 'Espectrometria beta (C14)';
$tiempo_respuesta = 'El mismo dia';
$preanaliticaCliente = 'Ayuno minimo de 6 horas. No fumar ni consumir alcohol 24 horas antes.';
$preanaliticaReferencias = 'Suspender antibioticos, inhibidores de bomba y bismuto segun protocolo medico vigente.';
$tipoMuestra = 'Aire espirado';
$tipoTubo = 'Tarjeta/Captador para C14';
$observaciones = 'Plantilla generada automaticamente para validacion del formato dinamico v2.';
$precioPublico = 0.00;
$vigente = 1;

$payload = [
    'schema_version' => 2,
    'layout' => [
        'columns' => [
            ['id' => 'parametro', 'label' => 'Parametro', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '34%', 'order' => 1],
            ['id' => 'valor_cpm', 'label' => 'Valor (CPM)', 'kind' => 'number', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '14%', 'order' => 2],
            ['id' => 'unidad', 'label' => 'Unidad', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 3],
            ['id' => 'referencia', 'label' => 'Valores referenciales', 'kind' => 'reference', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '22%', 'order' => 4],
            ['id' => 'interpretacion', 'label' => 'Interpretacion', 'kind' => 'select', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '20%', 'order' => 5],
        ],
        'rows' => [
            [
                'id' => 'titulo_prueba',
                'type' => 'title',
                'label' => '14C Test de Aliento',
                'negrita' => true,
                'alineacion' => 'left',
                'color_fondo' => '#eaf3ff',
                'color_texto' => '#0f2a57',
                'cells' => [],
            ],
            [
                'id' => 'subtitulo_resultados',
                'type' => 'subtitle',
                'label' => 'Resultados cuantitativos',
                'cells' => [],
            ],
            [
                'id' => 'fila_estandar_negativo',
                'type' => 'data',
                'cells' => [
                    'parametro' => 'Estandar negativo (control)',
                    'valor_cpm' => '40',
                    'unidad' => 'CPM',
                    'referencia' => '<= 40 CPM',
                    'interpretacion' => 'Control',
                ],
                'select_options' => [
                    'interpretacion' => ['Control'],
                ],
                'decimales' => [
                    'valor_cpm' => 0,
                ],
            ],
            [
                'id' => 'fila_resultado_test',
                'type' => 'data',
                'cells' => [
                    'parametro' => 'Resultado del test',
                    'valor_cpm' => '',
                    'unidad' => 'CPM',
                    'referencia' => '<= 40 Negativo | 41-49 Indeterminado | >= 50 Positivo',
                    'interpretacion' => 'Negativo',
                ],
                'select_options' => [
                    'interpretacion' => ['Negativo', 'Indeterminado', 'Positivo'],
                ],
                'reference_ranges' => [
                    'valor_cpm' => [
                        [
                            'desc' => 'Negativo',
                            'valor' => '<= 40 CPM',
                            'valor_max' => '40',
                            'sexo' => 'cualquiera',
                            'edad_min' => '',
                            'edad_max' => '',
                            'alerta_modo' => 'none',
                            'alerta_color' => '#1b5e20',
                            'mostrar_descripcion_resultado' => 1,
                            'marcar_anormal' => 0,
                        ],
                        [
                            'desc' => 'Indeterminado',
                            'valor' => '41 - 49 CPM',
                            'valor_min' => '41',
                            'valor_max' => '49',
                            'sexo' => 'cualquiera',
                            'edad_min' => '',
                            'edad_max' => '',
                            'alerta_modo' => 'color',
                            'alerta_color' => '#b45309',
                            'mostrar_descripcion_resultado' => 1,
                            'marcar_anormal' => 1,
                        ],
                        [
                            'desc' => 'Positivo',
                            'valor' => '>= 50 CPM',
                            'valor_min' => '50',
                            'sexo' => 'cualquiera',
                            'edad_min' => '',
                            'edad_max' => '',
                            'alerta_modo' => 'both',
                            'alerta_color' => '#b91c1c',
                            'mostrar_descripcion_resultado' => 1,
                            'marcar_anormal' => 1,
                        ],
                    ],
                ],
                'decimales' => [
                    'valor_cpm' => 0,
                ],
            ],
            [
                'id' => 'texto_observaciones',
                'type' => 'long_text',
                'label' => 'Observaciones',
                'template_text' => 'Registrar hallazgos clinicos relevantes y correlacion con contexto del paciente.',
                'template_editable' => true,
                'template_visible_pdf' => true,
                'template_align' => 'left',
                'color_fondo' => '#f8fbff',
                'color_texto' => '#1f2d5c',
            ],
        ],
        'charts' => [
            [
                'id' => 'curva_c14_cpm',
                'label' => 'Grafico C14 (CPM)',
                'type' => 'bar_chart',
                'y_col_id' => 'valor_cpm',
                'min_points_required' => 2,
                'connect_gaps' => true,
                'strict_units' => true,
                'points' => [
                    [
                        'row_id' => 'fila_estandar_negativo',
                        'label' => '0 min (Control)',
                        'x_value' => '0',
                        'enabled' => true,
                    ],
                    [
                        'row_id' => 'fila_resultado_test',
                        'label' => '20 min (Muestra)',
                        'x_value' => '20',
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];

$adicional = json_encode($payload, JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO examenes (
    codigo, nombre, descripcion, area, metodologia, tiempo_respuesta,
    preanalitica_cliente, preanalitica_referencias, tipo_muestra, tipo_tubo,
    observaciones, precio_publico, adicional, vigente
) VALUES (
    :codigo, :nombre, :descripcion, :area, :metodologia, :tiempo_respuesta,
    :preanalitica_cliente, :preanalitica_referencias, :tipo_muestra, :tipo_tubo,
    :observaciones, :precio_publico, :adicional, :vigente
)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':codigo' => $codigo,
    ':nombre' => $nombre,
    ':descripcion' => $descripcion,
    ':area' => $area,
    ':metodologia' => $metodologia,
    ':tiempo_respuesta' => $tiempo_respuesta,
    ':preanalitica_cliente' => $preanaliticaCliente,
    ':preanalitica_referencias' => $preanaliticaReferencias,
    ':tipo_muestra' => $tipoMuestra,
    ':tipo_tubo' => $tipoTubo,
    ':observaciones' => $observaciones,
    ':precio_publico' => $precioPublico,
    ':adicional' => $adicional,
    ':vigente' => $vigente,
]);

$id = (int)$pdo->lastInsertId();

echo "OK\n";
echo "id_examen=" . $id . "\n";
echo "codigo=" . $codigo . "\n";
echo "nombre=" . $nombre . "\n";
