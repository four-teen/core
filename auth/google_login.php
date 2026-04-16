<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

if (is_student_authenticated()) {
    redirect_to('student/index.php');
}

if (!google_configuration_is_ready()) {
    flash('error', 'Google login is not configured yet. Please review the .env file.');
    redirect_to('auth/login.php');
}

redirect_to(google_authorization_url());
