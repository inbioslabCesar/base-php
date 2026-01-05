# Checklist Hostinger (Facturación/CPE)

## 1) Permisos de escritura
El ERP guarda tokens y estado del comprobante en archivos (no en BD):
- `tmp/api_tokens.json`
- `tmp/auth.log`
- `tmp/facturacion/status.json`
- `tmp/facturacion/logs/*.log`
- `tmp/facturacion/payloads/*.json`

Asegura que en producción exista y sea escribible por PHP:
- `tmp/`
- `tmp/facturacion/`
- `tmp/facturacion/logs/`
- `tmp/facturacion/payloads/`

## 2) Migración BD
Ejecutar el SQL en producción:
- Preferido (idempotente): `sql/migracion_facturacion_produccion.sql`
- Alternativo simple: `sql/migracion_facturacion_produccion_simple.sql`

## 3) Verificación rápida
1. Crear una cotización con `emitir_comprobante = 0` (Solo Ticket)
   - Debe permitir pagar y NO debe intentar emitir CPE.
2. Crear una cotización con `emitir_comprobante = 1`
   - Pagar completa y emitir comprobante.
3. Pagos parciales
   - Debe guardar `estado_pago='abonado'` sin error.
