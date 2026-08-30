-- Hotfix: corrige edad_paciente_valor para registros heredados sin fecha_nacimiento
-- donde se guardo '6 meses' como 6.000000 en vez de 0.500000.

UPDATE resultados_examenes re
INNER JOIN clientes c ON c.id = re.id_cliente
SET re.edad_paciente_valor = CASE
    WHEN LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%mes%'
        THEN ROUND(CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6)) / 12, 6)
    WHEN LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%dia%'
        THEN ROUND(CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6)) / 365.2425, 6)
    WHEN TRIM(COALESCE(c.edad, '')) REGEXP '[0-9]'
        THEN CAST(REPLACE(TRIM(COALESCE(c.edad, '')), ',', '.') AS DECIMAL(10,6))
    ELSE re.edad_paciente_valor
END
WHERE (c.fecha_nacimiento IS NULL OR c.fecha_nacimiento = '0000-00-00')
  AND TRIM(COALESCE(c.edad, '')) <> ''
  AND (
      LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%mes%'
      OR LOWER(TRIM(COALESCE(c.edad, ''))) LIKE '%dia%'
  );
