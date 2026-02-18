<?php
// Calcula el porcentaje de parámetros llenados para una cotización
function obtenerPorcentajeResultadosCotizacion($pdo, $idCotizacion) {
    static $hasSnapshotCol = null;
    if ($hasSnapshotCol === null) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM resultados_examenes LIKE 'adicional_snapshot'")->fetch(PDO::FETCH_ASSOC);
            $hasSnapshotCol = !empty($col);
        } catch (Exception $e) {
            $hasSnapshotCol = false;
        }
    }

    if ($hasSnapshotCol) {
        $stmt = $pdo->prepare("SELECT re.resultados, COALESCE(re.adicional_snapshot, e.adicional) AS adicional
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    } else {
        $stmt = $pdo->prepare("SELECT re.resultados, e.adicional AS adicional
            FROM resultados_examenes re
            JOIN examenes e ON re.id_examen = e.id
            WHERE re.id_cotizacion = ?");
    }
    $stmt->execute([$idCotizacion]);
    $examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_parametros = 0;
    $parametros_llenados = 0;
    foreach ($examenes as $examen) {
        $adicional = $examen['adicional'] ? json_decode($examen['adicional'], true) : [];
        $resultados = $examen['resultados'] ? json_decode($examen['resultados'], true) : [];
        foreach ($adicional as $item) {
            // Contabilizar parámetros ingresables: Parámetro, Campo y Texto Largo
            if ($item['tipo'] === 'Parámetro' || $item['tipo'] === 'Campo' || $item['tipo'] === 'Texto Largo') {
                $total_parametros++;
                $nombre = $item['nombre'];
                $stableKey = '';
                if (is_array($item) && !empty($item['id_parametro'])) {
                    $stableKey = 'id_parametro_' . trim((string)$item['id_parametro']);
                }
                $valor = null;
                if ($stableKey !== '' && array_key_exists($stableKey, $resultados)) {
                    $valor = $resultados[$stableKey];
                } elseif (array_key_exists($nombre, $resultados)) {
                    $valor = $resultados[$nombre];
                }
                if (
                    ($valor !== null || $valor === 0 || $valor === '0') && (
                        $valor !== '' && $valor !== null
                        || $valor === 0
                        || $valor === '0'
                    )
                ) {
                    $parametros_llenados++;
                }
            }
        }
    }
    if ($total_parametros === 0) return 0;
    return round(($parametros_llenados / $total_parametros) * 100);
}

// Funciones utilitarias para cotizaciones
function obtenerSaldoCotizacion($pdo, $idCotizacion) {
    $stmt = $pdo->prepare('SELECT total, estado_pago, (SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = c.id) AS pagado FROM cotizaciones c WHERE c.id = ?');
    $stmt->execute([$idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    if (isset($row['estado_pago']) && strtolower((string)$row['estado_pago']) === 'anulada') {
        return 0;
    }
    return max(0, floatval($row['total']) - floatval($row['pagado']));
}
