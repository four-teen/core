<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

redirect_to('auth/login.php');
