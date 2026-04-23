<?php
declare(strict_types=1);

function consolidated_faculty_performance_term_options(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);
    ensure_program_chair_tables($pdo);

    $statement = $pdo->query(
        "SELECT
            ev.ay_id,
            ev.semester,
            COALESCE(ay.ay, CONCAT('AY ID ', ev.ay_id)) AS academic_year_label,
            COUNT(*) AS evaluation_count,
            MAX(ev.updated_at) AS last_updated
         FROM tbl_student_faculty_evaluations ev
         INNER JOIN tbl_program_chair_faculty pcf
            ON pcf.faculty_id = ev.faculty_id
           AND pcf.is_active = 1
         INNER JOIN tbl_faculty f
            ON f.faculty_id = ev.faculty_id
           AND f.status = 'active'
         LEFT JOIN tbl_academic_years ay ON ay.ay_id = ev.ay_id
         WHERE ev.submission_status = 'submitted'
           AND ev.student_enrollment_id IS NOT NULL
         GROUP BY ev.ay_id, ev.semester, academic_year_label
         ORDER BY ev.ay_id DESC, ev.semester DESC"
    );

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

function consolidated_faculty_performance_term_scope(?array $termFilter, array $termOptions): array
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
                    'term_label' => (string) ($option['term_label'] ?? ''),
                    'note' => 'Filtered to ' . (string) ($option['term_label'] ?? ''),
                ];
            }
        }

        return [
            'semester_label' => format_semester($termFilter['semester'] ?? 0),
            'academic_year_label' => 'AY ID ' . (string) ((int) ($termFilter['ay_id'] ?? 0)),
            'term_label' => format_semester($termFilter['semester'] ?? 0) . ', A.Y. AY ID ' . (string) ((int) ($termFilter['ay_id'] ?? 0)),
            'note' => 'Filtered to the selected submitted term.',
        ];
    }

    if ($termOptions !== []) {
        return [
            'semester_label' => (string) ($termOptions[0]['semester_label'] ?? ''),
            'academic_year_label' => (string) ($termOptions[0]['academic_year_label'] ?? ''),
            'term_label' => (string) ($termOptions[0]['term_label'] ?? ''),
            'note' => 'Using the latest submitted term available in the consolidated report.',
        ];
    }

    return [
        'semester_label' => '___________ Semester',
        'academic_year_label' => '__________',
        'term_label' => '___________ Semester, A.Y. __________',
        'note' => 'No submitted term is available yet.',
    ];
}

function consolidated_faculty_performance_display_campus(string $campus): string
{
    $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', $campus)));

    if ($normalized === '') {
        return '__________ CAMPUS';
    }

    if (!str_ends_with($normalized, 'CAMPUS')) {
        $normalized .= ' CAMPUS';
    }

    return $normalized;
}

function consolidated_faculty_performance_report(PDO $pdo, ?array $termFilter = null, array $termOptions = []): array
{
    ensure_program_chair_tables($pdo);
    ensure_evaluation_subject_scope($pdo);

    $statement = $pdo->query(
        "SELECT
            pcf.program_chair_faculty_id,
            pcf.faculty_id,
            pcf.faculty_classification,
            f.last_name,
            f.first_name,
            f.middle_name,
            f.ext_name,
            f.status
         FROM tbl_program_chair_faculty pcf
         INNER JOIN tbl_faculty f
            ON f.faculty_id = pcf.faculty_id
         WHERE pcf.is_active = 1
           AND f.status = 'active'
         ORDER BY f.last_name ASC, f.first_name ASC, f.middle_name ASC, f.faculty_id ASC"
    );

    $sections = [
        'REGULAR' => [
            'key' => 'REGULAR',
            'title' => 'I. REGULAR FACULTY',
            'rows' => [],
        ],
        'CONTRACT OF SERVICE' => [
            'key' => 'CONTRACT OF SERVICE',
            'title' => 'II. CONTRACT OF SERVICE FACULTY',
            'rows' => [],
        ],
    ];
    $unclassified = [];
    $evaluatedFacultyCount = 0;

    foreach ($statement->fetchAll() as $faculty) {
        $studentSection = individual_faculty_performance_student_section(
            $pdo,
            (int) ($faculty['faculty_id'] ?? 0),
            $termFilter
        );
        $supervisorSection = individual_faculty_performance_supervisor_section(
            $pdo,
            (int) ($faculty['faculty_id'] ?? 0),
            $faculty
        );

        if ((int) ($studentSection['evaluation_count'] ?? 0) <= 0 && (int) ($supervisorSection['evaluation_count'] ?? 0) <= 0) {
            continue;
        }

        $evaluatedFacultyCount++;
        $facultyName = individual_faculty_performance_faculty_name_from_row($faculty);
        $classification = program_chair_normalize_faculty_classification(
            (string) ($faculty['faculty_classification'] ?? ''),
            true
        );
        $studentPercentage = $studentSection['weighted_percentage'];
        $supervisorPercentage = $supervisorSection['weighted_percentage'];
        $totalPercentage = null;

        if ($studentPercentage !== null || $supervisorPercentage !== null) {
            $totalPercentage = round((float) ($studentPercentage ?? 0) + (float) ($supervisorPercentage ?? 0), 2);
        }

        $row = [
            'program_chair_faculty_id' => (int) ($faculty['program_chair_faculty_id'] ?? 0),
            'faculty_id' => (int) ($faculty['faculty_id'] ?? 0),
            'faculty_name' => $facultyName,
            'classification' => $classification,
            'classification_label' => program_chair_faculty_classification_label($classification),
            'student_weighted_percentage' => $studentPercentage,
            'supervisor_weighted_percentage' => $supervisorPercentage,
            'total_percentage' => $totalPercentage,
            'student_overall_mean' => $studentSection['overall_mean'],
            'supervisor_overall_mean' => $supervisorSection['overall_mean'],
            'student_evaluation_count' => (int) ($studentSection['evaluation_count'] ?? 0),
            'supervisor_evaluation_count' => (int) ($supervisorSection['evaluation_count'] ?? 0),
        ];

        if ($classification === '') {
            $unclassified[] = $row;
            continue;
        }

        if (!isset($sections[$classification])) {
            $unclassified[] = $row;
            continue;
        }

        $sections[$classification]['rows'][] = $row;
    }

    foreach ($sections as $sectionKey => $section) {
        foreach ($section['rows'] as $index => $row) {
            $sections[$sectionKey]['rows'][$index]['row_number'] = $index + 1;
        }
    }

    return [
        'term_scope' => consolidated_faculty_performance_term_scope($termFilter, $termOptions),
        'sections' => array_values($sections),
        'unclassified' => array_values($unclassified),
        'included_count' => count($sections['REGULAR']['rows']) + count($sections['CONTRACT OF SERVICE']['rows']),
        'evaluated_count' => $evaluatedFacultyCount,
    ];
}
