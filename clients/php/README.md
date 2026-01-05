# Cliente PHP (plantilla)

- Define variables de entorno:
  - `FACT_API_BASE_URL`, `FACT_API_USER`, `FACT_API_PASSWORD`
  - `FACT_COMPANY_ID`, `FACT_BRANCH_ID`
  - `FACT_SEND_METHOD` (`individual`|`resumen_diario`)
  - `FACT_TAX_MODE` (`gravado`|`exonerado`)
  - `FACT_PDF_FORMAT` (`A4`|`80mm`)

- Ejecutar (Laragon/PowerShell):
```powershell
& "C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe" "c:\laragon\www\base-php\clients\php\client.php"
```

Hace:
- Login, crear CPE, enviar a SUNAT, obtener estado (fallback GET), y muestra URL de descarga del PDF.
