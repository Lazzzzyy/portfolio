<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_otp.php';

require_method('POST');

$pendingUser = $_SESSION['pending_otp_user'] ?? null;
if (!is_array($pendingUser) || !isset($pendingUser['id'])) {
    json_response(401, [
        'success' => false,
        'message' => 'Your login session expired. Please sign in again.',
    ]);
}

$data = request_data();
$otpCode = preg_replace('/\D/', '', (string) ($data['otp'] ?? ''));

if ($otpCode === null || strlen($otpCode) !== 6) {
    json_response(422, [
        'success' => false,
        'message' => 'Enter a valid 6-digit OTP.',
    ]);
}

$userId = (int) $pendingUser['id'];

try {
    $otpRecord = otp_latest_record($userId);

    if (!is_array($otpRecord) || (string) ($otpRecord['used_at'] ?? '') !== '') {
        json_response(410, [
            'success' => false,
            'message' => 'OTP expired. Please request a new code.',
            'seconds_remaining' => 0,
        ]);
    }

    $secondsRemaining = otp_seconds_remaining($otpRecord);
    if ($secondsRemaining <= 0) {
        json_response(410, [
            'success' => false,
            'message' => 'OTP expired. Please resend a new code.',
            'seconds_remaining' => 0,
        ]);
    }

    $attempts = (int) ($otpRecord['attempts'] ?? 0);
    if ($attempts >= otp_max_attempts()) {
        json_response(429, [
            'success' => false,
            'message' => 'Too many invalid OTP attempts. Please resend a new code.',
            'seconds_remaining' => $secondsRemaining,
        ]);
    }

    $incomingHash = otp_hash($otpCode);
    $storedHash = (string) ($otpRecord['otp_hash'] ?? '');

    if (!hash_equals($storedHash, $incomingHash)) {
        $updateAttemptStmt = db()->prepare('UPDATE login_otps SET attempts = attempts + 1 WHERE id = :id');
        $updateAttemptStmt->execute(['id' => (int) $otpRecord['id']]);

        json_response(401, [
            'success' => false,
            'message' => 'Invalid OTP. Please try again.',
            'seconds_remaining' => $secondsRemaining,
        ]);
    }

    $markUsedStmt = db()->prepare('UPDATE login_otps SET used_at = NOW() WHERE id = :id');
    $markUsedStmt->execute(['id' => (int) $otpRecord['id']]);

    $_SESSION['auth_user'] = [
        'id' => (int) $pendingUser['id'],
        'full_name' => (string) ($pendingUser['full_name'] ?? ''),
        'email' => (string) ($pendingUser['email'] ?? ''),
    ];

    unset($_SESSION['pending_otp_user']);

    json_response(200, [
        'success' => true,
        'message' => 'OTP verified. Redirecting...',
        'redirect' => './dashboard',
    ]);
} catch (Throwable $exception) {
    json_response(500, [
        'success' => false,
        'message' => 'Unable to verify OTP right now. Please try again.',
    ]);
}
