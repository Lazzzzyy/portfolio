<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$steps = [];
$timings = [];
$t = microtime(true);

$steps[] = 'php_ok';
$timings['php_ok'] = round((microtime(true) - $t) * 1000);

require_once __DIR__ . '/../config/env.php';
load_env(__DIR__ . '/../.env');
$steps[] = 'env_loaded';
$timings['env_loaded'] = round((microtime(true) - $t) * 1000);

$dbHost = $_ENV['PORTFOLIO_DB_HOST'] ?? 'NOT SET';
$dbName = $_ENV['PORTFOLIO_DB_NAME'] ?? 'NOT SET';
$otpEnabled = $_ENV['OTP_ENABLED'] ?? 'NOT SET';
$brevoKey = isset($_ENV['BREVO_API_KEY']) && $_ENV['BREVO_API_KEY'] !== '' ? 'set' : 'not set';

$steps[] = 'env_read';
$timings['env_read'] = round((microtime(true) - $t) * 1000);

require_once __DIR__ . '/../config/session.php';
session_start();
$steps[] = 'session_ok';
$timings['session_ok'] = round((microtime(true) - $t) * 1000);

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = db();
    $steps[] = 'db_connected';
    $timings['db_connected'] = round((microtime(true) - $t) * 1000);

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $steps[] = 'db_query_ok';
    $timings['db_query_ok'] = round((microtime(true) - $t) * 1000);
} catch (Throwable $e) {
    $steps[] = 'db_failed: ' . $e->getMessage();
    $timings['db_failed'] = round((microtime(true) - $t) * 1000);
}

session_write_close();
$timings['session_closed'] = round((microtime(true) - $t) * 1000);

try {
    require_once __DIR__ . '/_otp.php';
    $steps[] = 'otp_included';
    $timings['otp_included'] = round((microtime(true) - $t) * 1000);
} catch (Throwable $e) {
    $steps[] = 'otp_include_failed: ' . $e->getMessage();
    $timings['otp_include_failed'] = round((microtime(true) - $t) * 1000);
}

try {
    session_start();
    session_regenerate_id(true);
    session_write_close();
    $steps[] = 'session_regen_ok';
    $timings['session_regen_ok'] = round((microtime(true) - $t) * 1000);
} catch (Throwable $e) {
    $steps[] = 'session_regen_failed: ' . $e->getMessage();
    $timings['session_regen_failed'] = round((microtime(true) - $t) * 1000);
}

$loginPhp   = file_get_contents(__DIR__ . '/login.php') ?: '';
$mailerPhp  = file_get_contents(__DIR__ . '/../config/mailer.php') ?: '';
$traceFile      = sys_get_temp_dir() . '/login_trace.txt';
$traceLog       = is_file($traceFile) ? file_get_contents($traceFile) : 'no trace yet';
$authTraceFile  = sys_get_temp_dir() . '/auth_trace.txt';
$authTraceLog   = is_file($authTraceFile) ? file_get_contents($authTraceFile) : 'no trace yet';

$writeTest = @file_put_contents(__DIR__ . '/write_test.txt', 'ok') !== false ? 'writable' : 'NOT writable';
$loginExists = is_file(__DIR__ . '/login.php') ? 'exists' : 'MISSING';

$selfPostResult = 'not tested';
if (function_exists('curl_init')) {
    $selfUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/health.php';
    $ch = curl_init($selfUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{"test":1}',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $selfBody = curl_exec($ch);
    $selfCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $selfErr  = curl_error($ch);
    curl_close($ch);
    $selfPostResult = $selfErr !== '' ? 'curl_error: ' . $selfErr : 'http_' . $selfCode;
}

$selfAuthResult = 'not tested';
if (function_exists('curl_init')) {
    $authUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/authenticate.php';
    $ch2 = curl_init($authUrl);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'email=probe%40test.invalid&password=probe_test_123',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $authBody = curl_exec($ch2);
    $authCode = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $authErr  = curl_error($ch2);
    curl_close($ch2);
    $authBodySnippet = is_string($authBody) ? substr($authBody, 0, 300) : '';
    $selfAuthResult  = $authErr !== '' ? 'curl_error: ' . $authErr : 'http_' . $authCode . ' body=' . $authBodySnippet;
}

echo json_encode([
    'steps'                 => $steps,
    'timings_ms'            => $timings,
    'db_host'               => $dbHost,
    'otp_enabled'           => $otpEnabled,
    'login_has_otp_flag'    => str_contains($loginPhp, 'OTP_ENABLED'),
    'login_has_trace'       => str_contains($loginPhp, '_trace('),
    'mailer_has_brevo'      => str_contains($mailerPhp, 'send_otp_via_brevo'),
    'login_trace'           => $traceLog,
    'auth_trace'            => $authTraceLog,
    'auth_exists'           => is_file(__DIR__ . '/authenticate.php') ? 'exists' : 'MISSING',
    'api_dir_writable'      => $writeTest,
    'self_post_result'      => $selfPostResult,
    'self_post_authenticate' => $selfAuthResult,
]);
