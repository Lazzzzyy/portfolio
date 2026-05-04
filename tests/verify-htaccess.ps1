$base = 'http://localhost/portfolio'
$pass = 0; $fail = 0

function T {
    param([string]$label, [string]$url, [string]$want)
    $got = & curl.exe --silent --output NUL --write-out "%{http_code}" --request GET --url $url
    if ($got -eq $want) {
        Write-Host "  [PASS] $label  HTTP $got" -ForegroundColor Green
        $script:pass++
    } else {
        Write-Host "  [FAIL] $label  HTTP $got (want $want)" -ForegroundColor Red
        $script:fail++
    }
}

Write-Host ""
Write-Host "=== BLOCKING (expect 403) ===" -ForegroundColor Cyan
T ".env file"                  "$base/.env"                   "403"
T "composer.json"              "$base/composer.json"          "403"
T "README.md"                  "$base/README.md"              "403"
T "setup.php"                  "$base/setup.php"              "403"
T "config/ directory"          "$base/config/"                "403"
T "vendor/ directory"          "$base/vendor/"                "403"
T "database/ directory"        "$base/database/"              "403"
T "tests/ directory"           "$base/tests/"                 "403"
T "scripts/ directory"         "$base/scripts/"               "403"
T "partials/ directory"        "$base/partials/"              "403"
T "api/_bootstrap.php"         "$base/api/_bootstrap.php"     "403"
T "api/_otp.php"               "$base/api/_otp.php"           "403"

Write-Host ""
Write-Host "=== CLEAN URLS ===" -ForegroundColor Cyan
T "/visitor   -> 200"          "$base/visitor"                "200"
T "/dashboard -> 302 (auth)"   "$base/dashboard"              "302"
T "/logout    -> 302"          "$base/logout"                 "302"
T "/post      -> 302 (no slug)" "$base/post"                   "302"

Write-Host ""
Write-Host "=== NORMAL ACCESS (expect 200) ===" -ForegroundColor Cyan
T "index.html (admin login)"   "$base/"                       "200"
T "visitor.php direct"         "$base/visitor.php"            "200"
T "assets/css/visitor.css"     "$base/assets/css/visitor.css" "200"
T "api/public-profile.php"     "$base/api/public-profile.php" "200"
T "api/public-data.php"        "$base/api/public-data.php"    "200"

Write-Host ""
$total = $pass + $fail
$col = if ($fail -eq 0) { "Green" } else { "Red" }
Write-Host "RESULTS: $pass / $total passed" -ForegroundColor $col
