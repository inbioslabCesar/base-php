# Merge seguro (10 comandos)

Contexto actual:
- Rama de trabajo: `feature/sis-convenios-fase1`
- Rama objetivo sugerida para sincronizar: `origin/main`

## Comandos (PowerShell)

1. `git status --short`
2. `git branch --show-current`
3. `git fetch --all --prune`
4. `git branch backup/premerge-$(Get-Date -Format yyyyMMdd-HHmmss)-feature-sis-convenios-fase1`
5. `git tag premerge-$(Get-Date -Format yyyyMMdd-HHmmss)-feature-sis-convenios-fase1`
6. `git log --oneline --left-right --graph feature/sis-convenios-fase1...origin/main`
7. `git merge --no-commit --no-ff origin/main`
8. `git diff --name-only --diff-filter=U`
9. `php -l src/dashboard.php; php -l src/resultados/guardar.php; php -l src/clientes/editar.php; php -l src/cotizaciones/api/procesar_agenda.php`
10. `git status --short`

## Si todo sale bien
- Corre pruebas funcionales criticas (pacientes offline, resultados offline, sync reconexion, modo SIS).
- Confirma merge: `git commit`

## Si algo sale mal
- Cancelar merge: `git merge --abort`
- Volver al punto seguro (rama/tag backup creados).

## Opcion automatica
Tambien puedes ejecutar:
- `powershell -ExecutionPolicy Bypass -File scripts/merge_seguro_precheck.ps1 -TargetBranch main`
