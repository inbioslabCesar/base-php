-- Mejoras robustas para caja por turnos
-- Ejecutar SI YA creaste tablas con la versión inicial

SET @sql_add_col := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cajas'
              AND COLUMN_NAME = 'numero_turno'
        ),
        'SELECT 1',
        'ALTER TABLE cajas ADD COLUMN numero_turno TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER fecha_operacion'
    )
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

-- Normalizar turnos históricos (si hubiera múltiples del mismo día)
-- Compatible con versiones antiguas (sin variables en UPDATE)
UPDATE cajas c
JOIN (
    SELECT c1.id,
           (
               SELECT COUNT(*)
               FROM cajas c2
               WHERE c2.fecha_operacion = c1.fecha_operacion
                 AND (
                     c2.fecha_hora_apertura < c1.fecha_hora_apertura
                     OR (c2.fecha_hora_apertura = c1.fecha_hora_apertura AND c2.id <= c1.id)
                 )
           ) AS turno_calculado
    FROM cajas c1
) t ON t.id = c.id
SET c.numero_turno = t.turno_calculado;

-- Recrear índice único por fecha/turno
SET @sql_drop := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cajas'
              AND INDEX_NAME = 'uk_cajas_fecha_turno'
        ),
        'ALTER TABLE cajas DROP INDEX uk_cajas_fecha_turno',
        'SELECT 1'
    )
);
PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

ALTER TABLE cajas
    ADD UNIQUE INDEX uk_cajas_fecha_turno (fecha_operacion, numero_turno);
