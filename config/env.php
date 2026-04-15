<?php
declare(strict_types=1);

function load_env(string $path): void
{
    static $loaded = [];

    if (isset($loaded[$path])) {
        return;
    }

    if (!is_file($path)) {
        $loaded[$path] = true;
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        $loaded[$path] = true;
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $trimmed, 2);
        $name = trim($name);
        $value = trim($value);

        if ($value !== '') {
            $firstCharacter = $value[0];
            $lastCharacter = $value[strlen($value) - 1];
            if (($firstCharacter === '"' && $lastCharacter === '"') || ($firstCharacter === "'" && $lastCharacter === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $value = str_replace(['\n', '\r'], ["\n", "\r"], $value);

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    $loaded[$path] = true;
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}
