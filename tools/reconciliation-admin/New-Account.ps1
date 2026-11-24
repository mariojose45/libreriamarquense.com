param(
    [string]$PhpPath = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe',
    [string]$OutputDirectory = (Join-Path $env:USERPROFILE 'Downloads\Marquense_Admin_Cuentas')
)

$ErrorActionPreference = 'Stop'
$username = (Read-Host 'Usuario individual: 3-64 caracteres, minusculas, numeros, punto, guion o guion bajo').Trim().ToLowerInvariant()
if ($username -cnotmatch '^[a-z0-9][a-z0-9._-]{2,63}$') { throw 'Usuario no valido.' }
if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) { throw 'No se encontro el PHP local indicado.' }
$null = New-Item -ItemType Directory -Path $OutputDirectory -Force
$outputFile = Join-Path (Resolve-Path -LiteralPath $OutputDirectory).Path ($username + '.json')
if (Test-Path -LiteralPath $outputFile) { throw 'El archivo ya existe. No se sobrescribira.' }
$first = Read-Host 'Contrasena nueva: minimo 16 caracteres y maximo 72 bytes UTF-8' -AsSecureString
$second = Read-Host 'Repita la contrasena' -AsSecureString
$a = [IntPtr]::Zero
$b = [IntPtr]::Zero
$oldEncoding = $OutputEncoding
try {
    $a = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($first)
    $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($second)
    $inputObject = @{username=$username; password=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($a); confirmation=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)}
    $json = $inputObject | ConvertTo-Json -Compress
    $OutputEncoding = New-Object System.Text.UTF8Encoding($false)
    $json | & $PhpPath -n -d display_errors=0 -d log_errors=0 (Join-Path $PSScriptRoot 'create_account.php') ('--output=' + $outputFile)
    if ($LASTEXITCODE -ne 0) { throw 'No se genero la cuenta. No suba un archivo incompleto.' }
    Write-Output ('Archivo privado local: ' + $outputFile)
    Write-Output 'No suba esta herramienta al hosting ni comparta el JSON o la contrasena.'
} finally {
    $OutputEncoding = $oldEncoding
    $inputObject = $null
    $json = $null
    if ($a -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($a) }
    if ($b -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
    $first.Dispose()
    $second.Dispose()
}
