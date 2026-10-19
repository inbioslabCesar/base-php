<?php
// Criterio único para "cotizaciones antiguas sin atender" (usado en Contabilidad y en el filtro de Cotizaciones).

if (!defined('COTIZACIONES_DIAS_SIN_ATENDER')) {
    define('COTIZACIONES_DIAS_SIN_ATENDER', 30);
}

if (!function_exists('cotizacionesCondicionSinAtender')) {
    // Condición SQL sobre el alias `c` (cotizaciones). Lleva un placeholder `?` para los días.
    // Sin atender = antigua, con monto, sin ningún pago, sin resultados completados y con toma de muestra pendiente.
    function cotizacionesCondicionSinAtender(PDO $pdo): string
    {
        $muestra = app_database_has_column($pdo, 'cotizaciones', 'estado_muestra') ? "AND c.estado_muestra = 'pendiente'" : '';
        return "(c.estado_pago IS NULL OR c.estado_pago <> 'anulada')
            AND c.total > 0
            AND c.fecha < DATE_SUB(NOW(), INTERVAL ? DAY)
            {$muestra}
            AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.id_cotizacion = c.id)
            AND NOT EXISTS (SELECT 1 FROM resultados_examenes r WHERE r.id_cotizacion = c.id AND r.estado = 'completado')";
    }
}
