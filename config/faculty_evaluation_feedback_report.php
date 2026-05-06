<?php
declare(strict_types=1);

function faculty_evaluation_feedback_report_faculty_options(PDO $pdo): array
{
    return individual_faculty_performance_faculty_options($pdo);
}

function faculty_evaluation_feedback_report_key_areas(): array
{
    return [
        'Commitment',
        'Knowledge of Subject Matter',
        'Teaching for Independent Learning',
        'Management of Learning',
    ];
}

function faculty_evaluation_feedback_report_report(
    PDO $pdo,
    int $facultyId,
    ?array $termFilter = null,
    array $termOptions = []
): ?array {
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);

    $report = individual_faculty_performance_report($pdo, $facultyId, $termFilter, $termOptions);
    if ($report === null) {
        return null;
    }

    $subjects = faculty_evaluation_feedback_report_subjects($pdo, $facultyId, $termFilter);
    $programCode = faculty_evaluation_feedback_report_program_code($pdo, $facultyId, $report['faculty'], $subjects);
    $analysis = faculty_evaluation_feedback_report_analysis($report);

    $report['feedback'] = [
        'program_code' => $programCode,
        'program_label' => faculty_evaluation_feedback_report_program_label($pdo, $programCode),
        'subjects' => $subjects,
        'subject_line' => faculty_evaluation_feedback_report_limited_list($subjects),
        'term_line' => faculty_evaluation_feedback_report_term_line($report),
        'key_areas' => faculty_evaluation_feedback_report_key_areas(),
        'supervisor_remark' => faculty_evaluation_feedback_report_source_remark($report['supervisor']),
        'student_remark' => faculty_evaluation_feedback_report_source_remark($report['student']),
        'overall_remark' => faculty_evaluation_feedback_report_overall_remark($report),
        'strengths' => $analysis['strengths'],
        'improvements' => $analysis['improvements'],
        'recommendations' => $analysis['recommendations'],
        'program_chair_name' => individual_faculty_performance_program_chair_evaluator_name($pdo, $facultyId),
    ];

    return $report;
}

function faculty_evaluation_feedback_report_program_code(PDO $pdo, int $facultyId, array $faculty, array $subjects = []): string
{
    $programCode = program_chair_faculty_program_code($pdo, $facultyId);
    if ($programCode !== '') {
        return $programCode;
    }

    $facultyUser = individual_faculty_performance_faculty_user_management($pdo, $faculty);
    if ($facultyUser !== null) {
        $facultyUserRole = user_management_normalize_role((string) ($facultyUser['account_role'] ?? ''));
        $facultyUserId = (int) ($facultyUser['user_management_id'] ?? 0);

        if ($facultyUserRole === 'program_chair' && $facultyUserId > 0) {
            $programCode = program_chair_user_program_code($pdo, $facultyUserId);
            if ($programCode !== '') {
                return $programCode;
            }
        }
    }

    $programCode = faculty_evaluation_feedback_report_evaluator_program_code($pdo, $facultyId);
    if ($programCode !== '') {
        return $programCode;
    }

    return faculty_evaluation_feedback_report_subject_program_code($subjects);
}

function faculty_evaluation_feedback_report_evaluator_program_code(PDO $pdo, int $facultyId): string
{
    $statement = $pdo->prepare(
        "SELECT assignment.program_code
         FROM tbl_program_chair_faculty_evaluations ev
         INNER JOIN tbl_program_chair_user_programs assignment
            ON assignment.program_chair_user_management_id = ev.program_chair_user_management_id
           AND assignment.is_active = 1
           AND assignment.program_code <> ''
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
         GROUP BY assignment.program_code
         ORDER BY
            COUNT(*) DESC,
            MAX(COALESCE(ev.final_submitted_at, ev.updated_at, ev.completed_at, ev.created_at)) DESC
         LIMIT 1"
    );
    $statement->execute(['faculty_id' => $facultyId]);

    return program_chair_normalize_program_code((string) ($statement->fetchColumn() ?: ''), true);
}

function faculty_evaluation_feedback_report_subject_program_code(array $subjects): string
{
    $counts = [];
    foreach ($subjects as $subject) {
        if (!preg_match('/\((BSIT|BSIS|BSCS)\s+\d+[A-Z]?\)/i', (string) $subject, $matches)) {
            continue;
        }

        $programCode = program_chair_normalize_program_code((string) $matches[1], true);
        if ($programCode === '') {
            continue;
        }

        $counts[$programCode] = ($counts[$programCode] ?? 0) + 1;
    }

    if ($counts === []) {
        return '';
    }

    arsort($counts);

    return (string) array_key_first($counts);
}

function faculty_evaluation_feedback_report_program_label(PDO $pdo, string $programCode): string
{
    $programCode = program_chair_normalize_program_code($programCode, true);
    if ($programCode === '') {
        return 'Not set';
    }

    $programOptions = program_chair_program_options($pdo);
    if (isset($programOptions[$programCode]['program_label'])) {
        return (string) $programOptions[$programCode]['program_label'];
    }

    return program_chair_program_label($programCode);
}

function faculty_evaluation_feedback_report_subjects(PDO $pdo, int $facultyId, ?array $termFilter = null): array
{
    $subjects = [];

    $studentSql = "SELECT DISTINCT subject_summary
         FROM tbl_student_faculty_evaluations
         WHERE faculty_id = :faculty_id
           AND submission_status = 'submitted'
           AND student_enrollment_id IS NOT NULL
           AND TRIM(COALESCE(subject_summary, '')) <> ''";
    $studentParameters = ['faculty_id' => $facultyId];

    if ($termFilter !== null) {
        $studentSql .= ' AND ay_id = :student_ay_id AND semester = :student_semester';
        $studentParameters['student_ay_id'] = (int) ($termFilter['ay_id'] ?? 0);
        $studentParameters['student_semester'] = (int) ($termFilter['semester'] ?? 0);
    }

    $studentSql .= ' ORDER BY subject_summary ASC';
    $studentStatement = $pdo->prepare($studentSql);
    $studentStatement->execute($studentParameters);

    foreach ($studentStatement->fetchAll(PDO::FETCH_COLUMN) as $subjectLabel) {
        faculty_evaluation_feedback_report_add_subject($subjects, (string) $subjectLabel);
    }

    $supervisorSql = "SELECT DISTINCT
            TRIM(CONCAT(
                COALESCE(subject_code, ''),
                CASE
                    WHEN TRIM(COALESCE(subject_code, '')) <> ''
                     AND TRIM(COALESCE(subject_text, '')) <> ''
                    THEN ' - '
                    ELSE ''
                END,
                COALESCE(subject_text, '')
            )) AS subject_label
         FROM tbl_program_chair_faculty_evaluations ev
         WHERE ev.faculty_id = :faculty_id
           AND ev.submission_status = 'submitted'
           AND (
                TRIM(COALESCE(ev.subject_code, '')) <> ''
                OR TRIM(COALESCE(ev.subject_text, '')) <> ''
           )";
    $supervisorParameters = ['faculty_id' => $facultyId];

    if ($termFilter !== null) {
        $supervisorSql .= " AND EXISTS (
            SELECT 1
            FROM tbl_student_management_enrolled_subjects es
            WHERE es.faculty_id = ev.faculty_id
              AND es.subject_id = ev.subject_id
              AND es.ay_id = :supervisor_ay_id
              AND es.semester = :supervisor_semester
              AND es.is_active = 1
        )";
        $supervisorParameters['supervisor_ay_id'] = (int) ($termFilter['ay_id'] ?? 0);
        $supervisorParameters['supervisor_semester'] = (int) ($termFilter['semester'] ?? 0);
    }

    $supervisorSql .= ' ORDER BY subject_label ASC';
    $supervisorStatement = $pdo->prepare($supervisorSql);
    $supervisorStatement->execute($supervisorParameters);

    foreach ($supervisorStatement->fetchAll(PDO::FETCH_COLUMN) as $subjectLabel) {
        faculty_evaluation_feedback_report_add_subject($subjects, (string) $subjectLabel);
    }

    if ($subjects === []) {
        foreach (faculty_evaluation_feedback_report_active_subjects($pdo, $facultyId, $termFilter) as $subjectLabel) {
            faculty_evaluation_feedback_report_add_subject($subjects, $subjectLabel);
        }
    }

    return array_values($subjects);
}

function faculty_evaluation_feedback_report_active_subjects(PDO $pdo, int $facultyId, ?array $termFilter = null): array
{
    $sql = "SELECT DISTINCT
            TRIM(CONCAT(
                COALESCE(subject_code, ''),
                CASE
                    WHEN TRIM(COALESCE(subject_code, '')) <> ''
                     AND TRIM(COALESCE(descriptive_title, '')) <> ''
                    THEN ' - '
                    ELSE ''
                END,
                COALESCE(descriptive_title, ''),
                CASE
                    WHEN TRIM(COALESCE(section_text, '')) <> ''
                    THEN CONCAT(' (', TRIM(section_text), ')')
                    ELSE ''
                END
            )) AS subject_label
         FROM tbl_student_management_enrolled_subjects
         WHERE faculty_id = :faculty_id
           AND is_active = 1";
    $parameters = ['faculty_id' => $facultyId];

    if ($termFilter !== null) {
        $sql .= ' AND ay_id = :ay_id AND semester = :semester';
        $parameters['ay_id'] = (int) ($termFilter['ay_id'] ?? 0);
        $parameters['semester'] = (int) ($termFilter['semester'] ?? 0);
    }

    $sql .= ' ORDER BY subject_label ASC';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function faculty_evaluation_feedback_report_add_subject(array &$subjects, string $subjectLabel): void
{
    $subjectLabel = faculty_evaluation_feedback_report_normalize_subject_label($subjectLabel);
    if ($subjectLabel === '') {
        return;
    }

    $baseLabel = faculty_evaluation_feedback_report_subject_base_label($subjectLabel);
    $hasSection = $baseLabel !== $subjectLabel;

    foreach ($subjects as $key => $existingSubject) {
        $existingBaseLabel = faculty_evaluation_feedback_report_subject_base_label((string) $existingSubject);
        $existingHasSection = $existingBaseLabel !== (string) $existingSubject;

        if (strcasecmp($existingBaseLabel, $baseLabel) !== 0) {
            continue;
        }

        if (!$hasSection) {
            return;
        }

        if (!$existingHasSection) {
            unset($subjects[$key]);
        }
    }

    $subjects[strtolower($subjectLabel)] = $subjectLabel;
}

function faculty_evaluation_feedback_report_normalize_subject_label(string $subjectLabel): string
{
    $subjectLabel = trim(preg_replace('/\s+/', ' ', $subjectLabel) ?? '');
    if ($subjectLabel === '') {
        return '';
    }

    $parts = array_map('trim', explode(' - ', $subjectLabel));
    if (count($parts) >= 3 && strcasecmp((string) $parts[0], (string) $parts[1]) === 0) {
        array_splice($parts, 1, 1);
        $subjectLabel = implode(' - ', $parts);
    }

    return $subjectLabel;
}

function faculty_evaluation_feedback_report_subject_base_label(string $subjectLabel): string
{
    $subjectLabel = faculty_evaluation_feedback_report_normalize_subject_label($subjectLabel);

    return trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $subjectLabel));
}

function faculty_evaluation_feedback_report_limited_list(array $items): string
{
    $items = array_values(array_filter(array_map('trim', $items)));
    if ($items === []) {
        return 'Not set';
    }

    return implode("\n", $items);
}

function faculty_evaluation_feedback_report_term_line(array $report): string
{
    $termScope = $report['term_scope'] ?? [];
    $semester = trim((string) ($termScope['semester_label'] ?? ''));
    $academicYear = trim((string) ($termScope['academic_year_label'] ?? ''));

    if ($semester !== '' && $academicYear !== '') {
        return $semester . ', A.Y. ' . $academicYear;
    }

    return trim((string) ($termScope['note'] ?? 'Not set')) ?: 'Not set';
}

function faculty_evaluation_feedback_report_source_remark(array $section): string
{
    $mean = $section['overall_mean'] ?? null;
    if ($mean === null || $mean === '') {
        return 'No submitted rating yet.';
    }

    $sourceWeight = (float) ($section['source_weight'] ?? 0);
    $weightedPercentage = $section['weighted_percentage'] ?? null;
    $ratingPercentage = round(((float) $mean / 5) * 100, 2);
    $ratingLabel = individual_faculty_performance_rating_label($ratingPercentage, true);

    return 'Mean ' . individual_faculty_performance_format_mean($mean)
        . '/5.00; '
        . individual_faculty_performance_format_percentage($weightedPercentage)
        . ' of ' . number_format($sourceWeight, 0) . '%; '
        . $ratingLabel . '.';
}

function faculty_evaluation_feedback_report_overall_remark(array $report): string
{
    $percentage = $report['current_percentage'] ?? null;
    if ($percentage === null || $percentage === '') {
        return 'No submitted rating yet.';
    }

    return 'Total '
        . individual_faculty_performance_format_percentage($percentage)
        . '; '
        . (string) ($report['rating_label'] ?? individual_faculty_performance_rating_label((float) $percentage, true))
        . '.';
}

function faculty_evaluation_feedback_report_analysis(array $report): array
{
    $categories = faculty_evaluation_feedback_report_category_summary($report);
    $strengths = faculty_evaluation_feedback_report_strengths_text($categories);
    $improvements = faculty_evaluation_feedback_report_improvements_text($categories);

    return [
        'categories' => $categories,
        'strengths' => $strengths,
        'improvements' => $improvements,
        'recommendations' => faculty_evaluation_feedback_report_recommendations($report, $categories),
    ];
}

function faculty_evaluation_feedback_report_category_summary(array $report): array
{
    $summary = [];

    foreach (['student', 'supervisor'] as $sourceKey) {
        $section = $report[$sourceKey] ?? [];
        $sourceWeight = (float) ($section['source_weight'] ?? 0);

        foreach (($section['categories'] ?? []) as $categoryKey => $category) {
            $mean = $category['mean'] ?? null;
            if ($mean === null || $mean === '' || $sourceWeight <= 0) {
                continue;
            }

            $categoryKey = (string) ($category['key'] ?? $categoryKey);
            if (!isset($summary[$categoryKey])) {
                $summary[$categoryKey] = [
                    'key' => $categoryKey,
                    'title' => (string) ($category['title'] ?? individual_faculty_performance_category_title($categoryKey)),
                    'weighted_total' => 0.0,
                    'weight' => 0.0,
                    'sources' => [],
                    'mean' => null,
                ];
            }

            $summary[$categoryKey]['weighted_total'] += (float) $mean * $sourceWeight;
            $summary[$categoryKey]['weight'] += $sourceWeight;
            $summary[$categoryKey]['sources'][$sourceKey] = true;
        }
    }

    foreach ($summary as $categoryKey => $category) {
        $weight = (float) ($category['weight'] ?? 0);
        if ($weight <= 0) {
            unset($summary[$categoryKey]);
            continue;
        }

        $summary[$categoryKey]['mean'] = round(((float) $category['weighted_total']) / $weight, 2);
    }

    return array_values($summary);
}

function faculty_evaluation_feedback_report_strengths_text(array $categories): string
{
    if ($categories === []) {
        return 'No submitted category ratings are available yet.';
    }

    usort($categories, static function (array $left, array $right): int {
        return ((float) ($right['mean'] ?? 0)) <=> ((float) ($left['mean'] ?? 0));
    });

    $topMean = (float) ($categories[0]['mean'] ?? 0);
    $topCategories = array_values(array_filter($categories, static function (array $category) use ($topMean): bool {
        return abs(((float) ($category['mean'] ?? 0)) - $topMean) < 0.005;
    }));
    $labels = array_map(static function (array $category): string {
        return (string) ($category['title'] ?? '');
    }, $topCategories);

    $prefix = count($labels) > 1 ? 'Highest evaluated areas: ' : 'Highest evaluated area: ';

    return $prefix . faculty_evaluation_feedback_report_sentence_list($labels)
        . ' (mean ' . individual_faculty_performance_format_mean($topMean) . ').';
}

function faculty_evaluation_feedback_report_improvements_text(array $categories): string
{
    if ($categories === []) {
        return 'No submitted category ratings are available yet.';
    }

    usort($categories, static function (array $left, array $right): int {
        return ((float) ($left['mean'] ?? 0)) <=> ((float) ($right['mean'] ?? 0));
    });

    $lowest = $categories[0];
    $mean = (float) ($lowest['mean'] ?? 0);
    $title = (string) ($lowest['title'] ?? 'the evaluated area');

    if ($mean >= 4.20) {
        return 'Continue strengthening ' . $title
            . ', the lowest comparative area (mean '
            . individual_faculty_performance_format_mean($mean)
            . ').';
    }

    return 'Priority area for follow-up: ' . $title
        . ' (mean '
        . individual_faculty_performance_format_mean($mean)
        . ').';
}

function faculty_evaluation_feedback_report_sentence_list(array $labels): string
{
    $labels = array_values(array_filter(array_map('trim', $labels)));
    $count = count($labels);

    if ($count === 0) {
        return '';
    }

    if ($count === 1) {
        return $labels[0];
    }

    if ($count === 2) {
        return $labels[0] . ' and ' . $labels[1];
    }

    $last = array_pop($labels);

    return implode(', ', $labels) . ', and ' . $last;
}

function faculty_evaluation_feedback_report_recommendations(array $report, array $categories): array
{
    $lowestCategory = null;
    foreach ($categories as $category) {
        if ($lowestCategory === null || (float) ($category['mean'] ?? 0) < (float) ($lowestCategory['mean'] ?? 0)) {
            $lowestCategory = $category;
        }
    }

    $lowestMean = $lowestCategory !== null ? (float) ($lowestCategory['mean'] ?? 0) : null;
    $lowestTitle = $lowestCategory !== null ? (string) ($lowestCategory['title'] ?? '') : '';
    $totalPercentage = $report['current_percentage'] ?? null;
    $supervisorMean = $report['supervisor']['overall_mean'] ?? null;

    $trainingChecked = false;
    $peerChecked = false;
    $reobservationChecked = false;
    $othersChecked = false;
    $trainingSpecify = '';
    $othersText = '';

    if ($lowestMean !== null && $lowestMean < 4.00) {
        $trainingChecked = true;
        $peerChecked = true;
        $trainingSpecify = faculty_evaluation_feedback_report_title_case($lowestTitle);
    }

    if ($totalPercentage !== null && (float) $totalPercentage < 80.00) {
        $trainingChecked = true;
        $peerChecked = true;
        if ($trainingSpecify === '') {
            $trainingSpecify = $lowestTitle !== ''
                ? faculty_evaluation_feedback_report_title_case($lowestTitle)
                : 'Teaching effectiveness improvement';
        }
    }

    if ($supervisorMean !== null && $supervisorMean !== '' && (float) $supervisorMean < 4.00) {
        $reobservationChecked = true;
    }

    if (!($report['is_complete'] ?? false)) {
        $othersChecked = true;
        $othersText = 'Complete pending evaluation source and review updated results.';
    }

    if (!$trainingChecked && !$peerChecked && !$reobservationChecked && !$othersChecked) {
        $othersChecked = true;
        $othersText = 'Sustain current effective practices and continue regular feedback monitoring.';
    }

    return [
        'attend_training' => [
            'checked' => $trainingChecked,
            'specify' => $trainingSpecify,
        ],
        'peer_mentoring' => [
            'checked' => $peerChecked,
        ],
        'reobservation' => [
            'checked' => $reobservationChecked,
        ],
        'research_extension' => [
            'checked' => false,
        ],
        'others' => [
            'checked' => $othersChecked,
            'text' => $othersText,
        ],
    ];
}

function faculty_evaluation_feedback_report_title_case(string $value): string
{
    $value = strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
    if ($value === '') {
        return '';
    }

    return ucwords($value);
}
