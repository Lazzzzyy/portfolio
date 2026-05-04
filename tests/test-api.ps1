# ===========================================================================
#  Portfolio API Test Suite
#  Tests: JSON response, HTTP status codes, file upload, response time
#  Usage: powershell -ExecutionPolicy Bypass -File tests\test-api.ps1
# ===========================================================================

$BASE     = "http://localhost/portfolio/api"
$PASS     = 0
$FAIL     = 0
$LOG_PHP  = "C:\laragon\tmp\php_errors.log"
$LOG_HTTP = "C:\laragon\bin\apache\httpd-2.4.63\logs\access.log"

function Write-Bar { param([string]$c = "-", [int]$n = 66)
    Write-Host ($c * $n) -ForegroundColor DarkGray
}

function Write-Title { param([string]$t)
    Write-Host ""
    Write-Bar "=" 66
    Write-Host "  $t" -ForegroundColor Cyan
    Write-Bar "=" 66
}

function Run-Test {
    param(
        [string]   $Label,
        [string]   $Url,
        [string]   $Method    = "GET",
        [string]   $Body      = "",
        [int]      $Expect    = 200,
        [string]   $CheckKey  = "",
        [string]   $CheckVal  = "",
        [string[]] $Extra     = @()
    )

    # Use a safe sentinel that won't clash with PS operators
    $sentinel = "APIMETA"
    $ca = @(
        "--silent", "--location",
        "--request", $Method,
        "--url", $Url,
        "--write-out", "`n${sentinel}_%{http_code}_%{time_total}"
    )
    if ($Extra.Count -gt 0) { $ca += $Extra }

    # POST bodies: pipe via stdin to avoid PowerShell 5 splatting stripping @path args
    if ($Body -ne "") {
        $raw = ($Body | & curl.exe @ca --header "Content-Type: application/json" --data-binary "@-" 2>&1) -join "`n"
    } else {
        $raw = (& curl.exe @ca 2>&1) -join "`n"
    }
    $status = 0
    $ms     = 0
    $btext  = $raw

    if ($raw -match "${sentinel}_(\d+)_([\d.]+)") {
        $status = [int]$Matches[1]
        $ms     = [math]::Round([double]$Matches[2] * 1000)
        $btext  = $raw.Substring(0, $raw.LastIndexOf($Matches[0])).TrimEnd()
    }

    $statusOk = ($status -eq $Expect)
    $keyOk    = $true
    $actual   = $null

    if ($CheckKey -ne "") {
        try {
            $j      = $btext | ConvertFrom-Json -ErrorAction Stop
            $actual = $j.$CheckKey
            $keyOk  = ("$actual" -eq "$CheckVal")
        } catch { $keyOk = $false; $actual = "(parse-error)" }
    }

    $ok = $statusOk -and $keyOk
    if ($ok) { $script:PASS++; $icon = "PASS"; $fc = "Green" }
    else     { $script:FAIL++; $icon = "FAIL"; $fc = "Red"   }

    $disp = $btext.Trim()
    try {
        $j2 = $disp | ConvertFrom-Json -ErrorAction Stop
        $disp = ($j2 | ConvertTo-Json -Depth 2 -Compress)
    } catch {}
    if ($disp.Length -gt 200) { $disp = $disp.Substring(0,197) + "..." }

    Write-Host ""
    Write-Host "  [$icon] $Label" -ForegroundColor $fc
    Write-Host ("         HTTP {0} (want {1}) | {2}ms" -f $status, $Expect, $ms) -ForegroundColor $(if ($statusOk){"Gray"}else{"Red"})
    if ($CheckKey -ne "") {
        Write-Host ("         '{0}' = {1}  (want: {2})" -f $CheckKey, $actual, $CheckVal) -ForegroundColor $(if ($keyOk){"Gray"}else{"Red"})
    }
    Write-Host "         $disp" -ForegroundColor DarkGray
}

# ---------------------------------------------------------------------------
# Setup: temp files
# ---------------------------------------------------------------------------
$TMP       = [System.IO.Path]::GetTempPath()
$FAKE_TXT  = Join-Path $TMP "pf_test.txt"
$FAKE_PDF  = Join-Path $TMP "pf_test.pdf"
[System.IO.File]::WriteAllText($FAKE_TXT, "not a pdf")
[System.IO.File]::WriteAllText($FAKE_PDF, "%PDF-1.4 fake")

# Snapshot log offsets before tests begin
$phpOff  = if (Test-Path $LOG_PHP)  { (Get-Item $LOG_PHP).Length  } else { 0 }
$httpOff = if (Test-Path $LOG_HTTP) { (Get-Item $LOG_HTTP).Length } else { 0 }

# ---------------------------------------------------------------------------
# Header
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "  Portfolio API Test Suite" -ForegroundColor White
Write-Host "  Base : $BASE" -ForegroundColor DarkGray
Write-Host "  Time : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor DarkGray
Write-Bar "-"

# ===========================================================================
#  1. PUBLIC ENDPOINTS
# ===========================================================================
Write-Title "1. PUBLIC ENDPOINTS  (no auth required)"

Run-Test "GET public-profile.php -- 200 + success:true" `
    -Url "$BASE/public-profile.php" `
    -CheckKey "success" -CheckVal "True"

Run-Test "GET public-profile.php -- social+phones+name in response body" `
    -Url "$BASE/public-profile.php"

Run-Test "GET public-profile.php -- success key is boolean true" `
    -Url "$BASE/public-profile.php" `
    -CheckKey "success" -CheckVal "True"

Run-Test "GET public-data.php -- 200 + success:true" `
    -Url "$BASE/public-data.php" `
    -CheckKey "success" -CheckVal "True"

Run-Test "GET public-data.php?type=profile -- 200" `
    -Url "$BASE/public-data.php?type=profile"

Run-Test "GET public-data.php?type=projects -- 200" `
    -Url "$BASE/public-data.php?type=projects"

Run-Test "GET public-data.php?type=awards -- 200" `
    -Url "$BASE/public-data.php?type=awards"

# ===========================================================================
#  2. CONTACT FORM
# ===========================================================================
Write-Title "2. CONTACT FORM  (public POST)"

Run-Test "POST valid payload -- 200 success" `
    -Url "$BASE/contact-submit.php" -Method "POST" `
    -Body '{"name":"Test User","email":"test@example.com","category":"Other","subject":"API Test Suite","message":"Automated test message please disregard."}' `
    -Expect 200 -CheckKey "success" -CheckVal "True"

Run-Test "POST empty fields -- 422 validation error" `
    -Url "$BASE/contact-submit.php" -Method "POST" `
    -Body '{"name":"","email":"","category":"","subject":"","message":""}' `
    -Expect 422

Run-Test "POST invalid email -- 422" `
    -Url "$BASE/contact-submit.php" -Method "POST" `
    -Body '{"name":"Test","email":"not-an-email","category":"Other","subject":"Test","message":"Testing invalid email."}' `
    -Expect 422

Run-Test "POST bad category -- 422" `
    -Url "$BASE/contact-submit.php" -Method "POST" `
    -Body '{"name":"Test","email":"t@t.com","category":"Hacking","subject":"X","message":"Testing bad category value."}' `
    -Expect 422

Run-Test "GET contact-submit (wrong method) -- 405" `
    -Url "$BASE/contact-submit.php" -Method "GET" -Expect 405

# ===========================================================================
#  3. LOGIN
# ===========================================================================
Write-Title "3. LOGIN"

Run-Test "POST empty credentials -- 422" `
    -Url "$BASE/login.php" -Method "POST" `
    -Body '{"email":"","password":""}' -Expect 422

Run-Test "POST wrong credentials -- 401" `
    -Url "$BASE/login.php" -Method "POST" `
    -Body '{"email":"definitelynotreal@example.com","password":"WrongPass999"}' -Expect 401

Run-Test "GET login (wrong method) -- 405" `
    -Url "$BASE/login.php" -Method "GET" -Expect 405

# ===========================================================================
#  4. AUTH-GUARDED ENDPOINTS  (no session cookie -> 401)
# ===========================================================================
Write-Title "4. AUTH-GUARDED ENDPOINTS  (no cookie, expect 401)"

$guards = @(
    @{ n = "dashboard.php";  u = "$BASE/dashboard.php";  m = "GET"  },
    @{ n = "profile.php";    u = "$BASE/profile.php";    m = "GET"  },
    @{ n = "messages.php";   u = "$BASE/messages.php";   m = "GET"  },
    @{ n = "projects.php";   u = "$BASE/projects.php";   m = "GET"  },
    @{ n = "awards.php";     u = "$BASE/awards.php";     m = "GET"  },
    @{ n = "blog.php";       u = "$BASE/blog.php";       m = "GET"  },
    @{ n = "upload-cv.php";  u = "$BASE/upload-cv.php";  m = "POST" }
)

foreach ($g in $guards) {
    Run-Test "$($g.m) $($g.n) -- no session -> 401" `
        -Url $g.u -Method $g.m -Expect 401 `
        -CheckKey "success" -CheckVal "False"
}

# ===========================================================================
#  5. FILE UPLOAD  upload-cv.php
# ===========================================================================
Write-Title "5. FILE UPLOAD  upload-cv.php"

Run-Test "POST .txt without auth -- 401 (auth checked before MIME)" `
    -Url "$BASE/upload-cv.php" -Method "POST" `
    -Extra @("--form", "cv=@${FAKE_TXT};type=text/plain") `
    -Expect 401

Run-Test "POST .pdf without auth -- 401" `
    -Url "$BASE/upload-cv.php" -Method "POST" `
    -Extra @("--form", "cv=@${FAKE_PDF};type=application/pdf") `
    -Expect 401

Run-Test "GET upload-cv (wrong method, no auth) -- 401" `
    -Url "$BASE/upload-cv.php" -Method "GET" -Expect 401

# ===========================================================================
#  6. RESPONSE TIME BENCHMARK  (5 samples)
# ===========================================================================
Write-Title "6. RESPONSE TIME BENCHMARK  (5 samples each)"

$bench = @(
    @{ n = "public-profile.php";       u = "$BASE/public-profile.php";         m = "GET";  b = "" },
    @{ n = "public-data.php";          u = "$BASE/public-data.php";             m = "GET";  b = "" },
    @{ n = "public-data.php?projects"; u = "$BASE/public-data.php?type=projects"; m = "GET"; b = "" },
    @{ n = "contact-submit (POST)";    u = "$BASE/contact-submit.php";          m = "POST";
       b = '{"name":"Bench Runner","email":"bench@example.com","category":"Other","subject":"Benchmark","message":"Automated benchmark test run."}' }
)

foreach ($ep in $bench) {
    $times = @()
    for ($i = 0; $i -lt 5; $i++) {
        $ca2 = @("--silent", "--output", "NUL",
                 "--write-out", "%{time_total}",
                 "--request", $ep.m, "--url", $ep.u)
        if ($ep.b) {
            $tv = ($ep.b | & curl.exe @ca2 --header "Content-Type: application/json" --data-binary "@-" 2>&1) -replace "[^0-9.]",""
            try { if ($tv -ne "") { $times += [double]$tv } } catch {}
            continue
        }
        $tv = (& curl.exe @ca2 2>&1) -replace "[^0-9.]",""
        try { if ($tv -ne "") { $times += [double]$tv } } catch {}
        continue
    }
    if ($times.Count -gt 0) {
        $avg = [math]::Round(($times | Measure-Object -Average).Average * 1000, 1)
        $min = [math]::Round(($times | Measure-Object -Minimum).Minimum * 1000, 1)
        $max = [math]::Round(($times | Measure-Object -Maximum).Maximum * 1000, 1)
    } else { $avg = $min = $max = 0 }
    $pc = if ($avg -lt 100) {"Green"} elseif ($avg -lt 300) {"Yellow"} else {"Red"}
    Write-Host ""
    Write-Host "  $($ep.n)" -ForegroundColor White
    Write-Host "    avg=${avg}ms   min=${min}ms   max=${max}ms" -ForegroundColor $pc
}

# ===========================================================================
#  7. SERVER LOGS  (new lines written during test run)
# ===========================================================================
Write-Title "7. SERVER LOG TAILS  (entries written during this run)"

function Read-LogTail { param([string]$path, [long]$offset, [int]$maxLines = 25, [string]$label)
    Write-Host ""
    Write-Host "  -- $label" -ForegroundColor Yellow
    if (-not (Test-Path $path)) {
        Write-Host "     (not found: $path)" -ForegroundColor DarkYellow; return
    }
    try {
        $fs = [System.IO.File]::Open($path,
            [System.IO.FileMode]::Open,
            [System.IO.FileAccess]::Read,
            [System.IO.FileShare]::ReadWrite)
        $fs.Seek($offset, [System.IO.SeekOrigin]::Begin) | Out-Null
        $sr  = New-Object System.IO.StreamReader($fs)
        $txt = $sr.ReadToEnd().Trim()
        $sr.Close(); $fs.Close()
        if ($txt -eq "") {
            Write-Host "     (no new entries)" -ForegroundColor DarkGreen
        } else {
            $txt -split "`n" | Select-Object -Last $maxLines | ForEach-Object {
                Write-Host "     $_" -ForegroundColor DarkGray
            }
        }
    } catch {
        Write-Host "     (read error: $_)" -ForegroundColor DarkYellow
    }
}

Read-LogTail -path $LOG_HTTP -offset $httpOff -label "Apache access.log"
Read-LogTail -path $LOG_PHP  -offset $phpOff  -maxLines 50 -label "PHP error.log"

# ===========================================================================
#  SUMMARY
# ===========================================================================
Write-Host ""
Write-Bar "=" 66
$total = $PASS + $FAIL
$sc    = if ($FAIL -eq 0) {"Green"} else {"Red"}
Write-Host ("  RESULTS: {0} / {1} passed" -f $PASS, $total) -ForegroundColor $sc
if ($FAIL -gt 0) {
    Write-Host "  $FAIL test(s) FAILED -- review output above" -ForegroundColor Red
}
Write-Host "  Done : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor DarkGray
Write-Bar "=" 66

# Cleanup
Remove-Item -Path $FAKE_TXT, $FAKE_PDF -Force -ErrorAction SilentlyContinue

exit $FAIL
