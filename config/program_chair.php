<?php
declare(strict_types=1);

function program_chair_evaluation_scale_options(): array
{
    return [
        5 => [
            'label' => 'Outstanding',
            'description' => 'Performance almost always exceeds the job requirements.',
        ],
        4 => [
            'label' => 'Very Satisfactory',
            'description' => 'Performance meets and often exceeds the job requirements.',
        ],
        3 => [
            'label' => 'Satisfactory',
            'description' => 'Performance meets and sometimes exceeds the job requirements.',
        ],
        2 => [
            'label' => 'Fair',
            'description' => 'Performance needs development to meet job requirements.',
        ],
        1 => [
            'label' => 'Poor',
            'description' => 'Performance fails to meet job requirements.',
        ],
    ];
}

function program_chair_evaluation_question_bank(): array
{
    return [
        [
            'key' => 'commitment',
            'code' => 'cmt',
            'title' => 'COMMITMENT',
            'questions' => [
                'Demonstrates sensitivity to students\' ability to attend and absorb content information.',
                'Integrates sensitivity to his/her learning objectives with those of the students in a collaborative process.',
                'Makes her/himself available to students beyond official time.',
                'Coordinates student needs with internal and external enabling groups.',
                'Supplements available resources.',
            ],
        ],
        [
            'key' => 'knowledge_of_subject_matter',
            'code' => 'ksm',
            'title' => 'KNOWLEDGE OF SUBJECT MATTER',
            'questions' => [
                'Discusses the subject matter without completely relying on the prescribed reading.',
                'Draws and shares information on the state-of-the-art theory and practice in his/her discipline.',
                'Integrates subject to practical circumstances and learning intents/purposes of students.',
                'Explains the relevance of present topics to the previous lessons, and relates the subject matter to relevant current issues and/or daily life activities.',
                'Demonstrates up-to-date knowledge and/or awareness on current trends and issues of the subject.',
            ],
        ],
        [
            'key' => 'teaching_for_independent_learning',
            'code' => 'til',
            'title' => 'TEACHING FOR INDEPENDENT LEARNING',
            'questions' => [
                'Employs teaching strategies that allow students to practice using concepts they need to understand (interactive discussion).',
                'Provides exercises which develop critical and analytical thinking among the students.',
                'Enhances student self-esteem through proper recognition of their abilities.',
                'Allows students of the course to create their own use of well-defined objectives and realistic student-faculty rules.',
                'Empowers students to make their own decisions and be accountable for their performance.',
            ],
        ],
        [
            'key' => 'management_of_learning',
            'code' => 'mol',
            'title' => 'MANAGEMENT OF LEARNING',
            'questions' => [
                'Creates opportunities for intensive and/or extensive contribution of students in the class activities (e.g. breaks class into dyads, triads or buzz/task groups).',
                'Assumes roles as facilitator, resource person, coach, inquisitor, integrator, referee in drawing students to contribute to knowledge and understanding of the concepts at hand.',
                'Designs and implements learning conditions and experience that promotes healthy exchange and/or confrontations.',
                'Structures/re-structures learning and teaching-learning context to enhance attainment of collective learning objectives.',
                'Stimulates students\' desire and interest to learn more about the subject matter.',
            ],
        ],
    ];
}

function ensure_program_chair_tables(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_program_chair_faculty (
            program_chair_faculty_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            faculty_id INT UNSIGNED NOT NULL,
            faculty_classification VARCHAR(40) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by_user_management_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_program_chair_faculty (faculty_id),
            KEY idx_program_chair_faculty_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    program_chair_ensure_faculty_columns($pdo);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_program_chair_faculty_evaluations (
            program_chair_evaluation_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            program_chair_user_management_id INT UNSIGNED NOT NULL DEFAULT 0,
            faculty_id INT UNSIGNED NOT NULL DEFAULT 0,
            subject_id INT UNSIGNED NULL,
            subject_code VARCHAR(50) NOT NULL DEFAULT '',
            faculty_name VARCHAR(255) NOT NULL DEFAULT '',
            subject_text VARCHAR(255) NOT NULL DEFAULT '',
            evaluation_date DATE NULL,
            evaluation_time TIME NULL,
            comment_text TEXT NULL,
            question_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            total_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            average_rating DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            evaluation_token VARCHAR(64) NOT NULL,
            submission_status ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
            final_submission_token VARCHAR(64) NOT NULL DEFAULT '',
            final_submitted_at DATETIME NULL,
            completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_pc_eval_user_faculty (program_chair_user_management_id, faculty_id),
            UNIQUE KEY uniq_pc_eval_token (evaluation_token),
            KEY idx_pc_eval_faculty (faculty_id),
            KEY idx_pc_eval_user (program_chair_user_management_id),
            KEY idx_pc_eval_status (submission_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    program_chair_ensure_evaluation_subject_columns($pdo);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tbl_program_chair_faculty_evaluation_answers (
            program_chair_answer_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            program_chair_evaluation_id INT UNSIGNED NOT NULL DEFAULT 0,
            category_key VARCHAR(64) NOT NULL DEFAULT '',
            category_title VARCHAR(255) NOT NULL DEFAULT '',
            question_key VARCHAR(32) NOT NULL DEFAULT '',
            question_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            question_text TEXT NULL,
            rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_pc_eval_answer_evaluation (program_chair_evaluation_id),
            KEY idx_pc_eval_answer_category (category_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $initialized = true;
}

function program_chair_faculty_classification_options(): array
{
    return [
        'REGULAR' => 'Regular Faculty',
        'CONTRACT OF SERVICE' => 'Contract of Service Faculty',
    ];
}

function program_chair_normalize_faculty_classification(string $classification, bool $allowEmpty = false): string
{
    $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $classification) ?? ''));

    if ($normalized === 'REGULAR FACULTY') {
        $normalized = 'REGULAR';
    }

    if ($normalized === 'CONTRACT OF SERVICE FACULTY') {
        $normalized = 'CONTRACT OF SERVICE';
    }

    if ($normalized === '' && $allowEmpty) {
        return '';
    }

    if (!array_key_exists($normalized, program_chair_faculty_classification_options())) {
        throw new RuntimeException('Please select a valid faculty classification.');
    }

    return $normalized;
}

function program_chair_faculty_classification_label(string $classification): string
{
    $normalized = program_chair_normalize_faculty_classification($classification, true);
    $options = program_chair_faculty_classification_options();

    return $options[$normalized] ?? 'Not set';
}

function program_chair_table_columns(PDO $pdo, string $tableName): array
{
    $columns = [];
    $statement = $pdo->query('SHOW COLUMNS FROM ' . $tableName);

    foreach ($statement->fetchAll() as $row) {
        $columns[(string) $row['Field']] = true;
    }

    return $columns;
}

function program_chair_ensure_faculty_columns(PDO $pdo): void
{
    $columns = program_chair_table_columns($pdo, 'tbl_program_chair_faculty');

    if (!isset($columns['faculty_classification'])) {
        program_chair_add_column_if_missing(
            $pdo,
            "ALTER TABLE tbl_program_chair_faculty
             ADD COLUMN faculty_classification VARCHAR(40) NOT NULL DEFAULT '' AFTER faculty_id"
        );
    }
}

function program_chair_ensure_evaluation_subject_columns(PDO $pdo): void
{
    $columns = program_chair_table_columns($pdo, 'tbl_program_chair_faculty_evaluations');

    if (!isset($columns['subject_id'])) {
        program_chair_add_column_if_missing(
            $pdo,
            "ALTER TABLE tbl_program_chair_faculty_evaluations
             ADD COLUMN subject_id INT UNSIGNED NULL AFTER faculty_id"
        );
    }

    if (!isset($columns['subject_code'])) {
        program_chair_add_column_if_missing(
            $pdo,
            "ALTER TABLE tbl_program_chair_faculty_evaluations
             ADD COLUMN subject_code VARCHAR(50) NOT NULL DEFAULT '' AFTER subject_id"
        );
    }
}

function program_chair_add_column_if_missing(PDO $pdo, string $sql): void
{
    try {
        $pdo->exec($sql);
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() === '42S21') {
            return;
        }

        throw $exception;
    }
}

function program_chair_faculty_name_from_row(array $row): string
{
    $name = person_full_name(
        $row['last_name'] ?? '',
        $row['first_name'] ?? '',
        $row['middle_name'] ?? '',
        $row['ext_name'] ?? ''
    );

    return $name !== '' ? $name : 'Faculty #' . (string) ((int) ($row['faculty_id'] ?? 0));
}

function program_chair_subject_label(array $subject): string
{
    $parts = [];
    $subjectCode = trim((string) ($subject['sub_code'] ?? $subject['subject_code'] ?? ''));
    $subjectDescription = trim((string) ($subject['sub_description'] ?? $subject['descriptive_title'] ?? ''));

    if ($subjectCode !== '') {
        $parts[] = $subjectCode;
    }

    if ($subjectDescription !== '') {
        $parts[] = $subjectDescription;
    }

    $label = implode(' - ', $parts);

    return $label !== '' ? $label : 'Subject #' . (string) ((int) ($subject['sub_id'] ?? 0));
}

function program_chair_subject_key_from_values(string $subjectCode, string $subjectDescription): string
{
    return sha1(strtolower(trim($subjectCode)) . "\0" . strtolower(trim($subjectDescription)));
}

function program_chair_subject_key(array $subject): string
{
    return program_chair_subject_key_from_values(
        (string) ($subject['sub_code'] ?? $subject['subject_code'] ?? ''),
        (string) ($subject['sub_description'] ?? $subject['descriptive_title'] ?? '')
    );
}

function program_chair_subject_options(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT
            MIN(NULLIF(subject_id, 0)) AS sub_id,
            TRIM(subject_code) AS sub_code,
            TRIM(descriptive_title) AS sub_description
         FROM tbl_student_management_enrolled_subjects
         WHERE (
                TRIM(COALESCE(subject_code, '')) <> ''
                OR TRIM(COALESCE(descriptive_title, '')) <> ''
           )
         GROUP BY
            TRIM(subject_code),
            TRIM(descriptive_title)
         ORDER BY sub_code ASC, sub_description ASC"
    );

    $subjects = $statement->fetchAll();
    foreach ($subjects as $index => $subject) {
        $subjects[$index]['subject_label'] = program_chair_subject_label($subject);
        $subjects[$index]['subject_key'] = program_chair_subject_key($subject);
    }

    return $subjects;
}

function program_chair_subject_find_by_key(PDO $pdo, string $subjectKey): ?array
{
    $subjectKey = trim($subjectKey);

    if ($subjectKey === '') {
        return null;
    }

    foreach (program_chair_subject_options($pdo) as $subject) {
        if ((string) ($subject['subject_key'] ?? '') === $subjectKey) {
            return $subject;
        }
    }

    return null;
}

function program_chair_subject_key_for_saved_evaluation(PDO $pdo, array $evaluation): string
{
    $subjectId = (int) ($evaluation['subject_id'] ?? 0);
    $subjectCode = trim((string) ($evaluation['subject_code'] ?? ''));
    $subjectText = trim((string) ($evaluation['subject_text'] ?? ''));

    foreach (program_chair_subject_options($pdo) as $subject) {
        $optionSubjectId = (int) ($subject['sub_id'] ?? 0);
        $optionSubjectCode = trim((string) ($subject['sub_code'] ?? ''));
        $optionLabel = trim((string) ($subject['subject_label'] ?? ''));

        if ($subjectId > 0 && $optionSubjectId === $subjectId) {
            return (string) ($subject['subject_key'] ?? '');
        }

        if ($subjectCode !== '' && $subjectCode === $optionSubjectCode && $subjectText === $optionLabel) {
            return (string) ($subject['subject_key'] ?? '');
        }

        if ($subjectText !== '' && $subjectText === $optionLabel) {
            return (string) ($subject['subject_key'] ?? '');
        }
    }

    return '';
}

function program_chair_subject_payload(PDO $pdo, string $subjectKey, bool $required): array
{
    $subjectKey = trim($subjectKey);

    if ($subjectKey === '') {
        if ($required) {
            throw new RuntimeException('Please select a subject from the enrolled-subject list.');
        }

        return [
            'subject_id' => null,
            'subject_code' => '',
            'subject_text' => '',
        ];
    }

    $subject = program_chair_subject_find_by_key($pdo, $subjectKey);

    if ($subject === null) {
        throw new RuntimeException('The selected subject could not be found in the enrolled-subject list.');
    }

    return [
        'subject_id' => (int) ($subject['sub_id'] ?? 0) > 0 ? (int) $subject['sub_id'] : null,
        'subject_code' => (string) $subject['sub_code'],
        'subject_text' => (string) $subject['subject_label'],
    ];
}

function program_chair_master_faculty_count(PDO $pdo): int
{
    $statement = $pdo->query("SELECT COUNT(*) FROM tbl_faculty WHERE status = 'active'");
    return (int) $statement->fetchColumn();
}

function program_chair_faculty_list_count(PDO $pdo): int
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->query(
        "SELECT COUNT(*)
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
           AND f.status = 'active'"
    );

    return (int) $statement->fetchColumn();
}

function program_chair_faculty_options(PDO $pdo): array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->query(
        "SELECT
            f.faculty_id,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name
         FROM tbl_faculty f
         LEFT JOIN tbl_program_chair_faculty pcf
            ON pcf.faculty_id = f.faculty_id
           AND pcf.is_active = 1
         WHERE f.status = 'active'
           AND pcf.program_chair_faculty_id IS NULL
         ORDER BY f.last_name ASC, f.first_name ASC, f.faculty_id ASC"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['faculty_name'] = program_chair_faculty_name_from_row($row);
    }

    return $rows;
}

function program_chair_selected_faculty_list(PDO $pdo): array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->query(
        "SELECT
            pcf.program_chair_faculty_id,
            pcf.faculty_id,
            pcf.faculty_classification,
            pcf.is_active,
            pcf.created_at,
            pcf.updated_at,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            f.status,
            COUNT(ev.program_chair_evaluation_id) AS evaluation_count,
            SUM(CASE WHEN ev.submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
            SUM(CASE WHEN ev.submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
            ROUND(AVG(NULLIF(ev.average_rating, 0)), 2) AS average_rating
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
         LEFT JOIN tbl_program_chair_faculty_evaluations ev
            ON ev.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
         GROUP BY
            pcf.program_chair_faculty_id,
            pcf.faculty_id,
            pcf.faculty_classification,
            pcf.is_active,
            pcf.created_at,
            pcf.updated_at,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            f.status
         ORDER BY f.status ASC, f.last_name ASC, f.first_name ASC, pcf.faculty_id ASC"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['faculty_name'] = program_chair_faculty_name_from_row($row);
    }

    return $rows;
}

function program_chair_faculty_add(PDO $pdo, int $facultyId, string $classification, int $createdByUserId): void
{
    ensure_program_chair_tables($pdo);

    if ($facultyId <= 0) {
        throw new RuntimeException('Please select a valid faculty member.');
    }

    $classification = program_chair_normalize_faculty_classification($classification);

    $statement = $pdo->prepare(
        "SELECT faculty_id
         FROM tbl_faculty
         WHERE faculty_id = :faculty_id
           AND status = 'active'
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    if (!$statement->fetch()) {
        throw new RuntimeException('The selected faculty member could not be found in the active faculty master list.');
    }

    $insertStatement = $pdo->prepare(
        "INSERT INTO tbl_program_chair_faculty (
            faculty_id,
            faculty_classification,
            is_active,
            created_by_user_management_id
         ) VALUES (
            :faculty_id,
            :faculty_classification,
            1,
            :created_by_user_management_id
         )
         ON DUPLICATE KEY UPDATE
            faculty_classification = VALUES(faculty_classification),
            is_active = 1,
            created_by_user_management_id = VALUES(created_by_user_management_id),
            updated_at = NOW()"
    );
    $insertStatement->execute([
        'faculty_id' => $facultyId,
        'faculty_classification' => $classification,
        'created_by_user_management_id' => $createdByUserId > 0 ? $createdByUserId : null,
    ]);
}

function program_chair_faculty_update_classification(PDO $pdo, int $programChairFacultyId, string $classification): void
{
    ensure_program_chair_tables($pdo);

    if ($programChairFacultyId <= 0) {
        throw new RuntimeException('Please select a valid program chair faculty list row.');
    }

    $classification = program_chair_normalize_faculty_classification($classification);

    $statement = $pdo->prepare(
        "UPDATE tbl_program_chair_faculty
         SET faculty_classification = :faculty_classification,
             updated_at = NOW()
         WHERE program_chair_faculty_id = :program_chair_faculty_id"
    );
    $statement->execute([
        'faculty_classification' => $classification,
        'program_chair_faculty_id' => $programChairFacultyId,
    ]);
}

function program_chair_faculty_remove(PDO $pdo, int $programChairFacultyId): void
{
    ensure_program_chair_tables($pdo);

    if ($programChairFacultyId <= 0) {
        throw new RuntimeException('Please select a valid program chair faculty list row.');
    }

    $statement = $pdo->prepare(
        "UPDATE tbl_program_chair_faculty
         SET is_active = 0,
             updated_at = NOW()
         WHERE program_chair_faculty_id = :program_chair_faculty_id"
    );
    $statement->execute(['program_chair_faculty_id' => $programChairFacultyId]);
}

function program_chair_evaluation_summary(PDO $pdo, int $programChairUserId): array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*)
             FROM tbl_program_chair_faculty pcf
             INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
             WHERE pcf.is_active = 1
               AND f.status = 'active') AS eligible_faculty,
            (SELECT COUNT(*)
             FROM tbl_program_chair_faculty_evaluations
             WHERE program_chair_user_management_id = :summary_user_id
               AND submission_status = 'submitted') AS submitted_evaluations,
            (SELECT COUNT(*)
             FROM tbl_program_chair_faculty_evaluations
             WHERE program_chair_user_management_id = :draft_user_id
               AND submission_status = 'draft'
               AND (
                    question_count > 0
                    OR TRIM(COALESCE(subject_text, '')) <> ''
                    OR evaluation_date IS NOT NULL
                    OR evaluation_time IS NOT NULL
                    OR TRIM(COALESCE(comment_text, '')) <> ''
               )) AS draft_evaluations,
            (SELECT ROUND(AVG(NULLIF(average_rating, 0)), 2)
             FROM tbl_program_chair_faculty_evaluations
             WHERE program_chair_user_management_id = :average_user_id
               AND submission_status = 'submitted') AS average_rating"
    );
    $statement->execute([
        'summary_user_id' => $programChairUserId,
        'draft_user_id' => $programChairUserId,
        'average_user_id' => $programChairUserId,
    ]);

    return $statement->fetch() ?: [
        'eligible_faculty' => 0,
        'submitted_evaluations' => 0,
        'draft_evaluations' => 0,
        'average_rating' => 0,
    ];
}

function program_chair_faculty_for_evaluation(PDO $pdo, int $programChairUserId, string $search = ''): array
{
    ensure_program_chair_tables($pdo);

    $sql = "SELECT
            pcf.program_chair_faculty_id,
            f.faculty_id,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            ev.program_chair_evaluation_id,
            ev.subject_id,
            ev.subject_code,
            ev.subject_text,
            ev.evaluation_date,
            ev.evaluation_time,
            ev.average_rating,
            um.full_name AS evaluator_full_name,
            um.email_address AS evaluator_email,
            CASE
                WHEN ev.submission_status = 'draft'
                 AND ev.question_count = 0
                 AND TRIM(COALESCE(ev.subject_text, '')) = ''
                 AND ev.evaluation_date IS NULL
                 AND ev.evaluation_time IS NULL
                 AND TRIM(COALESCE(ev.comment_text, '')) = ''
                THEN NULL
                ELSE ev.submission_status
            END AS submission_status,
            ev.updated_at AS evaluation_updated_at,
            ev.final_submitted_at
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
        LEFT JOIN tbl_program_chair_faculty_evaluations ev
            ON ev.faculty_id = pcf.faculty_id
           AND ev.program_chair_user_management_id = :program_chair_user_management_id
         LEFT JOIN tbl_user_management um
            ON um.user_management_id = ev.program_chair_user_management_id
         WHERE pcf.is_active = 1
           AND f.status = 'active'";

    $parameters = ['program_chair_user_management_id' => $programChairUserId];
    $search = trim($search);

    if ($search !== '') {
        $searchValue = '%' . $search . '%';
        $sql .= " AND (
            CAST(f.faculty_id AS CHAR) LIKE :faculty_search_id
            OR f.last_name LIKE :faculty_search_last_name
            OR f.first_name LIKE :faculty_search_first_name
            OR f.middle_name LIKE :faculty_search_middle_name
            OR f.ext_name LIKE :faculty_search_ext_name
            OR ev.subject_text LIKE :faculty_search_subject
        )";
        $parameters['faculty_search_id'] = $searchValue;
        $parameters['faculty_search_last_name'] = $searchValue;
        $parameters['faculty_search_first_name'] = $searchValue;
        $parameters['faculty_search_middle_name'] = $searchValue;
        $parameters['faculty_search_ext_name'] = $searchValue;
        $parameters['faculty_search_subject'] = $searchValue;
    }

    $sql .= " ORDER BY f.last_name ASC, f.first_name ASC, f.faculty_id ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['faculty_name'] = program_chair_faculty_name_from_row($row);
        $submissionStatus = (string) ($row['submission_status'] ?? '');
        $evaluatorName = trim((string) ($row['evaluator_full_name'] ?? ''));

        if ($evaluatorName === '') {
            $evaluatorName = trim((string) ($row['evaluator_email'] ?? ''));
        }

        $rows[$index]['evaluation_status_text'] = $submissionStatus === 'submitted' ? 'Evaluated' : '';
        $rows[$index]['evaluated_by'] = $submissionStatus === 'submitted' ? $evaluatorName : '';
    }

    return $rows;
}

function program_chair_recent_evaluations(PDO $pdo, int $programChairUserId, int $limit = 8): array
{
    ensure_program_chair_tables($pdo);
    $limit = max(1, min(50, $limit));

    $statement = $pdo->prepare(
        "SELECT
            ev.program_chair_evaluation_id,
            ev.faculty_id,
            ev.faculty_name,
            ev.subject_id,
            ev.subject_code,
            ev.subject_text,
            ev.evaluation_date,
            ev.evaluation_time,
            ev.average_rating,
            ev.submission_status,
            ev.final_submitted_at,
            ev.updated_at
         FROM tbl_program_chair_faculty_evaluations ev
         WHERE ev.program_chair_user_management_id = :program_chair_user_management_id
           AND (
                ev.submission_status = 'submitted'
                OR ev.question_count > 0
                OR TRIM(COALESCE(ev.subject_text, '')) <> ''
                OR ev.evaluation_date IS NOT NULL
                OR ev.evaluation_time IS NOT NULL
                OR TRIM(COALESCE(ev.comment_text, '')) <> ''
           )
         ORDER BY ev.updated_at DESC, ev.program_chair_evaluation_id DESC
         LIMIT " . $limit
    );
    $statement->execute(['program_chair_user_management_id' => $programChairUserId]);

    return $statement->fetchAll();
}

function program_chair_evaluation_context(PDO $pdo, int $facultyId, int $programChairUserId): ?array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT
            pcf.program_chair_faculty_id,
            f.faculty_id,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            f.status
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
           AND f.status = 'active'
           AND pcf.faculty_id = :faculty_id
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);
    $context = $statement->fetch();

    if (!$context) {
        return null;
    }

    $context['program_chair_user_management_id'] = $programChairUserId;
    $context['faculty_name'] = program_chair_faculty_name_from_row($context);

    return $context;
}

function program_chair_find_evaluation_by_context(PDO $pdo, int $programChairUserId, int $facultyId): ?array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT *
         FROM tbl_program_chair_faculty_evaluations
         WHERE program_chair_user_management_id = :program_chair_user_management_id
           AND faculty_id = :faculty_id
         LIMIT 1"
    );
    $statement->execute([
        'program_chair_user_management_id' => $programChairUserId,
        'faculty_id' => $facultyId,
    ]);

    $evaluation = $statement->fetch();
    return $evaluation ?: null;
}

function program_chair_create_or_get_evaluation(PDO $pdo, array $context): array
{
    ensure_program_chair_tables($pdo);

    $programChairUserId = (int) ($context['program_chair_user_management_id'] ?? 0);
    $facultyId = (int) ($context['faculty_id'] ?? 0);
    $existing = program_chair_find_evaluation_by_context($pdo, $programChairUserId, $facultyId);

    if ($existing !== null) {
        return $existing;
    }

    $statement = $pdo->prepare(
        "INSERT INTO tbl_program_chair_faculty_evaluations (
            program_chair_user_management_id,
            faculty_id,
            subject_id,
            subject_code,
            faculty_name,
            subject_text,
            comment_text,
            question_count,
            total_score,
            average_rating,
            evaluation_token,
            submission_status,
            final_submission_token
         ) VALUES (
            :program_chair_user_management_id,
            :faculty_id,
            NULL,
            '',
            :faculty_name,
            '',
            '',
            0,
            0,
            0,
            :evaluation_token,
            'draft',
            ''
         )"
    );
    $statement->execute([
        'program_chair_user_management_id' => $programChairUserId,
        'faculty_id' => $facultyId,
        'faculty_name' => $context['faculty_name'],
        'evaluation_token' => bin2hex(random_bytes(16)),
    ]);

    $created = program_chair_find_evaluation_by_context($pdo, $programChairUserId, $facultyId);

    if ($created === null) {
        throw new RuntimeException('Unable to create the program chair evaluation record.');
    }

    return $created;
}

function program_chair_find_evaluation_answers(PDO $pdo, int $programChairEvaluationId): array
{
    ensure_program_chair_tables($pdo);

    $statement = $pdo->prepare(
        "SELECT question_key, rating
         FROM tbl_program_chair_faculty_evaluation_answers
         WHERE program_chair_evaluation_id = :program_chair_evaluation_id
         ORDER BY question_order ASC"
    );
    $statement->execute(['program_chair_evaluation_id' => $programChairEvaluationId]);

    $answers = [];
    foreach ($statement->fetchAll() as $row) {
        $answers[$row['question_key']] = (int) $row['rating'];
    }

    return $answers;
}

function program_chair_evaluation_answer_value(array $answers, array $category, int $position): int
{
    $questionKey = evaluation_question_key($category, $position);

    return isset($answers[$questionKey]) ? (int) $answers[$questionKey] : 0;
}

function program_chair_evaluation_has_any_answer(array $submittedAnswers): bool
{
    foreach (program_chair_evaluation_question_bank() as $category) {
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

function program_chair_evaluation_submitted_answer_values(array $submittedAnswers): array
{
    $answers = [];
    $validScores = array_keys(program_chair_evaluation_scale_options());

    foreach (program_chair_evaluation_question_bank() as $category) {
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

function program_chair_normalize_evaluation_answers(array $submittedAnswers, bool $requireComplete = true): array
{
    $normalized = [];
    $validScores = array_keys(program_chair_evaluation_scale_options());

    foreach (program_chair_evaluation_question_bank() as $category) {
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
                throw new RuntimeException('Please provide a rating for every supervisory evaluation item.');
            }

            $normalized[] = [
                'category_key' => $category['key'],
                'category_title' => $category['title'],
                'question_key' => $questionKey,
                'question_order' => count($normalized) + 1,
                'question_text' => $questionText,
                'rating' => $rating,
            ];
            $position++;
        }
    }

    return $normalized;
}

function program_chair_normalize_evaluation_date(string $dateValue, bool $required): ?string
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

function program_chair_normalize_evaluation_time(string $timeValue, bool $required): ?string
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

function program_chair_format_evaluation_time($timeValue): string
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

function program_chair_save_evaluation_submission(
    PDO $pdo,
    array $evaluation,
    array $context,
    array $submittedAnswers,
    string $subjectKey,
    string $evaluationDate,
    string $evaluationTime,
    string $commentText,
    string $status
): array {
    ensure_program_chair_tables($pdo);

    $isSubmitted = $status === 'submitted';
    $subjectPayload = program_chair_subject_payload($pdo, $subjectKey, $isSubmitted);
    $subjectText = $subjectPayload['subject_text'];
    $normalizedDate = program_chair_normalize_evaluation_date($evaluationDate, $isSubmitted);
    $normalizedTime = program_chair_normalize_evaluation_time($evaluationTime, $isSubmitted);

    $normalizedAnswers = program_chair_normalize_evaluation_answers($submittedAnswers, $isSubmitted);
    $totalScore = 0;
    foreach ($normalizedAnswers as $answer) {
        $totalScore += (int) $answer['rating'];
    }

    $questionCount = count($normalizedAnswers);
    $averageRating = $questionCount > 0 ? round($totalScore / $questionCount, 2) : 0;

    $pdo->beginTransaction();

    try {
        $updateStatement = $pdo->prepare(
            "UPDATE tbl_program_chair_faculty_evaluations
             SET faculty_name = :faculty_name,
                 subject_id = :subject_id,
                 subject_code = :subject_code,
                 subject_text = :subject_text,
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
             WHERE program_chair_evaluation_id = :program_chair_evaluation_id"
        );
        $updateStatement->execute([
            'faculty_name' => $context['faculty_name'],
            'subject_id' => $subjectPayload['subject_id'],
            'subject_code' => $subjectPayload['subject_code'],
            'subject_text' => $subjectText,
            'evaluation_date' => $normalizedDate,
            'evaluation_time' => $normalizedTime,
            'comment_text' => trim($commentText),
            'question_count' => $questionCount,
            'total_score' => $totalScore,
            'average_rating' => $averageRating,
            'submission_status' => $status,
            'final_submission_token' => $isSubmitted ? bin2hex(random_bytes(16)) : '',
            'final_submitted_at' => $isSubmitted ? date('Y-m-d H:i:s') : null,
            'program_chair_evaluation_id' => $evaluation['program_chair_evaluation_id'],
        ]);

        $deleteStatement = $pdo->prepare(
            "DELETE FROM tbl_program_chair_faculty_evaluation_answers
             WHERE program_chair_evaluation_id = :program_chair_evaluation_id"
        );
        $deleteStatement->execute([
            'program_chair_evaluation_id' => $evaluation['program_chair_evaluation_id'],
        ]);

        $insertStatement = $pdo->prepare(
            "INSERT INTO tbl_program_chair_faculty_evaluation_answers (
                program_chair_evaluation_id,
                category_key,
                category_title,
                question_key,
                question_order,
                question_text,
                rating
             ) VALUES (
                :program_chair_evaluation_id,
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
                'program_chair_evaluation_id' => $evaluation['program_chair_evaluation_id'],
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

    $saved = program_chair_find_evaluation_by_context(
        $pdo,
        (int) ($context['program_chair_user_management_id'] ?? 0),
        (int) ($context['faculty_id'] ?? 0)
    );

    if ($saved === null) {
        throw new RuntimeException('Unable to reload the saved supervisory evaluation.');
    }

    return $saved;
}
