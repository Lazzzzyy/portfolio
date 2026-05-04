<?php

declare(strict_types=1);

function load_env(string $path): void
{
    static $loadedPaths = [];

    if (isset($loadedPaths[$path])) {
        return;
    }

    if (!is_file($path)) {
        $loadedPaths[$path] = true;
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        $loadedPaths[$path] = true;
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        $key = trim($parts[0] ?? '');
        $value = trim($parts[1] ?? '');

        if ($key === '') {
            continue;
        }

        $firstChar = $value[0] ?? '';
        $lastChar = $value === '' ? '' : $value[strlen($value) - 1];

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }

    $loadedPaths[$path] = true;
}
