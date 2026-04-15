<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

logout_student();
flash('notice', 'You have been signed out of the student portal.');

redirect_to('student/login.php');
