<?php
declare(strict_types=1);

function administrator_profile(): ?array
{
    $administrator = $_SESSION['administrator'] ?? null;
    return is_array($administrator) ? $administrator : null;
}

function administrator_profile_role(): string
{
    $administrator = administrator_profile();

    if ($administrator === null) {
        return '';
    }

    $role = trim((string) ($administrator['role'] ?? ''));

    return $role === '' ? '' : user_management_normalize_role($role);
}

function is_admin_authenticated(): bool
{
    return administrator_profile() !== null && administrator_profile_role() === 'administrator';
}

function is_program_chair_authenticated(): bool
{
    return administrator_profile() !== null && administrator_profile_role() === 'program_chair';
}

function require_admin_authentication(): void
{
    if (administrator_profile() === null) {
        flash('error', 'Please sign in with your authorized Google account.');
        redirect_to('auth/login.php');
    }

    if (administrator_profile_role() === '') {
        logout_administrator();
        flash('error', 'Please sign in again so your account role can be verified.');
        redirect_to('auth/login.php');
    }

    if (!is_admin_authenticated()) {
        flash('error', 'Your account is assigned to the Program Chair module.');
        redirect_to('programchair/index.php');
    }
}

function require_program_chair_authentication(): void
{
    if (administrator_profile() === null) {
        flash('error', 'Please sign in with your authorized Google account.');
        redirect_to('auth/login.php');
    }

    if (administrator_profile_role() === '') {
        logout_administrator();
        flash('error', 'Please sign in again so your account role can be verified.');
        redirect_to('auth/login.php');
    }

    if (!is_program_chair_authenticated()) {
        flash('error', 'Your account is assigned to the Administrator module.');
        redirect_to('administrator/index.php');
    }
}

function login_administrator(array $profile, ?array $managedUser = null): void
{
    session_regenerate_id(true);
    unset($_SESSION['student']);

    $displayName = trim((string) ($managedUser['full_name'] ?? ''));
    if ($displayName === '') {
        $displayName = (string) ($profile['name'] ?? 'Administrator');
    }

    $_SESSION['administrator'] = [
        'user_management_id' => (int) ($managedUser['user_management_id'] ?? 0),
        'google_id' => (string) ($profile['sub'] ?? ''),
        'name' => $displayName,
        'email' => user_management_normalize_email((string) ($profile['email'] ?? $managedUser['email_address'] ?? '')),
        'picture' => (string) ($profile['picture'] ?? ''),
        'role' => user_management_normalize_role((string) ($managedUser['account_role'] ?? 'administrator')),
        'logged_in_at' => date('Y-m-d H:i:s'),
    ];
}

function logout_administrator(): void
{
    unset($_SESSION['administrator'], $_SESSION['google_oauth_state']);
    session_regenerate_id(true);
}

function allowed_administrator_emails(): array
{
    return array_map('strtolower', csv_values(env('ADMIN_ALLOWED_EMAILS')));
}

function allowed_administrator_domains(): array
{
    return array_map('strtolower', csv_values(env('ADMIN_ALLOWED_DOMAINS')));
}

function primary_administrator_domain(): ?string
{
    $domains = allowed_administrator_domains();
    return $domains[0] ?? null;
}

function administrator_email_is_allowed(string $email): bool
{
    $email = strtolower(trim($email));
    $allowedEmails = allowed_administrator_emails();
    $allowedDomains = allowed_administrator_domains();

    if ($allowedEmails === [] && $allowedDomains === []) {
        return false;
    }

    if (in_array($email, $allowedEmails, true)) {
        return true;
    }

    if (strpos($email, '@') !== false) {
        list(, $domain) = explode('@', $email, 2);
        if (in_array(strtolower($domain), $allowedDomains, true)) {
            return true;
        }
    }

    return false;
}
