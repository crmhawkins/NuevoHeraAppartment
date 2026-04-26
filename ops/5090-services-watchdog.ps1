# 5090-services-watchdog.ps1
#
# [2026-04-26] Watchdog que vigila los servicios IA del 5090 (Hawkins AI).
#
# Vive en C:\Users\ia-local\services-watchdog.ps1 en el 5090. Lo registra
# como tarea programada `IAServicesWatchdog` que arranca al boot y se
# repite cada 1 hora (cada ciclo dura ~1h, 60 chequeos a 1 min).
#
# Comprueba cada minuto que:
#  - El proceso ollama esta vivo Y escucha en 127.0.0.1:11434.
#  - El proceso python (wrapper) esta vivo Y escucha en 127.0.0.1:11435.
#
# Si alguno falla, relanza la tarea programada correspondiente
# (OllamaServe / AIWrapper) y registra en C:\Users\ia-local\services-watchdog.log.
#
# Razon de existir: 26/04/2026 ambas tareas aparecieron en estado
# "Ready" (no Running) sin que nada las relanzase. El tunel reverso
# seguia vivo pero los puertos no respondian -> alertas IA NO
# DISPONIBLE durante varias horas. Esto evita que vuelva a pasar.
#
# Para instalar/actualizar usa ops/install-5090-watchdog.ps1.

$ErrorActionPreference = 'Continue'
$LogFile = "C:\Users\ia-local\services-watchdog.log"

function Log {
    param([string]$msg)
    $ts = Get-Date -Format "yyyy-MM-ddTHH:mm:ssK"
    Add-Content -Path $LogFile -Value "$ts $msg" -ErrorAction SilentlyContinue
}

function Test-Port {
    param([int]$port)
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $async = $client.BeginConnect('127.0.0.1', $port, $null, $null)
        $ok = $async.AsyncWaitHandle.WaitOne(2000, $false)
        if ($ok -and $client.Connected) {
            $client.Close()
            return $true
        }
        $client.Close()
        return $false
    } catch {
        return $false
    }
}

function Restart-IATask {
    param([string]$taskName, [string]$reason)
    Log "RESTART $taskName ($reason)"
    try {
        Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
        Start-ScheduledTask -TaskName $taskName -ErrorAction Stop
        Log "  $taskName arrancado"
    } catch {
        Log "  ERROR arrancando $taskName : $($_.Exception.Message)"
    }
}

# Bucle: 1 ciclo por minuto durante 60 ciclos (1 hora). El task scheduler
# nos relanza cada hora exacta para evitar memory leaks o estados raros.
for ($i = 0; $i -lt 60; $i++) {
    # 1) Ollama
    $ollamaProc = Get-Process -Name ollama -ErrorAction SilentlyContinue
    $ollamaPort = Test-Port -port 11434
    if (-not $ollamaProc) {
        Restart-IATask -taskName 'OllamaServe' -reason 'proceso ollama no existe'
    } elseif (-not $ollamaPort) {
        Restart-IATask -taskName 'OllamaServe' -reason 'puerto 11434 no responde aunque ollama vive'
    }

    # 2) Wrapper python (AIWrapper). Comprobamos solo el puerto, no el proceso
    #    (puede haber muchos python.exe).
    $wrapperPort = Test-Port -port 11435
    if (-not $wrapperPort) {
        Restart-IATask -taskName 'AIWrapper' -reason 'puerto 11435 no responde'
    }

    Start-Sleep -Seconds 60
}

Log "Ciclo de 60 minutos completado, saliendo (task scheduler relanzara)"
