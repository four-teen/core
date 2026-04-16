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

function dashboard_recent_evaluations(PDO $pdo): array
{
    ensure_evaluation_subject_scope($pdo);

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
            ev.average_rating,
            ev.submission_status,
            ev.completed_at,
            ev.updated_at
        FROM tbl_student_faculty_evaluations ev
        LEFT JOIN tbl_faculty f ON f.faculty_id = ev.faculty_id
        WHERE ev.student_enrollment_id IS NOT NULL
        ORDER BY ev.updated_at DESC, ev.evaluation_id DESC
        LIMIT 8"
    );

    return $statement->fetchAll();
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
