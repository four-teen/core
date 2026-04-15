<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

logout_administrator();
flash('notice', 'You have been signed out.');

redirect_to('auth/login.php');
