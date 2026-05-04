<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(int $statusCode, array $payload): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function require_method(string $method): void
{
    $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($requestMethod !== strtoupper($method)) {
        json_response(405, [
            'success' => false,
            'message' => 'Method not allowed.',
        ]);
    }
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    parse_str($raw, $parsed);

    return is_array($parsed) ? $parsed : [];
}

function normalize_email(mixed $value): string
{
    $email = trim((string) ($value ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }

    return strtolower($email);
}
