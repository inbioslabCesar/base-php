@echo off
setlocal
cd /d "%~dp0.."
for /f %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd')"') do set TODAY=%%i
php scripts\reporte_kpi_offline_sync.php --save --date=%TODAY%
if errorlevel 1 (
  echo [ERROR] No se pudo generar reporte KPI offline diario.
  exit /b 1
)
echo [OK] Reporte KPI offline diario generado.
endlocal
