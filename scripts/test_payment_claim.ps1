# Bo hoi quy cho POST /resident/payments/claim tren https://x2bms.test
# Chung chi tu ky cua .test -> phai bo qua kiem chung chi.
add-type @"
using System.Net; using System.Security.Cryptography.X509Certificates;
public class TrustAll : ICertificatePolicy {
  public bool CheckValidationResult(ServicePoint sp, X509Certificate c, WebRequest r, int p) { return true; }
}
"@ -ErrorAction SilentlyContinue
[System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAll
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

$base = 'https://x2bms.test/api/v1'
$dev = 'claim-test-0001'
$script:pass = 0; $script:fail = 0

function Req($method, $path, $body, $token, $ctx) {
    $h = @{ 'Accept' = 'application/json'; 'X-Device-Id' = $dev }
    if ($token) { $h['Authorization'] = "Bearer $token" }
    if ($ctx) { $h['X-Context-Id'] = $ctx }
    try {
        if ($null -ne $body) {
            $r = Invoke-WebRequest -Uri "$base/$path" -Method $method -Headers $h `
                -Body ($body | ConvertTo-Json -Depth 5) -ContentType 'application/json' -UseBasicParsing -TimeoutSec 30
        } else {
            $r = Invoke-WebRequest -Uri "$base/$path" -Method $method -Headers $h -UseBasicParsing -TimeoutSec 30
        }
        return @{ code = [int]$r.StatusCode; json = (ConvertFrom-Json $r.Content); raw = $r.Content }
    } catch [System.Net.WebException] {
        $resp = $_.Exception.Response
        if ($null -eq $resp) { return @{ code = 0; raw = $_.Exception.Message } }
        $txt = (New-Object System.IO.StreamReader($resp.GetResponseStream())).ReadToEnd()
        $j = $null; try { $j = ConvertFrom-Json $txt } catch {}
        return @{ code = [int]$resp.StatusCode; json = $j; raw = $txt }
    }
}

function Check($name, $cond, $detail) {
    if ($cond) { $script:pass++; Write-Output "  OK   $name" }
    else { $script:fail++; Write-Output "  FAIL $name  -> $detail" }
}

# --- dang nhap
$login = Req 'POST' 'auth/login' @{ identifier = 'nguyenvananh@gmail.com'; password = 'Resident@2026!'; device_id = $dev } $null $null
if ($login.code -ne 200) { Write-Output "KHONG dang nhap duoc: $($login.code) $($login.raw)"; exit 1 }
$token = $login.json.data.tokens.access_token   # AuthController tra ve data.tokens
if (-not $token) { $token = $login.json.data.access_token }
if (-not $token) { Write-Output "khong lay duoc token"; exit 1 }
Write-Output "dang nhap OK`n"

# --- lay ngu canh + hoa don that
$boot = Req 'GET' 'me/bootstrap' $null $token $null
$ctx = $boot.json.data.available_contexts[0].context_id
Write-Output "ngu canh: $ctx"
$st = Req 'GET' 'resident/statements' $null $token $ctx
$stId = $st.json.data[0].id
Write-Output "hoa don thu nghiem: $stId`n"

Write-Output "== 1. Thieu anh chung tu -> 422 =="
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500000; paid_at = (Get-Date).AddHours(-2).ToString('o') } $token $ctx
Check 'tu choi khi khong co anh' ($r.code -eq 422) "code=$($r.code)"
Check 'bao dung truong attachment_ids' ($r.raw -match 'attachment_ids') "raw=$($r.raw)"

Write-Output "`n== 2. Upload anh that roi khai bao =="
# tao 1 anh PNG 1x1 that de upload (validate 'image' se soi noi dung)
$png = [Convert]::FromBase64String('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==')
$tmp = Join-Path $env:TEMP 'claim-proof.png'
[IO.File]::WriteAllBytes($tmp, $png)

# multipart bang .NET vi Invoke-WebRequest -Form khong co o PS 5.1
$bound = [Guid]::NewGuid().ToString()
$nl = "`r`n"
$head = "--$bound$nl" + "Content-Disposition: form-data; name=`"file`"; filename=`"proof.png`"$nl" + "Content-Type: image/png$nl$nl"
$tail = "$nl--$bound--$nl"
$bytes = [Text.Encoding]::ASCII.GetBytes($head) + $png + [Text.Encoding]::ASCII.GetBytes($tail)
$req = [Net.HttpWebRequest]::Create("$base/resident/uploads")
$req.Method = 'POST'; $req.ContentType = "multipart/form-data; boundary=$bound"
$req.Headers.Add('Authorization', "Bearer $token"); $req.Headers.Add('X-Device-Id', $dev); $req.Headers.Add('X-Context-Id', $ctx)
$req.Accept = 'application/json'
$s = $req.GetRequestStream(); $s.Write($bytes, 0, $bytes.Length); $s.Close()
$attId = $null
try {
    $resp = $req.GetResponse()
    $txt = (New-Object IO.StreamReader($resp.GetResponseStream())).ReadToEnd()
    $attId = (ConvertFrom-Json $txt).data.id
    Write-Output "  upload anh -> attachment id = $attId"
} catch {
    $er = $_.Exception.Response
    $txt = if ($er) { (New-Object IO.StreamReader($er.GetResponseStream())).ReadToEnd() } else { $_.Exception.Message }
    Write-Output "  upload LOI: $txt"
}
Check 'upload anh chung tu duoc' ($null -ne $attId) 'khong co attachment id'

if ($attId) {
    $paidAt = (Get-Date).AddHours(-3).ToString('o')
    $body = @{ statement_id = [int]$stId; amount = 1234000; paid_at = $paidAt; reference_no = 'FT26073012345'; note = 'Da chuyen Vietcombank'; attachment_ids = @([int]$attId) }
    $r = Req 'POST' 'resident/payments/claim' $body $token $ctx
    Check 'tao khai bao -> 201' ($r.code -eq 201) "code=$($r.code) raw=$($r.raw)"
    Check 'trang thai la pending' ($r.json.data.status -eq 'pending') "status=$($r.json.data.status)"
    Check 'source la resident_app' ($r.json.data.source -eq 'resident_app') "source=$($r.json.data.source)"
    Check 'co tra ve anh dinh kem' ($r.json.data.attachments.Count -ge 1) "attachments=$($r.json.data.attachments.Count)"
    $payId = $r.json.data.id

    Write-Output "`n== 3. KHONG duoc giam cong no khi chua duyet =="
    $sum = Req 'GET' 'resident/billing/summary' $null $token $ctx
    $detail = Req 'GET' "resident/payments/$payId" $null $token $ctx
    Check 'khoan cho duyet khong co phan bo nao' (($detail.json.data.allocations -eq $null) -or ($detail.json.data.allocations.Count -eq 0)) "allocations=$($detail.json.data.allocations.Count)"
    Check 'chua co bien lai' ($null -eq $detail.json.data.receipt) "receipt co san"

    Write-Output "`n== 4. Chan khai TRUNG (bam gui hai lan) =="
    $r2 = Req 'POST' 'resident/payments/claim' $body $token $ctx
    Check 'lan hai bi chan 409' ($r2.code -eq 409) "code=$($r2.code) raw=$($r2.raw)"
    Check 'ma loi duplicate_claim' ($r2.raw -match 'duplicate_claim') "raw=$($r2.raw)"

    Write-Output "`n== 5. Khoan moi hien trong lich su =="
    $list = Req 'GET' 'resident/payments' $null $token $ctx
    # @() bat buoc: PS 5.1 tra ve mot doi tuong don (khong phai mang) khi chi
    # co 1 ket qua, va .Count tren doi tuong don khong dang tin.
    $ids = @($list.json.data | ForEach-Object { [string]$_.id })
    Check 'lich su chua khoan vua khai' ($ids -contains [string]$payId) "payId=$payId, ids trong danh sach = $($ids -join ',')"
}

Write-Output "`n== 6. Hoa don cua CAN KHAC -> 404 =="
$r = Req 'POST' 'resident/payments/claim' @{ statement_id = 999999; amount = 200000; paid_at = (Get-Date).AddHours(-1).ToString('o'); attachment_ids = @(1) } $token $ctx
Check 'hoa don khong thuoc can -> 404' ($r.code -eq 404) "code=$($r.code) raw=$($r.raw)"

Write-Output "`n== 7. Chan gia tri vo ly =="
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500; paid_at = (Get-Date).AddHours(-1).ToString('o'); attachment_ids = @(1) } $token $ctx
Check 'so tien duoi 1000 bi chan' ($r.code -eq 422) "code=$($r.code)"
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500000; paid_at = (Get-Date).AddDays(2).ToString('o'); attachment_ids = @(1) } $token $ctx
Check 'thoi diem TUONG LAI bi chan' ($r.code -eq 422) "code=$($r.code)"
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500000; paid_at = (Get-Date).AddDays(-400).ToString('o'); attachment_ids = @(1) } $token $ctx
Check 'thoi diem qua xa qua khu bi chan' ($r.code -eq 422) "code=$($r.code)"

Write-Output "`n== 7b. Gio KHONG kem mui gio phai bi tu choi RO RANG =="
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500000; paid_at = '2026-07-30T10:00:00'; attachment_ids = @(1) } $token $ctx
Check 'thieu offset -> 422' ($r.code -eq 422) "code=$($r.code)"
Check 'noi ro thieu mui gio (paid_at_timezone)' ($r.raw -match 'paid_at_timezone') "raw=$($r.raw)"
Check 'thong bao co vi du de sua' ($r.raw -match '\+07:00') "raw=$($r.raw)"
Write-Output "`n== 8. Chua dang nhap thi khong khai duoc =="
$r = Req 'POST' 'resident/payments/claim' @{ amount = 500000; paid_at = (Get-Date).AddHours(-1).ToString('o'); attachment_ids = @(1) } $null $ctx
Check 'khong token -> 401' ($r.code -eq 401) "code=$($r.code)"

Write-Output "`n---------------- $script:pass PASS / $script:fail FAIL ----------------"





