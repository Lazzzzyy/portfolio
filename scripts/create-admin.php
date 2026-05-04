<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['name:', 'email:', 'password:']);

$name = trim((string) ($options['name'] ?? ''));
$email = strtolower(trim((string) ($options['email'] ?? '')));
$password = (string) ($options['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/create-admin.php --name=\"Your Name\" --email=you@example.com --password=YourStrongPassword\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: invalid email format.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Error: password must be at least 8 characters.\n");
    exit(1);
}

try {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        fwrite(STDERR, "Error: user with this email already exists.\n");
        exit(1);
    }

    $insertStmt = db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_active)
         VALUES (:full_name, :email, :password_hash, :role, :is_active)'
    );

    $insertStmt->execute([
        'full_name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
        'is_active' => 1,
    ]);

    fwrite(STDOUT, "Admin account created successfully for {$email}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Error: " . $exception->getMessage() . "\n");
    exit(1);
}
