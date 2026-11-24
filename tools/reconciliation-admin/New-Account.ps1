param(
    [string]$PhpPath = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe',
    [string]$OutputDirectory = (Join-Path $env:USERPROFILE 'Downloads\Marquense_Admin_Cuentas')
)

$ErrorActionPreference = 'Stop'
$username = (Read-Host 'Usuario individual: 3-64 caracteres, minusculas, numeros, punto, guion o guion bajo').Trim().ToLowerInvariant()
if ($username -cnotmatch '^[a-z0-9][a-z0-9._-]{2,63}$') { throw 'Usuario no valido. [USERNAME]' }
if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) { throw 'No se encontro el PHP local indicado. [PHP_NOT_FOUND]' }
$null = New-Item -ItemType Directory -Path $OutputDirectory -Force
$outputFile = Join-Path (Resolve-Path -LiteralPath $OutputDirectory).Path ($username + '.json')
if (Test-Path -LiteralPath $outputFile) { throw 'El archivo ya existe. No se sobrescribira. [OUTPUT_EXISTS]' }
$first = Read-Host 'Contrasena nueva: minimo 16 caracteres y maximo 72 bytes UTF-8' -AsSecureString
$second = Read-Host 'Repita la contrasena' -AsSecureString
$a = [IntPtr]::Zero
$b = [IntPtr]::Zero
$plainFirst = $null
$plainSecond = $null
$process = $null
$payload = $null
try {
    $a = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($first)
    $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($second)
    $plainFirst = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($a)
    $plainSecond = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)
    $utf8 = New-Object System.Text.UTF8Encoding -ArgumentList @($false, $true)
    try { $byteCount = $utf8.GetByteCount($plainFirst) }
    catch { throw 'La contrasena contiene Unicode no valido. No se creo ninguna cuenta. [PASSWORD_ENCODING]' }
    if ($plainFirst.IndexOf([char]0) -ge 0) { throw 'La contrasena contiene un caracter no permitido. [PASSWORD_ENCODING]' }
    # Count Unicode code points, matching PHP PCRE /u rather than UTF-16 units.
    $characterCount = [regex]::Matches($plainFirst, '[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]').Count
    if ($characterCount -lt 16) { throw 'La contrasena debe tener al menos 16 caracteres. No se creo ninguna cuenta. [PASSWORD_LENGTH]' }
    if ($byteCount -gt 72) { throw 'La contrasena supera 72 bytes UTF-8. No se creo ninguna cuenta. [PASSWORD_BYTES]' }
    if (-not [string]::Equals($plainFirst, $plainSecond, [StringComparison]::Ordinal)) {
        throw 'Las contrasenas no coinciden. No se creo ninguna cuenta. [PASSWORD_MISMATCH]'
    }
    $inputObject = @{username=$username; password=$plainFirst; confirmation=$plainSecond}
    $json = $inputObject | ConvertTo-Json -Compress
    # Binary stdin avoids PowerShell 5.1's native-pipeline encoding and stderr behavior.
    # Only resolved file paths are arguments; the password never enters the command line.
    $start = New-Object System.Diagnostics.ProcessStartInfo
    $start.FileName = (Resolve-Path -LiteralPath $PhpPath).Path
    $helper = Join-Path $PSScriptRoot 'create_account.php'
    $start.Arguments = '-n -d display_errors=0 -d log_errors=0 "' + $helper + '" "--output=' + $outputFile + '"'
    $start.UseShellExecute = $false
    $start.CreateNoWindow = $true
    $start.RedirectStandardInput = $true
    $start.RedirectStandardOutput = $true
    $start.RedirectStandardError = $true
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $start
    $processStage = 'PHP_START'
    try {
        if (-not $process.Start()) { throw 'start' }
        $processStage = 'PHP_IO'
        $stdout = $process.StandardOutput.ReadToEndAsync()
        $stderr = $process.StandardError.ReadToEndAsync()
        $payload = $utf8.GetBytes($json)
        $process.StandardInput.BaseStream.Write($payload, 0, $payload.Length)
        $process.StandardInput.Close()
        $processStage = 'PHP_TIMEOUT'
        if (-not $process.WaitForExit(30000)) { throw 'timeout' }
        $processStage = 'PHP_IO'
        $exitCode = $process.ExitCode
        $diagnostic = $stderr.GetAwaiter().GetResult()
        $null = $stdout.GetAwaiter().GetResult()
    } catch {
        throw ('No se pudo completar el proceso PHP. No utilice un archivo incompleto. [' + $processStage + ']')
    }
    if ($exitCode -ne 0 -or -not (Test-Path -LiteralPath $outputFile -PathType Leaf)) {
        $messages = @{
            ARGUMENTS = 'Argumentos del generador PHP no validos.'
            DEPENDENCY = 'No se pudieron cargar las dependencias locales del generador.'
            INPUT_SIZE = 'No se recibio una entrada completa con el tamano permitido.'
            INPUT_JSON = 'PHP no recibio JSON UTF-8 valido.'
            INPUT_FIELDS = 'Faltan campos validos para crear la cuenta.'
            USERNAME = 'El nombre de usuario no es valido.'
            PASSWORD_LENGTH = 'La contrasena debe tener al menos 16 caracteres y como maximo 72 bytes UTF-8.'
            PASSWORD_MISMATCH = 'Las contrasenas no coinciden.'
            OUTPUT_PATH = 'La salida debe estar fuera de la raiz web y llamarse usuario.json.'
            OUTPUT_EXISTS = 'El archivo ya existe. No se sobrescribira.'
            OUTPUT_CREATE = 'PHP no pudo crear el archivo. Revise permisos y protecciones de la carpeta de salida.'
            OUTPUT_WRITE = 'No se pudo completar la escritura. No utilice un archivo incompleto.'
            OUTPUT_SYNC = 'No se pudo sincronizar el archivo. No utilice un archivo incompleto.'
            INTERNAL = 'El generador no termino correctamente. No utilice un archivo incompleto.'
        }
        $match = [regex]::Match($diagnostic, 'LM_ADMIN_ACCOUNT_ERROR=([A-Z_]+)')
        $code = 'INTERNAL'
        if ($match.Success -and $messages.ContainsKey($match.Groups[1].Value)) { $code = $match.Groups[1].Value }
        throw ($messages[$code] + ' [' + $code + ']')
    }
    Write-Output ('Archivo privado local: ' + $outputFile)
    Write-Output 'No suba esta herramienta al hosting ni comparta el JSON o la contrasena.'
} finally {
    if ($process) {
        try { if (-not $process.HasExited) { $process.Kill(); $process.WaitForExit() } } catch {}
        $process.Dispose()
    }
    if ($payload) { [Array]::Clear($payload, 0, $payload.Length) }
    $inputObject = $null
    $json = $null
    $plainFirst = $null
    $plainSecond = $null
    $diagnostic = $null
    if ($a -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($a) }
    if ($b -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
    $first.Dispose()
    $second.Dispose()
}
