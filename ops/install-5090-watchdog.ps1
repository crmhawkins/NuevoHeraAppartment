# install-5090-watchdog.ps1
#
# [2026-04-26] Registra (o reemplaza) la tarea programada IAServicesWatchdog
# en el 5090. Asume que 5090-services-watchdog.ps1 ya esta copiado en
# C:\Users\ia-local\services-watchdog.ps1.
#
# Triggers:
#   - AtStartup: arranca al boot del PC.
#   - Once cada 1 hora: relanza el ciclo si el anterior termino.
#
# Ejecutar como administrador / SYSTEM en el 5090:
#   powershell -NoProfile -ExecutionPolicy Bypass -File install-5090-watchdog.ps1
#
# Para subirlo desde tu equipo a 5090 usa scp/sftp + paramiko (ver historial
# de ejecucion en .claude del repo si necesitas el snippet).

$ErrorActionPreference = 'Stop'

$action = New-ScheduledTaskAction `
    -Execute 'powershell.exe' `
    -Argument '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File C:\Users\ia-local\services-watchdog.ps1'

$triggerBoot = New-ScheduledTaskTrigger -AtStartup

$triggerHourly = New-ScheduledTaskTrigger `
    -Once -At ((Get-Date).AddMinutes(1)) `
    -RepetitionInterval (New-TimeSpan -Hours 1)

$principal = New-ScheduledTaskPrincipal `
    -UserId 'SYSTEM' -RunLevel Highest

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2)

Unregister-ScheduledTask -TaskName 'IAServicesWatchdog' -Confirm:$false -ErrorAction SilentlyContinue

Register-ScheduledTask `
    -TaskName 'IAServicesWatchdog' `
    -Action $action `
    -Trigger $triggerBoot, $triggerHourly `
    -Principal $principal `
    -Settings $settings

Start-ScheduledTask -TaskName 'IAServicesWatchdog'

Get-ScheduledTask -TaskName 'IAServicesWatchdog' |
    Select-Object TaskName, State |
    Format-Table -AutoSize |
    Out-String
