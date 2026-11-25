param(
    [Parameter(Mandatory = $true)]
    [string] $Database,
    [int] $Port = 8016,
    [string] $PhpBinary = 'C:\laragon\bin\php\php-8.4.27-1-Win32-vs17-x64\php.exe',
    [string] $WhatsAppTestRecipient = ''
)

$ErrorActionPreference = 'Stop'
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$testingDirectory = [System.IO.Path]::GetFullPath((Join-Path $root 'storage\framework\testing'))
$databaseFile = Get-Item -LiteralPath $Database -ErrorAction Stop
$databasePath = $databaseFile.FullName

if ($databaseFile.DirectoryName -ne $testingDirectory -or
    $databaseFile.Name -notmatch '^kronik-e2e-[0-9a-f-]{36}\.sqlite$' -or
    $databaseFile.Length -eq 0) {
    throw 'Selecciona una base SQLite QA ya migrada dentro de storage/framework/testing.'
}
if ($Port -lt 1024 -or $Port -gt 65535) {
    throw 'Indica un puerto local entre 1024 y 65535.'
}
if ($WhatsAppTestRecipient -ne '' -and
    $WhatsAppTestRecipient -notmatch '^\+[1-9][0-9]{7,14}$') {
    throw 'El destinatario de prueba debe tener formato internacional E.164.'
}
if (-not (Test-Path -LiteralPath $PhpBinary -PathType Leaf)) {
    throw 'Indica la ruta del ejecutable PHP 8.3 o superior.'
}
$phpVersionId = & $PhpBinary -r 'echo PHP_VERSION_ID;'
if ($LASTEXITCODE -ne 0 -or [int]$phpVersionId -lt 80300) {
    throw 'El proyecto requiere PHP 8.3 o superior.'
}

$keyLine = Get-Content -LiteralPath (Join-Path $root '.env') |
    Where-Object { $_ -match '^APP_KEY=' } | Select-Object -Last 1
if (-not $keyLine -or $keyLine.Length -le 8) {
    throw 'Configura APP_KEY en .env antes de iniciar QA.'
}

$env:APP_KEY = $keyLine.Substring(8)
$env:APP_ENV = 'e2e'
$env:APP_DEBUG = 'false'
$env:DEBUGBAR_ENABLED = 'false'
$env:APP_URL = "http://127.0.0.1:$Port"
$env:E2E_DATABASE = 'true'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $databasePath
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'file'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'
$env:GEOCODING_ENABLED = 'false'
$env:COMMUNICATIONS_SEND_ENABLED = 'false'
$env:COMMUNICATIONS_WHATSAPP_TEST_ENABLED = if ($WhatsAppTestRecipient) { 'true' } else { 'false' }
$env:COMMUNICATIONS_WHATSAPP_TEST_RECIPIENT = $WhatsAppTestRecipient

$hotFile = Join-Path $root 'public\hot'
if (Test-Path -LiteralPath $hotFile) {
    $viteUrl = (Get-Content -LiteralPath $hotFile -Raw).Trim()
    $viteUri = $null
    if (-not [uri]::TryCreate($viteUrl, [System.UriKind]::Absolute, [ref] $viteUri) -or
        $viteUri.Host -notin @('127.0.0.1', 'localhost')) {
        throw 'El archivo public/hot no apunta a un servidor Vite local.'
    }
    try {
        Invoke-WebRequest -Uri ($viteUri.AbsoluteUri.TrimEnd('/') + '/@vite/client') -UseBasicParsing -TimeoutSec 3 | Out-Null
    } catch {
        throw "Vite no responde en $viteUrl. Inícialo con npm run dev -- --host 127.0.0.1 --port $($viteUri.Port)."
    }
}

Push-Location (Join-Path $root 'public')
try {
    & $PhpBinary -d extension=pdo_sqlite -d extension=sqlite3 -S "127.0.0.1:$Port" -t . (Join-Path $root 'tests\E2E\server.php')
} finally {
    Pop-Location
}
