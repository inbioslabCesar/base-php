<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/conexion/conexion.php';

$codigo = 'TEST-ESP-FX-V2-' . date('YmdHis');
$nombre = 'Prueba Espermatograma V2 Formulas';
$descripcion = 'Examen de prueba para validar formulas en formato dinamico v2.';
$area = 'Andrologia';
$metodologia = 'Microscopia';
$tiempo_respuesta = '24 horas';
$preanaliticaCliente = '';
$preanaliticaReferencias = '';
$tipoMuestra = 'Semen';
$tipoTubo = 'Frasco Esteril';
$observaciones = 'Prueba automatica de formulas v2.';
$precioPublico = 0.00;
$vigente = 1;

$payload = [
    'schema_version' => 2,
    'layout' => [
        'columns' => [
            ['id' => 'item', 'label' => 'Item', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '34%', 'order' => 1],
            ['id' => 'eval_1h', 'label' => 'Eval 1 Hora', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '16%', 'order' => 2],
            ['id' => 'eval_2h', 'label' => 'Eval 2 Horas', 'kind' => 'result', 'editable' => true, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '16%', 'order' => 3],
            ['id' => 'unidad', 'label' => 'Unidades', 'kind' => 'text', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '10%', 'order' => 4],
            ['id' => 'referencia', 'label' => 'Valores Referenciales', 'kind' => 'reference', 'editable' => false, 'visible_capture' => true, 'visible_pdf' => true, 'width' => '24%', 'order' => 5],
        ],
        'rows' => [
            ['id' => 'hdr_concentracion', 'type' => 'title', 'label' => 'Concentracion', 'cells' => []],
            [
                'id' => 'r_contados',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'item' => 'Espermatozoides-Contados',
                    'eval_1h' => '',
                    'eval_2h' => '',
                    'unidad' => '',
                    'referencia' => '',
                ],
                'formulas' => [],
            ],
            [
                'id' => 'r_factor',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'item' => 'Factor-Conversion',
                    'eval_1h' => '',
                    'eval_2h' => '',
                    'unidad' => '',
                    'referencia' => '',
                ],
                'formulas' => [],
            ],
            [
                'id' => 'r_ml',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'item' => 'Espermatozoides/ml',
                    'eval_1h' => '',
                    'eval_2h' => '',
                    'unidad' => 'X10^6',
                    'referencia' => '(20 - 250)',
                ],
                'formulas' => [
                    'eval_1h' => '[r_contados:eval_1h]*[r_factor:eval_1h]',
                    'eval_2h' => '[r_contados:eval_2h]*[r_factor:eval_2h]',
                ],
            ],
            [
                'id' => 'r_eyaculado',
                'type' => 'data',
                'label' => '',
                'cells' => [
                    'item' => 'Espermatozoides/Eyaculado',
                    'eval_1h' => '',
                    'eval_2h' => '',
                    'unidad' => 'X10^6',
                    'referencia' => '(40 - 500)',
                ],
                'formulas' => [
                    'eval_1h' => '[r_ml:eval_1h]*2',
                    'eval_2h' => '[r_ml:eval_2h]*2',
                ],
            ],
            ['id' => 'hdr_motilidad', 'type' => 'title', 'label' => 'Motilidad (grado de mov.)', 'cells' => []],
            ['id' => 'r_g3', 'type' => 'data', 'label' => '', 'cells' => ['item' => '3. Lineear Rapido', 'eval_1h' => '', 'eval_2h' => '', 'unidad' => '%', 'referencia' => '(Grado 3: > 25)'], 'formulas' => []],
            ['id' => 'r_g2', 'type' => 'data', 'label' => '', 'cells' => ['item' => '2. Lento u Ondulan', 'eval_1h' => '', 'eval_2h' => '', 'unidad' => '%', 'referencia' => '(Grado 3 + Grado 2: > 50%)'], 'formulas' => []],
            ['id' => 'r_total_motilidad', 'type' => 'data', 'label' => '', 'cells' => ['item' => 'Total Motilidad (G3+G2)', 'eval_1h' => '', 'eval_2h' => '', 'unidad' => '%', 'referencia' => '> 50%'], 'formulas' => ['eval_1h' => '[r_g3:eval_1h]+[r_g2:eval_1h]', 'eval_2h' => '[r_g3:eval_2h]+[r_g2:eval_2h]']],
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
