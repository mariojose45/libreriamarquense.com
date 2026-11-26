param([string]$PhpPath = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe')

$ErrorActionPreference = 'Stop'
$app = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..\..')).Path
$tempBase = [IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\')
$fixture = Join-Path $tempBase ('lm-admin-http-' + [guid]::NewGuid().ToString('N'))
$site = Join-Path $fixture 'site'
$private = Join-Path $fixture 'private'
$server = $null
$client = $null
$checks = 0
$cookie = ''
function Check($condition, $label) { if (-not $condition) { throw ('FAIL: ' + $label) }; $script:checks++ }
try {
    foreach ($path in @($site,($site+'\admin\conciliacion'),($site+'\config'),($site+'\storage'),($site+'\integrations\cybersource\src'),($private+'\users'))) {
        $null = New-Item -ItemType Directory -Path $path -Force
    }
    foreach ($name in @('index.php','view.php')) {
        Copy-Item -LiteralPath (Join-Path $app ('admin\conciliacion\'+$name)) -Destination (Join-Path $site ('admin\conciliacion\'+$name))
    }
    Copy-Item -LiteralPath (Join-Path $app 'integrations\cybersource\Autoload.php') -Destination (Join-Path $site 'integrations\cybersource\Autoload.php')
    foreach ($file in (Get-ChildItem -LiteralPath (Join-Path $app 'integrations\cybersource\src') -Filter '*.php' -File)) {
        Copy-Item -LiteralPath $file.FullName -Destination (Join-Path $site 'integrations\cybersource\src')
    }
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'reconciliation_admin_http_settings.php') -Destination (Join-Path $site 'config\reconciliation_admin.php')
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'reconciliation_admin_http_config.php') -Destination (Join-Path $site 'config\cybersource.php')
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'reconciliation_admin_http_router.php') -Destination (Join-Path $fixture 'router.php')
    $null = New-Item -ItemType File -Path (Join-Path $fixture 'fixture-only')
    $password = 'Synthetic-only-' + [guid]::NewGuid().ToString('N')
    $seed = @{username='fixture.operator';password=$password;confirmation=$password} | ConvertTo-Json -Compress
    $null = $seed | & $PhpPath -n (Join-Path $app 'tools\reconciliation-admin\create_account.php') ('--output='+(Join-Path $private 'users\fixture.operator.json'))
    if ($LASTEXITCODE -ne 0) { throw 'Could not create administrative fixture.' }
    $reference = 'LMQ-20261124000000-AAAAAAAA'
    $payment = Join-Path $site ('storage\'+$reference+'.json')
    @{reference=$reference;_revision=1;status='PAID';payment_verified=$true;environment='production';amount=30;currency='GTQ';
        created_at='2026-11-24T00:00:00Z';order_dispatch=@{state='UNKNOWN'};order_payload=@{nit='C/F';nombre_cliente='Cliente Prueba';datosArticulos=@{articulos=@{idarticulo=@(1);cantidad=@(1);precio_venta=@(30)}}}} |
        ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $payment -Encoding ascii
    $paymentHash = (Get-FileHash -LiteralPath $payment -Algorithm SHA256).Hash
    $heldReference = 'LMQ-20261124000000-BBBBBBBB'
    $heldPayment = Join-Path $site ('storage\'+$heldReference+'.json')
    @{reference=$heldReference;_revision=1;status='REVIEW';payment_verified=$false;environment='production';amount=30;currency='GTQ';
        reconciliation=@{fixture=@{state='OPEN';reasons=@('missing_or_unsigned_auth_code');transaction_id='PRIVATE-SENTINEL'}}} |
        ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $heldPayment -Encoding ascii
    $heldHash = (Get-FileHash -LiteralPath $heldPayment -Algorithm SHA256).Hash
    $listener = New-Object Net.Sockets.TcpListener([Net.IPAddress]::Loopback,0)
    $listener.Start()
    $port = $listener.LocalEndpoint.Port
    $listener.Stop()
    $server = Start-Process -FilePath $PhpPath -ArgumentList @('-n','-d','allow_url_fopen=0','-d','disable_functions=mail,curl_exec,exec,shell_exec,system,proc_open,passthru','-S',('127.0.0.1:'+$port),'-t',$site,(Join-Path $fixture 'router.php')) -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $fixture 'stdout.txt') -RedirectStandardError (Join-Path $fixture 'stderr.txt')
    $handler = New-Object Net.Http.HttpClientHandler
    $handler.AllowAutoRedirect = $false
    $handler.UseCookies = $false
    $client = New-Object Net.Http.HttpClient($handler)
    $url = 'http://127.0.0.1:'+$port+'/admin/conciliacion/'
    function Send-Panel([string]$method, [hashtable]$fields) {
        $request = New-Object Net.Http.HttpRequestMessage((New-Object Net.Http.HttpMethod($method)), $url)
        $response = $null
        try {
            if ($script:cookie -ne '') { $null = $request.Headers.TryAddWithoutValidation('Cookie',$script:cookie) }
            if ($method -eq 'POST') {
                $null = $request.Headers.TryAddWithoutValidation('Origin','https://libreriamarquense.com')
                $dict = New-Object 'System.Collections.Generic.Dictionary[string,string]'
                foreach ($key in $fields.Keys) { $dict.Add($key,[string]$fields[$key]) }
                $request.Content = New-Object Net.Http.FormUrlEncodedContent($dict)
            }
            $response = $client.SendAsync($request).GetAwaiter().GetResult()
            $body = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
            $setCookie = ''
            if ($response.Headers.Contains('Set-Cookie')) {
                $setCookie = [string]::Join(';', $response.Headers.GetValues('Set-Cookie'))
                $match = [regex]::Match($setCookie,'__Host-LMReconciliation=[^;]*')
                if ($match.Success) { $script:cookie = $match.Value }
            }
            return @{status=[int]$response.StatusCode;body=$body;setCookie=$setCookie;cache=[string]$response.Headers.CacheControl}
        } finally { if ($response) { $response.Dispose() }; $request.Dispose() }
    }
    $anonymous = $null
    for ($i=0; $i -lt 30; $i++) {
        try { $anonymous = Send-Panel 'GET' @{}; break } catch { Start-Sleep -Milliseconds 100 }
    }
    Check ($anonymous -and $anonymous.status -eq 200 -and -not $anonymous.body.Contains($reference)) 'anonymous privacy'
    Check ($anonymous.setCookie.Contains('secure') -and $anonymous.setCookie.Contains('HttpOnly') -and $anonymous.setCookie.Contains('SameSite=Strict')) 'secure cookie attributes'
    Check ($anonymous.cache.Contains('no-store')) 'no response caching'
    Check (@(Get-ChildItem -LiteralPath (Join-Path $private 'audit') -Filter 'performance-*.jsonl').Count -eq 0) 'no anonymous performance records'
    $csrf = [regex]::Match($anonymous.body,'name="csrf" value="([a-f0-9]+)"').Groups[1].Value
    $denied = Send-Panel 'POST' @{action='login';csrf='wrong';username='fixture.operator';password=$password}
    Check ($denied.status -eq 403) 'login CSRF'
    $login = Send-Panel 'POST' @{action='login';csrf=$csrf;username='fixture.operator';password=$password}
    Check ($login.status -eq 303) 'login redirect'
    $dashboard = Send-Panel 'GET' @{}
    Check ($dashboard.status -eq 200 -and $dashboard.body.Contains($reference)) 'authenticated summary and rendering'
    Check (-not $dashboard.body.Contains('name="review_token"')) 'links remain blocked'
    Check (-not $dashboard.body.Contains($heldReference)) 'original approved-only list unchanged'
    $csrf = [regex]::Match($dashboard.body,'name="csrf" value="([a-f0-9]+)"').Groups[1].Value
    $deniedIncidents = Send-Panel 'POST' @{action='incidents';csrf='wrong'}
    Check ($deniedIncidents.status -eq 403) 'incident selection requires CSRF'
    $selected = Send-Panel 'POST' @{action='incidents';csrf=$csrf}
    Check ($selected.status -eq 303) 'private incident selection redirects safely'
    $incidents = Send-Panel 'GET' @{}
    Check ($incidents.status -eq 200 -and $incidents.body.Contains($heldReference) -and $incidents.body.Contains('ERP_UNKNOWN')) 'held and uncertain cases visible'
    Check (-not $incidents.body.Contains('name="review_token"')) 'incident view has no mutation form'
    $heldSearch = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$heldReference}
    $heldDetail = Send-Panel 'GET' @{}
    Check ($heldSearch.status -eq 303 -and $heldDetail.body.Contains('missing_or_unsigned_auth_code') -and -not $heldDetail.body.Contains('PRIVATE-SENTINEL')) 'safe incident detail without raw receipt'
    Check ((Get-FileHash -LiteralPath $heldPayment -Algorithm SHA256).Hash -eq $heldHash) 'incident queries never alter financial state'
    $back = Send-Panel 'POST' @{action='list';csrf=$csrf}
    Check ($back.status -eq 303 -and -not (Send-Panel 'GET' @{}).body.Contains($heldReference)) 'return to original pending list'
    $search = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$reference}
    Check ($search.status -eq 303) 'search persists before redirect'
    $detail = Send-Panel 'GET' @{}
    Check ($detail.status -eq 200 -and $detail.body.Contains('Cliente Prueba')) 'full detail after session close'
    $blocked = Send-Panel 'POST' @{action='link';csrf=$csrf;quotation='123';evidence='ticket-fixture'}
    Check ($blocked.status -eq 303 -and (Get-FileHash -LiteralPath $payment -Algorithm SHA256).Hash -eq $paymentHash) 'forged link cannot change fixture payment'
    $logout = Send-Panel 'POST' @{action='logout';csrf=$csrf}
    Check ($logout.status -eq 303) 'logout redirect'
    $after = Send-Panel 'GET' @{}
    Check ($after.status -eq 200 -and -not $after.body.Contains($reference)) 'logout revokes browser session'
    $records = @(Get-ChildItem -LiteralPath (Join-Path $private 'audit') -Filter 'performance-*.jsonl' | Get-Content | ForEach-Object { $_ | ConvertFrom-Json })
    Check (@($records | Where-Object {$_.kind -eq 'login' -and $_.stages_ms.password_verify_ms -gt 0}).Count -eq 1) 'private bcrypt timing'
    $gets = @($records | Where-Object {$_.kind -in @('dashboard','detail')})
    Check ($gets.Count -ge 2 -and @($gets | Where-Object {$_.stages_ms.PSObject.Properties.Name -contains 'password_verify_ms'}).Count -eq 0) 'refresh has no bcrypt verification'
    Check (@($gets | Where-Object {$_.stages_ms.PSObject.Properties.Name -contains 'render_ms'}).Count -ge 2) 'render timing'
    $logs = $records | ConvertTo-Json -Depth 8 -Compress
    Check (-not $logs.Contains($password) -and -not $logs.Contains('fixture.operator') -and -not $logs.Contains($reference) -and -not [regex]::IsMatch($logs,'\$2[aby]\$')) 'minimal performance metadata'
    [pscustomobject]@{Result='PASS';Assertions=$script:checks;TlsWasEmulatedOnlyInLoopbackFixture=$true;RealCommercialOperations=$false} | ConvertTo-Json -Compress
} finally {
    $password = $null
    $seed = $null
    $cookie = $null
    if ($client) { $client.Dispose() }
    if ($server -and -not $server.HasExited) { Stop-Process -Id $server.Id -Force }
    $resolved = (Resolve-Path -LiteralPath $fixture).Path
    if ($resolved -ne [IO.Path]::GetFullPath($fixture) -or -not $resolved.StartsWith($tempBase+'\',[StringComparison]::OrdinalIgnoreCase) -or
        (Split-Path -Leaf $resolved) -notmatch '^lm-admin-http-[a-f0-9]{32}$') { throw 'Unsafe fixture cleanup refused.' }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
