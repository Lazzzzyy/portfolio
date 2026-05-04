<?php

declare(strict_types=1);

$_auth_trace = sys_get_temp_dir() . '/auth_trace.txt';
$_t0 = microtime(true);
$_trace = function (string $step) use ($_auth_trace, $_t0): void {
    $ms = round((microtime(true) - $_t0) * 1000);
    file_put_contents($_auth_trace, date('H:i:s') . " [{$ms}ms] {$step}\n", FILE_APPEND | LOCK_EX);
};
$_trace('start method=' . ($_SERVER['REQUEST_METHOD'] ?? 'NONE'));

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_otp.php';

require_method('POST');

$data = request_data();
$email = normalize_email($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(422, [
        'success' => false,
        'message' => 'Email and password are required.',
    ]);
}

try {
    $userCount = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($userCount === 0) {
        json_response(409, [
            'success' => false,
            'message' => 'No account found yet. Create your first admin at /setup.php.',
            'setup_url' => './setup.php',
        ]);
    }

    $stmt = db()->prepare(
        'SELECT id, full_name, email, password_hash, is_active FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
        json_response(401, [
            'success' => false,
            'message' => 'Invalid email or password.',
        ]);
    }

    if ((int) $user['is_active'] !== 1) {
        json_response(403, [
            'success' => false,
            'message' => 'This account is currently inactive.',
        ]);
    }

    session_regenerate_id(true);

    $otpEnabled = strtolower((string) ($_ENV['OTP_ENABLED'] ?? getenv('OTP_ENABLED') ?? 'true')) !== 'false';

    if (!$otpEnabled) {
        unset($_SESSION['pending_otp_user']);
        $_SESSION['auth_user'] = [
            'id'        => (int) $user['id'],
            'full_name' => (string) $user['full_name'],
            'email'     => (string) $user['email'],
        ];

        json_response(200, [
            'success'  => true,
            'message'  => 'Login successful. Redirecting...',
            'redirect' => './dashboard.php',
        ]);
    }

    $pendingUser = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
    ];

    unset($_SESSION['auth_user']);
    $_SESSION['pending_otp_user'] = $pendingUser;

    $otpMeta = issue_login_otp($pendingUser);

    if (($otpMeta['throttled'] ?? false) === true) {
        json_response(429, [
            'success' => false,
            'message' => 'An OTP is already active. Please enter it or wait for resend.',
            'requires_otp' => true,
            'email_hint' => otp_mask_email((string) $pendingUser['email']),
            'expires_in' => (int) ($otpMeta['expires_in'] ?? otp_ttl_seconds()),
        ]);
    }

    json_response(200, [
        'success' => true,
        'message' => 'OTP sent to your Gmail. Enter the code to continue.',
        'requires_otp' => true,
        'email_hint' => otp_mask_email((string) $pendingUser['email']),
        'expires_in' => (int) ($otpMeta['expires_in'] ?? otp_ttl_seconds()),
    ]);
} catch (Throwable $exception) {
    json_response(500, [
        'success' => false,
        'message' => 'Unable to process login. Check your database, .env mailer config, and SMTP credentials.',
    ]);
}
