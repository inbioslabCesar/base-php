-- Diagnóstico semanal de caja (últimos 7 días)
-- Uso: ejecutar completo en phpMyAdmin para validar operación por turnos.

-- 1) Resumen diario (aperturas, cierres, descuadre neto y movimientos)
SELECT
    c.fecha_operacion,
    COUNT(*) AS turnos_abiertos,
    SUM(CASE WHEN c.estado = 'cerrada' THEN 1 ELSE 0 END) AS turnos_cerrados,
    ROUND(SUM(c.monto_apertura), 2) AS apertura_total,
    ROUND(SUM(COALESCE(c.total_efectivo_registrado, 0)), 2) AS cierre_total_registrado,
    ROUND(SUM(COALESCE(c.total_efectivo_teorico, 0)), 2) AS cierre_total_teorico,
    ROUND(SUM(COALESCE(c.diferencia_cierre, 0)), 2) AS descuadre_neto,
    (
        SELECT COUNT(*)
        FROM caja_movimientos m
        JOIN cajas c2 ON c2.id = m.caja_id
        WHERE c2.fecha_operacion = c.fecha_operacion
    ) AS movimientos_dia
FROM cajas c
WHERE c.fecha_operacion >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY c.fecha_operacion
ORDER BY c.fecha_operacion DESC;

-- 2) Detalle por turno (estado, horas, montos y diferencia)
SELECT
    c.fecha_operacion,
    c.numero_turno,
    c.estado,
    c.fecha_hora_apertura,
    c.fecha_hora_cierre,
    ROUND(c.monto_apertura, 2) AS monto_apertura,
    ROUND(COALESCE(c.total_efectivo_teorico, 0), 2) AS efectivo_teorico,
    ROUND(COALESCE(c.total_efectivo_registrado, 0), 2) AS efectivo_registrado,
    ROUND(COALESCE(c.diferencia_cierre, 0), 2) AS diferencia,
    COALESCE(c.observaciones_cierre, '') AS observacion_cierre
FROM cajas c
WHERE c.fecha_operacion >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
ORDER BY c.fecha_operacion DESC, c.numero_turno ASC;

-- 3) Validación de regla: máximo 2 turnos por día
SELECT
    c.fecha_operacion,
    COUNT(*) AS turnos_dia
FROM cajas c
WHERE c.fecha_operacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY c.fecha_operacion
HAVING COUNT(*) > 2
ORDER BY c.fecha_operacion DESC;

-- 4) Cajas abiertas sin cierre (pendientes)
SELECT
    c.id,
    c.fecha_operacion,
    c.numero_turno,
    c.fecha_hora_apertura,
    ROUND(c.monto_apertura, 2) AS monto_apertura
FROM cajas c
WHERE c.estado = 'abierta'
ORDER BY c.fecha_hora_apertura DESC;

-- 5) Movimientos por método en la semana (para revisar mezcla efectivo/no efectivo)
SELECT
    DATE(m.fecha_hora) AS fecha,
    m.metodo_pago,
    COUNT(*) AS cantidad,
    ROUND(SUM(m.monto), 2) AS monto_total,
    ROUND(SUM(CASE WHEN m.afecta_efectivo = 1 THEN m.monto ELSE 0 END), 2) AS monto_que_afecta_efectivo
FROM caja_movimientos m
WHERE DATE(m.fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY DATE(m.fecha_hora), m.metodo_pago
ORDER BY fecha DESC, m.metodo_pago;
