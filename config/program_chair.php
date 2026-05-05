<?php
declare(strict_types=1);

function program_chair_evaluation_scale_options(): array
{
    return [
        5 => [
            'label' => 'Always manifested',
            'description' => 'Evident in nearly all relevant situations (91-100% of instances).',
        ],
        4 => [
            'label' => 'Often manifested',
            'description' => 'Evident most of the time, with occasional lapses (61-90%).',
        ],
        3 => [
            'label' => 'Sometimes manifested',
            'description' => 'Evident about half the time (31-60%).',
        ],
        2 => [
            'label' => 'Seldom manifested',
            'description' => 'Rarely evident in relevant instances (11-30%).',
        ],
        1 => [
            'label' => 'Never/Rarely manifested',
            'description' => 'Almost never evident, with only isolated cases (0-10%).',
        ],
    ];
}

function program_chair_latest_instrument_version(): string
{
    return 'SEF_2026_15_ITEM';
}

function program_chair_legacy_instrument_version(): string
{
    return 'SEF_LEGACY_20_ITEM';
}

function program_chair_evaluation_question_bank(?string $instrumentVersion = null): array
{
    $instrumentVersion = trim((string) ($instrumentVersion ?? program_chair_latest_instrument_version()));

    if ($instrumentVersion === program_chair_legacy_instrument_version()) {
        return program_chair_legacy_evaluation_question_bank();
    }

    return program_chair_sef_2026_question_bank();
}

function program_chair_sef_2026_question_bank(): array
{
    return [
        [
            'key' => 'management_of_teaching_and_learning',
            'code' => 'mtl',
            'title' => 'MANAGEMENT OF TEACHING AND LEARNING',
            'questions' => [
                'Comes to class on time.',
                'Submits updated syllabus, grade sheets, and other required reports on time.',
                'Maximizes the allocated time/learning hours effectively.',
                'Provides appropriate learning activities that facilitate critical thinking and creativity of students.',
                'Guides students to learn on their own, reflect on new ideas and experiences, and make decisions in accomplishing given tasks.',
                'Communicates constructive feedback to students for their academic growth.',
            ],
            'verification' => [
                1 => ['Daily Time Record', 'Faculty schedule and timetable', 'Informal interview with students'],
                2 => ['Documents submission log', 'Submission receipts or acknowledgment emails'],
                3 => ['Class schedules and timetables', 'LMS logs', 'Informal interview with students'],
                4 => ['Course syllabus', 'Learning plan', 'Classroom observation', 'Informal interview with students', 'LMS logs'],
                5 => ['Course syllabus', 'Learning plan', 'Student work samples', 'Classroom observation', 'LMS logs', 'Informal interview with students', 'Faculty consultation log'],
                6 => ['Graded student work with feedback', 'Faculty consultation log', 'Informal interview with students', 'Emails or official correspondence', 'LMS logs'],
            ],
        ],
        [
            'key' => 'content_knowledge_pedagogy_and_technology',
            'code' => 'ckpt',
            'title' => 'CONTENT KNOWLEDGE, PEDAGOGY AND TECHNOLOGY',
            'questions' => [
                'Demonstrates extensive and broad knowledge of the subject/course.',
                'Simplifies complex ideas in the lesson for ease of understanding.',
                'Integrates contemporary issues in the discipline and/or daily life activities in the syllabus.',
                'Promotes active learning and student engagement by using appropriate teaching and learning resources including ICT tools and platforms.',
                'Uses appropriate assessments (projects, exams, quizzes, assignments, etc.) aligned with the learning outcomes.',
            ],
            'verification' => [
                1 => ['Course syllabus', 'Learning plan', 'Instructional materials developed by the faculty', 'Informal interview with students', 'Mentorship or thesis/dissertation advisory records'],
                2 => ['Learning plan', 'Course syllabus', 'Classroom observation', 'Informal interview with students', 'Lecture notes and presentations', 'LMS logs'],
                3 => ['Course syllabus', 'Learning plan', 'Classroom observation', 'Informal interview with students', 'LMS logs', 'Instructional materials developed by the faculty', 'Participation in conferences, webinars, and training'],
                4 => ['Course syllabus', 'Learning plan', 'Classroom observation', 'Informal interview with students', 'LMS logs', 'Multimedia lecture materials', 'Student work samples'],
                5 => ['Course syllabus', 'Learning plan', 'Informal interview with students', 'Assessment tools and rubrics', 'Exam and quiz samples', 'Graded student work samples', 'LMS records'],
            ],
        ],
        [
            'key' => 'commitment_and_transparency',
            'code' => 'ct',
            'title' => 'COMMITMENT AND TRANSPARENCY',
            'questions' => [
                'Recognizes and values the unique diversity and individual differences among students.',
                'Assists students with their learning challenges during consultation hours.',
                'Provides immediate feedback on student outputs and performance.',
                'Provides transparent and clear criteria in rating student\'s performance.',
            ],
            'verification' => [
                1 => ['Course syllabus', 'Learning plan', 'Instructional materials developed by the faculty', 'Classroom observation', 'Informal interview with students'],
                2 => ['Course syllabus', 'Faculty consultation log', 'Advisory records', 'SMS logs', 'Emails or official correspondence'],
                3 => ['Graded student work samples', 'Assessment tools and rubrics', 'Informal interview with students', 'LMS logs', 'Emails or official correspondence', 'Faculty consultation log', 'Advising reports'],
                4 => ['Course syllabus', 'Assessment tools and rubrics', 'Informal interview with students', 'LMS records', 'Grade sheets and records'],
            ],
        ],
    ];
}

function program_chair_legacy_evaluation_question_bank(): array
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

function program_chair_evaluation_instrument_version_for(?array $evaluation, array $answers = []): string
{
    $savedVersion = trim((string) ($evaluation['instrument_version'] ?? ''));
    if ($savedVersion !== '') {
        return $savedVersion;
    }

    if ($answers !== []) {
        foreach (array_keys($answers) as $questionKey) {
            if (preg_match('/^(mtl|ckpt|ct)_\d+$/', (string) $questionKey)) {
                return program_chair_latest_instrument_version();
            }
        }

        return program_chair_legacy_instrument_version();
    }

    $questionCount = (int) ($evaluation['question_count'] ?? 0);
    if ($questionCount >= 20) {
        return program_chair_legacy_instrument_version();
    }

    return program_chair_latest_instrument_version();
}

function program_chair_all_evaluation_categories(): array
{
    $categories = [];

    foreach ([program_chair_latest_instrument_version(), program_chair_legacy_instrument_version()] as $instrumentVersion) {
        foreach (program_chair_evaluation_question_bank($instrumentVersion) as $category) {
            $categoryKey = (string) ($category['key'] ?? '');
            if ($categoryKey === '' || isset($categories[$categoryKey])) {
                continue;
            }

            $categories[$categoryKey] = $category;
        }
    }

    return $categories;
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
            faculty_program_code VARCHAR(20) NOT NULL DEFAULT '',
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
        "CREATE TABLE IF NOT EXISTS tbl_program_chair_user_programs (
            program_chair_user_program_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            program_chair_user_management_id INT UNSIGNED NOT NULL,
            program_code VARCHAR(20) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by_user_management_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_program_chair_user_program (program_chair_user_management_id),
            KEY idx_program_chair_user_program_code (program_code, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

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

    program_chair_recalculate_saved_summaries($pdo);

    $initialized = true;
}

function program_chair_faculty_classification_options(): array
{
    return [
        'REGULAR' => 'Regular Faculty',
        'CONTRACT OF SERVICE' => 'Contract of Service Faculty',
    ];
}

function program_chair_program_codes(): array
{
    return ['BSIT', 'BSIS', 'BSCS'];
}

function program_chair_program_options(PDO $pdo): array
{
    $codes = program_chair_program_codes();
    $options = [];

    foreach ($codes as $code) {
        $options[$code] = [
            'program_code' => $code,
            'program_label' => $code,
            'program_name' => '',
        ];
    }

    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $statement = $pdo->prepare(
        "SELECT program_code, program_name
         FROM tbl_program
         WHERE status = 'active'
           AND UPPER(program_code) IN (" . $placeholders . ")
         ORDER BY FIELD(UPPER(program_code), " . implode(',', array_fill(0, count($codes), '?')) . "), program_id ASC"
    );
    $statement->execute(array_merge($codes, $codes));

    foreach ($statement->fetchAll() as $row) {
        $code = program_chair_normalize_program_code((string) ($row['program_code'] ?? ''), true);
        if ($code === '' || !isset($options[$code]) || $options[$code]['program_name'] !== '') {
            continue;
        }

        $name = trim((string) ($row['program_name'] ?? ''));
        $options[$code]['program_name'] = $name;
        $options[$code]['program_label'] = $name !== '' ? $code . ' - ' . $name : $code;
    }

    return $options;
}

function program_chair_normalize_program_code(string $programCode, bool $allowEmpty = false): string
{
    $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $programCode) ?? ''));

    if ($normalized === '' && $allowEmpty) {
        return '';
    }

    if (!in_array($normalized, program_chair_program_codes(), true)) {
        throw new RuntimeException('Please select a valid program.');
    }

    return $normalized;
}

function program_chair_program_label(string $programCode): string
{
    $normalized = program_chair_normalize_program_code($programCode, true);

    return $normalized !== '' ? $normalized : 'Not set';
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

    if (!isset($columns['faculty_program_code'])) {
        program_chair_add_column_if_missing(
            $pdo,
            "ALTER TABLE tbl_program_chair_faculty
             ADD COLUMN faculty_program_code VARCHAR(20) NOT NULL DEFAULT '' AFTER faculty_classification"
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

    if (!isset($columns['instrument_version'])) {
        program_chair_add_column_if_missing(
            $pdo,
            "ALTER TABLE tbl_program_chair_faculty_evaluations
             ADD COLUMN instrument_version VARCHAR(40) NOT NULL DEFAULT '' AFTER subject_text"
        );
    }
}

function program_chair_recalculate_saved_summaries(PDO $pdo): void
{
    static $synced = false;

    if ($synced) {
        return;
    }

    $pdo->exec(
        "UPDATE tbl_program_chair_faculty_evaluations ev
         INNER JOIN (
            SELECT
                program_chair_evaluation_id,
                COUNT(*) AS question_count,
                COALESCE(SUM(rating), 0) AS total_score,
                ROUND(AVG(rating), 2) AS average_rating
            FROM tbl_program_chair_faculty_evaluation_answers
            WHERE rating BETWEEN 1 AND 5
            GROUP BY program_chair_evaluation_id
         ) ans ON ans.program_chair_evaluation_id = ev.program_chair_evaluation_id
         SET ev.question_count = ans.question_count,
             ev.total_score = ans.total_score,
             ev.average_rating = ans.average_rating
         WHERE ev.question_count <> ans.question_count
            OR ev.total_score <> ans.total_score
            OR ev.average_rating <> ans.average_rating"
    );

    $pdo->exec(
        "UPDATE tbl_program_chair_faculty_evaluations ev
         INNER JOIN (
            SELECT
                program_chair_evaluation_id,
                COUNT(*) AS answer_count,
                SUM(CASE WHEN category_key IN ('commitment', 'knowledge_of_subject_matter', 'teaching_for_independent_learning', 'management_of_learning') THEN 1 ELSE 0 END) AS legacy_answer_count,
                SUM(CASE WHEN category_key IN ('management_of_teaching_and_learning', 'content_knowledge_pedagogy_and_technology', 'commitment_and_transparency') THEN 1 ELSE 0 END) AS latest_answer_count
            FROM tbl_program_chair_faculty_evaluation_answers
            GROUP BY program_chair_evaluation_id
         ) ans ON ans.program_chair_evaluation_id = ev.program_chair_evaluation_id
         SET ev.instrument_version = CASE
             WHEN ans.legacy_answer_count > 0 THEN '" . program_chair_legacy_instrument_version() . "'
             WHEN ans.latest_answer_count > 0 THEN '" . program_chair_latest_instrument_version() . "'
             WHEN ans.answer_count >= 20 THEN '" . program_chair_legacy_instrument_version() . "'
             ELSE '" . program_chair_latest_instrument_version() . "'
         END
         WHERE ev.instrument_version = ''"
    );

    $synced = true;
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

function program_chair_subject_options(PDO $pdo, ?int $facultyId = null): array
{
    $facultyId = $facultyId !== null ? max(0, (int) $facultyId) : 0;
    $facultyCondition = $facultyId > 0 ? ' AND faculty_id = :faculty_id' : '';

    $statement = $pdo->prepare(
        "SELECT
            MIN(NULLIF(subject_id, 0)) AS sub_id,
            TRIM(subject_code) AS sub_code,
            TRIM(descriptive_title) AS sub_description
         FROM tbl_student_management_enrolled_subjects
         WHERE (
                TRIM(COALESCE(subject_code, '')) <> ''
                OR TRIM(COALESCE(descriptive_title, '')) <> ''
           )
           " . $facultyCondition . "
         GROUP BY
            TRIM(subject_code),
            TRIM(descriptive_title)
         ORDER BY sub_code ASC, sub_description ASC"
    );
    $statement->execute($facultyId > 0 ? ['faculty_id' => $facultyId] : []);

    $subjects = $statement->fetchAll();
    foreach ($subjects as $index => $subject) {
        $subjects[$index]['subject_label'] = program_chair_subject_label($subject);
        $subjects[$index]['subject_key'] = program_chair_subject_key($subject);
    }

    return $subjects;
}

function program_chair_subject_find_by_key(PDO $pdo, string $subjectKey, ?int $facultyId = null): ?array
{
    $subjectKey = trim($subjectKey);

    if ($subjectKey === '') {
        return null;
    }

    foreach (program_chair_subject_options($pdo, $facultyId) as $subject) {
        if ((string) ($subject['subject_key'] ?? '') === $subjectKey) {
            return $subject;
        }
    }

    return null;
}

function program_chair_subject_key_for_saved_evaluation(PDO $pdo, array $evaluation, ?int $facultyId = null): string
{
    $subjectId = (int) ($evaluation['subject_id'] ?? 0);
    $subjectCode = trim((string) ($evaluation['subject_code'] ?? ''));
    $subjectText = trim((string) ($evaluation['subject_text'] ?? ''));

    foreach (program_chair_subject_options($pdo, $facultyId) as $subject) {
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

function program_chair_subject_payload(PDO $pdo, string $subjectKey, bool $required, ?int $facultyId = null): array
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

    $subject = program_chair_subject_find_by_key($pdo, $subjectKey, $facultyId);

    if ($subject === null) {
        throw new RuntimeException('The selected subject could not be found for this faculty member.');
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
    ensure_role_evaluation_tables($pdo);

    $statement = $pdo->query(
        "SELECT
            pcf.program_chair_faculty_id,
            pcf.faculty_id,
            pcf.faculty_classification,
            pcf.faculty_program_code,
            pcf.is_active,
            pcf.created_at,
            pcf.updated_at,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            f.status,
            COALESCE(student_eval.evaluation_count, 0) AS student_evaluation_count,
            COALESCE(student_eval.submitted_count, 0) AS student_submitted_count,
            COALESCE(student_eval.draft_count, 0) AS student_draft_count,
            student_eval.average_rating AS student_average_rating,
            COALESCE(pc_eval.evaluation_count, 0) AS program_chair_evaluation_count,
            COALESCE(pc_eval.submitted_count, 0) AS program_chair_submitted_count,
            COALESCE(pc_eval.draft_count, 0) AS program_chair_draft_count,
            COALESCE(pc_eval.average_total, 0) AS program_chair_average_total,
            COALESCE(pc_eval.average_count, 0) AS program_chair_average_count
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
         LEFT JOIN (
            SELECT
                faculty_id,
                COUNT(*) AS evaluation_count,
                SUM(CASE WHEN submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
                SUM(CASE WHEN submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                ROUND(AVG(NULLIF(average_rating, 0)), 2) AS average_rating
            FROM tbl_student_faculty_evaluations
            WHERE faculty_id <> 0
            GROUP BY faculty_id
         ) student_eval
            ON student_eval.faculty_id = pcf.faculty_id
         LEFT JOIN (
            SELECT
                faculty_id,
                COUNT(*) AS evaluation_count,
                SUM(CASE WHEN submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
                SUM(CASE WHEN submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN average_rating > 0 THEN average_rating ELSE 0 END) AS average_total,
                SUM(CASE WHEN average_rating > 0 THEN 1 ELSE 0 END) AS average_count
            FROM tbl_program_chair_faculty_evaluations
            WHERE faculty_id <> 0
            GROUP BY faculty_id
         ) pc_eval
            ON pc_eval.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
         ORDER BY f.status ASC, f.last_name ASC, f.first_name ASC, pcf.faculty_id ASC"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['faculty_name'] = program_chair_faculty_name_from_row($row);
        $rows[$index]['faculty_program_code'] = program_chair_normalize_program_code((string) ($row['faculty_program_code'] ?? ''), true);
        $rows[$index]['faculty_program_label'] = program_chair_program_label((string) ($row['faculty_program_code'] ?? ''));
        $roleCounts = program_chair_faculty_role_supervisory_counts($pdo, $row);
        $rows[$index]['supervisory_evaluation_count'] = (int) ($row['program_chair_evaluation_count'] ?? 0) + $roleCounts['evaluation_count'];
        $rows[$index]['supervisory_submitted_count'] = (int) ($row['program_chair_submitted_count'] ?? 0) + $roleCounts['submitted_count'];
        $rows[$index]['supervisory_draft_count'] = (int) ($row['program_chair_draft_count'] ?? 0) + $roleCounts['draft_count'];
        $supervisoryAverageTotal = (float) ($row['program_chair_average_total'] ?? 0) + $roleCounts['average_total'];
        $supervisoryAverageCount = (int) ($row['program_chair_average_count'] ?? 0) + $roleCounts['average_count'];
        $rows[$index]['supervisory_average_rating'] = $supervisoryAverageCount > 0
            ? round($supervisoryAverageTotal / $supervisoryAverageCount, 2)
            : null;
    }

    return $rows;
}

function program_chair_user_program_assignments(PDO $pdo): array
{
    ensure_program_chair_tables($pdo);
    ensure_user_management_table($pdo);

    $statement = $pdo->query(
        "SELECT
            um.user_management_id,
            um.full_name,
            um.email_address,
            um.account_role,
            um.is_active,
            assignment.program_code
         FROM tbl_user_management um
         LEFT JOIN tbl_program_chair_user_programs assignment
            ON assignment.program_chair_user_management_id = um.user_management_id
           AND assignment.is_active = 1
         WHERE um.account_role = 'program_chair'
           AND um.is_active = 1
         ORDER BY um.full_name ASC, um.email_address ASC"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['display_name'] = role_evaluation_user_display_name($row);
        $rows[$index]['program_code'] = program_chair_normalize_program_code((string) ($row['program_code'] ?? ''), true);
        $rows[$index]['program_label'] = program_chair_program_label((string) ($row['program_code'] ?? ''));
    }

    return $rows;
}

function program_chair_user_program_code(PDO $pdo, int $programChairUserManagementId): string
{
    ensure_program_chair_tables($pdo);

    if ($programChairUserManagementId <= 0) {
        return '';
    }

    $statement = $pdo->prepare(
        "SELECT program_code
         FROM tbl_program_chair_user_programs
         WHERE program_chair_user_management_id = :program_chair_user_management_id
           AND is_active = 1
         LIMIT 1"
    );
    $statement->execute(['program_chair_user_management_id' => $programChairUserManagementId]);

    return program_chair_normalize_program_code((string) ($statement->fetchColumn() ?: ''), true);
}

function program_chair_faculty_program_code(PDO $pdo, int $facultyId): string
{
    ensure_program_chair_tables($pdo);

    if ($facultyId <= 0) {
        return '';
    }

    $statement = $pdo->prepare(
        "SELECT faculty_program_code
         FROM tbl_program_chair_faculty
         WHERE faculty_id = :faculty_id
           AND is_active = 1
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    return program_chair_normalize_program_code((string) ($statement->fetchColumn() ?: ''), true);
}

function program_chair_program_signatory(PDO $pdo, string $programCode): ?array
{
    ensure_program_chair_tables($pdo);

    $programCode = program_chair_normalize_program_code($programCode, true);
    if ($programCode === '') {
        return null;
    }

    $statement = $pdo->prepare(
        "SELECT um.user_management_id, um.full_name, um.email_address
         FROM tbl_program_chair_user_programs assignment
         INNER JOIN tbl_user_management um
            ON um.user_management_id = assignment.program_chair_user_management_id
         WHERE assignment.program_code = :program_code
           AND assignment.is_active = 1
           AND um.account_role = 'program_chair'
           AND um.is_active = 1
         ORDER BY assignment.updated_at DESC, assignment.program_chair_user_program_id DESC
         LIMIT 1"
    );
    $statement->execute(['program_code' => $programCode]);
    $row = $statement->fetch();

    if (!$row) {
        return null;
    }

    return [
        'user_id' => (int) ($row['user_management_id'] ?? 0),
        'name' => role_evaluation_user_display_name([
            'full_name' => (string) ($row['full_name'] ?? ''),
            'email_address' => (string) ($row['email_address'] ?? ''),
        ]),
        'program_code' => $programCode,
    ];
}

function program_chair_faculty_role_supervisory_counts(PDO $pdo, array $faculty): array
{
    $programChairUserIds = [];
    $facultyId = (int) ($faculty['faculty_id'] ?? 0);

    if ($facultyId <= 0) {
        return [
            'evaluation_count' => 0,
            'submitted_count' => 0,
            'draft_count' => 0,
            'average_total' => 0.0,
            'average_count' => 0,
        ];
    }

    $statement = $pdo->prepare(
        "SELECT DISTINCT program_chair_user_management_id
         FROM tbl_program_chair_faculty_evaluations
         WHERE faculty_id = :faculty_id
           AND program_chair_user_management_id > 0"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $programChairUserId) {
        $programChairUserIds[(int) $programChairUserId] = true;
    }

    $facultyUser = individual_faculty_performance_faculty_user_management($pdo, $faculty);
    $facultyUserRole = $facultyUser !== null
        ? user_management_normalize_role((string) ($facultyUser['account_role'] ?? ''))
        : '';
    $facultyUserId = $facultyUser !== null ? (int) ($facultyUser['user_management_id'] ?? 0) : 0;

    if ($facultyUserRole === 'program_chair' && $facultyUserId > 0) {
        $programChairUserIds[$facultyUserId] = true;
    }

    $counts = [
        'evaluation_count' => 0,
        'submitted_count' => 0,
        'draft_count' => 0,
        'average_total' => 0.0,
        'average_count' => 0,
    ];

    $deanUserIds = [];
    foreach (program_chair_role_evaluation_count_rows($pdo, 'dean', 'program_chair', array_keys($programChairUserIds)) as $row) {
        $counts['evaluation_count'] += (int) ($row['evaluation_count'] ?? 0);
        $counts['submitted_count'] += (int) ($row['submitted_count'] ?? 0);
        $counts['draft_count'] += (int) ($row['draft_count'] ?? 0);
        $counts['average_total'] += (float) ($row['average_total'] ?? 0);
        $counts['average_count'] += (int) ($row['average_count'] ?? 0);

        $deanUserId = (int) ($row['evaluator_user_management_id'] ?? 0);
        if ($deanUserId > 0) {
            $deanUserIds[$deanUserId] = true;
        }
    }

    if ($facultyUserRole === 'dean' && $facultyUserId > 0) {
        $deanUserIds[$facultyUserId] = true;
    }

    foreach (individual_faculty_performance_dean_user_ids_for_program_chairs($pdo, array_keys($programChairUserIds)) as $deanUserId) {
        $deanUserIds[$deanUserId] = true;
    }

    foreach (program_chair_role_evaluation_count_rows($pdo, 'director', 'dean', array_keys($deanUserIds)) as $row) {
        $counts['evaluation_count'] += (int) ($row['evaluation_count'] ?? 0);
        $counts['submitted_count'] += (int) ($row['submitted_count'] ?? 0);
        $counts['draft_count'] += (int) ($row['draft_count'] ?? 0);
        $counts['average_total'] += (float) ($row['average_total'] ?? 0);
        $counts['average_count'] += (int) ($row['average_count'] ?? 0);
    }

    return $counts;
}

function program_chair_role_evaluation_count_rows(PDO $pdo, string $evaluatorRole, string $targetRole, array $targetUserIds): array
{
    $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds))));
    if ($targetUserIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
    $statement = $pdo->prepare(
        "SELECT
            evaluator_user_management_id,
            COUNT(*) AS evaluation_count,
            SUM(CASE WHEN submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
            SUM(CASE WHEN submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
            SUM(CASE WHEN average_rating > 0 THEN average_rating ELSE 0 END) AS average_total,
            SUM(CASE WHEN average_rating > 0 THEN 1 ELSE 0 END) AS average_count
         FROM tbl_role_evaluations
         WHERE evaluator_role = ?
           AND evaluatee_role = ?
           AND evaluatee_user_management_id IN (" . $placeholders . ")
         GROUP BY evaluator_user_management_id"
    );
    $statement->execute(array_merge([$evaluatorRole, $targetRole], $targetUserIds));

    return $statement->fetchAll();
}

function program_chair_admin_faculty_evaluation_details(PDO $pdo, int $facultyId, string $evaluationType): array
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);
    ensure_role_evaluation_tables($pdo);

    if ($facultyId <= 0) {
        return [];
    }

    if ($evaluationType === 'student') {
        $statement = $pdo->prepare(
            "SELECT
                ev.evaluation_id,
                ev.student_number,
                ev.subject_code,
                ev.subject_summary,
                ev.term_label,
                ev.comment_text,
                ev.question_count,
                ev.total_score,
                ev.average_rating,
                ev.submission_status,
                ev.updated_at,
                ev.completed_at,
                CONCAT_WS(' ', sm.first_name, sm.middle_name, sm.last_name, sm.suffix_name) AS student_name,
                sm.email_address AS student_email
             FROM tbl_student_faculty_evaluations ev
             LEFT JOIN tbl_student_management sm
                ON sm.student_id = ev.student_id
             WHERE ev.faculty_id = :faculty_id
               AND ev.student_enrollment_id IS NOT NULL
             ORDER BY ev.updated_at DESC, ev.evaluation_id DESC"
        );
        $statement->execute(['faculty_id' => $facultyId]);

        $rows = [];
        foreach ($statement->fetchAll() as $row) {
            $rows[] = [
                'type' => 'student',
                'id' => (int) ($row['evaluation_id'] ?? 0),
                'actor' => trim((string) ($row['student_name'] ?? '')) !== ''
                    ? trim((string) ($row['student_name'] ?? ''))
                    : (string) ($row['student_number'] ?? 'Student'),
                'actorMeta' => trim((string) ($row['student_email'] ?? '')),
                'subject' => trim((string) ($row['subject_code'] ?? '')) !== ''
                    ? trim((string) ($row['subject_code'] ?? '') . ' - ' . (string) ($row['subject_summary'] ?? ''))
                    : (string) ($row['subject_summary'] ?? ''),
                'term' => (string) ($row['term_label'] ?? ''),
                'date' => '',
                'time' => '',
                'comment' => (string) ($row['comment_text'] ?? ''),
                'questionCount' => (int) ($row['question_count'] ?? 0),
                'totalScore' => (int) ($row['total_score'] ?? 0),
                'averageRating' => (float) ($row['average_rating'] ?? 0),
                'status' => (string) ($row['submission_status'] ?? 'draft'),
                'updatedAt' => (string) ($row['updated_at'] ?? $row['completed_at'] ?? ''),
                'editUrl' => '',
            ];
        }

        return $rows;
    }

    $statement = $pdo->prepare(
        "SELECT
            ev.program_chair_evaluation_id,
            ev.program_chair_user_management_id,
            ev.subject_code,
            ev.subject_text,
            ev.evaluation_date,
            ev.evaluation_time,
            ev.comment_text,
            ev.question_count,
            ev.total_score,
            ev.average_rating,
            ev.submission_status,
            ev.updated_at,
            ev.completed_at,
            um.full_name AS evaluator_name,
            um.email_address AS evaluator_email
         FROM tbl_program_chair_faculty_evaluations ev
         LEFT JOIN tbl_user_management um
            ON um.user_management_id = ev.program_chair_user_management_id
         WHERE ev.faculty_id = :faculty_id
         ORDER BY ev.updated_at DESC, ev.program_chair_evaluation_id DESC"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    $rows = [];
    $programChairUserIds = [];
    foreach ($statement->fetchAll() as $row) {
        $programChairUserId = (int) ($row['program_chair_user_management_id'] ?? 0);
        if ($programChairUserId > 0) {
            $programChairUserIds[$programChairUserId] = true;
        }

        $evaluatorName = role_evaluation_user_display_name([
            'full_name' => (string) ($row['evaluator_name'] ?? ''),
            'email_address' => (string) ($row['evaluator_email'] ?? ''),
        ]);

        $rows[] = [
            'type' => 'supervisory',
            'id' => (int) ($row['program_chair_evaluation_id'] ?? 0),
            'actor' => $evaluatorName !== '' ? $evaluatorName : 'Program Chair',
            'actorMeta' => (string) ($row['evaluator_email'] ?? ''),
            'subject' => trim((string) ($row['subject_code'] ?? '')) !== ''
                ? trim((string) ($row['subject_code'] ?? '') . ' - ' . (string) ($row['subject_text'] ?? ''))
                : (string) ($row['subject_text'] ?? ''),
            'term' => '',
            'date' => (string) ($row['evaluation_date'] ?? ''),
            'time' => substr((string) ($row['evaluation_time'] ?? ''), 0, 5),
            'comment' => (string) ($row['comment_text'] ?? ''),
            'questionCount' => (int) ($row['question_count'] ?? 0),
            'totalScore' => (int) ($row['total_score'] ?? 0),
            'averageRating' => (float) ($row['average_rating'] ?? 0),
            'status' => (string) ($row['submission_status'] ?? 'draft'),
            'updatedAt' => (string) ($row['updated_at'] ?? $row['completed_at'] ?? ''),
            'editUrl' => base_url('programchair/evaluate.php?faculty_id=' . (int) $facultyId),
        ];
    }

    $faculty = individual_faculty_performance_faculty($pdo, $facultyId);
    $facultyUser = $faculty !== null ? individual_faculty_performance_faculty_user_management($pdo, $faculty) : null;
    $facultyUserRole = $facultyUser !== null
        ? user_management_normalize_role((string) ($facultyUser['account_role'] ?? ''))
        : '';
    $facultyUserId = $facultyUser !== null ? (int) ($facultyUser['user_management_id'] ?? 0) : 0;

    if ($facultyUserRole === 'program_chair' && $facultyUserId > 0) {
        $programChairUserIds[$facultyUserId] = true;
    }

    $deanUserIds = [];
    if ($facultyUserRole === 'dean' && $facultyUserId > 0) {
        $deanUserIds[$facultyUserId] = true;
    }

    foreach (program_chair_admin_role_evaluation_detail_rows($pdo, 'dean', 'program_chair', array_keys($programChairUserIds), 'Dean') as $row) {
        $rows[] = $row;
        $deanUserId = (int) ($row['evaluatorUserId'] ?? 0);
        if ($deanUserId > 0) {
            $deanUserIds[$deanUserId] = true;
        }
    }

    foreach (individual_faculty_performance_dean_user_ids_for_program_chairs($pdo, array_keys($programChairUserIds)) as $deanUserId) {
        $deanUserIds[$deanUserId] = true;
    }

    foreach (program_chair_admin_role_evaluation_detail_rows($pdo, 'director', 'dean', array_keys($deanUserIds), 'Director') as $row) {
        $rows[] = $row;
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string) ($right['updatedAt'] ?? ''), (string) ($left['updatedAt'] ?? ''));
    });

    return $rows;
}

function program_chair_admin_role_evaluation_detail_rows(PDO $pdo, string $evaluatorRole, string $targetRole, array $targetUserIds, string $roleLabel): array
{
    $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds))));
    if ($targetUserIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
    $statement = $pdo->prepare(
        "SELECT
            ev.role_evaluation_id,
            ev.evaluator_user_management_id,
            ev.evaluatee_user_management_id,
            ev.evaluatee_role,
            ev.evaluatee_name,
            ev.comment_text,
            ev.question_count,
            ev.total_score,
            ev.average_rating,
            ev.submission_status,
            ev.updated_at,
            ev.completed_at,
            evaluator.full_name AS evaluator_name,
            evaluator.email_address AS evaluator_email,
            evaluatee.full_name AS evaluatee_user_name,
            evaluatee.email_address AS evaluatee_email
         FROM tbl_role_evaluations ev
         LEFT JOIN tbl_user_management evaluator
            ON evaluator.user_management_id = ev.evaluator_user_management_id
         LEFT JOIN tbl_user_management evaluatee
            ON evaluatee.user_management_id = ev.evaluatee_user_management_id
         WHERE ev.evaluator_role = ?
           AND ev.evaluatee_role = ?
           AND ev.evaluatee_user_management_id IN (" . $placeholders . ")
         ORDER BY ev.updated_at DESC, ev.role_evaluation_id DESC"
    );
    $statement->execute(array_merge([$evaluatorRole, $targetRole], $targetUserIds));

    $rows = [];
    foreach ($statement->fetchAll() as $row) {
        $evaluatorName = role_evaluation_user_display_name([
            'full_name' => (string) ($row['evaluator_name'] ?? ''),
            'email_address' => (string) ($row['evaluator_email'] ?? ''),
        ]);
        $evaluateeName = role_evaluation_user_display_name([
            'full_name' => (string) ($row['evaluatee_user_name'] ?? $row['evaluatee_name'] ?? ''),
            'email_address' => (string) ($row['evaluatee_email'] ?? ''),
        ]);

        $rows[] = [
            'type' => 'role',
            'id' => (int) ($row['role_evaluation_id'] ?? 0),
            'evaluatorUserId' => (int) ($row['evaluator_user_management_id'] ?? 0),
            'actor' => $evaluatorName !== '' ? $evaluatorName : $roleLabel,
            'actorMeta' => (string) ($row['evaluator_email'] ?? ''),
            'subject' => $roleLabel . ' evaluation for ' . ($evaluateeName !== '' ? $evaluateeName : user_management_role_label($targetRole)),
            'term' => user_management_role_label($targetRole),
            'date' => '',
            'time' => '',
            'comment' => (string) ($row['comment_text'] ?? ''),
            'questionCount' => (int) ($row['question_count'] ?? 0),
            'totalScore' => (int) ($row['total_score'] ?? 0),
            'averageRating' => (float) ($row['average_rating'] ?? 0),
            'status' => (string) ($row['submission_status'] ?? 'draft'),
            'updatedAt' => (string) ($row['updated_at'] ?? $row['completed_at'] ?? ''),
            'editUrl' => '',
        ];
    }

    return $rows;
}

function program_chair_admin_update_evaluation(PDO $pdo, string $evaluationType, int $evaluationId, array $payload): void
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);
    ensure_role_evaluation_tables($pdo);

    $status = strtolower(trim((string) ($payload['submission_status'] ?? 'draft')));
    if (!in_array($status, ['draft', 'submitted'], true)) {
        throw new RuntimeException('Please select a valid evaluation status.');
    }

    $commentText = trim((string) ($payload['comment_text'] ?? ''));

    if ($evaluationType === 'student') {
        $statement = $pdo->prepare(
            "UPDATE tbl_student_faculty_evaluations
             SET submission_status = :submission_status,
                 comment_text = :comment_text,
                 final_submitted_at = CASE
                    WHEN :final_status = 'submitted' AND final_submitted_at IS NULL THEN NOW()
                    WHEN :clear_status = 'draft' THEN NULL
                    ELSE final_submitted_at
                 END,
                 updated_at = NOW()
             WHERE evaluation_id = :evaluation_id"
        );
        $statement->execute([
            'submission_status' => $status,
            'comment_text' => $commentText,
            'final_status' => $status,
            'clear_status' => $status,
            'evaluation_id' => $evaluationId,
        ]);

        return;
    }

    if ($evaluationType === 'role') {
        $statement = $pdo->prepare(
            "UPDATE tbl_role_evaluations
             SET submission_status = :submission_status,
                 comment_text = :comment_text,
                 final_submitted_at = CASE
                    WHEN :final_status = 'submitted' AND final_submitted_at IS NULL THEN NOW()
                    WHEN :clear_status = 'draft' THEN NULL
                    ELSE final_submitted_at
                 END,
                 updated_at = NOW()
             WHERE role_evaluation_id = :role_evaluation_id"
        );
        $statement->execute([
            'submission_status' => $status,
            'comment_text' => $commentText,
            'final_status' => $status,
            'clear_status' => $status,
            'role_evaluation_id' => $evaluationId,
        ]);

        return;
    }

    $evaluationDate = program_chair_normalize_evaluation_date(trim((string) ($payload['evaluation_date'] ?? '')), false);
    $evaluationTime = program_chair_normalize_evaluation_time(trim((string) ($payload['evaluation_time'] ?? '')), false);
    $statement = $pdo->prepare(
        "UPDATE tbl_program_chair_faculty_evaluations
         SET submission_status = :submission_status,
             evaluation_date = :evaluation_date,
             evaluation_time = :evaluation_time,
             comment_text = :comment_text,
             final_submitted_at = CASE
                WHEN :final_status = 'submitted' AND final_submitted_at IS NULL THEN NOW()
                WHEN :clear_status = 'draft' THEN NULL
                ELSE final_submitted_at
             END,
             updated_at = NOW()
         WHERE program_chair_evaluation_id = :program_chair_evaluation_id"
    );
    $statement->execute([
        'submission_status' => $status,
        'evaluation_date' => $evaluationDate,
        'evaluation_time' => $evaluationTime,
        'comment_text' => $commentText,
        'final_status' => $status,
        'clear_status' => $status,
        'program_chair_evaluation_id' => $evaluationId,
    ]);

}

function program_chair_admin_delete_evaluation(PDO $pdo, string $evaluationType, int $evaluationId): void
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);
    ensure_role_evaluation_tables($pdo);

    if ($evaluationId <= 0) {
        throw new RuntimeException('Please select a valid evaluation row.');
    }

    $pdo->beginTransaction();

    try {
        if ($evaluationType === 'student') {
            $answerStatement = $pdo->prepare(
                "DELETE FROM tbl_student_faculty_evaluation_answers
                 WHERE evaluation_id = :evaluation_id"
            );
            $answerStatement->execute(['evaluation_id' => $evaluationId]);

            $evaluationStatement = $pdo->prepare(
                "DELETE FROM tbl_student_faculty_evaluations
                 WHERE evaluation_id = :evaluation_id"
            );
            $evaluationStatement->execute(['evaluation_id' => $evaluationId]);
        } elseif ($evaluationType === 'role') {
            $answerStatement = $pdo->prepare(
                "DELETE FROM tbl_role_evaluation_answers
                 WHERE role_evaluation_id = :role_evaluation_id"
            );
            $answerStatement->execute(['role_evaluation_id' => $evaluationId]);

            $evaluationStatement = $pdo->prepare(
                "DELETE FROM tbl_role_evaluations
                 WHERE role_evaluation_id = :role_evaluation_id"
            );
            $evaluationStatement->execute(['role_evaluation_id' => $evaluationId]);
        } else {
            $answerStatement = $pdo->prepare(
                "DELETE FROM tbl_program_chair_faculty_evaluation_answers
                 WHERE program_chair_evaluation_id = :program_chair_evaluation_id"
            );
            $answerStatement->execute(['program_chair_evaluation_id' => $evaluationId]);

            $evaluationStatement = $pdo->prepare(
                "DELETE FROM tbl_program_chair_faculty_evaluations
                 WHERE program_chair_evaluation_id = :program_chair_evaluation_id"
            );
            $evaluationStatement->execute(['program_chair_evaluation_id' => $evaluationId]);
        }

        if ($evaluationStatement->rowCount() <= 0) {
            throw new RuntimeException('The selected evaluation row could not be found.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function program_chair_faculty_add(PDO $pdo, int $facultyId, string $classification, string $programCode, int $createdByUserId): void
{
    ensure_program_chair_tables($pdo);

    if ($facultyId <= 0) {
        throw new RuntimeException('Please select a valid faculty member.');
    }

    $classification = program_chair_normalize_faculty_classification($classification);
    $programCode = program_chair_normalize_program_code($programCode);

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
            faculty_program_code,
            is_active,
            created_by_user_management_id
         ) VALUES (
            :faculty_id,
            :faculty_classification,
            :faculty_program_code,
            1,
            :created_by_user_management_id
         )
         ON DUPLICATE KEY UPDATE
            faculty_classification = VALUES(faculty_classification),
            faculty_program_code = VALUES(faculty_program_code),
            is_active = 1,
            created_by_user_management_id = VALUES(created_by_user_management_id),
            updated_at = NOW()"
    );
    $insertStatement->execute([
        'faculty_id' => $facultyId,
        'faculty_classification' => $classification,
        'faculty_program_code' => $programCode,
        'created_by_user_management_id' => $createdByUserId > 0 ? $createdByUserId : null,
    ]);
}

function program_chair_faculty_update_classification(PDO $pdo, int $programChairFacultyId, string $classification, ?string $programCode = null): void
{
    ensure_program_chair_tables($pdo);

    if ($programChairFacultyId <= 0) {
        throw new RuntimeException('Please select a valid program chair faculty list row.');
    }

    $classification = program_chair_normalize_faculty_classification($classification);
    $programCode = $programCode !== null ? program_chair_normalize_program_code($programCode) : null;

    $setSql = "faculty_classification = :faculty_classification";
    $parameters = [
        'faculty_classification' => $classification,
        'program_chair_faculty_id' => $programChairFacultyId,
    ];

    if ($programCode !== null) {
        $setSql .= ", faculty_program_code = :faculty_program_code";
        $parameters['faculty_program_code'] = $programCode;
    }

    $statement = $pdo->prepare(
        "UPDATE tbl_program_chair_faculty
         SET " . $setSql . ",
             updated_at = NOW()
         WHERE program_chair_faculty_id = :program_chair_faculty_id"
    );
    $statement->execute($parameters);
}

function program_chair_user_program_update(PDO $pdo, int $programChairUserManagementId, string $programCode, int $createdByUserId): void
{
    ensure_program_chair_tables($pdo);
    ensure_user_management_table($pdo);

    if ($programChairUserManagementId <= 0) {
        throw new RuntimeException('Please select a valid program chair account.');
    }

    $programCode = program_chair_normalize_program_code($programCode);

    $userStatement = $pdo->prepare(
        "SELECT user_management_id
         FROM tbl_user_management
         WHERE user_management_id = :user_management_id
           AND account_role = 'program_chair'
           AND is_active = 1
         LIMIT 1"
    );
    $userStatement->execute(['user_management_id' => $programChairUserManagementId]);

    if (!$userStatement->fetch()) {
        throw new RuntimeException('The selected program chair account could not be found.');
    }

    $statement = $pdo->prepare(
        "INSERT INTO tbl_program_chair_user_programs (
            program_chair_user_management_id,
            program_code,
            is_active,
            created_by_user_management_id
         ) VALUES (
            :program_chair_user_management_id,
            :program_code,
            1,
            :created_by_user_management_id
         )
         ON DUPLICATE KEY UPDATE
            program_code = VALUES(program_code),
            is_active = 1,
            created_by_user_management_id = VALUES(created_by_user_management_id),
            updated_at = NOW()"
    );
    $statement->execute([
        'program_chair_user_management_id' => $programChairUserManagementId,
        'program_code' => $programCode,
        'created_by_user_management_id' => $createdByUserId > 0 ? $createdByUserId : null,
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

    $eligibleCondition = "pcf.is_active = 1 AND f.status = 'active'";
    $parameters = [
        'summary_user_id' => $programChairUserId,
        'draft_user_id' => $programChairUserId,
        'average_user_id' => $programChairUserId,
    ];
    $programChairProgramCode = program_chair_user_program_code($pdo, $programChairUserId);
    if ($programChairProgramCode !== '') {
        $eligibleCondition .= " AND pcf.faculty_program_code = :eligible_program_code";
        $parameters['eligible_program_code'] = $programChairProgramCode;
    }

    $statement = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*)
             FROM tbl_program_chair_faculty pcf
             INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
             WHERE " . $eligibleCondition . ") AS eligible_faculty,
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
    $statement->execute($parameters);

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
            pcf.faculty_program_code,
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
    $programChairProgramCode = program_chair_user_program_code($pdo, $programChairUserId);
    if ($programChairProgramCode !== '') {
        $sql .= " AND pcf.faculty_program_code = :program_chair_program_code";
        $parameters['program_chair_program_code'] = $programChairProgramCode;
    }

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
        $rows[$index]['faculty_program_code'] = program_chair_normalize_program_code((string) ($row['faculty_program_code'] ?? ''), true);
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

    $sql = "SELECT
            pcf.program_chair_faculty_id,
            f.faculty_id,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            pcf.faculty_program_code,
            f.status
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f ON f.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
           AND f.status = 'active'
           AND pcf.faculty_id = :faculty_id";
    $parameters = ['faculty_id' => $facultyId];
    $programChairProgramCode = program_chair_user_program_code($pdo, $programChairUserId);
    if ($programChairProgramCode !== '') {
        $sql .= " AND pcf.faculty_program_code = :program_chair_program_code";
        $parameters['program_chair_program_code'] = $programChairProgramCode;
    }
    $sql .= " LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
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
            instrument_version,
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
            :instrument_version,
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
        'instrument_version' => program_chair_latest_instrument_version(),
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

function program_chair_evaluation_has_any_answer(array $submittedAnswers, ?string $instrumentVersion = null): bool
{
    foreach (program_chair_evaluation_question_bank($instrumentVersion) as $category) {
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

function program_chair_evaluation_submitted_answer_values(array $submittedAnswers, ?string $instrumentVersion = null): array
{
    $answers = [];
    $validScores = array_keys(program_chair_evaluation_scale_options());

    foreach (program_chair_evaluation_question_bank($instrumentVersion) as $category) {
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

function program_chair_normalize_evaluation_answers(array $submittedAnswers, bool $requireComplete = true, ?string $instrumentVersion = null): array
{
    $normalized = [];
    $validScores = array_keys(program_chair_evaluation_scale_options());

    foreach (program_chair_evaluation_question_bank($instrumentVersion) as $category) {
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
    $subjectPayload = program_chair_subject_payload(
        $pdo,
        $subjectKey,
        $isSubmitted,
        (int) ($context['faculty_id'] ?? 0)
    );
    $subjectText = $subjectPayload['subject_text'];
    $normalizedDate = program_chair_normalize_evaluation_date($evaluationDate, $isSubmitted);
    $normalizedTime = program_chair_normalize_evaluation_time($evaluationTime, $isSubmitted);

    $instrumentVersion = program_chair_evaluation_instrument_version_for($evaluation);
    $normalizedAnswers = program_chair_normalize_evaluation_answers($submittedAnswers, $isSubmitted, $instrumentVersion);
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
                 instrument_version = :instrument_version,
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
            'instrument_version' => $instrumentVersion,
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
