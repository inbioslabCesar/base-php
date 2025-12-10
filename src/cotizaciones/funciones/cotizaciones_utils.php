<?php
// Funciones utilitarias para cotizaciones
function obtenerSaldoCotizacion($pdo, $idCotizacion) {
    $stmt = $pdo->prepare('SELECT total, (SELECT IFNULL(SUM(monto),0) FROM pagos WHERE id_cotizacion = c.id) AS pagado FROM cotizaciones c WHERE c.id = ?');
    $stmt->execute([$idCotizacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    return floatval($row['total']) - floatval($row['pagado']);
}
