<?php
declare(strict_types=1);

function evaluation_scale_options(): array
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

function evaluation_latest_instrument_version(): string
{
    return 'SET_2026_15_ITEM';
}

function evaluation_legacy_instrument_version(): string
{
    return 'SET_LEGACY_20_ITEM';
}

function evaluation_question_bank(?string $instrumentVersion = null): array
{
    $instrumentVersion = trim((string) ($instrumentVersion ?? evaluation_latest_instrument_version()));

    if ($instrumentVersion === evaluation_legacy_instrument_version()) {
        return evaluation_legacy_question_bank();
    }

    return evaluation_set_2026_question_bank();
}

function evaluation_set_2026_question_bank(): array
{
    return [
        [
            'key' => 'management_of_teaching_and_learning',
            'code' => 'mtl',
            'title' => 'MANAGEMENT OF TEACHING AND LEARNING',
            'description' => 'Management of Teaching and Learning refers to the intentional and organized handling of classroom presence, clear communication of academic expectations, efficient use of time, and the purposeful use of student-centered activities that promote critical thinking, independent learning, reflection, decision-making, and continuous academic improvement through constructive feedback.',
            'questions' => [
                'Comes to class on time.',
                'Explains learning outcomes, expectations, grading system, and various requirements of the subject/course.',
                'Maximizes the allocated time/learning hours effectively.',
                'Facilitates students to think critically and creatively by providing appropriate learning activities.',
                'Guides students to learn on their own, reflect on new ideas and experiences, and make decisions in accomplishing given tasks.',
                'Communicates constructive feedback to students for their academic growth.',
            ],
        ],
        [
            'key' => 'content_knowledge_pedagogy_and_technology',
            'code' => 'ckpt',
            'title' => 'CONTENT KNOWLEDGE, PEDAGOGY AND TECHNOLOGY',
            'description' => 'Content Knowledge, Pedagogy, and Technology refer to a teacher\'s ability to demonstrate a strong grasp of subject matter, present complex concepts in a clear and accessible way, relate content to real-world contexts and current developments, engage students through appropriate instructional strategies and digital tools, and apply assessment methods aligned with intended learning outcomes.',
            'questions' => [
                'Demonstrates extensive and broad knowledge of the subject/course.',
                'Simplifies complex ideas in the lesson for ease of understanding.',
                'Relates the subject matter to contemporary issues and developments in the discipline and/or daily life activities.',
                'Promotes active learning and student engagement by using appropriate teaching and learning resources including ICT tools and platforms.',
                'Uses appropriate assessments (projects, exams, quizzes, assignments, etc.) aligned with the learning outcomes.',
            ],
        ],
        [
            'key' => 'commitment_and_transparency',
            'code' => 'ct',
            'title' => 'COMMITMENT AND TRANSPARENCY',
            'description' => 'Commitment and Transparency refer to the teacher\'s consistent dedication to supporting student learning by acknowledging learner diversity, offering timely academic support and feedback, and upholding fairness and accountability through the use of clear and openly communicated performance criteria.',
            'questions' => [
                'Recognizes and values the unique diversity and individual differences among students.',
                'Assists students with their learning challenges during consultation hours.',
                'Provides immediate feedback on student outputs and performance.',
                'Provides transparent and clear criteria in rating student\'s performance.',
            ],
        ],
    ];
}

function evaluation_legacy_question_bank(): array
{
    return [
        [
            'key' => 'commitment',
            'code' => 'cmt',
            'title' => 'COMMITMENT',
            'questions' => [
                'Demonstrates sensitivity to students’ ability to attend and absorb content information.',
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
                'Stimulates students’ desire and interest to learn more about the subject matter.',
            ],
        ],
    ];
}

function evaluation_all_question_categories(): array
{
    $categories = [];

    foreach ([evaluation_latest_instrument_version(), evaluation_legacy_instrument_version()] as $instrumentVersion) {
        foreach (evaluation_question_bank($instrumentVersion) as $category) {
            $categoryKey = (string) ($category['key'] ?? '');
            if ($categoryKey === '' || isset($categories[$categoryKey])) {
                continue;
            }

            $categories[$categoryKey] = $category;
        }
    }

    return $categories;
}

function evaluation_question_key(array $category, int $position): string
{
    $code = strtolower(trim((string) ($category['code'] ?? $category['key'] ?? 'q')));
    $code = preg_replace('/[^a-z0-9_]+/', '', $code);
    $code = is_string($code) && $code !== '' ? $code : 'q';

    return $code . '_' . $position;
}

function evaluation_question_key_aliases(array $category, int $position): array
{
    $keys = [evaluation_question_key($category, $position)];
    $legacyBase = trim((string) ($category['key'] ?? ''));

    if ($legacyBase !== '') {
        $legacyKey = $legacyBase . '_' . $position;
        if (!in_array($legacyKey, $keys, true)) {
            $keys[] = $legacyKey;
        }
    }

    return $keys;
}

function evaluation_answer_value(array $answers, array $category, int $position): int
{
    foreach (evaluation_question_key_aliases($category, $position) as $questionKey) {
        if (isset($answers[$questionKey])) {
            return (int) $answers[$questionKey];
        }
    }

    return 0;
}

function evaluation_total_question_count(?string $instrumentVersion = null): int
{
    $count = 0;
    foreach (evaluation_question_bank($instrumentVersion) as $category) {
        $count += count($category['questions']);
    }

    return $count;
}

function evaluation_instrument_version_for(?array $evaluation, array $answers = []): string
{
    $savedVersion = trim((string) ($evaluation['instrument_version'] ?? ''));
    if ($savedVersion !== '') {
        return $savedVersion;
    }

    if ($answers !== []) {
        foreach (array_keys($answers) as $questionKey) {
            if (preg_match('/^(mtl|ckpt|ct)_\d+$/', (string) $questionKey)) {
                return evaluation_latest_instrument_version();
            }
        }

        return evaluation_legacy_instrument_version();
    }

    $questionCount = (int) ($evaluation['question_count'] ?? 0);
    if ($questionCount >= 20) {
        return evaluation_legacy_instrument_version();
    }

    return evaluation_latest_instrument_version();
}

function evaluation_term_label($academicYearLabel, $semester): string
{
    $academicYearLabel = trim((string) $academicYearLabel);
    if ($academicYearLabel === '') {
        $academicYearLabel = 'Academic Year';
    }

    return $academicYearLabel . ' | ' . format_semester($semester);
}

function ensure_evaluation_subject_scope(PDO $pdo): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $columns = evaluation_table_columns($pdo);

    if (!isset($columns['student_enrollment_id'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD COLUMN student_enrollment_id INT(10) UNSIGNED NULL AFTER student_id"
        );
    }

    if (!isset($columns['subject_id'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD COLUMN subject_id INT(10) UNSIGNED NULL AFTER faculty_id"
        );
    }

    if (!isset($columns['subject_code'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD COLUMN subject_code VARCHAR(50) NOT NULL DEFAULT '' AFTER subject_id"
        );
    }

    if (!isset($columns['instrument_version'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD COLUMN instrument_version VARCHAR(40) NOT NULL DEFAULT '' AFTER subject_summary"
        );
    }

    $indexes = evaluation_table_indexes($pdo);
    if (isset($indexes['uniq_student_faculty_term'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             DROP INDEX uniq_student_faculty_term"
        );
    }

    evaluation_backfill_single_subject_records($pdo);

    $indexes = evaluation_table_indexes($pdo);
    if (!isset($indexes['idx_eval_student_enrollment'])) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD INDEX idx_eval_student_enrollment (student_enrollment_id)"
        );
    }

    if (
        !isset($indexes['uniq_student_enrollment_evaluation'])
        && !evaluation_has_duplicate_subject_scoped_records($pdo)
    ) {
        $pdo->exec(
            "ALTER TABLE tbl_student_faculty_evaluations
             ADD UNIQUE INDEX uniq_student_enrollment_evaluation (student_enrollment_id)"
        );
    }

    $ensured = true;

    evaluation_recalculate_saved_summaries($pdo);
}

function evaluation_recalculate_saved_summaries(PDO $pdo): void
{
    static $synced = false;

    if ($synced) {
        return;
    }

    $pdo->exec(
        "UPDATE tbl_student_faculty_evaluations ev
         INNER JOIN (
            SELECT
                evaluation_id,
                COUNT(*) AS question_count,
                COALESCE(SUM(rating), 0) AS total_score,
                ROUND(AVG(rating), 2) AS average_rating
            FROM tbl_student_faculty_evaluation_answers
            WHERE rating BETWEEN 1 AND 5
            GROUP BY evaluation_id
         ) ans ON ans.evaluation_id = ev.evaluation_id
         SET ev.question_count = ans.question_count,
             ev.total_score = ans.total_score,
             ev.average_rating = ans.average_rating
         WHERE ev.question_count <> ans.question_count
            OR ev.total_score <> ans.total_score
            OR ev.average_rating <> ans.average_rating"
    );

    $pdo->exec(
        "UPDATE tbl_student_faculty_evaluations ev
         INNER JOIN (
            SELECT
                evaluation_id,
                COUNT(*) AS answer_count,
                SUM(CASE WHEN category_key IN ('commitment', 'knowledge_of_subject_matter', 'teaching_for_independent_learning', 'management_of_learning') THEN 1 ELSE 0 END) AS legacy_answer_count,
                SUM(CASE WHEN category_key IN ('management_of_teaching_and_learning', 'content_knowledge_pedagogy_and_technology', 'commitment_and_transparency') THEN 1 ELSE 0 END) AS latest_answer_count
            FROM tbl_student_faculty_evaluation_answers
            GROUP BY evaluation_id
         ) ans ON ans.evaluation_id = ev.evaluation_id
         SET ev.instrument_version = CASE
             WHEN ans.legacy_answer_count > 0 THEN '" . evaluation_legacy_instrument_version() . "'
             WHEN ans.latest_answer_count > 0 THEN '" . evaluation_latest_instrument_version() . "'
             WHEN ans.answer_count >= 20 THEN '" . evaluation_legacy_instrument_version() . "'
             ELSE '" . evaluation_latest_instrument_version() . "'
         END
         WHERE ev.instrument_version = ''"
    );

    $synced = true;
}

function evaluation_table_columns(PDO $pdo): array
{
    $columns = [];
    $statement = $pdo->query('SHOW COLUMNS FROM tbl_student_faculty_evaluations');

    foreach ($statement->fetchAll() as $row) {
        $columns[(string) $row['Field']] = true;
    }

    return $columns;
}

function evaluation_table_indexes(PDO $pdo): array
{
    $indexes = [];
    $statement = $pdo->query('SHOW INDEX FROM tbl_student_faculty_evaluations');

    foreach ($statement->fetchAll() as $row) {
        $indexes[(string) $row['Key_name']] = true;
    }

    return $indexes;
}

function evaluation_has_duplicate_subject_scoped_records(PDO $pdo): bool
{
    $sql = "SELECT student_enrollment_id
            FROM tbl_student_faculty_evaluations
            WHERE student_enrollment_id IS NOT NULL
            GROUP BY student_enrollment_id
            HAVING COUNT(*) > 1
            LIMIT 1";

    $statement = $pdo->query($sql);

    return (bool) $statement->fetch();
}

function evaluation_backfill_single_subject_records(PDO $pdo): void
{
    $sql = "UPDATE tbl_student_faculty_evaluations ev
            INNER JOIN (
                SELECT
                    ev_inner.evaluation_id,
                    MIN(es.student_enrollment_id) AS student_enrollment_id,
                    MIN(es.subject_id) AS subject_id,
                    MIN(es.subject_code) AS subject_code,
                    COUNT(*) AS matched_subjects
                FROM tbl_student_faculty_evaluations ev_inner
                INNER JOIN tbl_student_management_enrolled_subjects es
                    ON es.student_id = ev_inner.student_id
                   AND es.faculty_id = ev_inner.faculty_id
                   AND es.ay_id = ev_inner.ay_id
                   AND es.semester = ev_inner.semester
                   AND es.is_active = 1
                WHERE ev_inner.student_enrollment_id IS NULL
                GROUP BY ev_inner.evaluation_id
                HAVING matched_subjects = 1
            ) matched ON matched.evaluation_id = ev.evaluation_id
            SET ev.student_enrollment_id = matched.student_enrollment_id,
                ev.subject_id = matched.subject_id,
                ev.subject_code = matched.subject_code";

    $pdo->exec($sql);
}

function student_evaluation_context(PDO $pdo, int $studentId, int $enrollmentId): ?array
{
    ensure_evaluation_subject_scope($pdo);

    $sql = "SELECT
                es.student_enrollment_id,
                es.student_id,
                es.faculty_id,
                es.subject_id,
                es.subject_code,
                COALESCE(smst.sub_description, es.descriptive_title) AS descriptive_title,
                es.section_text,
                es.schedule_text,
                es.room_text,
                es.ay_id,
                COALESCE(ay.ay, CONCAT('AY ID ', es.ay_id)) AS academic_year_label,
                es.semester,
                sm.student_number,
                sm.email_address,
                sm.first_name,
                sm.last_name,
                sm.middle_name,
                sm.suffix_name,
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )) AS faculty_name
            FROM tbl_student_management_enrolled_subjects es
            INNER JOIN tbl_student_management sm ON sm.student_id = es.student_id
            LEFT JOIN tbl_faculty f ON f.faculty_id = es.faculty_id
            LEFT JOIN tbl_subject_masterlist smst ON smst.sub_id = es.subject_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = es.ay_id
            WHERE es.student_enrollment_id = :enrollment_id
              AND es.student_id = :student_id
              AND es.is_active = 1
            LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'enrollment_id' => $enrollmentId,
        'student_id' => $studentId,
    ]);
    $context = $statement->fetch();

    if (!$context) {
        return null;
    }

    if (trim((string) $context['faculty_name']) === '') {
        $context['faculty_name'] = 'Faculty #' . (string) $context['faculty_id'];
    }

    $context['student_name'] = person_full_name(
        $context['last_name'] ?? '',
        $context['first_name'] ?? '',
        $context['middle_name'] ?? '',
        $context['suffix_name'] ?? ''
    );
    $context['term_label'] = evaluation_term_label($context['academic_year_label'], $context['semester']);
    $context['subject_summary'] = evaluation_subject_summary($context);

    return $context;
}

function evaluation_subject_summary(array $context): string
{
    $parts = [];

    if (trim((string) ($context['subject_code'] ?? '')) !== '') {
        $parts[] = trim((string) $context['subject_code']);
    }

    if (trim((string) ($context['descriptive_title'] ?? '')) !== '') {
        $parts[] = trim((string) $context['descriptive_title']);
    }

    $summary = implode(' - ', $parts);
    $section = trim((string) ($context['section_text'] ?? ''));

    if ($section !== '') {
        $summary .= ' (' . $section . ')';
    }

    return $summary !== '' ? $summary : 'Subject #' . (string) ((int) ($context['subject_id'] ?? 0));
}

function find_evaluation_by_context(PDO $pdo, int $studentEnrollmentId): ?array
{
    ensure_evaluation_subject_scope($pdo);

    $sql = "SELECT *
            FROM tbl_student_faculty_evaluations
            WHERE student_enrollment_id = :student_enrollment_id
            LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_enrollment_id' => $studentEnrollmentId]);

    $evaluation = $statement->fetch();
    return $evaluation ?: null;
}

function find_evaluation_answers(PDO $pdo, int $evaluationId): array
{
    $sql = "SELECT category_key, question_key, rating
            FROM tbl_student_faculty_evaluation_answers
            WHERE evaluation_id = :evaluation_id
            ORDER BY question_order ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute(['evaluation_id' => $evaluationId]);

    $answers = [];
    foreach ($statement->fetchAll() as $row) {
        $answers[$row['question_key']] = (int) $row['rating'];
    }

    return $answers;
}

function create_or_get_evaluation(PDO $pdo, array $context): array
{
    ensure_evaluation_subject_scope($pdo);

    $existing = find_evaluation_by_context($pdo, (int) $context['student_enrollment_id']);

    if ($existing !== null) {
        return $existing;
    }

    $sql = "INSERT INTO tbl_student_faculty_evaluations (
                student_id,
                student_enrollment_id,
                faculty_id,
                subject_id,
                subject_code,
                ay_id,
                semester,
                faculty_name,
                student_number,
                term_label,
                subject_summary,
                instrument_version,
                comment_text,
                question_count,
                total_score,
                average_rating,
                evaluation_token,
                submission_status,
                final_submission_token
            ) VALUES (
                :student_id,
                :student_enrollment_id,
                :faculty_id,
                :subject_id,
                :subject_code,
                :ay_id,
                :semester,
                :faculty_name,
                :student_number,
                :term_label,
                :subject_summary,
                :instrument_version,
                '',
                0,
                0,
                0,
                :evaluation_token,
                'draft',
                ''
            )";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'student_id' => $context['student_id'],
        'student_enrollment_id' => $context['student_enrollment_id'],
        'faculty_id' => $context['faculty_id'],
        'subject_id' => $context['subject_id'],
        'subject_code' => $context['subject_code'],
        'ay_id' => $context['ay_id'],
        'semester' => $context['semester'],
        'faculty_name' => $context['faculty_name'],
        'student_number' => $context['student_number'],
        'term_label' => $context['term_label'],
        'subject_summary' => $context['subject_summary'],
        'instrument_version' => evaluation_latest_instrument_version(),
        'evaluation_token' => bin2hex(random_bytes(16)),
    ]);

    $evaluationId = (int) $pdo->lastInsertId();
    $created = find_evaluation_by_context($pdo, (int) $context['student_enrollment_id']);

    if ($created === null) {
        throw new RuntimeException('Unable to create the evaluation record.');
    }

    return $created;
}

function normalize_evaluation_answers(array $submittedAnswers, bool $requireComplete = true, ?string $instrumentVersion = null): array
{
    $normalized = [];
    $validScores = array_keys(evaluation_scale_options());

    foreach (evaluation_question_bank($instrumentVersion) as $category) {
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

function evaluation_has_any_answer(array $submittedAnswers, ?string $instrumentVersion = null): bool
{
    foreach (evaluation_question_bank($instrumentVersion) as $category) {
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

function evaluation_submitted_answer_values(array $submittedAnswers, ?string $instrumentVersion = null): array
{
    $answers = [];
    $validScores = array_keys(evaluation_scale_options());

    foreach (evaluation_question_bank($instrumentVersion) as $category) {
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

function save_evaluation_submission(PDO $pdo, array $evaluation, array $context, array $submittedAnswers, string $commentText, string $status): array
{
    ensure_evaluation_subject_scope($pdo);

    $isSubmitted = $status === 'submitted';
    $instrumentVersion = evaluation_instrument_version_for($evaluation);
    $normalizedAnswers = normalize_evaluation_answers($submittedAnswers, $isSubmitted, $instrumentVersion);
    $totalScore = 0;
    foreach ($normalizedAnswers as $answer) {
        $totalScore += (int) $answer['rating'];
    }

    $questionCount = count($normalizedAnswers);
    $averageRating = $questionCount > 0 ? round($totalScore / $questionCount, 2) : 0;

    $pdo->beginTransaction();

    try {
        $updateSql = "UPDATE tbl_student_faculty_evaluations
                      SET faculty_name = :faculty_name,
                          student_enrollment_id = :student_enrollment_id,
                          subject_id = :subject_id,
                          subject_code = :subject_code,
                          student_number = :student_number,
                          term_label = :term_label,
                          subject_summary = :subject_summary,
                          instrument_version = :instrument_version,
                          comment_text = :comment_text,
                          question_count = :question_count,
                          total_score = :total_score,
                          average_rating = :average_rating,
                          submission_status = :submission_status,
                          final_submission_token = :final_submission_token,
                          final_submitted_at = :final_submitted_at,
                          completed_at = NOW(),
                          updated_at = NOW()
                      WHERE evaluation_id = :evaluation_id";

        $updateStatement = $pdo->prepare($updateSql);
        $updateStatement->execute([
            'faculty_name' => $context['faculty_name'],
            'student_enrollment_id' => $context['student_enrollment_id'],
            'subject_id' => $context['subject_id'],
            'subject_code' => $context['subject_code'],
            'student_number' => $context['student_number'],
            'term_label' => $context['term_label'],
            'subject_summary' => $context['subject_summary'],
            'instrument_version' => $instrumentVersion,
            'comment_text' => trim($commentText),
            'question_count' => $questionCount,
            'total_score' => $totalScore,
            'average_rating' => $averageRating,
            'submission_status' => $status,
            'final_submission_token' => $isSubmitted ? bin2hex(random_bytes(16)) : '',
            'final_submitted_at' => $isSubmitted ? date('Y-m-d H:i:s') : null,
            'evaluation_id' => $evaluation['evaluation_id'],
        ]);

        $deleteStatement = $pdo->prepare(
            "DELETE FROM tbl_student_faculty_evaluation_answers
             WHERE evaluation_id = :evaluation_id"
        );
        $deleteStatement->execute(['evaluation_id' => $evaluation['evaluation_id']]);

        $insertSql = "INSERT INTO tbl_student_faculty_evaluation_answers (
                        evaluation_id,
                        category_key,
                        category_title,
                        question_key,
                        question_order,
                        question_text,
                        rating
                      ) VALUES (
                        :evaluation_id,
                        :category_key,
                        :category_title,
                        :question_key,
                        :question_order,
                        :question_text,
                        :rating
                      )";
        $insertStatement = $pdo->prepare($insertSql);

        foreach ($normalizedAnswers as $answer) {
            $insertStatement->execute([
                'evaluation_id' => $evaluation['evaluation_id'],
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

    $saved = find_evaluation_by_context($pdo, (int) $context['student_enrollment_id']);

    if ($saved === null) {
        throw new RuntimeException('Unable to reload the saved evaluation.');
    }

    return $saved;
}
