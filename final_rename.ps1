# Script final para renombrar TODAS las migraciones a formato de 3 dígitos

# Cambiar al directorio de migraciones
Set-Location "database\migrations"

# Obtener todas las migraciones que empiecen con números
$files = Get-ChildItem -Filter "*.php" | Where-Object { $_.Name -match '^(\d+)_' }

Write-Host "Encontradas $($files.Count) migraciones para procesar"

foreach ($file in $files) {
    $currentName = $file.Name
    $match = [regex]::Match($currentName, '^(\d+)_')

    if ($match.Success) {
        $number = $match.Groups[1].Value
        $restOfName = $currentName.Substring($match.Length)

        # Formatear el número a 3 dígitos
        $newNumber = $number.PadLeft(3, '0')
        $newName = $newNumber + $restOfName

        if ($currentName -ne $newName) {
            Write-Host "Renombrando: $currentName -> $newName"
            try {
                Rename-Item $currentName $newName -Force
                Write-Host "  Renombrado exitosamente"
            }
            catch {
                Write-Host "  Error al renombrar: $($_.Exception.Message)"
            }
        }
    }
}

Write-Host "Proceso completado!"
