<?php
declare(strict_types=1);

function evaluation_scale_options(): array
{
    return [
        5 => [
            'label' => 'Outstanding',
            'description' => 'The performance almost always exceeds the job requirements. The faculty is an exceptional role model.',
        ],
        4 => [
            'label' => 'Very Satisfactory',
            'description' => 'The performance meets and often exceeds the job requirements.',
        ],
        3 => [
            'label' => 'Satisfactory',
            'description' => 'The performance meets and sometimes exceeds the job requirements.',
        ],
        2 => [
            'label' => 'Fair',
            'description' => 'The performance needs some development to meet job requirements.',
        ],
        1 => [
            'label' => 'Poor',
            'description' => 'The faculty fails to meet job requirements.',
        ],
    ];
}

function evaluation_question_bank(): array
{
    return [
        [
            'key' => 'commitment',
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

function evaluation_total_question_count(): int
{
    $count = 0;
    foreach (evaluation_question_bank() as $category) {
        $count += count($category['questions']);
    }

    return $count;
}

function evaluation_term_label($academicYearLabel, $semester): string
{
    $academicYearLabel = trim((string) $academicYearLabel);
    if ($academicYearLabel === '') {
        $academicYearLabel = 'Academic Year';
    }

    return $academicYearLabel . ' | ' . format_semester($semester);
}

function student_evaluation_context(PDO $pdo, int $studentId, int $enrollmentId): ?array
{
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
    $context['subject_summary'] = evaluation_subject_summary(
        $pdo,
        $studentId,
        (int) $context['faculty_id'],
        (int) $context['ay_id'],
        (int) $context['semester']
    );

    return $context;
}

function evaluation_subject_summary(PDO $pdo, int $studentId, int $facultyId, int $ayId, int $semester): string
{
    $sql = "SELECT
                es.subject_code,
                COALESCE(smst.sub_description, es.descriptive_title) AS descriptive_title,
                es.section_text
            FROM tbl_student_management_enrolled_subjects es
            LEFT JOIN tbl_subject_masterlist smst ON smst.sub_id = es.subject_id
            WHERE es.student_id = :student_id
              AND es.faculty_id = :faculty_id
              AND es.ay_id = :ay_id
              AND es.semester = :semester
              AND es.is_active = 1
            ORDER BY es.subject_code ASC, es.section_text ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'student_id' => $studentId,
        'faculty_id' => $facultyId,
        'ay_id' => $ayId,
        'semester' => $semester,
    ]);

    $items = [];
    foreach ($statement->fetchAll() as $row) {
        $items[] = trim((string) $row['subject_code']) . ' - ' . trim((string) $row['descriptive_title']) . ' (' . trim((string) $row['section_text']) . ')';
    }

    return implode('; ', $items);
}

function find_evaluation_by_context(PDO $pdo, int $studentId, int $facultyId, int $ayId, int $semester): ?array
{
    $sql = "SELECT *
            FROM tbl_student_faculty_evaluations
            WHERE student_id = :student_id
              AND faculty_id = :faculty_id
              AND ay_id = :ay_id
              AND semester = :semester
            LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'student_id' => $studentId,
        'faculty_id' => $facultyId,
        'ay_id' => $ayId,
        'semester' => $semester,
    ]);

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
    $existing = find_evaluation_by_context(
        $pdo,
        (int) $context['student_id'],
        (int) $context['faculty_id'],
        (int) $context['ay_id'],
        (int) $context['semester']
    );

    if ($existing !== null) {
        return $existing;
    }

    $sql = "INSERT INTO tbl_student_faculty_evaluations (
                student_id,
                faculty_id,
                ay_id,
                semester,
                faculty_name,
                student_number,
                term_label,
                subject_summary,
                comment_text,
                question_count,
                total_score,
                average_rating,
                evaluation_token,
                submission_status,
                final_submission_token
            ) VALUES (
                :student_id,
                :faculty_id,
                :ay_id,
                :semester,
                :faculty_name,
                :student_number,
                :term_label,
                :subject_summary,
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
        'faculty_id' => $context['faculty_id'],
        'ay_id' => $context['ay_id'],
        'semester' => $context['semester'],
        'faculty_name' => $context['faculty_name'],
        'student_number' => $context['student_number'],
        'term_label' => $context['term_label'],
        'subject_summary' => $context['subject_summary'],
        'evaluation_token' => bin2hex(random_bytes(16)),
    ]);

    $evaluationId = (int) $pdo->lastInsertId();
    $created = find_evaluation_by_context(
        $pdo,
        (int) $context['student_id'],
        (int) $context['faculty_id'],
        (int) $context['ay_id'],
        (int) $context['semester']
    );

    if ($created === null) {
        throw new RuntimeException('Unable to create the evaluation record.');
    }

    return $created;
}

function normalize_evaluation_answers(array $submittedAnswers): array
{
    $normalized = [];
    $validScores = array_keys(evaluation_scale_options());

    foreach (evaluation_question_bank() as $category) {
        $position = 1;
        foreach ($category['questions'] as $questionText) {
            $questionKey = $category['key'] . '_' . $position;
            $rating = isset($submittedAnswers[$questionKey]) ? (int) $submittedAnswers[$questionKey] : 0;
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

function save_evaluation_submission(PDO $pdo, array $evaluation, array $context, array $submittedAnswers, string $commentText, string $status): array
{
    $normalizedAnswers = normalize_evaluation_answers($submittedAnswers);
    $totalScore = 0;
    foreach ($normalizedAnswers as $answer) {
        $totalScore += (int) $answer['rating'];
    }

    $questionCount = count($normalizedAnswers);
    $averageRating = $questionCount > 0 ? round($totalScore / $questionCount, 2) : 0;
    $isSubmitted = $status === 'submitted';

    $pdo->beginTransaction();

    try {
        $updateSql = "UPDATE tbl_student_faculty_evaluations
                      SET faculty_name = :faculty_name,
                          student_number = :student_number,
                          term_label = :term_label,
                          subject_summary = :subject_summary,
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
            'student_number' => $context['student_number'],
            'term_label' => $context['term_label'],
            'subject_summary' => $context['subject_summary'],
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

    $saved = find_evaluation_by_context(
        $pdo,
        (int) $context['student_id'],
        (int) $context['faculty_id'],
        (int) $context['ay_id'],
        (int) $context['semester']
    );

    if ($saved === null) {
        throw new RuntimeException('Unable to reload the saved evaluation.');
    }

    return $saved;
}
