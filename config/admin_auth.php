<?php
declare(strict_types=1);

function administrator_profile(): ?array
{
    $administrator = $_SESSION['administrator'] ?? null;
    return is_array($administrator) ? $administrator : null;
}

function is_admin_authenticated(): bool
{
    return administrator_profile() !== null;
}

function require_admin_authentication(): void
{
    if (!is_admin_authenticated()) {
        flash('error', 'Please sign in with your administrator Google account.');
        redirect_to('auth/login.php');
    }
}

function login_administrator(array $administrator): void
{
    session_regenerate_id(true);

    $_SESSION['administrator'] = [
        'google_id' => (string) ($administrator['sub'] ?? ''),
        'name' => (string) ($administrator['name'] ?? 'Administrator'),
        'email' => (string) ($administrator['email'] ?? ''),
        'picture' => (string) ($administrator['picture'] ?? ''),
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
