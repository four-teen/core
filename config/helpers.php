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

function base_url(string $path = ''): string
{
    $baseUrl = rtrim(env('APP_URL', 'http://localhost/core') ?? 'http://localhost/core', '/');

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
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
