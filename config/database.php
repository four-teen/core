<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1') ?? '127.0.0.1';
    $port = env('DB_PORT', '3306') ?? '3306';
    $database = env('DB_NAME', 'core_db') ?? 'core_db';
    $username = env('DB_USER', 'root') ?? 'root';
    $password = env('DB_PASS', '') ?? '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException('Database connection failed. ' . $exception->getMessage(), 0, $exception);
    }

    return $pdo;
}
