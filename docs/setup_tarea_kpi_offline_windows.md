# Configuracion de tarea diaria KPI Offline (Windows)

## Objetivo
Ejecutar automaticamente el reporte diario de KPI offline y guardar historico en:
- docs/offline_kpi_reports
- docs/offline_kpi_reports/weekly

## Opcion rapida (recomendada)
Desde la raiz del proyecto:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/register_kpi_offline_task.ps1 -TaskName "Inbioslab KPI Offline Diario" -RunAt "20:00"
```

Para crearla y lanzarla de inmediato:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/register_kpi_offline_task.ps1 -TaskName "Inbioslab KPI Offline Diario" -RunAt "20:00" -RunNow
```

## Verificacion
1. Confirmar que la tarea existe:

```powershell
schtasks /Query /TN "Inbioslab KPI Offline Diario" /V /FO LIST
```

2. Ejecutar manualmente una vez:

```powershell
schtasks /Run /TN "Inbioslab KPI Offline Diario"
```

3. Verificar historico generado:
- docs/offline_kpi_reports/kpi_offline_YYYY-MM-DD.json
- docs/offline_kpi_reports/kpi_offline_YYYY-MM-DD.md
- docs/offline_kpi_reports/weekly/kpi_offline_weekly_YYYYMMDD_YYYYMMDD.json
- docs/offline_kpi_reports/weekly/kpi_offline_weekly_YYYYMMDD_YYYYMMDD.md

## Solucion de problemas
- Si la tarea no crea archivos, verificar que PHP este en PATH.
- Si hay politicas de PowerShell estrictas, usar ExecutionPolicy Bypass como en los comandos.
- Si la hora del servidor no coincide con la esperada, revisar zona horaria de Windows.
