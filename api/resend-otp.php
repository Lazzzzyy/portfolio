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

$userId = (int) $pendingUser['id'];

try {
    $latest = otp_latest_record($userId);

    if (is_array($latest) && (string) ($latest['used_at'] ?? '') === '') {
        $secondsRemaining = otp_seconds_remaining($latest);

        if ($secondsRemaining > 0) {
            json_response(429, [
                'success' => false,
                'message' => "OTP still active. You can resend after {$secondsRemaining}s.",
                'seconds_remaining' => $secondsRemaining,
            ]);
        }
    }

    $otpMeta = issue_login_otp($pendingUser);

    json_response(200, [
        'success' => true,
        'message' => 'A new OTP has been sent to your Gmail.',
        'expires_in' => (int) ($otpMeta['expires_in'] ?? otp_ttl_seconds()),
        'email_hint' => otp_mask_email((string) ($pendingUser['email'] ?? '')),
    ]);
} catch (Throwable $exception) {
    json_response(500, [
        'success' => false,
        'message' => 'Unable to resend OTP right now. Check your mailer credentials.',
    ]);
}
