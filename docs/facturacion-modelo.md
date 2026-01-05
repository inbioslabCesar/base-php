# Modelo de Integración de Facturación (SUNAT)

Esta guía es independiente del ERP y describe cómo integrar cualquier sistema con un API de facturación que gestione CPEs (boletas/facturas) y su envío a SUNAT.

## 1. Requisitos
- API con autenticación (Sanctum/JWT) y recursos: `boletas`, `invoices`.
- Rutas disponibles:
  - `POST /api/auth/login`
  - `POST /api/v1/boletas` y `POST /api/v1/invoices` (crear CPE)
  - `POST /api/v1/boletas/{id}/send-sunat` y `POST /api/v1/invoices/{id}/send-sunat` (enviar a SUNAT)
  - `GET /api/v1/{boletas|invoices}/{id}` (obtener CPE)
  - `GET /api/v1/{boletas|invoices}/{id}/download-pdf?format=A4|80mm`
  - `GET /api/v1/{boletas|invoices}/{id}/download-xml`
  - `GET /api/v1/{boletas|invoices}/{id}/download-cdr`

## 2. Configuración (variables de entorno)
- `FACT_API_BASE_URL`: URL base del API (ej. `http://127.0.0.1:8000`).
- `FACT_API_USER` y `FACT_API_PASSWORD`: credenciales.
- `FACT_COMPANY_ID` y `FACT_BRANCH_ID`: emisor y sucursal.
- `FACT_SEND_METHOD`: `individual` o `resumen_diario`.
- `FACT_TAX_MODE`: `gravado` (IGV 18%) o `exonerado` (IGV 0%).
- `FACT_PDF_FORMAT`: `A4` o `80mm`.

## 3. Payload CPE
- Cliente:
  - `tipo_documento`: DNI=`1`, RUC=`6`.
  - `numero_documento`, `razon_social`.
- Detalles por ítem:
  - `codigo`, `descripcion`, `unidad`, `cantidad`.
  - `mto_valor_unitario` (precio sin IGV), `porcentaje_igv`, `tip_afe_igv` (`10` gravado, `20` exonerado).
- Cabecera:
  - `company_id`, `branch_id`, `metodo_envio`, `serie` (`B001` particulares, `F001` empresas), `fecha_emision`.

Ejemplo (boleta exonerada):
```json
{
  "company_id": 1,
  "branch_id": 1,
  "metodo_envio": "individual",
  "serie": "B001",
  "fecha_emision": "2025-12-23",
  "client": { "tipo_documento": "1", "numero_documento": "12345678", "razon_social": "Juan Perez" },
  "detalles": [{
    "codigo": "ITEM-001",
    "descripcion": "Servicio",
    "unidad": "NIU",
    "cantidad": 1,
    "mto_valor_unitario": 50.00,
    "porcentaje_igv": 0,
    "tip_afe_igv": "20"
  }]
}
```

## 4. Flujo de integración
1. Login: obtener `access_token`.
2. Crear CPE: `POST` a `boletas|invoices` con payload.
3. Enviar a SUNAT: `POST send-sunat` para el `id` retornado.
4. Leer estado: si existe `GET /status` úsalo; si no, `GET` del recurso y mapea `estado_sunat`.
5. Descargas: PDF (`format=A4|80mm`), XML y CDR.

## 5. Estados y mapeos
- Valores del API: `estado_sunat` o `sunat_status` → `aceptado`, `observado`, `rechazado`.
- Si no hay clave explícita, usa `status` o booleanos `accepted=true`.

## 6. Errores y reintentos
- Registrar errores y continuar con la operación de cobro.
- Reintentar envío/consulta de estado con backoff (cada 1–5 minutos) hasta `aceptado`/`rechazado`.
- Exponer `sunat_message` y códigos al usuario.

## 7. Seguridad
- Almacenar tokens con TTL y refrescar cuando expiren.
- Validar que `branch_id` pertenezca a `company_id`.
- Idempotencia: evitar duplicar CPE por misma operación (usar un `origen.id`).

## 8. Ejemplos cURL
```bash
# Login
curl -X POST $FACT_API_BASE_URL/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"$FACT_API_USER","password":"$FACT_API_PASSWORD"}'

# Crear boleta
curl -X POST $FACT_API_BASE_URL/api/v1/boletas \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d @payload.json

# Enviar a SUNAT
curl -X POST $FACT_API_BASE_URL/api/v1/boletas/$ID/send-sunat \
  -H "Authorization: Bearer $TOKEN"

# Descargas
curl -L "$FACT_API_BASE_URL/api/v1/boletas/$ID/download-pdf?format=80mm" -H "Authorization: Bearer $TOKEN" -o cpe.pdf
curl -L "$FACT_API_BASE_URL/api/v1/boletas/$ID/download-xml" -H "Authorization: Bearer $TOKEN" -o cpe.xml
curl -L "$FACT_API_BASE_URL/api/v1/boletas/$ID/download-cdr" -H "Authorization: Bearer $TOKEN" -o cdr.zip
```

## 9. Checklist de portabilidad
- [ ] Configurar variables de entorno (base URL, credenciales, IDs).
- [ ] Implementar cliente HTTP con login, crear, enviar, estado, descargas.
- [ ] Construir payload desde tu modelo de datos.
- [ ] Mapear estados y mostrar mensajes de SUNAT.
- [ ] Añadir reintentos y/o webhook.
- [ ] Probar en modo `gravado` y `exonerado`; validar PDF `A4/80mm`.

Este modelo es agnóstico del ERP: aplica igual en PHP, Node.js, Python o cualquier plataforma con HTTP y JSON.