<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

if (is_program_chair_authenticated() || is_role_evaluator_authenticated()) {
    redirect_to(administrator_role_landing_path(administrator_profile_role()));
}

redirect_to('auth/login.php');
