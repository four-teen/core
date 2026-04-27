<?php
declare(strict_types=1);

function individual_faculty_performance_categories(): array
{
    return [
        'management_of_teaching_and_learning' => [
            'title' => 'MANAGEMENT OF TEACHING AND LEARNING',
            'weight' => null,
        ],
        'content_knowledge_pedagogy_and_technology' => [
            'title' => 'CONTENT KNOWLEDGE, PEDAGOGY AND TECHNOLOGY',
            'weight' => null,
        ],
        'commitment_and_transparency' => [
            'title' => 'COMMITMENT AND TRANSPARENCY',
            'weight' => null,
        ],
    ];
}

function individual_faculty_performance_student_categories(array $categoryRows): array
{
    return individual_faculty_performance_categories();
}

function individual_faculty_performance_normalize_category_key(string $categoryKey): string
{
    $categoryKey = trim($categoryKey);

    if (isset(individual_faculty_performance_categories()[$categoryKey])) {
        return $categoryKey;
    }

    $legacyMap = [
        'management_of_learning' => 'management_of_teaching_and_learning',
        'teaching_for_independent_learning' => 'management_of_teaching_and_learning',
        'knowledge_of_subject_matter' => 'content_knowledge_pedagogy_and_technology',
        'commitment' => 'commitment_and_transparency',
    ];

    return $legacyMap[$categoryKey] ?? '';
}

function individual_faculty_performance_category_title(string $categoryKey): string
{
    return (string) (individual_faculty_performance_categories()[$categoryKey]['title'] ?? $categoryKey);
}

function individual_faculty_performance_normalize_category_rows(array $rows): array
{
    $categoryTotals = [];

    foreach ($rows as $row) {
        $categoryKey = individual_faculty_performance_normalize_category_key((string) ($row['category_key'] ?? ''));
        if ($categoryKey === '') {
            continue;
        }

        if (!isset($categoryTotals[$categoryKey])) {
            $categoryTotals[$categoryKey] = [
                'category_key' => $categoryKey,
                'category_title' => individual_faculty_performance_category_title($categoryKey),
                'weighted_total' => 0.0,
                'response_count' => 0,
                'evaluation_count' => 0,
            ];
        }

        $responseCount = (int) ($row['response_count'] ?? 0);
        $evaluationCount = (int) ($row['evaluation_count'] ?? 0);
        $meanRating = $row['mean_rating'] !== null ? (float) $row['mean_rating'] : null;

        if ($meanRating !== null && $responseCount > 0) {
            $categoryTotals[$categoryKey]['weighted_total'] += $meanRating * $responseCount;
            $categoryTotals[$categoryKey]['response_count'] += $responseCount;
        }

        $categoryTotals[$categoryKey]['evaluation_count'] += $evaluationCount;
    }

    $normalizedRows = [];
    foreach ($categoryTotals as $categoryTotal) {
        $responseCount = (int) ($categoryTotal['response_count'] ?? 0);
        if ($responseCount <= 0) {
            continue;
        }

        $normalizedRows[] = [
            'category_key' => (string) $categoryTotal['category_key'],
            'category_title' => (string) $categoryTotal['category_title'],
            'mean_rating' => round(((float) $categoryTotal['weighted_total']) / $responseCount, 2),
            'response_count' => $responseCount,
            'evaluation_count' => (int) $categoryTotal['evaluation_count'],
        ];
    }

    return $normalizedRows;
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
           AND (
                COALESCE(sr.student_evaluation_count, 0) > 0
                OR COALESCE(pr.supervisor_evaluation_count, 0) > 0
           )
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
    $supervisorSection = individual_faculty_performance_supervisor_section($pdo, $facultyId, $faculty);

    if ((int) $studentSection['evaluation_count'] <= 0 && (int) $supervisorSection['evaluation_count'] <= 0) {
        return null;
    }

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
        'evaluated_by_name' => individual_faculty_performance_program_chair_evaluator_name($pdo, $facultyId),
        'comments' => individual_faculty_performance_comments($pdo, $facultyId, $termFilter, 12, $faculty),
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

function individual_faculty_performance_program_chair_evaluator_name(PDO $pdo, int $facultyId): string
{
    $statement = $pdo->prepare(
        "SELECT um.full_name, um.email_address
         FROM tbl_program_chair_faculty_evaluations ev
         LEFT JOIN tbl_user_management um
            ON um.user_management_id = ev.program_chair_user_management_id
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
         ORDER BY
            COALESCE(ev.final_submitted_at, ev.updated_at, ev.completed_at, ev.created_at) DESC,
            ev.program_chair_evaluation_id DESC
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    $row = $statement->fetch();
    if (!$row) {
        return '';
    }

    return role_evaluation_user_display_name([
        'full_name' => (string) ($row['full_name'] ?? ''),
        'email_address' => (string) ($row['email_address'] ?? ''),
    ]);
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
        individual_faculty_performance_normalize_category_rows($categoryStatement->fetchAll()),
        true
    );
}

function individual_faculty_performance_supervisor_section(PDO $pdo, int $facultyId, array $faculty = []): array
{
    ensure_role_evaluation_tables($pdo);

    $summary = [
        'evaluation_count' => 0,
        'evaluator_count' => 0,
        'subject_count' => 0,
        'stored_average_rating' => null,
        'last_updated' => '',
    ];
    $categories = individual_faculty_performance_supervisor_rating_sources($pdo, $facultyId, $faculty, $summary);

    return individual_faculty_performance_build_rating_section(
        'Supervisors Rating',
        40,
        $summary,
        $categories,
        true
    );
}

function individual_faculty_performance_supervisor_rating_sources(PDO $pdo, int $facultyId, array $faculty, array &$summary): array
{
    $categoryTotals = [];
    $sourceEvaluationIds = [];
    $evaluatorIds = [];
    $subjectIds = [];
    $averageRatings = [];
    $lastUpdated = '';
    $programChairUserIds = [];

    $directStatement = $pdo->prepare(
        "SELECT
            ev.program_chair_evaluation_id,
            ev.program_chair_user_management_id,
            ev.subject_id,
            ev.average_rating,
            ev.updated_at,
            ans.category_key,
            ans.category_title,
            ans.rating
         FROM tbl_program_chair_faculty_evaluations ev
         LEFT JOIN tbl_program_chair_faculty_evaluation_answers ans
            ON ans.program_chair_evaluation_id = ev.program_chair_evaluation_id
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
         ORDER BY ev.program_chair_evaluation_id ASC, ans.question_order ASC"
    );
    $directStatement->execute(['faculty_id' => $facultyId]);

    foreach ($directStatement->fetchAll() as $row) {
        $sourceKey = 'program_chair:' . (string) ((int) ($row['program_chair_evaluation_id'] ?? 0));
        $sourceEvaluationIds[$sourceKey] = true;

        $programChairUserId = (int) ($row['program_chair_user_management_id'] ?? 0);
        if ($programChairUserId > 0) {
            $programChairUserIds[$programChairUserId] = true;
            $evaluatorIds['program_chair:' . (string) $programChairUserId] = true;
        }

        $subjectId = (int) ($row['subject_id'] ?? 0);
        if ($subjectId > 0) {
            $subjectIds[$subjectId] = true;
        }

        $averageRating = (float) ($row['average_rating'] ?? 0);
        if ($averageRating > 0) {
            $averageRatings[$sourceKey] = $averageRating;
        }

        $lastUpdated = individual_faculty_performance_latest_datetime($lastUpdated, (string) ($row['updated_at'] ?? ''));

        $categoryKey = individual_faculty_performance_normalize_category_key((string) ($row['category_key'] ?? ''));
        $rating = (int) ($row['rating'] ?? 0);
        if ($categoryKey !== '' && $rating >= 1 && $rating <= 5) {
            individual_faculty_performance_add_supervisor_category_rating(
                $categoryTotals,
                $categoryKey,
                $rating,
                individual_faculty_performance_category_title($categoryKey),
                $sourceKey
            );
        }
    }

    $facultyUser = individual_faculty_performance_faculty_user_management($pdo, $faculty);
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

    foreach (individual_faculty_performance_dean_user_ids_for_program_chairs($pdo, array_keys($programChairUserIds)) as $deanUserId) {
        $deanUserIds[$deanUserId] = true;
    }

    $roleEvaluationIds = [];
    foreach (individual_faculty_performance_role_evaluation_ids_for_targets($pdo, 'dean', 'program_chair', array_keys($programChairUserIds)) as $row) {
        $roleEvaluationId = (int) ($row['role_evaluation_id'] ?? 0);
        if ($roleEvaluationId > 0) {
            $roleEvaluationIds[$roleEvaluationId] = true;
        }

        $deanUserId = (int) ($row['evaluator_user_management_id'] ?? 0);
        if ($deanUserId > 0) {
            $deanUserIds[$deanUserId] = true;
        }
    }

    foreach (individual_faculty_performance_role_evaluation_ids_for_targets($pdo, 'director', 'dean', array_keys($deanUserIds)) as $row) {
        $roleEvaluationId = (int) ($row['role_evaluation_id'] ?? 0);
        if ($roleEvaluationId > 0) {
            $roleEvaluationIds[$roleEvaluationId] = true;
        }
    }

    if ($roleEvaluationIds !== []) {
        $placeholders = implode(',', array_fill(0, count($roleEvaluationIds), '?'));
        $roleStatement = $pdo->prepare(
            "SELECT
                ev.role_evaluation_id,
                ev.evaluator_user_management_id,
                ev.average_rating,
                ev.updated_at,
                ans.category_key,
                ans.rating
             FROM tbl_role_evaluations ev
             LEFT JOIN tbl_role_evaluation_answers ans
                ON ans.role_evaluation_id = ev.role_evaluation_id
             WHERE ev.role_evaluation_id IN (" . $placeholders . ")
               AND ev.submission_status = 'submitted'
             ORDER BY ev.role_evaluation_id ASC, ans.question_order ASC"
        );
        $roleStatement->execute(array_keys($roleEvaluationIds));

        foreach ($roleStatement->fetchAll() as $row) {
            $sourceKey = 'role:' . (string) ((int) ($row['role_evaluation_id'] ?? 0));
            $sourceEvaluationIds[$sourceKey] = true;

            $evaluatorId = (int) ($row['evaluator_user_management_id'] ?? 0);
            if ($evaluatorId > 0) {
                $evaluatorIds['role:' . (string) $evaluatorId] = true;
            }

            $averageRating = (float) ($row['average_rating'] ?? 0);
            if ($averageRating > 0) {
                $averageRatings[$sourceKey] = $averageRating;
            }

            $lastUpdated = individual_faculty_performance_latest_datetime($lastUpdated, (string) ($row['updated_at'] ?? ''));

            $categoryKey = individual_faculty_performance_role_category_key((string) ($row['category_key'] ?? ''));
            $rating = (int) ($row['rating'] ?? 0);
            if ($categoryKey !== '' && $rating >= 1 && $rating <= 5) {
                individual_faculty_performance_add_supervisor_category_rating(
                    $categoryTotals,
                    $categoryKey,
                    $rating,
                    individual_faculty_performance_category_title($categoryKey),
                    $sourceKey
                );
            }
        }
    }

    $categoryRows = [];
    foreach ($categoryTotals as $categoryKey => $categoryTotal) {
        $categoryTotal = $categoryTotals[$categoryKey];
        $responseCount = (int) ($categoryTotal['response_count'] ?? 0);
        if ($responseCount <= 0) {
            continue;
        }

        $categoryRows[] = [
            'category_key' => $categoryKey,
            'category_title' => (string) ($categoryTotal['category_title'] ?? $categoryKey),
            'mean_rating' => round(((float) $categoryTotal['total']) / $responseCount, 2),
            'response_count' => $responseCount,
            'evaluation_count' => count($categoryTotal['evaluation_ids'] ?? []),
        ];
    }

    $summary = [
        'evaluation_count' => count($sourceEvaluationIds),
        'evaluator_count' => count($evaluatorIds),
        'subject_count' => count($subjectIds),
        'stored_average_rating' => $averageRatings !== []
            ? round(array_sum($averageRatings) / count($averageRatings), 2)
            : null,
        'last_updated' => $lastUpdated,
    ];

    return $categoryRows;
}

function individual_faculty_performance_add_supervisor_category_rating(
    array &$categoryTotals,
    string $categoryKey,
    int $rating,
    string $categoryTitle,
    string $sourceKey
): void
{
    if (!isset($categoryTotals[$categoryKey])) {
        $categoryTotals[$categoryKey] = [
            'category_title' => $categoryTitle,
            'total' => 0,
            'response_count' => 0,
            'evaluation_ids' => [],
        ];
    }

    $categoryTotals[$categoryKey]['total'] += $rating;
    $categoryTotals[$categoryKey]['response_count']++;
    $categoryTotals[$categoryKey]['evaluation_ids'][$sourceKey] = true;
}

function individual_faculty_performance_role_category_key(string $categoryKey): string
{
    $normalizedCategoryKey = individual_faculty_performance_normalize_category_key($categoryKey);

    $map = [
        'professional_commitment' => 'commitment_and_transparency',
        'leadership_and_supervision' => 'management_of_teaching_and_learning',
        'program_management' => 'management_of_teaching_and_learning',
        'collaboration_and_service' => 'content_knowledge_pedagogy_and_technology',
    ];

    return $normalizedCategoryKey !== '' ? $normalizedCategoryKey : ($map[trim($categoryKey)] ?? '');
}

function individual_faculty_performance_latest_datetime(string $current, string $candidate): string
{
    $candidate = trim($candidate);
    if ($candidate === '') {
        return $current;
    }

    if ($current === '' || strcmp($candidate, $current) > 0) {
        return $candidate;
    }

    return $current;
}

function individual_faculty_performance_dean_user_ids_for_program_chairs(PDO $pdo, array $programChairUserIds): array
{
    $programChairUserIds = array_values(array_unique(array_filter(array_map('intval', $programChairUserIds))));
    if ($programChairUserIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($programChairUserIds), '?'));
    $statement = $pdo->prepare(
        "SELECT DISTINCT evaluator_user_management_id
         FROM tbl_role_evaluator_assignments
         WHERE evaluator_role = 'dean'
           AND evaluatee_role = 'program_chair'
           AND is_active = 1
           AND evaluatee_user_management_id IN (" . $placeholders . ")"
    );
    $statement->execute($programChairUserIds);

    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function individual_faculty_performance_role_evaluation_ids_for_targets(PDO $pdo, string $evaluatorRole, string $targetRole, array $targetUserIds): array
{
    $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds))));
    if ($targetUserIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
    $statement = $pdo->prepare(
        "SELECT role_evaluation_id, evaluator_user_management_id, evaluatee_user_management_id
         FROM tbl_role_evaluations
         WHERE evaluator_role = ?
           AND evaluatee_role = ?
           AND submission_status = 'submitted'
           AND evaluatee_user_management_id IN (" . $placeholders . ")"
    );
    $statement->execute(array_merge([$evaluatorRole, $targetRole], $targetUserIds));

    return $statement->fetchAll();
}

function individual_faculty_performance_faculty_user_management(PDO $pdo, array $faculty): ?array
{
    $facultyTokens = individual_faculty_performance_name_tokens(
        implode(' ', [
            (string) ($faculty['last_name'] ?? ''),
            (string) ($faculty['first_name'] ?? ''),
            (string) ($faculty['middle_name'] ?? ''),
            (string) ($faculty['ext_name'] ?? ''),
        ])
    );

    if (count($facultyTokens) < 2) {
        return null;
    }

    $statement = $pdo->query(
        "SELECT user_management_id, email_address, full_name, account_role, is_active
         FROM tbl_user_management
         WHERE is_active = 1
           AND account_role IN ('program_chair', 'dean', 'director')"
    );

    $bestMatch = null;
    $bestScore = 0;
    foreach ($statement->fetchAll() as $user) {
        $userTokens = individual_faculty_performance_name_tokens((string) ($user['full_name'] ?? ''));
        if (count($userTokens) < 2) {
            continue;
        }

        $missingTokens = array_diff($userTokens, $facultyTokens);
        if ($missingTokens !== []) {
            continue;
        }

        $score = count($userTokens);
        if ($score > $bestScore) {
            $bestMatch = $user;
            $bestScore = $score;
        }
    }

    return $bestMatch;
}

function individual_faculty_performance_name_tokens(string $name): array
{
    $normalized = strtolower($name);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
    $parts = preg_split('/\s+/', trim((string) $normalized));
    $tokens = [];

    foreach ($parts ?: [] as $part) {
        if ($part === '') {
            continue;
        }

        $tokens[$part] = true;
    }

    return array_keys($tokens);
}

function individual_faculty_performance_build_rating_section(
    string $label,
    float $sourceWeight,
    array $summary,
    array $categoryRows,
    bool $useSavedStudentCategories = false
): array
{
    $categoryByKey = [];
    foreach ($categoryRows as $row) {
        $categoryByKey[(string) ($row['category_key'] ?? '')] = $row;
    }

    $categoryCatalog = $useSavedStudentCategories
        ? individual_faculty_performance_student_categories($categoryRows)
        : individual_faculty_performance_categories();

    $categories = [];
    $weightedTotal = 0.0;
    $availableWeight = 0.0;
    $responseCount = 0;

    foreach ($categoryCatalog as $key => $category) {
        $row = $categoryByKey[$key] ?? null;
        $mean = $row !== null && $row['mean_rating'] !== null ? (float) $row['mean_rating'] : null;
        $weight = $category['weight'] !== null ? (float) $category['weight'] : null;

        if ($mean !== null && $weight !== null) {
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
    if ($useSavedStudentCategories && ($summary['stored_average_rating'] ?? null) !== null && (float) $summary['stored_average_rating'] > 0) {
        $overallMean = round((float) $summary['stored_average_rating'], 2);
    } elseif ($availableWeight > 0) {
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

function individual_faculty_performance_comments(PDO $pdo, int $facultyId, ?array $termFilter, int $limit = 12, array $faculty = []): array
{
    ensure_role_evaluation_tables($pdo);

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
        individual_faculty_performance_add_comment(
            $comments,
            'Student',
            (string) ($row['author_label'] ?? ''),
            (string) ($row['context_label'] ?? ''),
            (string) ($row['comment_text'] ?? ''),
            (string) ($row['updated_at'] ?? '')
        );
    }

    $programChairUserIds = [];
    $deanUserIds = [];
    $supervisorComments = [];
    $supervisorStatement = $pdo->prepare(
        "SELECT
            ev.comment_text,
            ev.subject_text AS context_label,
            ev.updated_at,
            ev.program_chair_user_management_id,
            um.full_name AS evaluator_name,
            um.email_address AS evaluator_email
         FROM tbl_program_chair_faculty_evaluations ev
         LEFT JOIN tbl_user_management um
            ON um.user_management_id = ev.program_chair_user_management_id
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
         ORDER BY ev.updated_at DESC"
    );
    $supervisorStatement->execute(['faculty_id' => $facultyId]);

    foreach ($supervisorStatement->fetchAll() as $row) {
        $programChairUserId = (int) ($row['program_chair_user_management_id'] ?? 0);
        if ($programChairUserId > 0) {
            $programChairUserIds[$programChairUserId] = true;
        }

        individual_faculty_performance_add_comment(
            $supervisorComments,
            'Program Chair',
            role_evaluation_user_display_name([
                'full_name' => (string) ($row['evaluator_name'] ?? ''),
                'email_address' => (string) ($row['evaluator_email'] ?? ''),
            ]),
            (string) ($row['context_label'] ?? ''),
            (string) ($row['comment_text'] ?? ''),
            (string) ($row['updated_at'] ?? '')
        );
    }

    $facultyUser = $faculty !== [] ? individual_faculty_performance_faculty_user_management($pdo, $faculty) : null;
    $facultyUserRole = $facultyUser !== null
        ? user_management_normalize_role((string) ($facultyUser['account_role'] ?? ''))
        : '';
    $facultyUserId = $facultyUser !== null ? (int) ($facultyUser['user_management_id'] ?? 0) : 0;

    if ($facultyUserRole === 'program_chair' && $facultyUserId > 0) {
        $programChairUserIds[$facultyUserId] = true;
    }

    if ($facultyUserRole === 'dean' && $facultyUserId > 0) {
        $deanUserIds[$facultyUserId] = true;
    }

    foreach (individual_faculty_performance_dean_user_ids_for_program_chairs($pdo, array_keys($programChairUserIds)) as $deanUserId) {
        $deanUserIds[$deanUserId] = true;
    }

    $deanComments = individual_faculty_performance_role_evaluation_comments(
        $pdo,
        'dean',
        'program_chair',
        array_keys($programChairUserIds),
        'Dean',
        $deanUserIds
    );

    $directorComments = individual_faculty_performance_role_evaluation_comments(
        $pdo,
        'director',
        'dean',
        array_keys($deanUserIds),
        'Campus Director'
    );

    foreach (array_merge($supervisorComments, $deanComments, $directorComments) as $comment) {
        $comments[] = $comment;
    }

    usort($comments, static function (array $left, array $right): int {
        return strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
    });

    $supervisorCommentCount = count($supervisorComments) + count($deanComments) + count($directorComments);

    return array_slice($comments, 0, max(max(1, $limit), $supervisorCommentCount));
}

function individual_faculty_performance_add_comment(
    array &$comments,
    string $source,
    string $authorLabel,
    string $contextLabel,
    string $text,
    string $updatedAt
): void {
    $text = trim($text);
    if ($text === '') {
        return;
    }

    $comments[] = [
        'source' => $source,
        'author_label' => trim($authorLabel),
        'context_label' => trim($contextLabel),
        'text' => $text,
        'updated_at' => trim($updatedAt),
    ];
}

function individual_faculty_performance_role_evaluation_comments(
    PDO $pdo,
    string $evaluatorRole,
    string $targetRole,
    array $targetUserIds,
    string $sourceLabel,
    ?array &$evaluatorUserIds = null
): array {
    $targetUserIds = array_values(array_unique(array_filter(array_map('intval', $targetUserIds))));
    if ($targetUserIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
    $statement = $pdo->prepare(
        "SELECT
            ev.comment_text,
            ev.updated_at,
            ev.evaluator_user_management_id,
            ev.evaluatee_user_management_id,
            evaluator.full_name AS evaluator_name,
            evaluator.email_address AS evaluator_email,
            evaluatee.full_name AS evaluatee_name,
            evaluatee.email_address AS evaluatee_email
         FROM tbl_role_evaluations ev
         LEFT JOIN tbl_user_management evaluator
            ON evaluator.user_management_id = ev.evaluator_user_management_id
         LEFT JOIN tbl_user_management evaluatee
            ON evaluatee.user_management_id = ev.evaluatee_user_management_id
         WHERE ev.evaluator_role = ?
           AND ev.evaluatee_role = ?
           AND ev.submission_status = 'submitted'
           AND ev.evaluatee_user_management_id IN (" . $placeholders . ")
         ORDER BY ev.updated_at DESC"
    );
    $statement->execute(array_merge([$evaluatorRole, $targetRole], $targetUserIds));

    $comments = [];
    foreach ($statement->fetchAll() as $row) {
        $evaluatorUserId = (int) ($row['evaluator_user_management_id'] ?? 0);
        if ($evaluatorUserIds !== null && $evaluatorUserId > 0) {
            $evaluatorUserIds[$evaluatorUserId] = true;
        }

        individual_faculty_performance_add_comment(
            $comments,
            $sourceLabel,
            role_evaluation_user_display_name([
                'full_name' => (string) ($row['evaluator_name'] ?? ''),
                'email_address' => (string) ($row['evaluator_email'] ?? ''),
            ]),
            role_evaluation_user_display_name([
                'full_name' => (string) ($row['evaluatee_name'] ?? ''),
                'email_address' => (string) ($row['evaluatee_email'] ?? ''),
            ]),
            (string) ($row['comment_text'] ?? ''),
            (string) ($row['updated_at'] ?? '')
        );
    }

    return $comments;
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
