<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$codigo = 'TEST-ESP-LARGO-V2-' . date('YmdHis');
$nombre = 'Prueba Espermatograma V2 Multipagina';
$descripcion = 'Examen de estres para evaluar comportamiento PDF con multiples columnas por hora y mas de una pagina.';
$area = 'Andrologia';
$metodologia = 'Microscopia';
$tiempo_respuesta = '48 horas';
$preanaliticaCliente = '';
$preanaliticaReferencias = '';
$tipoMuestra = 'Semen';
$tipoTubo = 'Frasco Esteril';
$observaciones = 'Generado automaticamente para prueba multipagina v2.';
$precioPublico = 5.00;
$vigente = 1;

$columns = [
    ['id' => 'parametro', 'label' => 'Parametro', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '26%', 'order' => 1],
    ['id' => 'eval_1h', 'label' => 'Eval 1 Hora', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 2],
    ['id' => 'eval_2h', 'label' => 'Eval 2 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 3],
    ['id' => 'eval_3h', 'label' => 'Eval 3 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 4],
    ['id' => 'eval_4h', 'label' => 'Eval 4 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 5],
    ['id' => 'eval_6h', 'label' => 'Eval 6 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 6],
    ['id' => 'eval_8h', 'label' => 'Eval 8 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 7],
    ['id' => 'unidad', 'label' => 'Unidades', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '7%', 'order' => 8],
    ['id' => 'referencia', 'label' => 'Valores Referenciales', 'kind' => 'reference', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '17%', 'order' => 9],
];

$sections = [
    ['id' => 'sec_examen_fisico', 'label' => 'Examen Fisico', 'rows' => 18],
    ['id' => 'sec_examen_microscopico', 'label' => 'Examen Microscopico', 'rows' => 22],
    ['id' => 'sec_concentracion', 'label' => 'Concentracion', 'rows' => 22],
    ['id' => 'sec_vitalidad', 'label' => 'Vitalidad', 'rows' => 18],
    ['id' => 'sec_motilidad', 'label' => 'Motilidad (grado de mov.)', 'rows' => 24],
    ['id' => 'sec_morfologia', 'label' => 'Morfologia', 'rows' => 20],
];

$rows = [];
foreach ($sections as $secIndex => $sec) {
    $rows[] = [
        'id' => 'hdr_' . $sec['id'],
        'type' => 'title',
        'label' => $sec['label'],
        'cells' => [],
    ];

    for ($i = 1; $i <= $sec['rows']; $i++) {
        $rowId = $sec['id'] . '_row_' . $i;
        $rows[] = [
            'id' => $rowId,
            'type' => 'data',
            'label' => '',
            'cells' => [
                'parametro' => $sec['label'] . ' - Parametro ' . $i,
                'eval_1h' => '',
                'eval_2h' => '',
                'eval_3h' => '',
                'eval_4h' => '',
                'eval_6h' => '',
                'eval_8h' => '',
                'unidad' => '%',
                'referencia' => 'Rango ' . $i . ' (' . max(0, $i - 5) . ' - ' . (50 + $i) . ')',
            ],
            'formulas' => [],
        ];
    }

    // Fila de promedio por seccion con formula para probar comportamiento tambien.
    $rows[] = [
        'id' => $sec['id'] . '_promedio',
        'type' => 'data',
        'label' => '',
        'cells' => [
            'parametro' => $sec['label'] . ' - Promedio 1-2h',
            'eval_1h' => '',
            'eval_2h' => '',
            'eval_3h' => '',
            'eval_4h' => '',
            'eval_6h' => '',
            'eval_8h' => '',
            'unidad' => '%',
            'referencia' => 'Informativo',
        ],
        'formulas' => [
            'eval_3h' => '[' . $sec['id'] . '_row_1:eval_1h]+[' . $sec['id'] . '_row_1:eval_2h]'
        ],
    ];
}

$rows[] = [
    'id' => 'hdr_conclusion',
    'type' => 'subtitle',
    'label' => 'Conclusion',
    'cells' => [],
];

$rows[] = [
    'id' => 'conclusion_texto',
    'type' => 'data',
    'label' => '',
    'cells' => [
        'parametro' => 'Comentario final',
        'eval_1h' => '',
        'eval_2h' => '',
        'eval_3h' => '',
        'eval_4h' => '',
        'eval_6h' => '',
        'eval_8h' => '',
        'unidad' => '',
        'referencia' => 'Completar por laboratorista',
    ],
    'formulas' => [],
];

$payload = [
    'schema_version' => 2,
    'layout' => [
        'columns' => $columns,
        'rows' => $rows,
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
echo "filas_totales=" . count($rows) . "\n";
