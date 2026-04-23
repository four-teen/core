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

function student_profile_update(PDO $pdo, int $studentId, array $payload): void
{
    $existingStudent = student_profile_record($pdo, $studentId);

    if ($existingStudent === null) {
        throw new RuntimeException('The selected student could not be found.');
    }

    $firstName = preg_replace('/\s+/', ' ', trim((string) ($payload['first_name'] ?? ''))) ?? '';
    $middleName = preg_replace('/\s+/', ' ', trim((string) ($payload['middle_name'] ?? ''))) ?? '';
    $lastName = preg_replace('/\s+/', ' ', trim((string) ($payload['last_name'] ?? ''))) ?? '';
    $suffixName = preg_replace('/\s+/', ' ', trim((string) ($payload['suffix_name'] ?? ''))) ?? '';
    $emailAddress = user_management_normalize_email((string) ($payload['email_address'] ?? ''));

    if ($firstName === '') {
        throw new RuntimeException('Please enter the student first name.');
    }

    if ($lastName === '') {
        throw new RuntimeException('Please enter the student last name.');
    }

    if ($emailAddress === '' || filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('Please enter a valid student email address.');
    }

    $duplicateStatement = $pdo->prepare(
        "SELECT student_id
           FROM tbl_student_management
          WHERE LOWER(email_address) = LOWER(:email_address)
            AND student_id <> :student_id
          LIMIT 1"
    );
    $duplicateStatement->execute([
        'email_address' => $emailAddress,
        'student_id' => $studentId,
    ]);

    if ($duplicateStatement->fetch()) {
        throw new RuntimeException('That email address is already assigned to another student record.');
    }

    $statement = $pdo->prepare(
        "UPDATE tbl_student_management
            SET first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                suffix_name = :suffix_name,
                email_address = :email_address,
                updated_at = NOW()
          WHERE student_id = :student_id
          LIMIT 1"
    );
    $statement->execute([
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'suffix_name' => $suffixName,
        'email_address' => $emailAddress,
        'student_id' => $studentId,
    ]);
}

function student_portal_qr_payload(array $student): string
{
    $studentNumber = preg_replace('/[^A-Za-z0-9._-]/', '', trim((string) ($student['student_number'] ?? '')));

    if ($studentNumber === '') {
        $studentNumber = (string) ((int) ($student['student_id'] ?? 0));
    }

    $payload = 'CORE:' . $studentNumber;

    if (strlen($payload) > 32) {
        $payload = 'CORE:' . substr(hash('sha256', $studentNumber), 0, 27);
    }

    return $payload;
}

function student_portal_qr_svg(string $payload): string
{
    $version = 2;
    $size = 25;
    $dataCodewordCount = 34;
    $eccCodewordCount = 10;
    $payloadBytes = array_map('ord', str_split($payload));

    if (count($payloadBytes) > 32) {
        throw new InvalidArgumentException('QR payload is too long for the student check code.');
    }

    $dataBits = [];
    student_portal_qr_append_bits($dataBits, 0x4, 4);
    student_portal_qr_append_bits($dataBits, count($payloadBytes), 8);

    foreach ($payloadBytes as $byte) {
        student_portal_qr_append_bits($dataBits, $byte, 8);
    }

    $capacityBits = $dataCodewordCount * 8;
    student_portal_qr_append_bits($dataBits, 0, min(4, $capacityBits - count($dataBits)));

    while (count($dataBits) % 8 !== 0) {
        $dataBits[] = 0;
    }

    $dataCodewords = [];
    for ($offset = 0; $offset < count($dataBits); $offset += 8) {
        $codeword = 0;
        for ($bit = 0; $bit < 8; $bit++) {
            $codeword = ($codeword << 1) | $dataBits[$offset + $bit];
        }
        $dataCodewords[] = $codeword;
    }

    for ($padIndex = 0; count($dataCodewords) < $dataCodewordCount; $padIndex++) {
        $dataCodewords[] = $padIndex % 2 === 0 ? 0xec : 0x11;
    }

    $codewords = array_merge(
        $dataCodewords,
        student_portal_qr_reed_solomon_remainder($dataCodewords, $eccCodewordCount)
    );

    $modules = array_fill(0, $size, array_fill(0, $size, false));
    $functionModules = array_fill(0, $size, array_fill(0, $size, false));

    student_portal_qr_draw_finder($modules, $functionModules, 0, 0);
    student_portal_qr_draw_finder($modules, $functionModules, $size - 7, 0);
    student_portal_qr_draw_finder($modules, $functionModules, 0, $size - 7);
    student_portal_qr_draw_alignment($modules, $functionModules, 18, 18);

    for ($index = 8; $index < $size - 8; $index++) {
        $isBlack = $index % 2 === 0;
        student_portal_qr_set_module($modules, $functionModules, $index, 6, $isBlack, true);
        student_portal_qr_set_module($modules, $functionModules, 6, $index, $isBlack, true);
    }

    student_portal_qr_set_module($modules, $functionModules, 8, (4 * $version) + 9, true, true);
    student_portal_qr_draw_format_bits($modules, $functionModules, 0);
    student_portal_qr_draw_codewords($modules, $functionModules, $codewords, 0);

    $quietZone = 4;
    $svgSize = $size + ($quietZone * 2);
    $pathParts = [];

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($modules[$y][$x]) {
                $pathParts[] = 'M' . ($x + $quietZone) . ' ' . ($y + $quietZone) . 'h1v1h-1z';
            }
        }
    }

    return '<svg class="student-qr-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '
        . $svgSize . ' ' . $svgSize
        . '" role="img" aria-label="Student QR check"><rect width="100%" height="100%" fill="#fff"/>'
        . '<path fill="currentColor" d="' . implode('', $pathParts) . '"/></svg>';
}

function student_portal_qr_append_bits(array &$bits, int $value, int $length): void
{
    for ($index = $length - 1; $index >= 0; $index--) {
        $bits[] = ($value >> $index) & 1;
    }
}

function student_portal_qr_set_module(array &$modules, array &$functionModules, int $x, int $y, bool $isBlack, bool $isFunction): void
{
    if (!isset($modules[$y][$x])) {
        return;
    }

    $modules[$y][$x] = $isBlack;

    if ($isFunction) {
        $functionModules[$y][$x] = true;
    }
}

function student_portal_qr_draw_finder(array &$modules, array &$functionModules, int $left, int $top): void
{
    for ($dy = -1; $dy <= 7; $dy++) {
        for ($dx = -1; $dx <= 7; $dx++) {
            $x = $left + $dx;
            $y = $top + $dy;
            $isInFinder = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
            $isBlack = $isInFinder
                && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));

            student_portal_qr_set_module($modules, $functionModules, $x, $y, $isBlack, true);
        }
    }
}

function student_portal_qr_draw_alignment(array &$modules, array &$functionModules, int $centerX, int $centerY): void
{
    for ($dy = -2; $dy <= 2; $dy++) {
        for ($dx = -2; $dx <= 2; $dx++) {
            $distance = max(abs($dx), abs($dy));
            $isBlack = $distance === 2 || $distance === 0;
            student_portal_qr_set_module($modules, $functionModules, $centerX + $dx, $centerY + $dy, $isBlack, true);
        }
    }
}

function student_portal_qr_draw_format_bits(array &$modules, array &$functionModules, int $mask): void
{
    $formatBits = student_portal_qr_format_bits($mask);
    $size = count($modules);

    for ($index = 0; $index <= 5; $index++) {
        student_portal_qr_set_module($modules, $functionModules, 8, $index, student_portal_qr_get_bit($formatBits, $index), true);
    }

    student_portal_qr_set_module($modules, $functionModules, 8, 7, student_portal_qr_get_bit($formatBits, 6), true);
    student_portal_qr_set_module($modules, $functionModules, 8, 8, student_portal_qr_get_bit($formatBits, 7), true);
    student_portal_qr_set_module($modules, $functionModules, 7, 8, student_portal_qr_get_bit($formatBits, 8), true);

    for ($index = 9; $index < 15; $index++) {
        student_portal_qr_set_module($modules, $functionModules, 14 - $index, 8, student_portal_qr_get_bit($formatBits, $index), true);
    }

    for ($index = 0; $index < 8; $index++) {
        student_portal_qr_set_module($modules, $functionModules, $size - 1 - $index, 8, student_portal_qr_get_bit($formatBits, $index), true);
    }

    for ($index = 8; $index < 15; $index++) {
        student_portal_qr_set_module($modules, $functionModules, 8, $size - 15 + $index, student_portal_qr_get_bit($formatBits, $index), true);
    }

    student_portal_qr_set_module($modules, $functionModules, 8, $size - 8, true, true);
}

function student_portal_qr_format_bits(int $mask): int
{
    $errorCorrectionLevelBits = 1;
    $data = ($errorCorrectionLevelBits << 3) | $mask;
    $remainder = $data;

    for ($index = 0; $index < 10; $index++) {
        $remainder = ($remainder << 1) ^ (((($remainder >> 9) & 1) !== 0) ? 0x537 : 0);
    }

    return (($data << 10) | ($remainder & 0x3ff)) ^ 0x5412;
}

function student_portal_qr_get_bit(int $value, int $index): bool
{
    return (($value >> $index) & 1) !== 0;
}

function student_portal_qr_draw_codewords(array &$modules, array &$functionModules, array $codewords, int $mask): void
{
    $size = count($modules);
    $bits = [];

    foreach ($codewords as $codeword) {
        student_portal_qr_append_bits($bits, $codeword, 8);
    }

    $bitIndex = 0;
    $upward = true;

    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }

        for ($vertical = 0; $vertical < $size; $vertical++) {
            $y = $upward ? $size - 1 - $vertical : $vertical;

            for ($dx = 0; $dx < 2; $dx++) {
                $x = $right - $dx;

                if ($functionModules[$y][$x]) {
                    continue;
                }

                $isBlack = ($bits[$bitIndex] ?? 0) === 1;
                $bitIndex++;

                if (student_portal_qr_mask_bit($mask, $x, $y)) {
                    $isBlack = !$isBlack;
                }

                $modules[$y][$x] = $isBlack;
            }
        }

        $upward = !$upward;
    }
}

function student_portal_qr_mask_bit(int $mask, int $x, int $y): bool
{
    if ($mask === 0) {
        return (($x + $y) % 2) === 0;
    }

    return false;
}

function student_portal_qr_reed_solomon_remainder(array $data, int $degree): array
{
    $generator = student_portal_qr_reed_solomon_generator($degree);
    $result = array_fill(0, $degree, 0);

    foreach ($data as $byte) {
        $factor = $byte ^ $result[0];
        array_shift($result);
        $result[] = 0;

        for ($index = 0; $index < $degree; $index++) {
            $result[$index] ^= student_portal_qr_gf_multiply($generator[$index], $factor);
        }
    }

    return $result;
}

function student_portal_qr_reed_solomon_generator(int $degree): array
{
    $result = array_fill(0, $degree, 0);
    $result[$degree - 1] = 1;
    $root = 1;

    for ($index = 0; $index < $degree; $index++) {
        for ($coefficient = 0; $coefficient < $degree; $coefficient++) {
            $result[$coefficient] = student_portal_qr_gf_multiply($result[$coefficient], $root);

            if ($coefficient + 1 < $degree) {
                $result[$coefficient] ^= $result[$coefficient + 1];
            }
        }

        $root = student_portal_qr_gf_multiply($root, 0x02);
    }

    return $result;
}

function student_portal_qr_gf_multiply(int $x, int $y): int
{
    $result = 0;

    while ($y > 0) {
        if (($y & 1) !== 0) {
            $result ^= $x;
        }

        $x <<= 1;
        if (($x & 0x100) !== 0) {
            $x ^= 0x11d;
        }

        $y >>= 1;
    }

    return $result & 0xff;
}

function student_portal_summary(PDO $pdo, int $studentId): array
{
    ensure_evaluation_subject_scope($pdo);

    $sql = "SELECT
                COUNT(*) AS enrolled_subjects,
                COUNT(DISTINCT CASE WHEN es.faculty_id <> 0 THEN es.faculty_id END) AS faculty_count,
                COUNT(DISTINCT CASE WHEN ev.submission_status = 'submitted' THEN es.student_enrollment_id END) AS submitted_evaluations,
                COUNT(DISTINCT CASE WHEN es.faculty_id <> 0 THEN es.student_enrollment_id END) AS evaluable_subjects
            FROM tbl_student_management_enrolled_subjects es
            LEFT JOIN tbl_student_faculty_evaluations ev
                ON ev.student_enrollment_id = es.student_enrollment_id
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
            'evaluable_subjects' => 0,
        ];
    }

    $summary['pending_evaluations'] = max(
        0,
        (int) $summary['evaluable_subjects'] - (int) $summary['submitted_evaluations']
    );

    return $summary;
}

function student_portal_subjects(PDO $pdo, int $studentId): array
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
                es.year_level,
                es.is_active,
                TRIM(CONCAT(
                    COALESCE(f.last_name, ''),
                    CASE WHEN f.last_name IS NULL OR f.last_name = '' THEN '' ELSE ', ' END,
                    COALESCE(f.first_name, ''),
                    CASE WHEN f.ext_name IS NULL OR f.ext_name = '' THEN '' ELSE CONCAT(' ', f.ext_name) END
                )) AS faculty_name,
                ev.evaluation_id,
                CASE
                    WHEN ev.submission_status = 'draft'
                     AND ev.question_count = 0
                     AND TRIM(COALESCE(ev.comment_text, '')) = ''
                    THEN NULL
                    ELSE ev.submission_status
                END AS submission_status,
                ev.average_rating,
                ev.term_label,
                ev.updated_at AS evaluation_updated_at
            FROM tbl_student_management_enrolled_subjects es
            LEFT JOIN tbl_subject_masterlist smst ON smst.sub_id = es.subject_id
            LEFT JOIN tbl_faculty f ON f.faculty_id = es.faculty_id
            LEFT JOIN tbl_academic_years ay ON ay.ay_id = es.ay_id
            LEFT JOIN tbl_student_faculty_evaluations ev
                ON ev.student_enrollment_id = es.student_enrollment_id
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
    ensure_evaluation_subject_scope($pdo);

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
              AND ev.student_enrollment_id IS NOT NULL
              AND (
                ev.submission_status = 'submitted'
                OR ev.question_count > 0
                OR TRIM(COALESCE(ev.comment_text, '')) <> ''
              )
            ORDER BY ev.updated_at DESC, ev.evaluation_id DESC";

    $statement = $pdo->prepare($sql);
    $statement->execute(['student_id' => $studentId]);
    return $statement->fetchAll();
}
