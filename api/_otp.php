<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/mailer.php';

function otp_env_int(string $key, int $default): int
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (int) $value;
}

function otp_ttl_seconds(): int
{
    return 60;
}

function otp_max_attempts(): int
{
    return max(3, otp_env_int('OTP_MAX_ATTEMPTS', 5));
}

function generate_otp_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function otp_hash(string $otpCode): string
{
    return hash('sha256', $otpCode);
}

function otp_mask_email(string $email): string
{
    $parts = explode('@', strtolower(trim($email)), 2);
    if (count($parts) !== 2) {
        return $email;
    }

    [$local, $domain] = $parts;
    if ($local === '') {
        return $email;
    }

    $visible = substr($local, 0, 2);
    $maskedLength = max(2, strlen($local) - 2);

    return $visible . str_repeat('*', $maskedLength) . '@' . $domain;
}

function otp_latest_record(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, user_id, otp_hash, attempts, expires_at, used_at, created_at
         FROM login_otps
         WHERE user_id = :user_id
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);

    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function otp_seconds_remaining(array $otpRecord): int
{
    $expiresAt = strtotime((string) ($otpRecord['expires_at'] ?? ''));
    if ($expiresAt === false) {
        return 0;
    }

    return max(0, $expiresAt - time());
}

function issue_login_otp(array $user): array
{
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        throw new RuntimeException('Unable to issue OTP for unknown user.');
    }

    $latest = otp_latest_record($userId);
    if (is_array($latest) && (string) ($latest['used_at'] ?? '') === '') {
        $remaining = otp_seconds_remaining($latest);
        if ($remaining > 0) {
            return [
                'expires_in' => $remaining,
                'throttled' => true,
            ];
        }
    }

    $otpCode = generate_otp_code();

    send_otp_email(
        (string) ($user['email'] ?? ''),
        (string) ($user['full_name'] ?? ''),
        $otpCode
    );

    $invalidateStmt = db()->prepare('UPDATE login_otps SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
    $invalidateStmt->execute(['user_id' => $userId]);

    $insertStmt = db()->prepare(
        'INSERT INTO login_otps (user_id, otp_hash, attempts, expires_at)
         VALUES (:user_id, :otp_hash, :attempts, :expires_at)'
    );

    $insertStmt->execute([
        'user_id' => $userId,
        'otp_hash' => otp_hash($otpCode),
        'attempts' => 0,
        'expires_at' => date('Y-m-d H:i:s', time() + otp_ttl_seconds()),
    ]);

    return [
        'expires_in' => otp_ttl_seconds(),
        'throttled' => false,
    ];
}
