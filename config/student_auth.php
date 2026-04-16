<?php
declare(strict_types=1);

function student_profile(): ?array
{
    $student = $_SESSION['student'] ?? null;
    return is_array($student) ? $student : null;
}

function is_student_authenticated(): bool
{
    return student_profile() !== null;
}

function require_student_authentication(): void
{
    if (!is_student_authenticated()) {
        flash('error', 'Please sign in with your enrolled student email.');
        redirect_to('student/login.php');
    }
}

function login_student(array $student): void
{
    session_regenerate_id(true);
    unset($_SESSION['administrator'], $_SESSION['google_oauth_state']);

    $_SESSION['student'] = [
        'student_id' => (int) ($student['student_id'] ?? 0),
        'student_number' => (string) ($student['student_number'] ?? ''),
        'name' => trim((string) ($student['full_name'] ?? 'Student')),
        'email' => (string) ($student['email_address'] ?? ''),
        'program_code' => (string) ($student['program_code'] ?? ''),
        'year_level' => (string) ($student['year_level'] ?? ''),
        'logged_in_at' => date('Y-m-d H:i:s'),
    ];
}

function logout_student(): void
{
    unset($_SESSION['student']);
    session_regenerate_id(true);
}
