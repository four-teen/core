<?php
declare(strict_types=1);

function dashboard_overview(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);

    $statement = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM tbl_student_management) AS total_students,
            (SELECT COUNT(*) FROM tbl_student_management_enrolled_subjects WHERE is_active = 1) AS active_enrollment_rows,
            (SELECT COUNT(*) FROM tbl_subject_masterlist WHERE status = 'active') AS active_subjects,
            (SELECT COUNT(*) FROM tbl_faculty WHERE status = 'active') AS active_faculty_master,
            (SELECT COUNT(DISTINCT faculty_id) FROM tbl_student_management_enrolled_subjects WHERE is_active = 1 AND faculty_id <> 0) AS active_faculty,
            (SELECT COUNT(*) FROM tbl_student_faculty_evaluations WHERE submission_status = 'submitted' AND student_enrollment_id IS NOT NULL) AS submitted_evaluations,
            (SELECT COUNT(*) FROM tbl_student_faculty_evaluations WHERE submission_status = 'draft' AND student_enrollment_id IS NOT NULL) AS draft_evaluations,
            (SELECT COUNT(DISTINCT faculty_id) FROM tbl_student_faculty_evaluations WHERE submission_status = 'submitted' AND faculty_id <> 0 AND student_enrollment_id IS NOT NULL) AS evaluated_faculty"
    );

    return $statement->fetch() ?: [];
}

function dashboard_current_term(PDO $pdo): ?array
{
    $statement = $pdo->query(
        "SELECT
            es.ay_id,
            COALESCE(ay.ay, CONCAT('AY ID ', es.ay_id)) AS academic_year_label,
            es.semester,
            COUNT(*) AS enrollment_rows,
            COUNT(DISTINCT es.student_id) AS students,
            COUNT(DISTINCT es.faculty_id) AS faculty
        FROM tbl_student_management_enrolled_subjects es
        LEFT JOIN tbl_academic_years ay ON ay.ay_id = es.ay_id
        WHERE es.is_active = 1
        GROUP BY es.ay_id, academic_year_label, es.semester
        ORDER BY es.ay_id DESC, es.semester DESC
        LIMIT 1"
    );

    $row = $statement->fetch();
    return $row ?: null;
}

function dashboard_faculty_load(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT
            es.faculty_id,
            TRIM(CONCAT(
                COALESCE(f.last_name, ''),
                CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                COALESCE(f.first_name, ''),
                CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
            )) AS faculty_name,
            COUNT(DISTINCT es.student_id) AS student_count,
            COUNT(DISTINCT CONCAT(es.subject_id, '|', es.section_text, '|', es.ay_id, '|', es.semester)) AS teaching_load,
            COUNT(DISTINCT es.subject_id) AS distinct_subjects,
            GROUP_CONCAT(
                DISTINCT CONCAT(es.subject_code, ' ', es.section_text)
                ORDER BY es.subject_code SEPARATOR ', '
            ) AS assignments
        FROM tbl_student_management_enrolled_subjects es
        LEFT JOIN tbl_faculty f ON f.faculty_id = es.faculty_id
        WHERE es.is_active = 1
          AND es.faculty_id <> 0
        GROUP BY es.faculty_id, faculty_name
        ORDER BY student_count DESC, teaching_load DESC, es.faculty_id ASC
        LIMIT 8"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        if (trim((string) $row['faculty_name']) === '') {
            $rows[$index]['faculty_name'] = 'Faculty #' . (string) $row['faculty_id'];
        }
    }

    return $rows;
}

function dashboard_subject_sections(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT
            es.faculty_id,
            TRIM(CONCAT(
                COALESCE(f.last_name, ''),
                CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                COALESCE(f.first_name, ''),
                CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
            )) AS faculty_name,
            es.subject_code,
            COALESCE(smst.sub_description, es.descriptive_title) AS descriptive_title,
            es.section_text,
            MAX(es.schedule_text) AS schedule_text,
            MAX(es.room_text) AS room_text,
            COUNT(DISTINCT es.student_id) AS student_count
        FROM tbl_student_management_enrolled_subjects es
        LEFT JOIN tbl_faculty f ON f.faculty_id = es.faculty_id
        LEFT JOIN tbl_subject_masterlist smst ON smst.sub_id = es.subject_id
        WHERE es.is_active = 1
        GROUP BY es.faculty_id, faculty_name, es.subject_code, descriptive_title, es.section_text, es.ay_id, es.semester
        ORDER BY student_count DESC, es.subject_code ASC, es.section_text ASC
        LIMIT 8"
    );

    $rows = $statement->fetchAll();
    foreach ($rows as $index => $row) {
        if (trim((string) $row['faculty_name']) === '') {
            $rows[$index]['faculty_name'] = 'Faculty #' . (string) $row['faculty_id'];
        }
    }

    return $rows;
}

function dashboard_recent_evaluations(PDO $pdo, int $limit = 8): array
{
    ensure_evaluation_subject_scope($pdo);
    $limit = max(1, min(100, $limit));

    $statement = $pdo->query(
        "SELECT
            ev.evaluation_id,
            ev.faculty_id,
            COALESCE(
                NULLIF(ev.faculty_name, ''),
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )),
                CONCAT('Faculty #', ev.faculty_id)
            ) AS faculty_name,
            ev.student_number,
            TRIM(CONCAT(
                COALESCE(sm.last_name, ''),
                CASE WHEN sm.last_name IS NULL OR sm.last_name = '' THEN '' ELSE ', ' END,
                COALESCE(sm.first_name, ''),
                CASE WHEN sm.middle_name IS NULL OR sm.middle_name = '' THEN '' ELSE CONCAT(' ', sm.middle_name) END,
                CASE WHEN sm.suffix_name IS NULL OR sm.suffix_name = '' THEN '' ELSE CONCAT(' ', sm.suffix_name) END
            )) AS student_full_name,
            ev.average_rating,
            ev.submission_status,
            ev.completed_at,
            ev.updated_at
        FROM tbl_student_faculty_evaluations ev
        LEFT JOIN tbl_faculty f ON f.faculty_id = ev.faculty_id
        LEFT JOIN tbl_student_management sm ON sm.student_id = ev.student_id
        WHERE ev.student_enrollment_id IS NOT NULL
        ORDER BY ev.updated_at DESC, ev.evaluation_id DESC
        LIMIT " . $limit
    );

    return $statement->fetchAll();
}

function dashboard_evaluation_faculty_summary(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);

    $statement = $pdo->query(
        "SELECT
            ev.faculty_id,
            COALESCE(
                NULLIF(ev.faculty_name, ''),
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )),
                CONCAT('Faculty #', ev.faculty_id)
            ) AS faculty_name,
            COUNT(*) AS evaluation_count,
            SUM(CASE WHEN ev.submission_status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
            SUM(CASE WHEN ev.submission_status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
            COUNT(DISTINCT ev.student_id) AS student_count,
            COUNT(DISTINCT ev.subject_id) AS subject_count,
            ROUND(AVG(NULLIF(ev.average_rating, 0)), 2) AS average_rating,
            MAX(ev.updated_at) AS last_updated
        FROM tbl_student_faculty_evaluations ev
        LEFT JOIN tbl_faculty f ON f.faculty_id = ev.faculty_id
        WHERE ev.student_enrollment_id IS NOT NULL
          AND ev.faculty_id <> 0
        GROUP BY ev.faculty_id, faculty_name
        ORDER BY submitted_count DESC, evaluation_count DESC, faculty_name ASC"
    );

    return $statement->fetchAll();
}

function dashboard_faculty_evaluation_details(PDO $pdo, int $facultyId): array
{
    ensure_evaluation_subject_scope($pdo);

    $statement = $pdo->prepare(
        "SELECT
            ev.evaluation_id,
            ev.faculty_id,
            COALESCE(
                NULLIF(ev.faculty_name, ''),
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )),
                CONCAT('Faculty #', ev.faculty_id)
            ) AS faculty_name,
            ev.student_number,
            TRIM(CONCAT(
                COALESCE(sm.last_name, ''),
                CASE WHEN sm.last_name IS NULL OR sm.last_name = '' THEN '' ELSE ', ' END,
                COALESCE(sm.first_name, ''),
                CASE WHEN sm.middle_name IS NULL OR sm.middle_name = '' THEN '' ELSE CONCAT(' ', sm.middle_name) END,
                CASE WHEN sm.suffix_name IS NULL OR sm.suffix_name = '' THEN '' ELSE CONCAT(' ', sm.suffix_name) END
            )) AS student_full_name,
            ev.subject_code,
            ev.subject_summary,
            ev.term_label,
            ev.question_count,
            ev.total_score,
            ev.average_rating,
            ev.submission_status,
            ev.final_submitted_at,
            ev.completed_at,
            ev.updated_at
        FROM tbl_student_faculty_evaluations ev
        LEFT JOIN tbl_faculty f ON f.faculty_id = ev.faculty_id
        LEFT JOIN tbl_student_management sm ON sm.student_id = ev.student_id
        WHERE ev.student_enrollment_id IS NOT NULL
          AND ev.faculty_id = :faculty_id
        ORDER BY ev.updated_at DESC, ev.evaluation_id DESC"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    return $statement->fetchAll();
}

function dashboard_evaluation_rating_trend(PDO $pdo, int $months = 12): array
{
    ensure_evaluation_subject_scope($pdo);
    $months = max(3, min(24, $months));

    $startDate = new DateTimeImmutable('first day of this month 00:00:00');
    $startDate = $startDate->modify('-' . ($months - 1) . ' months');

    $periodKeys = [];
    $periodLabels = [];
    for ($index = 0; $index < $months; $index++) {
        $period = $startDate->modify('+' . $index . ' months');
        $periodKey = $period->format('Y-m');
        $periodKeys[$periodKey] = $index;
        $periodLabels[] = $period->format('M Y');
    }

    $categoryLabels = [];
    foreach (evaluation_question_bank() as $category) {
        $categoryKey = (string) ($category['key'] ?? '');
        if ($categoryKey === '') {
            continue;
        }

        $categoryLabels[$categoryKey] = ucwords(strtolower((string) ($category['title'] ?? $categoryKey)));
    }

    $seriesByCategory = [];
    foreach ($categoryLabels as $categoryKey => $categoryLabel) {
        $seriesByCategory[$categoryKey] = array_fill(0, $months, null);
    }

    $sql = "SELECT
                DATE_FORMAT(COALESCE(ev.final_submitted_at, ev.updated_at, ev.completed_at, ans.created_at), '%Y-%m') AS period_key,
                ans.category_key,
                MAX(ans.category_title) AS category_title,
                ROUND(AVG(ans.rating), 2) AS average_rating
            FROM tbl_student_faculty_evaluation_answers ans
            INNER JOIN tbl_student_faculty_evaluations ev
                ON ev.evaluation_id = ans.evaluation_id
            WHERE ev.student_enrollment_id IS NOT NULL
              AND ev.submission_status = 'submitted'
              AND ans.rating BETWEEN 1 AND 5
              AND COALESCE(ev.final_submitted_at, ev.updated_at, ev.completed_at, ans.created_at) >= :start_date
            GROUP BY period_key, ans.category_key
            ORDER BY period_key ASC, ans.category_key ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute(['start_date' => $startDate->format('Y-m-d H:i:s')]);

    $hasData = false;
    foreach ($statement->fetchAll() as $row) {
        $periodKey = (string) ($row['period_key'] ?? '');
        $categoryKey = (string) ($row['category_key'] ?? '');

        if (!isset($periodKeys[$periodKey])) {
            continue;
        }

        if (!isset($seriesByCategory[$categoryKey])) {
            $categoryLabels[$categoryKey] = ucwords(strtolower((string) ($row['category_title'] ?? $categoryKey)));
            $seriesByCategory[$categoryKey] = array_fill(0, $months, null);
        }

        $seriesByCategory[$categoryKey][$periodKeys[$periodKey]] = (float) ($row['average_rating'] ?? 0);
        $hasData = true;
    }

    $series = [];
    foreach ($categoryLabels as $categoryKey => $categoryLabel) {
        $series[] = [
            'name' => $categoryLabel,
            'data' => $seriesByCategory[$categoryKey] ?? array_fill(0, $months, null),
        ];
    }

    return [
        'labels' => $periodLabels,
        'series' => $series,
        'hasData' => $hasData,
    ];
}

function dashboard_evaluation_category_averages(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);

    $categories = [];
    foreach (evaluation_question_bank() as $category) {
        $categoryKey = (string) ($category['key'] ?? '');
        if ($categoryKey === '') {
            continue;
        }

        $categoryTitle = ucwords(strtolower((string) ($category['title'] ?? $categoryKey)));
        $categories[$categoryKey] = [
            'name' => $categoryTitle,
            'label' => dashboard_category_axis_label($categoryTitle),
            'average' => null,
            'responses' => 0,
            'evaluations' => 0,
        ];
    }

    $sql = "SELECT
                ans.category_key,
                MAX(ans.category_title) AS category_title,
                ROUND(AVG(ans.rating), 2) AS average_rating,
                COUNT(*) AS response_count,
                COUNT(DISTINCT ev.evaluation_id) AS evaluation_count
            FROM tbl_student_faculty_evaluation_answers ans
            INNER JOIN tbl_student_faculty_evaluations ev
                ON ev.evaluation_id = ans.evaluation_id
            WHERE ev.student_enrollment_id IS NOT NULL
              AND ev.submission_status = 'submitted'
              AND ans.rating BETWEEN 1 AND 5
            GROUP BY ans.category_key
            ORDER BY ans.category_key ASC";

    $statement = $pdo->query($sql);

    $hasData = false;
    foreach ($statement->fetchAll() as $row) {
        $categoryKey = (string) ($row['category_key'] ?? '');
        if (!isset($categories[$categoryKey])) {
            $categoryTitle = ucwords(strtolower((string) ($row['category_title'] ?? $categoryKey)));
            $categories[$categoryKey] = [
                'name' => $categoryTitle,
                'label' => dashboard_category_axis_label($categoryTitle),
                'average' => null,
                'responses' => 0,
                'evaluations' => 0,
            ];
        }

        $categories[$categoryKey]['average'] = (float) ($row['average_rating'] ?? 0);
        $categories[$categoryKey]['responses'] = (int) ($row['response_count'] ?? 0);
        $categories[$categoryKey]['evaluations'] = (int) ($row['evaluation_count'] ?? 0);
        $hasData = true;
    }

    $details = [];
    $highest = null;
    $baseColors = ['#696cff', '#03c3ec', '#f29900', '#34a853', '#ff6b35'];
    $index = 0;

    foreach ($categories as $category) {
        $value = $category['average'];
        $details[] = [
            'name' => $category['name'],
            'label' => $category['label'],
            'average' => $value,
            'responses' => $category['responses'],
            'evaluations' => $category['evaluations'],
            'color' => $baseColors[$index % count($baseColors)],
        ];

        if ($value !== null && ($highest === null || $value > (float) $highest['average'])) {
            $highest = [
                'name' => $category['name'],
                'average' => $value,
            ];
        }

        $index++;
    }

    usort($details, static function (array $left, array $right): int {
        return ((float) ($right['average'] ?? -1)) <=> ((float) ($left['average'] ?? -1));
    });

    $labels = [];
    $data = [];
    $colors = [];
    foreach ($details as $detail) {
        $labels[] = $detail['label'];
        $data[] = $detail['average'];
        $colors[] = $detail['color'];
    }

    $scale = dashboard_rating_axis_scale($data);

    return [
        'labels' => $labels,
        'series' => [
            [
                'name' => 'Average Rating',
                'data' => $data,
            ],
        ],
        'colors' => $colors,
        'details' => $details,
        'highest' => $highest,
        'hasData' => $hasData,
        'xMin' => $scale['min'],
        'xMax' => $scale['max'],
        'tickAmount' => $scale['tickAmount'],
    ];
}

function dashboard_evaluation_college_averages(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);

    $sql = "SELECT
                es.college_id,
                COUNT(*) AS evaluation_count,
                COUNT(DISTINCT ev.student_id) AS student_count,
                COUNT(DISTINCT es.program_id) AS program_count,
                ROUND(AVG(NULLIF(ev.average_rating, 0)), 2) AS average_rating
            FROM tbl_student_faculty_evaluations ev
            INNER JOIN tbl_student_management_enrolled_subjects es
                ON es.student_enrollment_id = ev.student_enrollment_id
            WHERE ev.student_enrollment_id IS NOT NULL
              AND ev.submission_status = 'submitted'
              AND ev.average_rating > 0
            GROUP BY es.college_id
            ORDER BY average_rating DESC, evaluation_count DESC, es.college_id ASC";

    $statement = $pdo->query($sql);
    $rows = $statement->fetchAll();

    $labels = [];
    $data = [];
    $colors = [];
    $details = [];
    $baseColors = ['#03c3ec', '#696cff', '#34a853', '#f29900', '#ff6b35'];
    $highest = null;

    foreach ($rows as $index => $row) {
        $collegeId = (int) ($row['college_id'] ?? 0);
        $collegeLabel = $collegeId > 0 ? 'College ' . $collegeId : 'Unassigned College';
        $average = (float) ($row['average_rating'] ?? 0);

        $labels[] = $collegeLabel;
        $data[] = $average;
        $colors[] = $baseColors[$index % count($baseColors)];
        $details[] = [
            'name' => $collegeLabel,
            'average' => $average,
            'evaluations' => (int) ($row['evaluation_count'] ?? 0),
            'students' => (int) ($row['student_count'] ?? 0),
            'programs' => (int) ($row['program_count'] ?? 0),
        ];

        if ($highest === null || $average > (float) $highest['average']) {
            $highest = [
                'name' => $collegeLabel,
                'average' => $average,
            ];
        }
    }

    $scale = dashboard_rating_axis_scale($data);

    return [
        'labels' => $labels,
        'series' => [
            [
                'name' => 'Average Rating',
                'data' => $data,
            ],
        ],
        'colors' => $colors,
        'details' => $details,
        'highest' => $highest,
        'hasData' => $rows !== [],
        'xMin' => $scale['min'],
        'xMax' => $scale['max'],
        'tickAmount' => $scale['tickAmount'],
    ];
}

function dashboard_rating_axis_scale(array $values): array
{
    $numericValues = array_values(array_filter($values, static function ($value): bool {
        return $value !== null && is_numeric($value);
    }));

    if ($numericValues === []) {
        return [
            'min' => 0,
            'max' => 5,
            'tickAmount' => 5,
        ];
    }

    $minimum = min($numericValues);
    $maximum = max($numericValues);
    $axisMin = max(0, floor(($minimum - 0.08) * 10) / 10);
    $axisMax = min(5, ceil(($maximum + 0.08) * 10) / 10);

    if ($axisMax - $axisMin < 0.4) {
        $axisMin = max(0, $axisMin - 0.1);
        $axisMax = min(5, $axisMax + 0.1);
    }

    return [
        'min' => $axisMin,
        'max' => $axisMax,
        'tickAmount' => max(2, (int) round(($axisMax - $axisMin) / 0.1)),
    ];
}

function dashboard_category_axis_label(string $categoryTitle): array
{
    $normalized = strtolower($categoryTitle);

    if ($normalized === 'knowledge of subject matter') {
        return ['Knowledge of', 'Subject Matter'];
    }

    if ($normalized === 'teaching for independent learning') {
        return ['Teaching for', 'Independent Learning'];
    }

    if ($normalized === 'management of learning') {
        return ['Management of', 'Learning'];
    }

    return [$categoryTitle];
}

function dashboard_student_preview_list(PDO $pdo, string $search = ''): array
{
    $sql = "SELECT
                sm.student_id,
                sm.student_number,
                sm.first_name,
                sm.last_name,
                sm.middle_name,
                sm.suffix_name,
                sm.email_address,
                sm.year_level,
                sm.ay_id,
                COALESCE(ay.ay, CONCAT('AY ID ', sm.ay_id)) AS academic_year_label,
                sm.semester,
                p.program_code,
                p.program_name,
                MAX(sm.updated_at) AS last_updated,
                COUNT(DISTINCT es.student_enrollment_id) AS enrolled_subjects,
                COUNT(DISTINCT es.faculty_id) AS faculty_count
            FROM tbl_student_management sm
            LEFT JOIN tbl_program p ON p.program_id = sm.program_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = sm.ay_id
            LEFT JOIN tbl_student_management_enrolled_subjects es
                ON es.student_id = sm.student_id
               AND es.is_active = 1";

    $conditions = [];
    $parameters = [];

    if (trim($search) !== '') {
        $conditions[] = "(sm.student_number LIKE :search
            OR sm.email_address LIKE :search_email
            OR sm.first_name LIKE :search_first_name
            OR sm.last_name LIKE :search_last_name)";
        $searchValue = '%' . trim($search) . '%';
        $parameters['search'] = $searchValue;
        $parameters['search_email'] = $searchValue;
        $parameters['search_first_name'] = $searchValue;
        $parameters['search_last_name'] = $searchValue;
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= " GROUP BY
                sm.student_id,
                sm.student_number,
                sm.first_name,
                sm.last_name,
                sm.middle_name,
                sm.suffix_name,
                sm.email_address,
                sm.year_level,
                sm.ay_id,
                academic_year_label,
                sm.semester,
                p.program_code,
                p.program_name
              ORDER BY last_updated DESC, sm.student_id DESC
              LIMIT 10";

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    $rows = $statement->fetchAll();

    foreach ($rows as $index => $row) {
        $rows[$index]['full_name'] = person_full_name(
            $row['last_name'] ?? '',
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['suffix_name'] ?? ''
        );
    }

    return $rows;
}
