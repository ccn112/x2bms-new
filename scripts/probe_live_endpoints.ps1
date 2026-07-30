# Ban 2: login dung (co X-Device-Id) roi do lai. Muc dich: chac chan 404 la do
# thieu ROUTE tren live, khong phai do chua xac thuc.
$ErrorActionPreference = 'Continue'
$base = 'https://x2.fino.vn/api/v1'
$dev = 'probe-device-0001'

function Probe($method, $url, $body, $token) {
    $h = @{ 'Accept' = 'application/json'; 'X-Device-Id' = $dev }
    if ($token) { $h['Authorization'] = "Bearer $token" }
    try {
        if ($body) {
            $r = Invoke-WebRequest -Uri $url -Method $method -Headers $h -Body ($body | ConvertTo-Json) -ContentType 'application/json' -UseBasicParsing -TimeoutSec 25
        } else {
            $r = Invoke-WebRequest -Uri $url -Method $method -Headers $h -UseBasicParsing -TimeoutSec 25
        }
        return @{ code = [int]$r.StatusCode; body = $r.Content }
    } catch [System.Net.WebException] {
        $resp = $_.Exception.Response
        if ($null -eq $resp) { return @{ code = 0; body = $_.Exception.Message } }
        $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
        return @{ code = [int]$resp.StatusCode; body = $sr.ReadToEnd() }
    } catch { return @{ code = 0; body = $_.Exception.Message } }
}

$login = Probe 'POST' "$base/auth/login" @{ identifier = 'nguyenvananh@gmail.com'; password = 'Resident@2026!'; device_id = $dev } $null
Write-Output "auth/login -> $($login.code)"
$token = $null
if ($login.code -eq 200) {
    $j = ConvertFrom-Json $login.body
    foreach ($path in @('access_token', 'token', 'tokens')) {
        if ($j.data.PSObject.Properties.Name -contains $path) { break }
    }
    $token = $j.data.access_token
    if (-not $token) { $token = $j.data.token.access_token }
    if (-not $token) { $token = $j.data.tokens.access_token }
}
if (-not $token) {
    Write-Output "  KHONG lay duoc token. body: $($login.body.Substring(0,[Math]::Min(500,$login.body.Length)))"
    exit 1
}
Write-Output "  token OK`n"

$targets = @(
    @{ m = 'GET';  p = 'resident/community/posts';                 tag = 'CU ' },
    @{ m = 'GET';  p = 'resident/payments';                        tag = 'CU ' },
    @{ m = 'POST'; p = 'resident/link-preview';  b = @{ url = 'https://example.com' }; tag = 'MOI' },
    @{ m = 'GET';  p = 'resident/community/listings';              tag = 'MOI' },
    @{ m = 'POST'; p = 'resident/community/events/1/register'; b = @{}; tag = 'MOI' },
    @{ m = 'GET';  p = 'resident/community/posts?type=event_ref';  tag = 'MOI' }
)
foreach ($t in $targets) {
    $r = Probe $t.m "$base/$($t.p)" $t.b $token
    $v = switch ([int]$r.code) {
        404 { '<<< CHUA DEPLOY tren live' }
        200 { 'co' }
        201 { 'co' }
        422 { 'co route (loi validate)' }
        403 { 'co route (chan quyen)' }
        409 { 'co route (xung dot trang thai)' }
        default { '' }
    }
    Write-Output (("$($t.tag) $($t.m) /$($t.p)").PadRight(58) + "-> $($r.code)  $v")
}
