<?php
// Calcula el porcentaje de parámetros llenados para una cotización
function obtenerPorcentajeResultadosCotizacion($pdo, $idCotizacion) {
    $stmt = $pdo->prepare('SELECT re.resultados, e.adicional FROM resultados_examenes re JOIN examenes e ON re.id_examen = e.id WHERE re.id_cotizacion = ?');
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
                if (
                    isset($resultados[$nombre]) && (
                        $resultados[$nombre] !== '' && $resultados[$nombre] !== null
                        || $resultados[$nombre] === 0
                        || $resultados[$nombre] === '0'
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
    $stmt = $pdo->prepare('SELECT total, (SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = c.id) AS pagado FROM cotizaciones c WHERE c.id = ?');
    $stmt->execute([$idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    return max(0, floatval($row['total']) - floatval($row['pagado']));
}
