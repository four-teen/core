<?php
declare(strict_types=1);

function person_full_name($lastName, $firstName, $middleName = '', $suffixName = ''): string
{
    $parts = [];

    if (trim((string) $lastName) !== '') {
        $parts[] = trim((string) $lastName) . ',';
    }

    if (trim((string) $firstName) !== '') {
        $parts[] = trim((string) $firstName);
    }

    if (trim((string) $middleName) !== '') {
        $parts[] = trim((string) $middleName);
    }

    if (trim((string) $suffixName) !== '') {
        $parts[] = trim((string) $suffixName);
    }

    return trim(str_replace(' ,', ',', implode(' ', $parts)));
}

function find_student_for_login(PDO $pdo, string $email): ?array
{
    $sql = "SELECT
                sm.student_id,
                sm.student_number,
                sm.email_address,
                sm.first_name,
                sm.last_name,
                sm.middle_name,
                sm.suffix_name,
                sm.year_level,
                sm.ay_id,
                sm.semester,
                sm.program_id,
                COALESCE(ay.ay, CONCAT('AY ID ', sm.ay_id)) AS academic_year_label,
                p.program_code,
                p.program_name
            FROM tbl_student_management sm
            LEFT JOIN tbl_program p ON p.program_id = sm.program_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = sm.ay_id
            WHERE LOWER(sm.email_address) = LOWER(:email)
            ORDER BY sm.ay_id DESC, sm.semester DESC, sm.updated_at DESC, sm.student_id DESC
            LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute(['email' => trim($email)]);
    $student = $statement->fetch();

    if (!$student) {
        return null;
    }

    $student['full_name'] = person_full_name(
        $student['last_name'] ?? '',
        $student['first_name'] ?? '',
        $student['middle_name'] ?? '',
        $student['suffix_name'] ?? ''
    );
    $student['term_label'] = evaluation_term_label($student['academic_year_label'] ?? '', $student['semester'] ?? 0);

    return $student;
}

function student_profile_record(PDO $pdo, int $studentId): ?array
{
    $sql = "SELECT
                sm.student_id,
                sm.student_number,
                sm.email_address,
                sm.first_name,
                sm.last_name,
                sm.middle_name,
                sm.suffix_name,
                sm.year_level,
                sm.ay_id,
                sm.semester,
                sm.program_id,
                sm.created_at,
                sm.updated_at,
                COALESCE(ay.ay, CONCAT('AY ID ', sm.ay_id)) AS academic_year_label,
                p.program_code,
                p.program_name
            FROM tbl_student_management sm
            LEFT JOIN tbl_program p ON p.program_id = sm.program_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = sm.ay_id
            WHERE sm.student_id = :student_id
            LIMIT 1";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_id' => $studentId]);
    $student = $statement->fetch();

    if (!$student) {
        return null;
    }

    $student['full_name'] = person_full_name(
        $student['last_name'] ?? '',
        $student['first_name'] ?? '',
        $student['middle_name'] ?? '',
        $student['suffix_name'] ?? ''
    );
    $student['term_label'] = evaluation_term_label($student['academic_year_label'] ?? '', $student['semester'] ?? 0);

    return $student;
}

function student_portal_summary(PDO $pdo, int $studentId): array
{
    $sql = "SELECT
                COUNT(*) AS enrolled_subjects,
                COUNT(DISTINCT CASE WHEN es.faculty_id <> 0 THEN es.faculty_id END) AS faculty_count,
                COUNT(DISTINCT CASE WHEN ev.submission_status = 'submitted' THEN ev.faculty_id END) AS submitted_evaluations
            FROM tbl_student_management_enrolled_subjects es
            LEFT JOIN tbl_student_faculty_evaluations ev
                ON ev.student_id = es.student_id
               AND ev.faculty_id = es.faculty_id
               AND ev.ay_id = es.ay_id
               AND ev.semester = es.semester
            WHERE es.student_id = :student_id
              AND es.is_active = 1";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_id' => $studentId]);
    $summary = $statement->fetch();

    if (!$summary) {
        $summary = [
            'enrolled_subjects' => 0,
            'faculty_count' => 0,
            'submitted_evaluations' => 0,
        ];
    }

    $summary['pending_evaluations'] = max(
        0,
        (int) $summary['faculty_count'] - (int) $summary['submitted_evaluations']
    );

    return $summary;
}

function student_portal_subjects(PDO $pdo, int $studentId): array
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
                es.year_level,
                es.is_active,
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )) AS faculty_name,
                ev.evaluation_id,
                ev.submission_status,
                ev.average_rating,
                ev.term_label,
                ev.updated_at AS evaluation_updated_at
            FROM tbl_student_management_enrolled_subjects es
            LEFT JOIN tbl_subject_masterlist smst ON smst.sub_id = es.subject_id
            LEFT JOIN tbl_faculty f ON f.faculty_id = es.faculty_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = es.ay_id
            LEFT JOIN tbl_student_faculty_evaluations ev
                ON ev.student_id = es.student_id
               AND ev.faculty_id = es.faculty_id
               AND ev.ay_id = es.ay_id
               AND ev.semester = es.semester
            WHERE es.student_id = :student_id
              AND es.is_active = 1
            ORDER BY es.subject_code ASC, es.section_text ASC, es.student_enrollment_id ASC";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_id' => $studentId]);
    $rows = $statement->fetchAll();

    foreach ($rows as $index => $row) {
        if (trim((string) $row['faculty_name']) === '') {
            $rows[$index]['faculty_name'] = 'Faculty #' . (string) $row['faculty_id'];
        }

        $rows[$index]['term_label'] = trim((string) ($row['term_label'] ?? '')) !== ''
            ? (string) $row['term_label']
            : evaluation_term_label($row['academic_year_label'] ?? '', $row['semester'] ?? 0);
    }

    return $rows;
}

function student_portal_evaluations(PDO $pdo, int $studentId): array
{
    $sql = "SELECT
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
                ev.term_label,
                ev.subject_summary,
                ev.average_rating,
                ev.submission_status,
                ev.final_submitted_at,
                ev.updated_at
            FROM tbl_student_faculty_evaluations ev
            LEFT JOIN tbl_faculty f ON f.faculty_id = ev.faculty_id
            WHERE ev.student_id = :student_id
            ORDER BY ev.updated_at DESC, ev.evaluation_id DESC";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_id' => $studentId]);
    return $statement->fetchAll();
}
