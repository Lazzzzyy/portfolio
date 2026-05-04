<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_env(__DIR__ . '/../.env');

function db_env(string $key, string $default): string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = db_env('PORTFOLIO_DB_HOST', '127.0.0.1');
    $port = db_env('PORTFOLIO_DB_PORT', '3306');
    $name = db_env('PORTFOLIO_DB_NAME', 'portfolio_db');
    $user = db_env('PORTFOLIO_DB_USER', 'root');
    $pass = db_env('PORTFOLIO_DB_PASS', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4;connect_timeout=5', $host, $port, $name);

    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    return $pdo;
}
