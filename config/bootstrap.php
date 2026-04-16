<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_env(dirname(__DIR__) . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Manila') ?? 'Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $sessionPath = dirname(__DIR__) . '/storage/sessions';

    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }

    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    ini_set('session.use_strict_mode', '1');
    session_name(env('SESSION_NAME', 'core_admin_session') ?? 'core_admin_session');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
    }
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/user_management.php';
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/student_auth.php';
require_once __DIR__ . '/google.php';
require_once __DIR__ . '/dashboard.php';
require_once __DIR__ . '/student_portal.php';
require_once __DIR__ . '/evaluation.php';
