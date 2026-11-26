param(
    [string]$PhpPath = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe',
    [switch]$BrowserCheck,
    [string]$ArtifactsDirectory = ''
)

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
    $legacyReference = 'LMQ-20260820161331-8A8F4A7D'
    $legacyPayment = Join-Path $site ('storage\'+$legacyReference+'.json')
    @{reference=$legacyReference;status='ORDER_SENT';amount=29.75;currency='GTQ';created_at='2026-08-20T16:13:31Z';
        external_order_response=@{success=$true;idcotizacion=987};
        order_payload=@{nit='C/F';nombre_cliente='Cliente Historico Sintetico';datosArticulos=@{articulos=@{idarticulo=@(1);cantidad=@(1);precio_venta=@(29.75)}}}} |
        ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $legacyPayment -Encoding ascii
    $legacyHash = (Get-FileHash -LiteralPath $legacyPayment -Algorithm SHA256).Hash
    $eventFile = Join-Path $site ('storage\event-'+$legacyReference+'-'+('a'*64)+'.json')
    @{id=($legacyReference+'-'+('a'*64));reference=$legacyReference;state='APPLIED';received_at='2026-08-20T16:13:40Z';applied_at='2026-08-20T16:13:41Z';
        event=@{status='APPROVED';issues=@();authenticated_fields=@{decision='ACCEPT';reason_code='100';signed_date_time='2026-08-20T16:13:39Z';auth_code='PRIVATE-EVENT-SENTINEL';secret_key='PRIVATE-EVENT-SENTINEL'}}} |
        ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $eventFile -Encoding ascii
    $eventHash = (Get-FileHash -LiteralPath $eventFile -Algorithm SHA256).Hash

    $fixtureHashes = @{}
    foreach ($file in @($payment,$heldPayment,$legacyPayment,$eventFile)) { $fixtureHashes[$file] = (Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash }
    for ($n=0; $n -lt 28; $n++) {
        $extraReference = 'LMQ-20261125000000-' + ('{0:X8}' -f $n)
        $extraFile = Join-Path $site ('storage\'+$extraReference+'.json')
        @{reference=$extraReference;_revision=1;status='PAID';payment_verified=$true;catalog_verified=$true;environment='production';amount=30;currency='GTQ';order_dispatch=@{state='UNKNOWN'}} |
            ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $extraFile -Encoding ascii
        $fixtureHashes[$extraFile] = (Get-FileHash -LiteralPath $extraFile -Algorithm SHA256).Hash
    }

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
            return @{status=[int]$response.StatusCode;body=$body;setCookie=$setCookie;cache=[string]$response.Headers.CacheControl;location=[string]$response.Headers.Location}
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
    $anonymousShow = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$legacyReference}
    Check ($anonymousShow.status -eq 303 -and -not (Send-Panel 'GET' @{}).body.Contains($legacyReference)) 'anonymous reference query cannot disclose a record'
    $denied = Send-Panel 'POST' @{action='login';csrf='wrong';username='fixture.operator';password=$password}
    Check ($denied.status -eq 403) 'login CSRF'
    $login = Send-Panel 'POST' @{action='login';csrf=$csrf;username='fixture.operator';password=$password}
    Check ($login.status -eq 303) 'login redirect'
    $dashboard = Send-Panel 'GET' @{}
    Check ($dashboard.status -eq 200 -and $dashboard.body.Contains('data-screen="home"') -and [regex]::Matches($dashboard.body,'class="menu-card"').Count -eq 3) 'login shows the three-option home'
    Check (-not $dashboard.body.Contains($reference) -and -not $dashboard.body.Contains('id="reference"') -and -not $dashboard.body.Contains('<table')) 'home hides all query and list screens'
    $csrf = [regex]::Match($dashboard.body,'name="csrf" value="([a-f0-9]+)"').Groups[1].Value
    function Open-Panel([string]$action) {
        $selected = Send-Panel 'POST' @{action=$action;csrf=$csrf}
        Check ($selected.status -eq 303) ('section navigation: '+$action)
        return (Send-Panel 'GET' @{})
    }
    function Check-Screen($response,[string]$section) {
        Check ($response.status -eq 200 -and $response.body.Contains('data-screen="'+$section+'"') -and [regex]::Matches($response.body,'data-screen="').Count -eq 1) ('one independent screen: '+$section)
    }
    function Get-ReferenceRows([string]$body) {
        return @([regex]::Matches($body,'name="reference" value="(LMQ-[0-9]{14}-[A-F0-9]{8})"') | ForEach-Object { $_.Groups[1].Value })
    }
    $pending = Open-Panel 'list'
    Check-Screen $pending 'pending'
    Check ($pending.body.Contains($reference) -and -not $pending.body.Contains($heldReference) -and -not $pending.body.Contains('id="reference"')) 'pending remains approved-only without a search form'
    Check (-not $pending.body.Contains('name="review_token"')) 'links remain blocked'
    $firstPendingRows = @(Get-ReferenceRows $pending.body)
    $search = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$reference}
    $detail = Send-Panel 'GET' @{}
    Check ($search.status -eq 303 -and $detail.body.Contains('id="detalle-operacion"') -and $detail.body.Contains('Cliente Prueba') -and -not $detail.body.Contains('data-screen="')) 'pending review opens only the selected operation'
    Check (-not $detail.body.Contains('id="reference"') -and -not $detail.body.Contains('<pre>') -and $detail.body.Contains('<details class="panel" id="technical-details">')) 'detail keeps search and commercial JSON out of the main screen'
    Check ($detail.body.Contains('No hay eventos durables disponibles')) 'missing historical event history remains explicit'
    $back = Open-Panel 'back'
    Check-Screen $back 'pending'
    Check ([string]::Join('|',@(Get-ReferenceRows $back.body)) -eq [string]::Join('|',$firstPendingRows)) 'pending back preserves the original page'
    $pendingNext = Open-Panel 'next'
    Check-Screen $pendingNext 'pending'
    $nextPendingRows = @(Get-ReferenceRows $pendingNext.body)
    Check ($nextPendingRows.Count -gt 0 -and -not $pendingNext.body.Contains($reference)) 'pending pagination remains active'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$nextPendingRows[0]}
    $null = Send-Panel 'GET' @{}
    $pendingBack = Open-Panel 'back'
    Check ([string]::Join('|',@(Get-ReferenceRows $pendingBack.body)) -eq [string]::Join('|',$nextPendingRows)) 'pending detail back preserves page two exactly'
    $homePage = Open-Panel 'home'
    Check-Screen $homePage 'home'
    $query = Open-Panel 'search'
    Check-Screen $query 'search'
    Check ($query.body.Contains('id="reference"') -and -not $query.body.Contains('<table') -and -not $query.body.Contains('class="menu-card"')) 'reference query shows only its form'
    $queryForms = @([regex]::Matches($query.body,'(?s)<form\b[^>]*>.*?</form>') | ForEach-Object { $_.Value })
    $searchForm = @($queryForms | Where-Object { $_.Contains('>Consultar referencia</button>') })
    Check ($searchForm.Count -eq 1 -and $searchForm[0].Contains('method="post" action="/admin/conciliacion/"')) 'reference search keeps the secure POST destination'
    $searchCsrf = [regex]::Match($searchForm[0],'name="csrf" value="([a-f0-9]+)"').Groups[1].Value
    $searched = Send-Panel 'POST' @{action='show';csrf=$searchCsrf;reference=$legacyReference}
    $legacyDetail = Send-Panel 'GET' @{}
    Check ($searched.location -eq '/admin/conciliacion/#detalle-operacion' -and $legacyDetail.body.Contains('Cliente Historico Sintetico') -and $legacyDetail.body.Contains('#987')) 'reference search opens legacy detail after redirect'
    Check ($legacyDetail.body.Contains('Registro hist') -and $legacyDetail.body.Contains('NO VERIFICADO')) 'legacy records never acquire verification from the UI'
    Check ($legacyDetail.body.Contains('class="panel history"') -and $legacyDetail.body.Contains('2026-08-20T16:13:40') -and $legacyDetail.body.Contains('ACCEPT / 100') -and -not $legacyDetail.body.Contains('PRIVATE-EVENT-SENTINEL')) 'summarized history retains allowed events only'
    $queryBack = Open-Panel 'back'
    Check-Screen $queryBack 'search'
    Check ($queryBack.body.Contains('id="reference"') -and -not $queryBack.body.Contains($legacyReference)) 'query back returns to the clean search form'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$legacyReference}
    $null = Send-Panel 'GET' @{}
    $queryAgain = Open-Panel 'search'
    Check-Screen $queryAgain 'search'
    Check (-not $queryAgain.body.Contains('id="detalle-operacion"')) 'search again clears only the UI selection'
    $null = Open-Panel 'home'
    $deniedIncidents = Send-Panel 'POST' @{action='incidents';csrf='wrong'}
    Check ($deniedIncidents.status -eq 403) 'section selection requires CSRF'
    $incidents = Open-Panel 'incidents'
    Check-Screen $incidents 'incidents'
    Check ($incidents.body.Contains($heldReference) -and $incidents.body.Contains($legacyReference) -and -not $incidents.body.Contains('id="reference"')) 'incident list is separate and retains historical cases'
    $firstIncidentRows = @(Get-ReferenceRows $incidents.body)
    $forms = @([regex]::Matches($incidents.body,'(?s)<form\b[^>]*>.*?</form>') | ForEach-Object { $_.Value })
    $reviewForm = @($forms | Where-Object { $_.Contains('name="reference" value="'+$legacyReference+'"') })
    Check ($reviewForm.Count -eq 1 -and $reviewForm[0].Contains('method="post" action="/admin/conciliacion/"') -and $reviewForm[0].Contains('>Revisar</button>')) 'review button posts the historical reference'
    $reviewCsrf = [regex]::Match($reviewForm[0],'name="csrf" value="([a-f0-9]+)"').Groups[1].Value
    $reviewAction = [regex]::Match($reviewForm[0],'name="action" value="([a-z]+)"').Groups[1].Value
    Check ((Send-Panel 'POST' @{action=$reviewAction;csrf='wrong';reference=$legacyReference}).status -eq 403) 'review cannot bypass CSRF'
    $reviewed = Send-Panel 'POST' @{action=$reviewAction;csrf=$reviewCsrf;reference=$legacyReference}
    $reviewedDetail = Send-Panel 'GET' @{}
    Check ($reviewed.location -eq '/admin/conciliacion/#detalle-operacion' -and $reviewedDetail.body.Contains('id="detalle-operacion"') -and -not $reviewedDetail.body.Contains('data-screen="incidents"')) 'incident review hides the main table'
    Check (-not $reviewedDetail.body.Contains('name="review_token"') -and -not $reviewedDetail.body.Contains('id="reference"')) 'incident detail has no write controls or repeated search'
    $incidentBack = Open-Panel 'back'
    Check-Screen $incidentBack 'incidents'
    Check ([string]::Join('|',@(Get-ReferenceRows $incidentBack.body)) -eq [string]::Join('|',$firstIncidentRows)) 'incident back preserves the first page'
    $incidentsNext = Open-Panel 'next'
    $nextIncidentRows = @(Get-ReferenceRows $incidentsNext.body)
    Check ($nextIncidentRows.Count -gt 0 -and -not $incidentsNext.body.Contains($legacyReference)) 'incident pagination remains active'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$nextIncidentRows[0]}
    $null = Send-Panel 'GET' @{}
    $incidentPageBack = Open-Panel 'back'
    Check-Screen $incidentPageBack 'incidents'
    Check ([string]::Join('|',@(Get-ReferenceRows $incidentPageBack.body)) -eq [string]::Join('|',$nextIncidentRows)) 'incident back preserves page two exactly'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$heldReference}
    $heldDetail = Send-Panel 'GET' @{}
    Check ($heldDetail.body.Contains('missing_or_unsigned_auth_code') -and -not $heldDetail.body.Contains('PRIVATE-SENTINEL')) 'technical details retain safe review reasons'
    $missingReference = 'LMQ-20260820161331-FFFFFFFF'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$missingReference}
    $missingDetail = Send-Panel 'GET' @{}
    Check ($missingDetail.status -eq 200 -and $missingDetail.body.Contains('No fue posible consultar esta referencia') -and -not $missingDetail.body.Contains('Cliente Historico Sintetico')) 'missing reference displays its own safe error'
    $corruptFile = Join-Path $site ('storage\'+$missingReference+'.json')
    [IO.File]::WriteAllText($corruptFile,'invalid-synthetic-record')
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference=$missingReference}
    $failedDetail = Send-Panel 'GET' @{}
    Check ($failedDetail.status -eq 200 -and $failedDetail.body.Contains('No fue posible consultar esta referencia') -and -not $failedDetail.body.Contains('invalid-synthetic-record')) 'unreadable record never exposes raw data'
    $null = Send-Panel 'POST' @{action='show';csrf=$csrf;reference='../private'}
    Check ((Send-Panel 'GET' @{}).body.Contains('Referencia no valida')) 'malformed reference receives a safe message'
    $blocked = Send-Panel 'POST' @{action='link';csrf=$csrf;quotation='123';evidence='ticket-fixture'}
    Check ($blocked.status -eq 303 -and (Get-FileHash -LiteralPath $payment -Algorithm SHA256).Hash -eq $paymentHash) 'forged link still cannot change a payment'
    $browserResult = $null
    if ($BrowserCheck) {
        if ([string]::IsNullOrWhiteSpace($ArtifactsDirectory)) { throw 'A private artifact directory is required for browser checks.' }
        $browserInput = @{url=$url;username='fixture.operator';password=$password;fixture=$fixture;output=$ArtifactsDirectory} | ConvertTo-Json -Compress
        $rawBrowserResult = $browserInput | & node (Join-Path $PSScriptRoot 'reconciliation_admin_browser.js')
        $browserInput = $null
        if ($LASTEXITCODE -ne 0) { throw 'Isolated responsive browser checks failed.' }
        $browserResult = $rawBrowserResult | ConvertFrom-Json
        Check ($browserResult.Result -eq 'PASS') 'responsive browser checks passed'
    }
    foreach ($file in $fixtureHashes.Keys) {
        Check ((Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash -eq $fixtureHashes[$file]) 'queries and navigation never change payment or event fixtures'
    }
    $logout = Send-Panel 'POST' @{action='logout';csrf=$csrf}
    Check ($logout.status -eq 303) 'logout redirect'
    $after = Send-Panel 'GET' @{}
    Check ($after.status -eq 200 -and -not $after.body.Contains($reference) -and -not $after.body.Contains($legacyReference)) 'logout revokes the administrative session'
    $records = @(Get-ChildItem -LiteralPath (Join-Path $private 'audit') -Filter 'performance-*.jsonl' | Get-Content | ForEach-Object { $_ | ConvertFrom-Json })
    Check (@($records | Where-Object {$_.kind -eq 'login' -and $_.stages_ms.password_verify_ms -gt 0}).Count -eq (1 + $(if ($browserResult) {$browserResult.Logins} else {0}))) 'private bcrypt timing'
    $gets = @($records | Where-Object {$_.kind -in @('dashboard','detail')})
    Check ($gets.Count -ge 2 -and @($gets | Where-Object {$_.stages_ms.PSObject.Properties.Name -contains 'password_verify_ms'}).Count -eq 0) 'refresh has no bcrypt verification'
    Check (@($gets | Where-Object {$_.stages_ms.PSObject.Properties.Name -contains 'render_ms'}).Count -ge 2) 'render timing'
    $logs = $records | ConvertTo-Json -Depth 8 -Compress
    Check (-not $logs.Contains($password) -and -not $logs.Contains('fixture.operator') -and -not $logs.Contains($reference) -and -not [regex]::IsMatch($logs,'\$2[aby]\$')) 'minimal performance metadata'
    [pscustomobject]@{Result='PASS';Assertions=$script:checks;Browser=$browserResult;TlsWasEmulatedOnlyInLoopbackFixture=$true;RealCommercialOperations=$false} | ConvertTo-Json -Compress
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
