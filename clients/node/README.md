# Cliente Node (plantilla)

- Requiere Node 18+ (fetch nativo).
- Variables de entorno: FACT_API_BASE_URL, FACT_API_USER, FACT_API_PASSWORD, FACT_COMPANY_ID, FACT_BRANCH_ID, FACT_SEND_METHOD, FACT_TAX_MODE, FACT_PDF_FORMAT.

Ejecutar:
```powershell
node clients/node/client.js
```

Flujo: login → crear CPE → enviar a SUNAT → estado (fallback GET) → muestra URL de descarga PDF.
