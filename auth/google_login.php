<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

if (!google_configuration_is_ready()) {
    flash('error', 'Google login is not configured yet. Please review the .env file.');
    redirect_to('auth/login.php');
}

if (allowed_administrator_emails() === [] && allowed_administrator_domains() === []) {
    flash('error', 'No administrator allowlist is configured in the .env file.');
    redirect_to('auth/login.php');
}

redirect_to(google_authorization_url());
