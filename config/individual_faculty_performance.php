<?php
declare(strict_types=1);

function individual_faculty_performance_categories(): array
{
    return [
        'commitment' => [
            'title' => 'COMMITMENT',
            'weight' => 20,
        ],
        'knowledge_of_subject_matter' => [
            'title' => 'KNOWLEDGE OF SUBJECT MATTER',
            'weight' => 30,
        ],
        'teaching_for_independent_learning' => [
            'title' => 'TEACHING FOR INDEPENDENT LEARNING',
            'weight' => 30,
        ],
        'management_of_learning' => [
            'title' => 'MANAGEMENT OF LEARNING',
            'weight' => 20,
        ],
    ];
}

function individual_faculty_performance_faculty_name_from_row(array $row): string
{
    $name = person_full_name(
        $row['last_name'] ?? '',
        $row['first_name'] ?? '',
        $row['middle_name'] ?? '',
        $row['ext_name'] ?? ''
    );

    return $name !== '' ? $name : 'Faculty #' . (string) ((int) ($row['faculty_id'] ?? 0));
}

function individual_faculty_performance_faculty_options(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);

    $statement = $pdo->query(
        "SELECT
            f.faculty_id,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            COALESCE(sr.student_evaluation_count, 0) AS student_evaluation_count,
            COALESCE(sr.student_count, 0) AS student_count,
            sr.student_average_rating,
            COALESCE(pr.supervisor_evaluation_count, 0) AS supervisor_evaluation_count,
            COALESCE(pr.supervisor_count, 0) AS supervisor_count,
            pr.supervisor_average_rating
         FROM tbl_faculty f
         LEFT JOIN (
            SELECT
                faculty_id,
                COUNT(*) AS student_evaluation_count,
                COUNT(DISTINCT student_id) AS student_count,
                ROUND(AVG(NULLIF(average_rating, 0)), 2) AS student_average_rating
            FROM tbl_student_faculty_evaluations
            WHERE submission_status = 'submitted'
              AND student_enrollment_id IS NOT NULL
              AND faculty_id <> 0
            GROUP BY faculty_id
         ) sr ON sr.faculty_id = f.faculty_id
         LEFT JOIN (
            SELECT
                faculty_id,
                COUNT(*) AS supervisor_evaluation_count,
                COUNT(DISTINCT program_chair_user_management_id) AS supervisor_count,
                ROUND(AVG(NULLIF(average_rating, 0)), 2) AS supervisor_average_rating
            FROM tbl_program_chair_faculty_evaluations
            WHERE submission_status = 'submitted'
              AND faculty_id <> 0
            GROUP BY faculty_id
         ) pr ON pr.faculty_id = f.faculty_id
         WHERE f.status = 'active'
         ORDER BY
            (COALESCE(sr.student_evaluation_count, 0) + COALESCE(pr.supervisor_evaluation_count, 0)) DESC,
            f.last_name ASC,
            f.first_name ASC,
            f.faculty_id ASC"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $rows[$index]['faculty_name'] = individual_faculty_performance_faculty_name_from_row($row);
    }

    return $rows;
}

function individual_faculty_performance_term_key(int $ayId, int $semester): string
{
    return (string) $ayId . ':' . (string) $semester;
}

function individual_faculty_performance_parse_term_key(string $termKey): ?array
{
    if (!preg_match('/^(\d+):(\d+)$/', trim($termKey), $matches)) {
        return null;
    }

    return [
        'ay_id' => (int) $matches[1],
        'semester' => (int) $matches[2],
    ];
}

function individual_faculty_performance_term_options(PDO $pdo, int $facultyId): array
{
    ensure_evaluation_subject_scope($pdo);

    $statement = $pdo->prepare(
        "SELECT
            ev.ay_id,
            ev.semester,
            COALESCE(ay.ay, CONCAT('AY ID ', ev.ay_id)) AS academic_year_label,
            COUNT(*) AS evaluation_count,
            MAX(ev.updated_at) AS last_updated
         FROM tbl_student_faculty_evaluations ev
         LEFT JOIN tbl_academic_years ay ON ay.ay_id = ev.ay_id
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
           AND ev.student_enrollment_id IS NOT NULL
         GROUP BY ev.ay_id, ev.semester, academic_year_label
         ORDER BY ev.ay_id DESC, ev.semester DESC"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        $semesterLabel = format_semester($row['semester'] ?? 0);
        $rows[$index]['semester_label'] = $semesterLabel;
        $rows[$index]['term_key'] = individual_faculty_performance_term_key(
            (int) ($row['ay_id'] ?? 0),
            (int) ($row['semester'] ?? 0)
        );
        $rows[$index]['term_label'] = $semesterLabel . ', A.Y. ' . (string) ($row['academic_year_label'] ?? '');
    }

    return $rows;
}

function individual_faculty_performance_report(PDO $pdo, int $facultyId, ?array $termFilter = null, array $termOptions = []): ?array
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);

    $faculty = individual_faculty_performance_faculty($pdo, $facultyId);
    if ($faculty === null) {
        return null;
    }

    $studentSection = individual_faculty_performance_student_section($pdo, $facultyId, $termFilter);
    $supervisorSection = individual_faculty_performance_supervisor_section($pdo, $facultyId);

    $studentPercentage = $studentSection['weighted_percentage'];
    $supervisorPercentage = $supervisorSection['weighted_percentage'];
    $currentPercentage = null;

    if ($studentPercentage !== null || $supervisorPercentage !== null) {
        $currentPercentage = round((float) ($studentPercentage ?? 0) + (float) ($supervisorPercentage ?? 0), 2);
    }

    $isComplete = $studentPercentage !== null && $supervisorPercentage !== null;
    $termScope = individual_faculty_performance_term_scope($termFilter, $termOptions);

    return [
        'faculty' => $faculty,
        'term_scope' => $termScope,
        'student' => $studentSection,
        'supervisor' => $supervisorSection,
        'current_percentage' => $currentPercentage,
        'is_complete' => $isComplete,
        'rating_label' => individual_faculty_performance_rating_label($currentPercentage, $isComplete),
        'comments' => individual_faculty_performance_comments($pdo, $facultyId, $termFilter),
    ];
}

function individual_faculty_performance_faculty(PDO $pdo, int $facultyId): ?array
{
    $statement = $pdo->prepare(
        "SELECT faculty_id, last_name, first_name, middle_name, ext_name, status
         FROM tbl_faculty
         WHERE faculty_id = :faculty_id
           AND status = 'active'
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    $faculty = $statement->fetch();
    if (!$faculty) {
        return null;
    }

    $faculty['faculty_name'] = individual_faculty_performance_faculty_name_from_row($faculty);

    return $faculty;
}

function individual_faculty_performance_student_section(PDO $pdo, int $facultyId, ?array $termFilter): array
{
    $condition = "ev.faculty_id = :faculty_id
        AND ev.submission_status = 'submitted'
        AND ev.student_enrollment_id IS NOT NULL";
    $parameters = ['faculty_id' => $facultyId];

    if ($termFilter !== null) {
        $condition .= ' AND ev.ay_id = :ay_id AND ev.semester = :semester';
        $parameters['ay_id'] = (int) $termFilter['ay_id'];
        $parameters['semester'] = (int) $termFilter['semester'];
    }

    $summaryStatement = $pdo->prepare(
        "SELECT
            COUNT(*) AS evaluation_count,
            COUNT(DISTINCT ev.student_id) AS evaluator_count,
            COUNT(DISTINCT ev.subject_id) AS subject_count,
            ROUND(AVG(NULLIF(ev.average_rating, 0)), 2) AS stored_average_rating,
            MAX(ev.updated_at) AS last_updated
         FROM tbl_student_faculty_evaluations ev
         WHERE " . $condition
    );
    $summaryStatement->execute($parameters);
    $summary = $summaryStatement->fetch() ?: [];

    $categoryStatement = $pdo->prepare(
        "SELECT
            ans.category_key,
            MAX(ans.category_title) AS category_title,
            ROUND(AVG(ans.rating), 2) AS mean_rating,
            COUNT(*) AS response_count,
            COUNT(DISTINCT ev.evaluation_id) AS evaluation_count
         FROM tbl_student_faculty_evaluation_answers ans
         INNER JOIN tbl_student_faculty_evaluations ev
            ON ev.evaluation_id = ans.evaluation_id
         WHERE " . $condition . "
           AND ans.rating BETWEEN 1 AND 5
         GROUP BY ans.category_key
         ORDER BY MIN(ans.question_order) ASC"
    );
    $categoryStatement->execute($parameters);

    return individual_faculty_performance_build_rating_section(
        'Students Rating',
        60,
        $summary,
        $categoryStatement->fetchAll()
    );
}

function individual_faculty_performance_supervisor_section(PDO $pdo, int $facultyId): array
{
    $parameters = ['faculty_id' => $facultyId];

    $summaryStatement = $pdo->prepare(
        "SELECT
            COUNT(*) AS evaluation_count,
            COUNT(DISTINCT ev.program_chair_user_management_id) AS evaluator_count,
            COUNT(DISTINCT ev.subject_id) AS subject_count,
            ROUND(AVG(NULLIF(ev.average_rating, 0)), 2) AS stored_average_rating,
            MAX(ev.updated_at) AS last_updated
         FROM tbl_program_chair_faculty_evaluations ev
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'"
    );
    $summaryStatement->execute($parameters);
    $summary = $summaryStatement->fetch() ?: [];

    $categoryStatement = $pdo->prepare(
        "SELECT
            ans.category_key,
            MAX(ans.category_title) AS category_title,
            ROUND(AVG(ans.rating), 2) AS mean_rating,
            COUNT(*) AS response_count,
            COUNT(DISTINCT ev.program_chair_evaluation_id) AS evaluation_count
         FROM tbl_program_chair_faculty_evaluation_answers ans
         INNER JOIN tbl_program_chair_faculty_evaluations ev
            ON ev.program_chair_evaluation_id = ans.program_chair_evaluation_id
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
           AND ans.rating BETWEEN 1 AND 5
         GROUP BY ans.category_key
         ORDER BY MIN(ans.question_order) ASC"
    );
    $categoryStatement->execute($parameters);

    return individual_faculty_performance_build_rating_section(
        'Supervisors Rating',
        40,
        $summary,
        $categoryStatement->fetchAll()
    );
}

function individual_faculty_performance_build_rating_section(string $label, float $sourceWeight, array $summary, array $categoryRows): array
{
    $categoryByKey = [];
    foreach ($categoryRows as $row) {
        $categoryByKey[(string) ($row['category_key'] ?? '')] = $row;
    }

    $categories = [];
    $weightedTotal = 0.0;
    $availableWeight = 0.0;
    $responseCount = 0;

    foreach (individual_faculty_performance_categories() as $key => $category) {
        $row = $categoryByKey[$key] ?? null;
        $mean = $row !== null && $row['mean_rating'] !== null ? (float) $row['mean_rating'] : null;
        $weight = (float) $category['weight'];

        if ($mean !== null) {
            $weightedTotal += $mean * ($weight / 100);
            $availableWeight += $weight / 100;
        }

        $responseCount += (int) ($row['response_count'] ?? 0);
        $categories[$key] = [
            'key' => $key,
            'title' => (string) $category['title'],
            'weight' => $weight,
            'mean' => $mean,
            'response_count' => (int) ($row['response_count'] ?? 0),
            'evaluation_count' => (int) ($row['evaluation_count'] ?? 0),
        ];
    }

    $overallMean = null;
    if ($availableWeight > 0) {
        $overallMean = round($weightedTotal / $availableWeight, 2);
    } elseif (($summary['stored_average_rating'] ?? null) !== null && (float) $summary['stored_average_rating'] > 0) {
        $overallMean = round((float) $summary['stored_average_rating'], 2);
    }

    return [
        'label' => $label,
        'source_weight' => $sourceWeight,
        'evaluation_count' => (int) ($summary['evaluation_count'] ?? 0),
        'evaluator_count' => (int) ($summary['evaluator_count'] ?? 0),
        'subject_count' => (int) ($summary['subject_count'] ?? 0),
        'response_count' => $responseCount,
        'last_updated' => (string) ($summary['last_updated'] ?? ''),
        'categories' => $categories,
        'overall_mean' => $overallMean,
        'weighted_percentage' => individual_faculty_performance_weighted_percentage($overallMean, $sourceWeight),
    ];
}

function individual_faculty_performance_weighted_percentage(?float $mean, float $sourceWeight): ?float
{
    if ($mean === null) {
        return null;
    }

    return round(($mean / 5) * $sourceWeight, 2);
}

function individual_faculty_performance_term_scope(?array $termFilter, array $termOptions): array
{
    if ($termFilter !== null) {
        $targetKey = individual_faculty_performance_term_key(
            (int) ($termFilter['ay_id'] ?? 0),
            (int) ($termFilter['semester'] ?? 0)
        );

        foreach ($termOptions as $option) {
            if ((string) ($option['term_key'] ?? '') === $targetKey) {
                return [
                    'semester_label' => (string) ($option['semester_label'] ?? ''),
                    'academic_year_label' => (string) ($option['academic_year_label'] ?? ''),
                    'note' => 'Filtered to ' . (string) ($option['term_label'] ?? ''),
                ];
            }
        }

        return [
            'semester_label' => format_semester($termFilter['semester'] ?? 0),
            'academic_year_label' => 'AY ID ' . (string) ((int) ($termFilter['ay_id'] ?? 0)),
            'note' => 'Filtered to the selected submitted term.',
        ];
    }

    if ($termOptions === []) {
        return [
            'semester_label' => '________ Semester',
            'academic_year_label' => '________',
            'note' => 'No submitted student-evaluation term is available yet.',
        ];
    }

    if (count($termOptions) === 1) {
        return [
            'semester_label' => (string) ($termOptions[0]['semester_label'] ?? ''),
            'academic_year_label' => (string) ($termOptions[0]['academic_year_label'] ?? ''),
            'note' => 'Consolidated from one submitted term.',
        ];
    }

    return [
        'semester_label' => 'All Submitted Semesters',
        'academic_year_label' => 'All Available',
        'note' => 'Consolidated across ' . format_number(count($termOptions)) . ' submitted terms.',
    ];
}

function individual_faculty_performance_comments(PDO $pdo, int $facultyId, ?array $termFilter, int $limit = 6): array
{
    $studentCondition = "ev.faculty_id = :faculty_id
        AND ev.submission_status = 'submitted'
        AND ev.student_enrollment_id IS NOT NULL
        AND TRIM(COALESCE(ev.comment_text, '')) <> ''";
    $studentParameters = ['faculty_id' => $facultyId];

    if ($termFilter !== null) {
        $studentCondition .= ' AND ev.ay_id = :ay_id AND ev.semester = :semester';
        $studentParameters['ay_id'] = (int) $termFilter['ay_id'];
        $studentParameters['semester'] = (int) $termFilter['semester'];
    }

    $studentStatement = $pdo->prepare(
        "SELECT
            ev.comment_text,
            ev.student_number AS author_label,
            ev.subject_summary AS context_label,
            ev.updated_at
         FROM tbl_student_faculty_evaluations ev
         WHERE " . $studentCondition . "
         ORDER BY ev.updated_at DESC
         LIMIT " . max(1, min(25, $limit))
    );
    $studentStatement->execute($studentParameters);

    $comments = [];
    foreach ($studentStatement->fetchAll() as $row) {
        $comments[] = [
            'source' => 'Student',
            'author_label' => (string) ($row['author_label'] ?? ''),
            'context_label' => (string) ($row['context_label'] ?? ''),
            'text' => trim((string) ($row['comment_text'] ?? '')),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    $supervisorStatement = $pdo->prepare(
        "SELECT
            ev.comment_text,
            ev.subject_text AS context_label,
            ev.updated_at
         FROM tbl_program_chair_faculty_evaluations ev
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
           AND TRIM(COALESCE(ev.comment_text, '')) <> ''
         ORDER BY ev.updated_at DESC
         LIMIT " . max(1, min(25, $limit))
    );
    $supervisorStatement->execute(['faculty_id' => $facultyId]);

    foreach ($supervisorStatement->fetchAll() as $row) {
        $comments[] = [
            'source' => 'Supervisor',
            'author_label' => '',
            'context_label' => (string) ($row['context_label'] ?? ''),
            'text' => trim((string) ($row['comment_text'] ?? '')),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    usort($comments, static function (array $left, array $right): int {
        return strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
    });

    return array_slice($comments, 0, max(1, $limit));
}

function individual_faculty_performance_format_mean($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((float) $value, 2);
}

function individual_faculty_performance_format_percentage($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((float) $value, 2) . '%';
}

function individual_faculty_performance_rating_label(?float $percentage, bool $isComplete): string
{
    if ($percentage === null) {
        return 'No Rating Yet';
    }

    if (!$isComplete) {
        return 'Partial Rating';
    }

    if ($percentage >= 90) {
        return 'Outstanding';
    }

    if ($percentage >= 80) {
        return 'Very Satisfactory';
    }

    if ($percentage >= 70) {
        return 'Satisfactory';
    }

    if ($percentage >= 60) {
        return 'Fair';
    }

    return 'Poor';
}
