param([string]$PhpPath = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe')

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$generator = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..\..\tools\reconciliation-admin\New-Account.ps1')).Path
$helper = Join-Path (Split-Path -Parent $generator) 'create_account.php'
$tempBase = [IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\')
$fixture = Join-Path $tempBase ('lm-admin-generator-test-' + [guid]::NewGuid().ToString('N'))
$null = New-Item -ItemType Directory -Path $fixture -ErrorAction Stop
$oldEncoding = $OutputEncoding
$OutputEncoding = New-Object System.Text.UTF8Encoding -ArgumentList @($false, $true)
$oldReadHost = Get-Item Function:\Read-Host -ErrorAction SilentlyContinue
$script:checks = 0

function Check($Condition, [string]$Label) {
    if (-not $Condition) { throw ('FAIL: ' + $Label) }
    $script:checks++
}

function global:Read-Host {
    param([string]$Prompt, [switch]$AsSecureString)
    if ($AsSecureString) {
        $answer = if ($global:lmGeneratorFixtureRead -eq 0) { $global:lmGeneratorFixturePassword } else { $global:lmGeneratorFixtureConfirmation }
        $global:lmGeneratorFixtureRead++
        return ConvertTo-SecureString -String $answer -AsPlainText -Force
    }
    return 'admin.marquense'
}

function Invoke-GeneratorCase([string]$Label, [string]$Password, [string]$Confirmation, [string]$ExpectedCode) {
    $out = Join-Path $fixture $Label
    $global:lmGeneratorFixturePassword = $Password
    $global:lmGeneratorFixtureConfirmation = $Confirmation
    $global:lmGeneratorFixtureRead = 0
    $output = @()
    $errorText = ''
    try { $output = @(& $generator -PhpPath $PhpPath -OutputDirectory $out 2>&1) }
    catch { $errorText = $_.Exception.Message }
    $file = Join-Path $out 'admin.marquense.json'
    $visible = ($output | Out-String) + $errorText
    Check (-not $visible.Contains($Password)) ($Label + ': no password disclosure')
    Check (-not [regex]::IsMatch($visible, '\$2[aby]\$[0-9]{2}\$')) ($Label + ': no hash disclosure')
    if ($ExpectedCode -ne '') {
        Check ($errorText.Contains('[' + $ExpectedCode + ']')) ($Label + ': safe error code')
        Check (-not (Test-Path -LiteralPath $file)) ($Label + ': no account created')
        return
    }
    Check ($errorText -eq '' -and (Test-Path -LiteralPath $file)) ($Label + ': complete generation')
    $account = Get-Content -LiteralPath $file -Raw | ConvertFrom-Json
    Check ($account.username -eq 'admin.marquense' -and $account.role -eq 'reconciliation_admin' -and $account.enabled -eq $true) ($Label + ': account schema')
    Check ($account.password_hash.StartsWith('$2y$12$')) ($Label + ': bcrypt cost 12')
    $verify = @'
$x=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR);
$a=json_decode(file_get_contents($x['file']),true,8,JSON_THROW_ON_ERROR);
$i=password_get_info($a['password_hash']);
echo json_encode(array('verified'=>password_verify($x['password'],$a['password_hash']),'cost'=>$i['options']['cost']));
'@
    $body = @{file=$file;password=$Password} | ConvertTo-Json -Compress
    $verified = ($body | & $PhpPath -n -r $verify) | ConvertFrom-Json
    Check ($LASTEXITCODE -eq 0 -and $verified.verified -eq $true -and $verified.cost -eq 12) ($Label + ': exact password and hash verified by PHP')
    $before = (Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash
    $blocked = ''
    try { $unused = @(& $generator -PhpPath $PhpPath -OutputDirectory $out 2>&1) }
    catch { $blocked = $_.Exception.Message }
    Check ($blocked.Contains('[OUTPUT_EXISTS]') -and (Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash -eq $before) ($Label + ': existing file unchanged')
}

function Invoke-HelperError([string]$Label, [string]$Body, [string]$ExpectedCode) {
    $file = Join-Path $fixture ('backend-' + $Label + '\admin.marquense.json')
    $null = New-Item -ItemType Directory -Path (Split-Path -Parent $file)
    $oldPreference = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $output = @($Body | & $PhpPath -n -d display_errors=0 -d log_errors=0 $helper ('--output=' + $file) 2>&1)
        $exitCode = $LASTEXITCODE
    } finally { $ErrorActionPreference = $oldPreference }
    $text = ($output | Out-String)
    Check ($exitCode -ne 0 -and $text.Contains('LM_ADMIN_ACCOUNT_ERROR=' + $ExpectedCode)) ($Label + ': PHP safe error')
    Check (-not (Test-Path -LiteralPath $file)) ($Label + ': PHP writes no account')
    Check (-not [regex]::IsMatch($text, '\$2[aby]\$[0-9]{2}\$')) ($Label + ': PHP discloses no hash')
}

try {
    $sixteen = [guid]::NewGuid().ToString('N').Substring(0,16)
    $valid = 'Synthetic-only-' + [guid]::NewGuid().ToString('N')
    Invoke-GeneratorCase 'minimum-16' $sixteen $sixteen ''
    $max = [guid]::NewGuid().ToString('N') + [guid]::NewGuid().ToString('N') + 'Test123!'
    Invoke-GeneratorCase 'maximum-72-bytes' $max $max ''
    $unicode = ([string][char]0x00f1) * 36
    Invoke-GeneratorCase 'unicode-and-path with spaces' $unicode $unicode ''
    $special = 'Synthetic-"quotes"-''single''-\-' + [guid]::NewGuid().ToString('N')
    Invoke-GeneratorCase 'special-characters' $special $special ''
    $short = $sixteen.Substring(0,15)
    Invoke-GeneratorCase 'reject-15' $short $short 'PASSWORD_LENGTH'
    $long = $max + 'X'
    Invoke-GeneratorCase 'reject-73-bytes' $long $long 'PASSWORD_BYTES'
    Invoke-GeneratorCase 'reject-mismatch' $valid ($valid + 'X') 'PASSWORD_MISMATCH'
    $nul = $valid + [char]0
    Invoke-GeneratorCase 'reject-nul' $nul $nul 'PASSWORD_ENCODING'
    $eightEmoji = ([string][char]0xD83D + [string][char]0xDE00) * 8
    Invoke-GeneratorCase 'reject-8-codepoints-16-utf16-units' $eightEmoji $eightEmoji 'PASSWORD_LENGTH'
    $rawShort = @{username='admin.marquense';password=$short;confirmation=$short} | ConvertTo-Json -Compress
    Invoke-HelperError 'short' $rawShort 'PASSWORD_LENGTH'
    $rawMismatch = @{username='admin.marquense';password=$valid;confirmation=($valid+'X')} | ConvertTo-Json -Compress
    Invoke-HelperError 'mismatch' $rawMismatch 'PASSWORD_MISMATCH'
    Invoke-HelperError 'json' '{' 'INPUT_JSON'
    $phpVersion = & $PhpPath -n -r 'echo PHP_VERSION;'
    [pscustomobject]@{Result='PASS';PowerShell=$PSVersionTable.PSVersion.ToString();PHP=$phpVersion;Assertions=$script:checks;RealAccountInstalled=$false} | ConvertTo-Json -Compress
} finally {
    $OutputEncoding = $oldEncoding
    foreach ($name in @('lmGeneratorFixturePassword','lmGeneratorFixtureConfirmation','lmGeneratorFixtureRead')) {
        Remove-Variable -Name $name -Scope Global -ErrorAction SilentlyContinue
    }
    if ($oldReadHost) { Set-Item Function:\global:Read-Host -Value $oldReadHost.ScriptBlock }
    else { Remove-Item Function:\Read-Host }
    $resolved = (Resolve-Path -LiteralPath $fixture).Path
    if ($resolved -ne [IO.Path]::GetFullPath($fixture) -or -not $resolved.StartsWith($tempBase + '\',[StringComparison]::OrdinalIgnoreCase) -or
        (Split-Path -Leaf $resolved) -notmatch '^lm-admin-generator-test-[a-f0-9]{32}$') { throw 'Unsafe fixture cleanup refused.' }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
