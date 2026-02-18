# Plantilla rápida: receta de Glucosa (reactivo + insumos)

Esta guía deja configurada la receta de **1 prueba de glucosa** para que descuente más de un ítem (reactivo, tubo, aguja).

## 1) Regla clave antes de configurar

- La `Cantidad/prueba` siempre se interpreta en la **unidad del ítem**.
- Si el ítem está en `frasco`, entonces `0.5` significa **0.5 frasco** (no 0.5 ml).
- Si quieres descontar en mililitros, crea/usa el ítem en unidad `ml`.

## 2) Plantilla sugerida (recomendada)

Usa unidades operativas para consumo diario:

| Examen | Ítem | Unidad del ítem | Cantidad/prueba sugerida |
|---|---|---|---|
| Glucosa | Reactivo glucosa | ml | 0.5 |
| Glucosa | Tubo tapa gris (fluoruro) | unid | 1 |
| Glucosa | Aguja vacutainer | unid | 1 |
| Glucosa | Alcohol / torunda (opcional) | unid | 1 |

> Si trabajas por frasco de 50 ml: `0.5 ml` por prueba equivale a `0.01 frasco` por prueba.

## 3) Dónde configurarlo en el sistema

En `Inventario → Inventario Interno`:

1. En **Nueva receta de consumo**, selecciona `Examen = Glucosa`.
2. Selecciona el primer ítem (por ejemplo Reactivo glucosa en `ml`).
3. Coloca `Cantidad por prueba`.
4. Guarda.
5. Repite el mismo proceso para cada ítem adicional (tubo, aguja, etc.).

El sistema permite múltiples recetas por examen (una por ítem).

## 4) Transferencia mínima para no quedar corto

Si proyectas 100 glucosas:

- Reactivo: `100 x 0.5 ml = 50 ml`
- Tubos: `100 x 1 = 100 unid`
- Agujas: `100 x 1 = 100 unid`

Transfiere ese stock al laboratorio para evitar pendientes por stock interno.

## 5) Verificación rápida

Después de registrar resultados:

- Revisa el banner de guardado: debe indicar consumos aplicados.
- Revisa `Inventario Interno → Stock interno estimado`: cada ítem debe bajar según su receta.

Si aparece “stock insuficiente”, casi siempre es por uno de estos motivos:

- Receta ligada a otro `ítem` distinto del transferido.
- Unidad mal definida (`frasco` vs `ml`).
- Cantidad por prueba sobredimensionada.
