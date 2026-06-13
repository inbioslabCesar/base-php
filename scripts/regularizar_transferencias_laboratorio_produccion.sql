-- ============================================================
-- REGULARIZACION DE TRANSFERENCIAS A LABORATORIO
-- Caso: movimientos registrados como salida de inventario
-- pero que debieron reflejarse como transferencia interna.
--
-- Movimientos a regularizar (segun evidencia): 20 y 18
-- ============================================================

START TRANSACTION;

SET @usuario_id := NULL;
SET @obs_transferencia := 'Regularizacion: salidas de inventario cargadas como transferencia a laboratorio (movimientos 20,18)';

DROP TEMPORARY TABLE IF EXISTS tmp_mov_regularizar;
CREATE TEMPORARY TABLE tmp_mov_regularizar (
  movimiento_id INT PRIMARY KEY
);

INSERT INTO tmp_mov_regularizar (movimiento_id)
VALUES (20), (18);

-- Bloquear y validar filas origen
SELECT
  m.id AS movimiento_id,
  m.fecha_hora,
  m.tipo,
  m.origen,
  m.item_id,
  i.codigo,
  i.nombre,
  m.cantidad,
  m.observacion
FROM inventario_movimientos m
JOIN inventario_items i ON i.id = m.item_id
JOIN tmp_mov_regularizar t ON t.movimiento_id = m.id
WHERE m.tipo = 'salida'
  AND (m.origen = 'inventario' OR m.origen IS NULL)
FOR UPDATE;

-- Crear cabecera de transferencia interna
INSERT INTO inventario_transferencias (origen, destino, usuario_id, observacion, fecha_hora)
VALUES ('almacen_principal', 'laboratorio', @usuario_id, @obs_transferencia, NOW());

SET @transferencia_id := LAST_INSERT_ID();

-- Crear detalle de transferencia usando la misma cantidad de los movimientos origen
INSERT INTO inventario_transferencias_detalle (transferencia_id, item_id, cantidad, created_at)
SELECT
  @transferencia_id,
  m.item_id,
  m.cantidad,
  NOW()
FROM inventario_movimientos m
JOIN tmp_mov_regularizar t ON t.movimiento_id = m.id
WHERE m.tipo = 'salida'
  AND (m.origen = 'inventario' OR m.origen IS NULL);

-- Marcar movimientos como regularizados para evitar duplicidades futuras
UPDATE inventario_movimientos m
JOIN tmp_mov_regularizar t ON t.movimiento_id = m.id
SET
  m.origen = 'transferencia_interna',
  m.observacion = CONCAT(
    IFNULL(m.observacion, ''),
    CASE
      WHEN IFNULL(m.observacion, '') = '' THEN ''
      ELSE ' | '
    END,
    'Regularizado como transferencia interna #',
    @transferencia_id
  )
WHERE m.tipo = 'salida'
  AND (m.origen = 'inventario' OR m.origen IS NULL);

-- Validaciones de salida
SELECT @transferencia_id AS transferencia_generada;

SELECT
  i.codigo,
  i.nombre,
  IFNULL(tra.total_transferido, 0) AS transferido,
  IFNULL(con.total_consumido, 0) AS consumido,
  IFNULL(tra.total_transferido, 0) - IFNULL(con.total_consumido, 0) AS saldo_interno
FROM inventario_items i
LEFT JOIN (
  SELECT td.item_id, SUM(td.cantidad) AS total_transferido
  FROM inventario_transferencias_detalle td
  JOIN inventario_transferencias t ON t.id = td.transferencia_id
  WHERE t.destino = 'laboratorio'
  GROUP BY td.item_id
) tra ON tra.item_id = i.id
LEFT JOIN (
  SELECT item_id, SUM(cantidad_consumida) AS total_consumido
  FROM inventario_consumos_examen
  WHERE estado = 'aplicado'
  GROUP BY item_id
) con ON con.item_id = i.id
WHERE i.codigo IN ('INV-00014', 'INV-00004')
ORDER BY i.codigo;

COMMIT;

-- Si deseas probar primero sin confirmar cambios:
-- 1) Ejecuta hasta antes de COMMIT
-- 2) Reemplaza COMMIT por ROLLBACK
