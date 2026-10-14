param(
    [string]$PhpPath
)

$ErrorActionPreference = 'Stop'

try {
    if (-not $PhpPath) {
        $PhpPath = (Get-Command php -CommandType Application -ErrorAction Stop).Source
    }

    $taskPhpExe = (Resolve-Path -LiteralPath $PhpPath).Path
    $taskPhpDir = Split-Path -Parent $taskPhpExe
    $taskVersion = & $taskPhpExe -r 'echo PHP_VERSION_ID;'
    if ($LASTEXITCODE -ne 0 -or [int]$taskVersion -lt 80300) {
        throw 'Este proyecto requiere PHP 8.3 o superior. Usa -PhpPath para indicar el PHP correcto.'
    }

    $taskDrivers = & $taskPhpExe -r 'echo json_encode(PDO::getAvailableDrivers());'
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo consultar PDO. Revisa la instalacion de PHP.'
    }
    if (($taskDrivers | ConvertFrom-Json) -contains 'pgsql') {
        Write-Host "pdo_pgsql ya esta habilitado en $taskPhpExe"
        exit 0
    }

    $taskExtensionDir = Join-Path $taskPhpDir 'ext'
    if (-not (Test-Path -LiteralPath (Join-Path $taskExtensionDir 'php_pdo_pgsql.dll'))) {
        throw 'Falta ext/php_pdo_pgsql.dll. Instala la extension de la misma version, arquitectura y tipo de PHP. No copies una DLL de otro PHP.'
    }
    if (-not (Test-Path -LiteralPath (Join-Path $taskPhpDir 'libpq.dll'))) {
        throw 'Falta libpq.dll junto a php.exe. Repara la instalacion de PHP con los archivos de su distribucion oficial.'
    }

    $taskIniPath = [string](& $taskPhpExe -r 'echo php_ini_loaded_file();')
    $taskIniPath = $taskIniPath.Trim()
    if (-not $taskIniPath) {
        $taskIniPath = Join-Path $taskPhpDir 'php.ini'
        $taskTemplate = Join-Path $taskPhpDir 'php.ini-development'
        if (Test-Path -LiteralPath $taskIniPath) {
            throw 'Hay un php.ini junto a PHP, pero no se carga. Revisa PHPRC y PHP_INI_SCAN_DIR antes de modificarlo.'
        }
        if (-not (Test-Path -LiteralPath $taskTemplate)) {
            throw 'PHP no carga un php.ini y no hay php.ini-development. Repara su instalacion.'
        }
        Copy-Item -LiteralPath $taskTemplate -Destination $taskIniPath
    }

    $taskIniPath = (Resolve-Path -LiteralPath $taskIniPath).Path
    $taskOriginal = [System.IO.File]::ReadAllText($taskIniPath)
    $taskBackup = $taskIniPath + '.postgresql-backup-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
    Copy-Item -LiteralPath $taskIniPath -Destination $taskBackup

    $taskDirectoryPattern = '(?m)^[ \t]*extension_dir[ \t]*=.*\r?$'
    $taskDirectoryLine = 'extension_dir="' + $taskExtensionDir + '"'
    if ([regex]::IsMatch($taskOriginal, $taskDirectoryPattern)) {
        $taskUpdated = [regex]::Replace($taskOriginal, $taskDirectoryPattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $taskDirectoryLine })
    } else {
        $taskUpdated = $taskOriginal + [Environment]::NewLine + $taskDirectoryLine + [Environment]::NewLine
    }

    $taskExtensionPattern = '(?m)^[ \t]*;?[ \t]*extension[ \t]*=[ \t]*"?(?:php_)?pdo_pgsql(?:\.dll)?"?[ \t]*(?:;[^\r\n]*)?\r?$'
    if ([regex]::IsMatch($taskUpdated, $taskExtensionPattern)) {
        $taskUpdated = [regex]::Replace($taskUpdated, $taskExtensionPattern, 'extension=pdo_pgsql')
    } else {
        $taskUpdated += [Environment]::NewLine + 'extension=pdo_pgsql' + [Environment]::NewLine
    }

    try {
        [System.IO.File]::WriteAllText($taskIniPath, $taskUpdated, [System.Text.UTF8Encoding]::new($false))
        $taskDrivers = & $taskPhpExe -r 'echo json_encode(PDO::getAvailableDrivers());'
        if ($LASTEXITCODE -ne 0 -or -not (($taskDrivers | ConvertFrom-Json) -contains 'pgsql')) {
            throw 'PHP no pudo cargar pdo_pgsql. Revisa la compatibilidad de la DLL y sus dependencias.'
        }
    } catch {
        [System.IO.File]::WriteAllText($taskIniPath, $taskOriginal, [System.Text.UTF8Encoding]::new($false))
        throw
    }

    Write-Host 'pdo_pgsql habilitado y verificado.'
    Write-Host "PHP: $taskPhpExe"
    Write-Host "php.ini: $taskIniPath"
    Write-Host "Respaldo: $taskBackup"
    Write-Host 'Deten el servidor actual con Ctrl+C y reinicialo con ese mismo PHP.'
    Write-Host 'Despues ejecuta php artisan config:clear, composer run diagnostico y php artisan migrate --seed.'
    Write-Host 'Esta herramienta no cambia .env ni borra datos de la base.'
} catch {
    Write-Error $_.Exception.Message -ErrorAction Continue
    exit 1
}
