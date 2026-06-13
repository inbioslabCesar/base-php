-- ============================================================
-- REVERTIR PAGO ERRÓNEO DE S/140.00
-- Cotización: COT-69D91365DA5FC  |  Cliente: Kessia Manzur Rojas
-- Fecha pago erróneo: ~2026-04-10
-- ============================================================

-- ① PASO 1 — VERIFICACIÓN (solo lectura, ejecutar primero)
-- Muestra la cotización y los pagos registrados para confirmar qué se va a eliminar.

SELECT
    c.id              AS cotizacion_id,
    c.codigo          AS cotizacion_codigo,
    c.total           AS total,
    c.estado_pago     AS estado_pago
FROM cotizaciones c
WHERE c.codigo = 'COT-69D91365DA5FC';

-- Pagos registrados para esa cotización:
SELECT
    p.id             AS pago_id,
    p.monto          AS monto,
    p.metodo_pago    AS metodo_pago,
    p.fecha          AS fecha,
    p.observaciones  AS observaciones
FROM pagos p
JOIN cotizaciones c ON c.id = p.id_cotizacion
WHERE c.codigo = 'COT-69D91365DA5FC'
ORDER BY p.fecha DESC;

-- Movimientos de caja vinculados a esos pagos:
SELECT
    cm.id            AS movimiento_id,
    cm.monto         AS monto,
    cm.metodo_pago   AS metodo_pago,
    cm.referencia_id AS pago_id_referencia,
    cm.fecha_hora    AS fecha_hora
FROM caja_movimientos cm
WHERE cm.referencia_tipo = 'pago_individual'
  AND cm.referencia_id IN (
      SELECT p.id
      FROM pagos p
      JOIN cotizaciones c ON c.id = p.id_cotizacion
      WHERE c.codigo = 'COT-69D91365DA5FC'
  )
ORDER BY cm.fecha_hora DESC;


-- ② PASO 2 — REVERSA (ejecutar solo DESPUÉS de confirmar los resultados del paso 1)
-- Elimina únicamente el pago de S/140 (ajusta el pago_id si hay varios pagos).
-- Reemplaza <PAGO_ID> con el id real del pago erróneo que aparece arriba.

START TRANSACTION;

-- Eliminar movimiento de caja (movimiento_id = 301, monto S/140, referencia pago 1534)
DELETE FROM caja_movimientos
WHERE id = 301
  AND referencia_tipo = 'pago_individual'
  AND referencia_id = 1534;

-- Eliminar el pago erróneo (pago_id = 1534, S/140.00, 2026-04-10)
DELETE FROM pagos
WHERE id = 1534
  AND id_cotizacion = (SELECT id FROM cotizaciones WHERE codigo = 'COT-69D91365DA5FC')
  AND monto = 140.00;

-- Verificar resultado (total_pagado debe quedar en 0.00, saldo_pendiente = 165.00)
SELECT
    c.codigo,
    c.total,
    IFNULL(SUM(p.monto), 0)           AS total_pagado,
    c.total - IFNULL(SUM(p.monto), 0) AS saldo_pendiente,
    c.estado_pago
FROM cotizaciones c
LEFT JOIN pagos p ON p.id_cotizacion = c.id
WHERE c.codigo = 'COT-69D91365DA5FC'
GROUP BY c.id;

-- Si total_pagado = 0.00 y saldo_pendiente = 165.00 → COMMIT
-- Si algo no cuadra → ROLLBACK
COMMIT;
-- ROLLBACK;
