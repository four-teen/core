<?php
declare(strict_types=1);

function role_evaluation_evaluator_roles(): array
{
    return [
        'dean' => 'Dean',
        'director' => 'Campus Director',
    ];
}

function role_evaluation_target_role_for(string $evaluatorRole): string
{
    $evaluatorRole = strtolower(trim($evaluatorRole));

    if ($evaluatorRole === 'dean') {
        return 'program_chair';
    }

    if ($evaluatorRole === 'director') {
        return 'dean';
    }

    return '';
}

function role_evaluation_target_label_for(string $evaluatorRole): string
{
    $targetRole = role_evaluation_target_role_for($evaluatorRole);

    return user_management_role_label($targetRole);
}

function role_evaluation_is_evaluator_role(string $role): bool
{
    return role_evaluation_target_role_for($role) !== '';
}

function role_evaluation_scale_options(): array
{
    return program_chair_evaluation_scale_options();
}

function role_evaluation_question_bank(): array
{
    return program_chair_evaluation_question_bank(program_chair_latest_instrument_version());
}

function ensure_role_evaluation_tables(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    ensure_user_management_table($pdo);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_role_evaluator_assignments (
            assignment_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            evaluator_user_management_id INT UNSIGNED NOT NULL,
            evaluator_role VARCHAR(50) NOT NULL,
            evaluatee_user_management_id INT UNSIGNED NOT NULL,
            evaluatee_role VARCHAR(50) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by_user_management_id INT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_role_evaluator_assignment (evaluator_user_management_id, evaluatee_user_management_id),
            KEY idx_role_assignment_evaluator (evaluator_user_management_id, evaluator_role, is_active),
            KEY idx_role_assignment_evaluatee (evaluatee_user_management_id, evaluatee_role, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_role_evaluations (
            role_evaluation_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            evaluator_user_management_id INT UNSIGNED NOT NULL,
            evaluator_role VARCHAR(50) NOT NULL,
            evaluatee_user_management_id INT UNSIGNED NOT NULL,
            evaluatee_role VARCHAR(50) NOT NULL,
            evaluatee_name VARCHAR(150) NOT NULL DEFAULT '',
            evaluation_date DATE NULL,
            evaluation_time TIME NULL,
            comment_text TEXT NULL,
            question_count INT UNSIGNED NOT NULL DEFAULT 0,
            total_score INT UNSIGNED NOT NULL DEFAULT 0,
            average_rating DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            submission_status VARCHAR(20) NOT NULL DEFAULT 'draft',
            final_submission_token VARCHAR(64) NOT NULL DEFAULT '',
            final_submitted_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_role_eval_pair (evaluator_user_management_id, evaluatee_user_management_id),
            KEY idx_role_eval_evaluator (evaluator_user_management_id, evaluator_role),
            KEY idx_role_eval_evaluatee (evaluatee_user_management_id, evaluatee_role),
            KEY idx_role_eval_status (submission_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_role_evaluation_answers (
            role_evaluation_answer_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            role_evaluation_id INT UNSIGNED NOT NULL DEFAULT 0,
            category_key VARCHAR(80) NOT NULL,
            category_title VARCHAR(150) NOT NULL,
            question_key VARCHAR(80) NOT NULL,
            question_order INT UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_role_eval_answer_evaluation (role_evaluation_id),
            KEY idx_role_eval_answer_category (category_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $initialized = true;
}

function role_evaluation_user_display_name(array $user): string
{
    $name = trim((string) ($user['full_name'] ?? ''));

    return $name !== '' ? $name : user_management_normalize_email((string) ($user['email_address'] ?? ''));
}

function role_evaluation_assignment_target_options(PDO $pdo, string $evaluatorRole, int $excludeUserId = 0): array
{
    ensure_role_evaluation_tables($pdo);

    $targetRole = role_evaluation_target_role_for($evaluatorRole);
    if ($targetRole === '') {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT user_management_id, email_address, full_name, account_role
         FROM tbl_user_management
         WHERE is_active = 1
           AND account_role = :target_role
           AND user_management_id <> :exclude_user_id
         ORDER BY full_name ASC, email_address ASC"
    );
    $statement->execute([
        'target_role' => $targetRole,
        'exclude_user_id' => $excludeUserId,
    ]);

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['display_name'] = role_evaluation_user_display_name($row);
    }

    return $rows;
}

function role_evaluation_assigned_target_ids(PDO $pdo, int $evaluatorUserId): array
{
    ensure_role_evaluation_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT evaluatee_user_management_id
         FROM tbl_role_evaluator_assignments
         WHERE evaluator_user_management_id = :evaluator_user_management_id
           AND is_active = 1
         ORDER BY evaluatee_user_management_id ASC"
    );
    $statement->execute(['evaluator_user_management_id' => $evaluatorUserId]);

    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function role_evaluation_replace_assignments(PDO $pdo, int $evaluatorUserId, string $evaluatorRole, array $targetUserIds, int $createdByUserId): void
{
    ensure_role_evaluation_tables($pdo);

    $evaluatorRole = user_management_normalize_role($evaluatorRole);
    $targetRole = role_evaluation_target_role_for($evaluatorRole);
    $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds), static function (int $targetUserId) use ($evaluatorUserId): bool {
        return $targetUserId > 0 && $targetUserId !== $evaluatorUserId;
    })));

    $pdo->beginTransaction();

    try {
        $deleteStatement = $pdo->prepare(
            "DELETE FROM tbl_role_evaluator_assignments
             WHERE evaluator_user_management_id = :evaluator_user_management_id"
        );
        $deleteStatement->execute(['evaluator_user_management_id' => $evaluatorUserId]);

        if ($targetRole !== '' && $targetUserIds !== []) {
            $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
            $validStatement = $pdo->prepare(
                "SELECT user_management_id
                 FROM tbl_user_management
                 WHERE is_active = 1
                   AND account_role = ?
                   AND user_management_id IN (" . $placeholders . ")"
            );
            $validStatement->execute(array_merge([$targetRole], $targetUserIds));
            $validTargetIds = array_map('intval', $validStatement->fetchAll(PDO::FETCH_COLUMN));

            $insertStatement = $pdo->prepare(
                "INSERT INTO tbl_role_evaluator_assignments (
                    evaluator_user_management_id,
                    evaluator_role,
                    evaluatee_user_management_id,
                    evaluatee_role,
                    is_active,
                    created_by_user_management_id
                 ) VALUES (
                    :evaluator_user_management_id,
                    :evaluator_role,
                    :evaluatee_user_management_id,
                    :evaluatee_role,
                    1,
                    :created_by_user_management_id
                 )"
            );

            foreach ($validTargetIds as $targetUserId) {
                $insertStatement->execute([
                    'evaluator_user_management_id' => $evaluatorUserId,
                    'evaluator_role' => $evaluatorRole,
                    'evaluatee_user_management_id' => $targetUserId,
                    'evaluatee_role' => $targetRole,
                    'created_by_user_management_id' => $createdByUserId > 0 ? $createdByUserId : null,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function role_evaluation_summary(PDO $pdo, int $evaluatorUserId, string $evaluatorRole): array
{
    ensure_role_evaluation_tables($pdo);
    $evaluatorRole = user_management_normalize_role($evaluatorRole);

    $assignedStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM tbl_role_evaluator_assignments
         WHERE evaluator_user_management_id = :evaluator_user_management_id
           AND evaluator_role = :evaluator_role
           AND is_active = 1"
    );
    $assignedStatement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => $evaluatorRole,
    ]);

    $evaluationStatement = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_evaluations,
            SUM(CASE WHEN submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_evaluations,
            ROUND(AVG(CASE WHEN submission_status = 'submitted' AND average_rating > 0 THEN average_rating ELSE NULL END), 2) AS average_rating
         FROM tbl_role_evaluations
         WHERE evaluator_user_management_id = :evaluator_user_management_id
           AND evaluator_role = :evaluator_role"
    );
    $evaluationStatement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => $evaluatorRole,
    ]);
    $evaluation = $evaluationStatement->fetch() ?: [];

    return [
        'assigned_targets' => (int) $assignedStatement->fetchColumn(),
        'submitted_evaluations' => (int) ($evaluation['submitted_evaluations'] ?? 0),
        'draft_evaluations' => (int) ($evaluation['draft_evaluations'] ?? 0),
        'average_rating' => $evaluation['average_rating'] ?? 0,
    ];
}

function role_evaluation_targets_for_evaluation(PDO $pdo, int $evaluatorUserId, string $evaluatorRole, string $search = ''): array
{
    ensure_role_evaluation_tables($pdo);

    $evaluatorRole = user_management_normalize_role($evaluatorRole);
    $targetRole = role_evaluation_target_role_for($evaluatorRole);
    if ($targetRole === '') {
        return [];
    }

    $condition = "a.evaluator_user_management_id = :evaluator_user_management_id
        AND a.evaluator_role = :evaluator_role
        AND a.evaluatee_role = :evaluatee_role
        AND a.is_active = 1
        AND u.is_active = 1";
    $parameters = [
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => $evaluatorRole,
        'evaluatee_role' => $targetRole,
    ];

    if ($search !== '') {
        $condition .= " AND (
            u.full_name LIKE :search
            OR u.email_address LIKE :search
        )";
        $parameters['search'] = '%' . $search . '%';
    }

    $statement = $pdo->prepare(
        "SELECT
            u.user_management_id AS target_user_management_id,
            u.email_address,
            u.full_name,
            u.account_role,
            ev.role_evaluation_id,
            ev.evaluation_date,
            ev.evaluation_time,
            ev.average_rating,
            ev.submission_status,
            ev.updated_at AS evaluation_updated_at
         FROM tbl_role_evaluator_assignments a
         INNER JOIN tbl_user_management u
            ON u.user_management_id = a.evaluatee_user_management_id
         LEFT JOIN tbl_role_evaluations ev
            ON ev.evaluator_user_management_id = a.evaluator_user_management_id
           AND ev.evaluatee_user_management_id = a.evaluatee_user_management_id
         WHERE " . $condition . "
         ORDER BY u.full_name ASC, u.email_address ASC"
    );
    $statement->execute($parameters);

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['target_name'] = role_evaluation_user_display_name($row);
        $status = (string) ($row['submission_status'] ?? '');
        $rows[$index]['evaluation_status_text'] = $status !== '' ? ucfirst($status) : 'Not started';
    }

    return $rows;
}

function role_evaluation_recent_evaluations(PDO $pdo, int $evaluatorUserId, string $evaluatorRole, int $limit = 8): array
{
    ensure_role_evaluation_tables($pdo);
    $limit = max(1, min(25, $limit));

    $statement = $pdo->prepare(
        "SELECT
            ev.role_evaluation_id,
            ev.evaluatee_user_management_id,
            ev.evaluatee_role,
            ev.evaluatee_name,
            ev.evaluation_date,
            ev.evaluation_time,
            ev.average_rating,
            ev.submission_status,
            ev.updated_at,
            u.email_address,
            u.full_name
         FROM tbl_role_evaluations ev
         LEFT JOIN tbl_user_management u
            ON u.user_management_id = ev.evaluatee_user_management_id
         WHERE ev.evaluator_user_management_id = :evaluator_user_management_id
           AND ev.evaluator_role = :evaluator_role
         ORDER BY ev.updated_at DESC, ev.role_evaluation_id DESC
         LIMIT " . $limit
    );
    $statement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => user_management_normalize_role($evaluatorRole),
    ]);

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $displayName = role_evaluation_user_display_name($row);
        $rows[$index]['target_name'] = $displayName !== '' ? $displayName : (string) ($row['evaluatee_name'] ?? '');
    }

    return $rows;
}

function role_evaluation_context(PDO $pdo, int $evaluatorUserId, string $evaluatorRole, int $targetUserId): ?array
{
    ensure_role_evaluation_tables($pdo);

    $evaluatorRole = user_management_normalize_role($evaluatorRole);
    $targetRole = role_evaluation_target_role_for($evaluatorRole);
    if ($targetRole === '') {
        return null;
    }

    $statement = $pdo->prepare(
        "SELECT
            a.assignment_id,
            a.evaluator_user_management_id,
            a.evaluator_role,
            a.evaluatee_user_management_id,
            a.evaluatee_role,
            u.email_address,
            u.full_name,
            u.account_role
         FROM tbl_role_evaluator_assignments a
         INNER JOIN tbl_user_management u
            ON u.user_management_id = a.evaluatee_user_management_id
         WHERE a.evaluator_user_management_id = :evaluator_user_management_id
           AND a.evaluator_role = :evaluator_role
           AND a.evaluatee_user_management_id = :evaluatee_user_management_id
           AND a.evaluatee_role = :evaluatee_role
           AND a.is_active = 1
           AND u.is_active = 1
         LIMIT 1"
    );
    $statement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => $evaluatorRole,
        'evaluatee_user_management_id' => $targetUserId,
        'evaluatee_role' => $targetRole,
    ]);

    $context = $statement->fetch();
    if (!$context) {
        return null;
    }

    $context['target_name'] = role_evaluation_user_display_name($context);

    return $context;
}

function role_evaluation_find_by_context(PDO $pdo, int $evaluatorUserId, int $targetUserId): ?array
{
    ensure_role_evaluation_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT *
         FROM tbl_role_evaluations
         WHERE evaluator_user_management_id = :evaluator_user_management_id
           AND evaluatee_user_management_id = :evaluatee_user_management_id
         LIMIT 1"
    );
    $statement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluatee_user_management_id' => $targetUserId,
    ]);

    $evaluation = $statement->fetch();

    return $evaluation ?: null;
}

function role_evaluation_create_or_get(PDO $pdo, array $context): array
{
    ensure_role_evaluation_tables($pdo);

    $evaluatorUserId = (int) ($context['evaluator_user_management_id'] ?? 0);
    $targetUserId = (int) ($context['evaluatee_user_management_id'] ?? 0);
    $existing = role_evaluation_find_by_context($pdo, $evaluatorUserId, $targetUserId);

    if ($existing !== null) {
        return $existing;
    }

    $statement = $pdo->prepare(
        "INSERT INTO tbl_role_evaluations (
            evaluator_user_management_id,
            evaluator_role,
            evaluatee_user_management_id,
            evaluatee_role,
            evaluatee_name,
            submission_status
         ) VALUES (
            :evaluator_user_management_id,
            :evaluator_role,
            :evaluatee_user_management_id,
            :evaluatee_role,
            :evaluatee_name,
            'draft'
         )"
    );
    $statement->execute([
        'evaluator_user_management_id' => $evaluatorUserId,
        'evaluator_role' => (string) ($context['evaluator_role'] ?? ''),
        'evaluatee_user_management_id' => $targetUserId,
        'evaluatee_role' => (string) ($context['evaluatee_role'] ?? ''),
        'evaluatee_name' => (string) ($context['target_name'] ?? ''),
    ]);

    $created = role_evaluation_find_by_context($pdo, $evaluatorUserId, $targetUserId);
    if ($created === null) {
        throw new RuntimeException('Unable to create the role evaluation record.');
    }

    return $created;
}

function role_evaluation_find_answers(PDO $pdo, int $roleEvaluationId): array
{
    ensure_role_evaluation_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT question_key, rating
         FROM tbl_role_evaluation_answers
         WHERE role_evaluation_id = :role_evaluation_id
         ORDER BY question_order ASC"
    );
    $statement->execute(['role_evaluation_id' => $roleEvaluationId]);

    $answers = [];
    foreach ($statement->fetchAll() as $row) {
        $answers[$row['question_key']] = (int) $row['rating'];
    }

    return $answers;
}

function role_evaluation_answer_value(array $answers, array $category, int $position): int
{
    $questionKey = evaluation_question_key($category, $position);

    return isset($answers[$questionKey]) ? (int) $answers[$questionKey] : 0;
}

function role_evaluation_has_any_answer(array $submittedAnswers): bool
{
    foreach (role_evaluation_question_bank() as $category) {
        $position = 1;
        foreach ($category['questions'] as $questionText) {
            $questionKey = evaluation_question_key($category, $position);

            if (isset($submittedAnswers[$questionKey]) && trim((string) $submittedAnswers[$questionKey]) !== '') {
                return true;
            }

            $position++;
        }
    }

    return false;
}

function role_evaluation_submitted_answer_values(array $submittedAnswers): array
{
    $answers = [];
    $validScores = array_keys(role_evaluation_scale_options());

    foreach (role_evaluation_question_bank() as $category) {
        $position = 1;
        foreach ($category['questions'] as $questionText) {
            $questionKey = evaluation_question_key($category, $position);

            if (isset($submittedAnswers[$questionKey])) {
                $rating = (int) $submittedAnswers[$questionKey];

                if (in_array($rating, $validScores, true)) {
                    $answers[$questionKey] = $rating;
                }
            }

            $position++;
        }
    }

    return $answers;
}

function role_evaluation_normalize_answers(array $submittedAnswers, bool $requireComplete = true): array
{
    $normalized = [];
    $validScores = array_keys(role_evaluation_scale_options());

    foreach (role_evaluation_question_bank() as $category) {
        $position = 1;
        foreach ($category['questions'] as $questionText) {
            $questionKey = evaluation_question_key($category, $position);
            $hasSubmittedRating = isset($submittedAnswers[$questionKey]) && trim((string) $submittedAnswers[$questionKey]) !== '';
            $rating = $hasSubmittedRating ? (int) $submittedAnswers[$questionKey] : 0;

            if (!$hasSubmittedRating && !$requireComplete) {
                $position++;
                continue;
            }

            if (!in_array($rating, $validScores, true)) {
                throw new RuntimeException('Please provide a rating for every evaluation item.');
            }

            $normalized[] = [
                'category_key' => (string) $category['key'],
                'category_title' => (string) $category['title'],
                'question_key' => $questionKey,
                'question_order' => count($normalized) + 1,
                'question_text' => (string) $questionText,
                'rating' => $rating,
            ];
            $position++;
        }
    }

    return $normalized;
}

function role_evaluation_normalize_date(string $dateValue, bool $required): ?string
{
    $dateValue = trim($dateValue);

    if ($dateValue === '') {
        if ($required) {
            throw new RuntimeException('Please select the evaluation date.');
        }

        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
    if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $dateValue) {
        throw new RuntimeException('Please enter a valid evaluation date.');
    }

    return $date->format('Y-m-d');
}

function role_evaluation_normalize_time(string $timeValue, bool $required): ?string
{
    $timeValue = trim($timeValue);

    if ($timeValue === '') {
        if ($required) {
            throw new RuntimeException('Please select the evaluation time.');
        }

        return null;
    }

    $time = DateTimeImmutable::createFromFormat('!H:i', $timeValue);
    if (!$time instanceof DateTimeImmutable || $time->format('H:i') !== $timeValue) {
        throw new RuntimeException('Please enter a valid evaluation time.');
    }

    return $time->format('H:i:s');
}

function role_evaluation_format_time($timeValue): string
{
    $timeValue = trim((string) $timeValue);

    if ($timeValue === '') {
        return 'Not available';
    }

    try {
        return (new DateTimeImmutable($timeValue))->format('h:i A');
    } catch (Throwable $exception) {
        return $timeValue;
    }
}

function role_evaluation_save_submission(
    PDO $pdo,
    array $evaluation,
    array $context,
    array $submittedAnswers,
    string $evaluationDate,
    string $evaluationTime,
    string $commentText,
    string $status
): array {
    ensure_role_evaluation_tables($pdo);

    $isSubmitted = $status === 'submitted';
    $normalizedDate = role_evaluation_normalize_date($evaluationDate, $isSubmitted);
    $normalizedTime = role_evaluation_normalize_time($evaluationTime, $isSubmitted);
    $normalizedAnswers = role_evaluation_normalize_answers($submittedAnswers, $isSubmitted);

    $totalScore = 0;
    foreach ($normalizedAnswers as $answer) {
        $totalScore += (int) $answer['rating'];
    }

    $questionCount = count($normalizedAnswers);
    $averageRating = $questionCount > 0 ? round($totalScore / $questionCount, 2) : 0;

    $pdo->beginTransaction();

    try {
        $updateStatement = $pdo->prepare(
            "UPDATE tbl_role_evaluations
             SET evaluator_role = :evaluator_role,
                 evaluatee_role = :evaluatee_role,
                 evaluatee_name = :evaluatee_name,
                 evaluation_date = :evaluation_date,
                 evaluation_time = :evaluation_time,
                 comment_text = :comment_text,
                 question_count = :question_count,
                 total_score = :total_score,
                 average_rating = :average_rating,
                 submission_status = :submission_status,
                 final_submission_token = :final_submission_token,
                 final_submitted_at = :final_submitted_at,
                 completed_at = NOW(),
                 updated_at = NOW()
             WHERE role_evaluation_id = :role_evaluation_id"
        );
        $updateStatement->execute([
            'evaluator_role' => (string) ($context['evaluator_role'] ?? ''),
            'evaluatee_role' => (string) ($context['evaluatee_role'] ?? ''),
            'evaluatee_name' => (string) ($context['target_name'] ?? ''),
            'evaluation_date' => $normalizedDate,
            'evaluation_time' => $normalizedTime,
            'comment_text' => trim($commentText),
            'question_count' => $questionCount,
            'total_score' => $totalScore,
            'average_rating' => $averageRating,
            'submission_status' => $status,
            'final_submission_token' => $isSubmitted ? bin2hex(random_bytes(16)) : '',
            'final_submitted_at' => $isSubmitted ? date('Y-m-d H:i:s') : null,
            'role_evaluation_id' => $evaluation['role_evaluation_id'],
        ]);

        $deleteStatement = $pdo->prepare(
            "DELETE FROM tbl_role_evaluation_answers
             WHERE role_evaluation_id = :role_evaluation_id"
        );
        $deleteStatement->execute([
            'role_evaluation_id' => $evaluation['role_evaluation_id'],
        ]);

        $insertStatement = $pdo->prepare(
            "INSERT INTO tbl_role_evaluation_answers (
                role_evaluation_id,
                category_key,
                category_title,
                question_key,
                question_order,
                question_text,
                rating
             ) VALUES (
                :role_evaluation_id,
                :category_key,
                :category_title,
                :question_key,
                :question_order,
                :question_text,
                :rating
             )"
        );

        foreach ($normalizedAnswers as $answer) {
            $insertStatement->execute([
                'role_evaluation_id' => $evaluation['role_evaluation_id'],
                'category_key' => $answer['category_key'],
                'category_title' => $answer['category_title'],
                'question_key' => $answer['question_key'],
                'question_order' => $answer['question_order'],
                'question_text' => $answer['question_text'],
                'rating' => $answer['rating'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    $saved = role_evaluation_find_by_context(
        $pdo,
        (int) ($context['evaluator_user_management_id'] ?? 0),
        (int) ($context['evaluatee_user_management_id'] ?? 0)
    );

    if ($saved === null) {
        throw new RuntimeException('Unable to reload the saved evaluation.');
    }

    return $saved;
}
