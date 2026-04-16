<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/_faculty_list.php';

require_program_chair_authentication();

$programChair = administrator_profile();
$programChairUserId = (int) ($programChair['user_management_id'] ?? 0);
$facultySearch = trim((string) ($_GET['faculty_search'] ?? ''));

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);
    $facultyList = program_chair_faculty_for_evaluation($pdo, $programChairUserId, $facultySearch);

    header('Content-Type: text/html; charset=UTF-8');
    program_chair_render_faculty_list($facultyList, $facultySearch);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    $message = is_local_env()
        ? 'Unable to search faculty. ' . $exception->getMessage()
        : 'Unable to search faculty right now. Please try again.';
    ?>
    <div class="alert alert-danger mb-0" role="alert"><?= h($message) ?></div>
    <?php
}
