<?php
declare(strict_types=1);

function app_name(): string
{
    return env('APP_NAME', 'CORE Faculty Evaluation') ?? 'CORE Faculty Evaluation';
}

function app_env(): string
{
    return env('APP_ENV', 'production') ?? 'production';
}

function is_local_env(): bool
{
    return app_env() === 'local';
}

function is_local_host_name(string $host): bool
{
    $host = strtolower(trim($host));

    return $host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1';
}

function url_host_name(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);

    return is_string($host) ? strtolower($host) : '';
}

function detected_app_base_url(): ?string
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '') {
        return null;
    }

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    ) {
        $scheme = 'https';
    }

    $appRoot = realpath(dirname(__DIR__));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $basePath = '';

    if ($appRoot !== false && $documentRoot !== false) {
        $normalizedAppRoot = str_replace('\\', '/', $appRoot);
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if ($normalizedDocumentRoot !== '' && strpos($normalizedAppRoot, $normalizedDocumentRoot) === 0) {
            $relativePath = trim(substr($normalizedAppRoot, strlen($normalizedDocumentRoot)), '/');
            $basePath = $relativePath === '' ? '' : '/' . $relativePath;
        }
    }

    return $scheme . '://' . $host . $basePath;
}

function resolved_app_url(): string
{
    $configuredUrl = trim((string) (env('APP_URL', '') ?? ''));
    $detectedUrl = detected_app_base_url();

    if ($detectedUrl !== null) {
        $configuredHost = url_host_name($configuredUrl);
        $detectedHost = url_host_name($detectedUrl);

        if (
            $configuredUrl === ''
            || is_local_host_name($configuredHost)
            || ($configuredHost !== '' && $detectedHost !== '' && $configuredHost !== $detectedHost)
        ) {
            return rtrim($detectedUrl, '/');
        }
    }

    if ($configuredUrl !== '') {
        return rtrim($configuredUrl, '/');
    }

    return 'http://localhost/core';
}

function base_url(string $path = ''): string
{
    $baseUrl = resolved_app_url();

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    $normalizedPath = ltrim($path, '/');
    $assetUrl = base_url($normalizedPath);
    $assetPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);

    if (!is_file($assetPath)) {
        return $assetUrl;
    }

    $lastModified = @filemtime($assetPath);
    if ($lastModified === false) {
        return $assetUrl;
    }

    return $assetUrl . '?v=' . $lastModified;
}

function redirect_to(string $path): void
{
    $target = preg_match('/^https?:\/\//i', $path) ? $path : base_url($path);
    header('Location: ' . $target);
    exit;
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function csrf_token(): string
{
    $token = $_SESSION['_csrf_token'] ?? null;

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
    }

    return $token;
}

function verify_csrf_token(?string $token): bool
{
    $sessionToken = $_SESSION['_csrf_token'] ?? null;

    return is_string($token)
        && $token !== ''
        && is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

function csv_values(?string $value): array
{
    if ($value === null || trim($value) === '') {
        return [];
    }

    $items = array_map('trim', explode(',', $value));
    $items = array_filter($items, static function ($item): bool {
        return $item !== '';
    });

    return array_values($items);
}

function format_number($value): string
{
    return number_format((float) ($value ?? 0));
}

function format_average($value): string
{
    return number_format((float) ($value ?? 0), 2);
}

function format_datetime($value): string
{
    if ($value === null || $value === '') {
        return 'Not available';
    }

    try {
        return (new DateTimeImmutable($value))->format('M d, Y h:i A');
    } catch (Throwable $exception) {
        return $value;
    }
}

function format_semester($semester): string
{
    switch ((int) $semester) {
        case 1:
            return '1st Semester';
        case 2:
            return '2nd Semester';
        case 3:
            return 'Summer';
        default:
            return 'Semester ' . (int) $semester;
    }
}

function format_year_level($yearLevel): string
{
    $yearLevel = (int) $yearLevel;

    if ($yearLevel <= 0) {
        return 'Not set';
    }

    return $yearLevel . ($yearLevel === 1 ? 'st' : ($yearLevel === 2 ? 'nd' : ($yearLevel === 3 ? 'rd' : 'th'))) . ' Year';
}

function truncate_text(string $value, int $limit = 80): string
{
    if (function_exists('mb_strlen')) {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 3)) . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 3)) . '...';
}
