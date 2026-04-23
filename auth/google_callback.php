<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (isset($_GET['error'])) {
    flash('error', 'Google sign-in was cancelled or denied.');
    redirect_to('auth/login.php');
}

$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_oauth_state']);

if (!is_string($state) || $state === '' || !is_string($expectedState) || !hash_equals($expectedState, $state)) {
    flash('error', 'The Google sign-in request could not be verified. Please try again.');
    redirect_to('auth/login.php');
}

$code = $_GET['code'] ?? '';
if (!is_string($code) || $code === '') {
    flash('error', 'Google did not return an authorization code.');
    redirect_to('auth/login.php');
}

try {
    $tokenPayload = google_exchange_code_for_tokens($code);
    $accessToken = $tokenPayload['access_token'] ?? '';

    if (!is_string($accessToken) || $accessToken === '') {
        throw new RuntimeException('Google did not return an access token.');
    }

    $profile = google_fetch_user_profile($accessToken);
    $email = isset($profile['email']) ? strtolower(trim((string) $profile['email'])) : '';
    $emailVerified = !empty($profile['email_verified']);

    if ($email === '' || !$emailVerified) {
        throw new RuntimeException('Your Google account must have a verified email address.');
    }

    $pdo = db();
    $managedUser = user_management_find_by_email($pdo, $email);

    if ($managedUser === null) {
        $managedUser = user_management_bootstrap_from_legacy_allowlist($pdo, $profile);

        if ($managedUser !== null) {
            flash('notice', 'Your Google account was added to User Management using the legacy allowlist. You can manage access from the administrator dashboard.');
        }
    }

    if ($managedUser !== null) {
        if ((int) ($managedUser['is_active'] ?? 0) !== 1) {
            flash('error', 'Your user management account is currently inactive.');
            redirect_to('auth/login.php');
        }

        login_administrator($profile, $managedUser);
        redirect_to(administrator_role_landing_path((string) ($managedUser['account_role'] ?? 'administrator')));
    }

    $student = find_student_for_login($pdo, $email);

    if ($student !== null) {
        login_student($student);
        redirect_to('student/index.php');
    }

    flash('error', 'This Google account is not authorized to access the system.');
    redirect_to('auth/login.php');
} catch (Throwable $exception) {
    $message = 'Unable to complete Google sign-in. Please check the OAuth configuration and try again.';
    if (is_local_env()) {
        $message .= ' ' . $exception->getMessage();
    }

    flash('error', $message);
    redirect_to('auth/login.php');
}
