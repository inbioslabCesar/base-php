-- Snapshot historico de edad por resultado para preservar reportes anteriores
-- y permitir edad dinamica correcta en nuevas solicitudes.

ALTER TABLE resultados_examenes
    ADD COLUMN IF NOT EXISTS edad_paciente_valor DECIMAL(10,6) NULL AFTER id_cotizacion,
    ADD COLUMN IF NOT EXISTS edad_paciente_texto VARCHAR(120) NULL AFTER edad_paciente_valor,
    ADD COLUMN IF NOT EXISTS fecha_ref_edad DATETIME NULL AFTER edad_paciente_texto;

-- Completar fecha de referencia cuando no exista.
UPDATE resultados_examenes re
LEFT JOIN cotizaciones co ON co.id = re.id_cotizacion
SET re.fecha_ref_edad = COALESCE(
        re.fecha_ref_edad,
        CASE
            WHEN co.fecha_toma IS NOT NULL THEN CONCAT(co.fecha_toma, ' 00:00:00')
            ELSE co.fecha
        END,
        re.fecha_ingreso
    )
WHERE re.fecha_ref_edad IS NULL;

-- Backfill primario usando fecha_nacimiento + fecha de referencia (compatible con MariaDB sin CTE).
DROP TEMPORARY TABLE IF EXISTS tmp_resultados_edad_calc;
CREATE TEMPORARY TABLE tmp_resultados_edad_calc (
    id INT NOT NULL PRIMARY KEY,
    fecha_nacimiento DATE NOT NULL,
    ref_fecha DATETIME NOT NULL,
    y INT NOT NULL DEFAULT 0,
    m INT NOT NULL DEFAULT 0,
    d INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO tmp_resultados_edad_calc (id, fecha_nacimiento, ref_fecha, y)
SELECT
    re.id,
    c.fecha_nacimiento,
    COALESCE(re.fecha_ref_edad, re.fecha_ingreso) AS ref_fecha,
    GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) AS y
FROM resultados_examenes re
INNER JOIN clientes c ON c.id = re.id_cliente
WHERE (re.edad_paciente_valor IS NULL OR re.edad_paciente_texto IS NULL OR re.edad_paciente_texto = '')
  AND c.fecha_nacimiento IS NOT NULL
  AND c.fecha_nacimiento <> '0000-00-00';

UPDATE tmp_resultados_edad_calc
SET m = GREATEST(TIMESTAMPDIFF(MONTH, DATE_ADD(fecha_nacimiento, INTERVAL y YEAR), ref_fecha), 0);

UPDATE tmp_resultados_edad_calc
SET d = GREATEST(DATEDIFF(ref_fecha, DATE_ADD(DATE_ADD(fecha_nacimiento, INTERVAL y YEAR), INTERVAL m MONTH)), 0);

UPDATE resultados_examenes re
INNER JOIN tmp_resultados_edad_calc t ON t.id = re.id
SET
    re.edad_paciente_valor = ROUND(t.y + (t.m / 12) + (t.d / 365.2425), 6),
    re.edad_paciente_texto = CONCAT(
        t.y, ' ', IF(t.y = 1, 'año', 'años'), ' ',
        t.m, ' ', IF(t.m = 1, 'mes', 'meses'), ' ',
        t.d, ' ', IF(t.d = 1, 'día', 'días')
    )
WHERE (re.edad_paciente_valor IS NULL OR re.edad_paciente_texto IS NULL OR re.edad_paciente_texto = '');

DROP TEMPORARY TABLE IF EXISTS tmp_resultados_edad_calc;

-- Fallback para registros sin fecha_nacimiento: reutilizar el campo legado clientes.edad.
UPDATE resultados_examenes re
INNER JOIN clientes c ON c.id = re.id_cliente
SET
    re.edad_paciente_texto = COALESCE(NULLIF(TRIM(c.edad), ''), re.edad_paciente_texto),
    re.edad_paciente_valor = COALESCE(
        re.edad_paciente_valor,
        CASE
            WHEN TRIM(COALESCE(c.edad, '')) REGEXP '[0-9]'
                THEN CASE
                    WHEN LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%mes%' THEN ROUND(CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6)) / 12, 6)
                    WHEN LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%dia%' THEN ROUND(CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6)) / 365.2425, 6)
                    ELSE CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6))
                END
            ELSE NULL
        END
    )
WHERE (re.edad_paciente_valor IS NULL OR re.edad_paciente_texto IS NULL OR re.edad_paciente_texto = '');

-- Opcional en tablas grandes: crear indice manual si no existe.
-- CREATE INDEX idx_resultados_examenes_fecha_ref_edad ON resultados_examenes (fecha_ref_edad);
