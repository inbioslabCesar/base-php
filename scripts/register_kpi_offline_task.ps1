param(
    [string]$TaskName = "Inbioslab KPI Offline Diario",
    [string]$RunAt = "20:00",
    [switch]$RunNow
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$cmdPath = Join-Path $repoRoot "scripts\run_kpi_offline_daily.cmd"

if (-not (Test-Path $cmdPath)) {
    throw "No se encontro el archivo: $cmdPath"
}

$taskCmd = ('cmd /c "{0}"' -f $cmdPath)

schtasks /Create /TN "$TaskName" /TR "$taskCmd" /SC DAILY /ST $RunAt /F | Out-Null
Write-Host "[OK] Tarea creada/actualizada: $TaskName"
Write-Host "     Hora diaria: $RunAt"

if ($RunNow) {
    schtasks /Run /TN "$TaskName" | Out-Null
    Write-Host "[OK] Tarea ejecutada manualmente: $TaskName"
}

Write-Host "[TIP] Ver estado: schtasks /Query /TN \"$TaskName\" /V /FO LIST"
