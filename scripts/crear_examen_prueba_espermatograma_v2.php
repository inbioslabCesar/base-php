<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$codigo = 'TEST-ESP-V2-' . date('YmdHis');
$nombre = 'Prueba Espermatograma V2 Columnas';
$descripcion = 'Examen de prueba para validar formato dinamico en columnas (motilidad por tiempos).';
$area = 'Andrologia';
$metodologia = 'Microscopia';
$tiempo_respuesta = '24 horas';
$preanaliticaCliente = '';
$preanaliticaReferencias = '';
$tipoMuestra = 'Semen';
$tipoTubo = 'Frasco Esteril';
$observaciones = 'Creado automaticamente para prueba de columnas v2.';
$precioPublico = 0.00;
$vigente = 1;

$payload = [
    'schema_version' => 2,
    'layout' => [
        'columns' => [
            [
                'id' => 'motilidad',
                'label' => 'Motilidad',
                'kind' => 'text',
                'editable' => false,
                'visible_capture' => true,
                'visible_pdf' => true,
                'width' => '30%',
                'order' => 1,
            ],
            [
                'id' => 'eval_1_hora',
                'label' => 'Eval 1 Hora',
                'kind' => 'result',
                'editable' => true,
                'visible_capture' => true,
                'visible_pdf' => true,
                'width' => '17%',
                'order' => 2,
            ],
            [
                'id' => 'eval_2_horas',
                'label' => 'Eval 2 Horas',
                'kind' => 'result',
                'editable' => true,
                'visible_capture' => true,
                'visible_pdf' => true,
                'width' => '17%',
                'order' => 3,
            ],
            [
                'id' => 'unidades',
                'label' => 'Unidades',
                'kind' => 'text',
                'editable' => false,
                'visible_capture' => true,
                'visible_pdf' => true,
                'width' => '12%',
                'order' => 4,
            ],
            [
                'id' => 'valores_referenciales',
                'label' => 'Valores Referenciales',
                'kind' => 'reference',
                'editable' => false,
                'visible_capture' => true,
                'visible_pdf' => true,
                'width' => '24%',
                'order' => 5,
            ],
        ],
        'rows' => [
            [
                'id' => 'hdr_concentracion',
                'type' => 'title',
                'label' => 'Concentracion',
                'cells' => [],
            ],
            [
                'id' => 'fila_espermatozoides_contados',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Espermatozoides-Contados',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_factor_conversion',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Factor-Conversion',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_espermatozoides_ml',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Espermatozoides/ml',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => 'X10^6',
                    'valores_referenciales' => '(20 - 250)',
                ],
            ],
            [
                'id' => 'fila_espermatozoides_eyaculado',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Espermatozoides/Eyaculado',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => 'X10^6',
                    'valores_referenciales' => '(40 - 500)',
                ],
            ],
            [
                'id' => 'hdr_motilidad',
                'type' => 'title',
                'label' => 'Motilidad (grado de mov.)',
                'cells' => [],
            ],
            [
                'id' => 'fila_lineal_rapido',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => '3. Lineear Rapido',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '(Grago 3: > 25)',
                ],
            ],
            [
                'id' => 'fila_lento_ondulan',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => '2. Lento u Ondulan',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '(Grado 3 + Grado 2: > 50%)',
                ],
            ],
            [
                'id' => 'fila_movimiento_situ',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => '1. Movimiento in situ',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_inmoviles',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => '0. Inmoviles',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'hdr_morfologia',
                'type' => 'title',
                'label' => 'Morfologia',
                'cells' => [],
            ],
            [
                'id' => 'fila_normales',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Normales',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '(Normales > 60)',
                ],
            ],
            [
                'id' => 'fila_inmaduros',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Inmaduros',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_anomalias_cabeza',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Anomalias de cabeza',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_anom_seg_intermedio',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Anom. del segm. Intermedio',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_anomalias_cola',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'motilidad' => 'Anomalias de la cola',
                    'eval_1_hora' => '',
                    'eval_2_horas' => '',
                    'unidades' => '%',
                    'valores_referenciales' => '',
                ],
            ],
            [
                'id' => 'fila_conclusion',
                'type' => 'subtitle',
                'label' => 'Conclusion',
                'cells' => [],
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
