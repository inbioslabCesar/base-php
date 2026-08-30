param(
    [Parameter(Mandatory=$false)]
    [string]$TargetBranch = "main"
)

$ErrorActionPreference = "Stop"

function Step($msg) {
    Write-Host "[STEP] $msg" -ForegroundColor Cyan
}

function Info($msg) {
    Write-Host "[INFO] $msg" -ForegroundColor Yellow
}

Step "Validando arbol limpio"
$st = git status --porcelain
if ($st) {
    throw "Hay cambios sin commit. Haz commit/stash antes de ejecutar este precheck."
}

$current = (git branch --show-current).Trim()
if (-not $current) {
    throw "No se pudo detectar la rama actual."
}

Step "Actualizando referencias remotas"
git fetch --all --prune

$ts = Get-Date -Format "yyyyMMdd-HHmmss"
$backupBranch = "backup/premerge-$ts-$current"
$backupTag = "premerge-$ts-$current"

Step "Creando punto de recuperacion"
git branch $backupBranch
git tag $backupTag
Info "Backup branch: $backupBranch"
Info "Backup tag: $backupTag"

Step "Resumen de diferencia antes de merge"
git log --oneline --left-right --graph "$current...origin/$TargetBranch"

Step "Simulando merge (no commit)"
git merge --no-commit --no-ff "origin/$TargetBranch"

$conf = git diff --name-only --diff-filter=U
if ($conf) {
    Write-Host "[WARN] Hay conflictos. Archivos:" -ForegroundColor Red
    $conf
    Write-Host "[ACTION] Resuelve conflictos y valida. Si quieres cancelar: git merge --abort" -ForegroundColor Red
    exit 2
}

Step "Precheck sintactico rapido en PHP modificados por merge"
$changed = git diff --name-only --diff-filter=ACMRT
$phpFiles = $changed | Where-Object { $_.ToLower().EndsWith('.php') }
foreach ($f in $phpFiles) {
    php -l $f | Out-Null
}

Step "Estado final"
Write-Host "Merge aplicado en staging sin commit." -ForegroundColor Green
Write-Host "Ejecuta tus pruebas funcionales y luego confirma con: git commit" -ForegroundColor Green
Write-Host "Si quieres deshacer esta simulacion: git merge --abort" -ForegroundColor Green
