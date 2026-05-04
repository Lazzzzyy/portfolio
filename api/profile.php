<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
session_start();
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
} catch (\Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────────────────────────

if ($method === 'GET') {
    $rows = $pdo->query(
        "SELECT entry_key, entry_type, entry_value FROM portfolio_entries"
    )->fetchAll(PDO::FETCH_ASSOC);

    $profile = [];
    foreach ($rows as $r) {
        $profile[$r['entry_key']] = $r['entry_type'] === 'json'
            ? json_decode((string) $r['entry_value'], true)
            : $r['entry_value'];
    }

    $profile['_account_name']  = (string) ($user['full_name'] ?? '');
    $profile['_account_email'] = (string) ($user['email']     ?? '');

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $profile]);
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────────────

if ($method === 'POST') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true);

    if (!is_array($input)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request body']);
        exit;
    }

    $action = $input['action'] ?? 'public';

    // ── Update admin account (users table) ────────────────────────────────────
    if ($action === 'account') {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $email    = trim((string) ($input['email']     ?? ''));

        if ($fullName === '' || $email === '') {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }

        $userId = (int) ($user['id'] ?? 0);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            ob_end_clean();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email is already in use']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $userId]);

        $_SESSION['auth_user']['full_name'] = $fullName;
        $_SESSION['auth_user']['email']     = $email;

        ob_end_clean();
        echo json_encode([
            'success'   => true,
            'message'   => 'Account updated',
            'full_name' => $fullName,
            'email'     => $email,
        ]);
        exit;
    }

    // ── Update public profile (portfolio_entries) ─────────────────────────────
    if ($action === 'public') {
        $upsert = static function (string $key, string $type, mixed $value) use ($pdo): void {
            $val  = $type === 'json'
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $value;
            $stmt = $pdo->prepare(
                "INSERT INTO portfolio_entries (entry_key, entry_type, entry_value)
                 VALUES (:k, :t, :v)
                 ON DUPLICATE KEY UPDATE entry_type = VALUES(entry_type), entry_value = VALUES(entry_value)"
            );
            $stmt->execute([':k' => $key, ':t' => $type, ':v' => $val]);
        };

        foreach (['name', 'bio', 'cv_url', 'phone_discord', 'phone_tnt', 'phone_globe'] as $key) {
            if (array_key_exists($key, $input)) {
                $upsert($key, 'text', trim((string) ($input[$key] ?? '')));
            }
        }

        if (array_key_exists('social_links', $input) && is_array($input['social_links'])) {
            $allowed   = ['email', 'github', 'linkedin', 'facebook', 'instagram', 'twitter', 'telegram', 'discord', 'viber', 'whatsapp'];
            $sanitized = [];
            foreach ($allowed as $k) {
                $sanitized[$k] = trim((string) ($input['social_links'][$k] ?? ''));
            }
            $upsert('social_links', 'json', $sanitized);
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Profile saved successfully']);
        exit;
    }

    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

ob_end_clean();
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
