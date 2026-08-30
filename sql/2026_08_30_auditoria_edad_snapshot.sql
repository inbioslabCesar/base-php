-- Auditoria de calidad: edad snapshot en resultados_examenes
-- Ejecutar en produccion despues de la migracion/hotfix.

-- 1) Resumen global de cobertura
SELECT
  COUNT(*) AS total_resultados,
  SUM(edad_paciente_valor IS NOT NULL) AS con_edad_valor,
  SUM(edad_paciente_texto IS NOT NULL AND TRIM(edad_paciente_texto) <> '') AS con_edad_texto,
  SUM(fecha_ref_edad IS NOT NULL) AS con_fecha_ref
FROM resultados_examenes;

-- 2) Outliers pediatrico-textuales: edades en meses/dias con valor numerico desalineado
-- Regla:
-- - Si texto indica meses, valor deberia ser menor a 2.0 anos (24 meses)
-- - Si texto indica dias, valor deberia ser menor a 0.25 anos (~91 dias)
SELECT
  re.id,
  re.id_cotizacion,
  re.edad_paciente_valor,
  re.edad_paciente_texto,
  re.fecha_ref_edad,
  c.edad AS edad_legacy,
  c.fecha_nacimiento
FROM resultados_examenes re
JOIN clientes c ON c.id = re.id_cliente
WHERE (
    LOWER(COALESCE(re.edad_paciente_texto, '')) LIKE '%mes%'
    AND (re.edad_paciente_valor IS NULL OR re.edad_paciente_valor >= 2.0)
  )
   OR (
    LOWER(COALESCE(re.edad_paciente_texto, '')) LIKE '%dia%'
    AND (re.edad_paciente_valor IS NULL OR re.edad_paciente_valor >= 0.25)
  )
ORDER BY re.id DESC
LIMIT 200;

-- 3) Inconsistencia para pacientes con fecha_nacimiento valida:
-- comparar valor snapshot con edad calculada por fecha_nacimiento + fecha_ref_edad
SELECT
  re.id,
  re.id_cotizacion,
  re.fecha_ref_edad,
  c.fecha_nacimiento,
  re.edad_paciente_valor AS valor_guardado,
  ROUND(
    GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0)
    + (
      GREATEST(
        TIMESTAMPDIFF(
          MONTH,
          DATE_ADD(
            c.fecha_nacimiento,
            INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
          ),
          COALESCE(re.fecha_ref_edad, re.fecha_ingreso)
        ),
        0
      ) / 12
    )
    + (
      GREATEST(
        DATEDIFF(
          COALESCE(re.fecha_ref_edad, re.fecha_ingreso),
          DATE_ADD(
            DATE_ADD(
              c.fecha_nacimiento,
              INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
            ),
            INTERVAL GREATEST(
              TIMESTAMPDIFF(
                MONTH,
                DATE_ADD(
                  c.fecha_nacimiento,
                  INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
                ),
                COALESCE(re.fecha_ref_edad, re.fecha_ingreso)
              ),
              0
            ) MONTH
          )
        ),
        0
      ) / 365.2425
    ),
    6
  ) AS valor_calculado,
  ROUND(
    ABS(
      re.edad_paciente_valor - (
        GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0)
        + (
          GREATEST(
            TIMESTAMPDIFF(
              MONTH,
              DATE_ADD(
                c.fecha_nacimiento,
                INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
              ),
              COALESCE(re.fecha_ref_edad, re.fecha_ingreso)
            ),
            0
          ) / 12
        )
        + (
          GREATEST(
            DATEDIFF(
              COALESCE(re.fecha_ref_edad, re.fecha_ingreso),
              DATE_ADD(
                DATE_ADD(
                  c.fecha_nacimiento,
                  INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
                ),
                INTERVAL GREATEST(
                  TIMESTAMPDIFF(
                    MONTH,
                    DATE_ADD(
                      c.fecha_nacimiento,
                      INTERVAL GREATEST(TIMESTAMPDIFF(YEAR, c.fecha_nacimiento, COALESCE(re.fecha_ref_edad, re.fecha_ingreso)), 0) YEAR
                    ),
                    COALESCE(re.fecha_ref_edad, re.fecha_ingreso)
                  ),
                  0
                ) MONTH
              )
            ),
            0
          ) / 365.2425
        )
      )
    ),
    6
  ) AS delta
FROM resultados_examenes re
JOIN clientes c ON c.id = re.id_cliente
WHERE c.fecha_nacimiento IS NOT NULL
  AND c.fecha_nacimiento <> '0000-00-00'
  AND re.edad_paciente_valor IS NOT NULL
HAVING delta > 0.05
ORDER BY delta DESC, re.id DESC
LIMIT 200;

-- 4) Muestreo rapido de recien creados para verificar formato textual
SELECT
  re.id,
  re.id_cotizacion,
  re.fecha_ref_edad,
  re.edad_paciente_valor,
  re.edad_paciente_texto
FROM resultados_examenes re
ORDER BY re.id DESC
LIMIT 30;
