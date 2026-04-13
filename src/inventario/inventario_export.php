<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexion/conexion.php';

$format = strtolower(trim((string)($_GET['format'] ?? 'excel')));
if (!in_array($format, ['excel', 'pdf'], true)) {
    $format = 'excel';
}

try {
    $requiredTables = ['inventario_items', 'inventario_lotes', 'inventario_movimientos'];
    $tablesReady = true;
    $stmtTbl = $pdo->prepare("SHOW TABLES LIKE ?");
    foreach ($requiredTables as $tblName) {
        $stmtTbl->execute([$tblName]);
        if (!$stmtTbl->fetchColumn()) {
            $tablesReady = false;
            break;
        }
    }

    if (!$tablesReady) {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<div style="padding:16px;font-family:Arial,sans-serif;">';
        echo '<h4>No se pudo exportar</h4>';
        echo '<p>Faltan tablas de inventario. Ejecuta sql/agregar_tablas_inventario.sql.</p>';
        echo '<p><a href="dashboard.php?vista=inventario">Volver</a></p>';
        echo '</div>';
        exit;
    }

    $stmtCols = $pdo->query("SHOW COLUMNS FROM inventario_items");
    $defs = $stmtCols ? $stmtCols->fetchAll(\PDO::FETCH_ASSOC) : [];
    $cols = [];
    foreach ($defs as $def) {
        if (!empty($def['Field'])) {
            $cols[] = (string)$def['Field'];
        }
    }
    $hasMarca = in_array('marca', $cols, true);
    $hasPresentacion = in_array('presentacion', $cols, true);

    $stmtColOrigenMov = $pdo->query("SHOW COLUMNS FROM inventario_movimientos LIKE 'origen'");
    $hasOrigenMovCol = (bool)($stmtColOrigenMov && $stmtColOrigenMov->fetch(\PDO::FETCH_ASSOC));
    $whereMovimientosInventario = $hasOrigenMovCol
        ? "COALESCE(m.origen, CASE WHEN COALESCE(m.observacion, '') LIKE 'Transferencia interna #% a laboratorio%' THEN 'transferencia_interna' ELSE 'inventario' END) = 'inventario'"
        : "COALESCE(m.observacion, '') NOT LIKE 'Transferencia interna #% a laboratorio%'";

    $stmt = $pdo->query("SELECT
        m.fecha_hora,
        i.codigo,
        i.nombre,
        " . ($hasMarca ? "i.marca" : "NULL AS marca") . ",
        " . ($hasPresentacion ? "i.presentacion" : "NULL AS presentacion") . ",
        i.categoria,
        m.tipo,
        m.cantidad,
        i.unidad_medida,
        COALESCE(l.lote_codigo, '-') AS lote_codigo,
        l.fecha_vencimiento,
        m.observacion,
        CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,'')) AS usuario
    FROM inventario_movimientos m
    JOIN inventario_items i ON i.id = m.item_id
    LEFT JOIN inventario_lotes l ON l.id = m.lote_id
    LEFT JOIN usuarios u ON u.id = m.usuario_id
    WHERE " . $whereMovimientosInventario . "
    ORDER BY m.fecha_hora DESC, m.id DESC
    LIMIT 10000");

    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $labelTipo = function (string $tipo): string {
        $map = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'ajuste_pos' => 'Ajuste (+)',
            'ajuste_neg' => 'Ajuste (-)',
            'merma' => 'Merma',
            'vencido' => 'Vencido',
        ];
        return $map[$tipo] ?? ucfirst($tipo);
    };

    $html = '';
    $html .= '<h2>Kardex de Inventario</h2>';
    $html .= '<p><strong>Fecha de exportación:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;">';
    $html .= '<thead><tr>';
    $html .= '<th>Fecha/Hora</th><th>Código</th><th>Ítem</th><th>Marca</th><th>Presentación</th><th>Categoría</th><th>Tipo</th><th>Cantidad</th><th>Unidad</th><th>Lote</th><th>Vencimiento</th><th>Usuario</th><th>Observación</th>';
    $html .= '</tr></thead><tbody>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="13" align="center">Sin movimientos</td></tr>';
    } else {
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars((string)($r['fecha_hora'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['codigo'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['nombre'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['marca'] ?? '-')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['presentacion'] ?? '-')) . '</td>';
            $html .= '<td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($r['categoria'] ?? '')))) . '</td>';
            $html .= '<td>' . htmlspecialchars($labelTipo((string)($r['tipo'] ?? ''))) . '</td>';
            $html .= '<td>' . htmlspecialchars(number_format((float)($r['cantidad'] ?? 0), 2)) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['unidad_medida'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['lote_codigo'] ?? '-')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['fecha_vencimiento'] ?? '-')) . '</td>';
            $usuario = trim((string)($r['usuario'] ?? ''));
            $html .= '<td>' . htmlspecialchars($usuario !== '' ? $usuario : 'Sin dato') . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($r['observacion'] ?? '')) . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';

    if ($format === 'excel') {
        $filename = 'kardex_inventario_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        echo $html;
        exit;
    }

    require_once __DIR__ . '/../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'margin_left' => 8,
        'margin_right' => 8,
        'margin_top' => 8,
        'margin_bottom' => 8,
    ]);
    $mpdf->WriteHTML($html);
    $filename = 'kardex_inventario_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    exit;
} catch (\Throwable $e) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div style="padding:16px;font-family:Arial,sans-serif;">';
    echo '<h4>No se pudo exportar el kardex</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="dashboard.php?vista=inventario">Volver</a></p>';
    echo '</div>';
    exit;
}
