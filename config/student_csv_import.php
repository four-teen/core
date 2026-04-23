<?php
declare(strict_types=1);

final class StudentCsvImportException extends RuntimeException
{
    /** @var array<int, string> */
    private $details = [];

    /**
     * @param array<int, string> $details
     */
    public function __construct(string $message, array $details = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->details = array_values(array_filter(array_map(static function ($detail): string {
            return trim((string) $detail);
        }, $details), static function (string $detail): bool {
            return $detail !== '';
        }));
    }

    /**
     * @return array<int, string>
     */
    public function details(): array
    {
        return $this->details;
    }
}

function student_csv_import_academic_year_options(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT ay_id, ay, status
           FROM tbl_academic_years
          ORDER BY status = 'active' DESC, ay_id DESC"
    );

    return $statement->fetchAll();
}

function student_csv_import_semester_options(): array
{
    return [
        1 => '1st Semester',
        2 => '2nd Semester',
        3 => 'Summer',
    ];
}

function student_csv_import_default_ay_id(PDO $pdo): int
{
    $options = student_csv_import_academic_year_options($pdo);

    foreach ($options as $option) {
        if ((string) ($option['status'] ?? '') === 'active') {
            return (int) ($option['ay_id'] ?? 0);
        }
    }

    return (int) ($options[0]['ay_id'] ?? 0);
}

function student_csv_import_store_report(array $report): void
{
    $_SESSION['student_csv_import_report'] = $report;
}

function student_csv_import_consume_report(): ?array
{
    $report = $_SESSION['student_csv_import_report'] ?? null;
    unset($_SESSION['student_csv_import_report']);

    return is_array($report) ? $report : null;
}

function student_csv_import_process_upload(PDO $pdo, array $uploadedFile, int $ayId, int $semester, int $uploadedByUserId = 0): array
{
    $fileName = student_csv_import_uploaded_file_name($uploadedFile);
    $temporaryPath = student_csv_import_uploaded_file_path($uploadedFile);
    $term = student_csv_import_resolve_term($pdo, $ayId, $semester);
    $rows = student_csv_import_read_rows($temporaryPath);
    $parsed = student_csv_import_parse_document($rows, $fileName, $term);
    $subject = student_csv_import_resolve_subject($pdo, (string) $parsed['subject_code'], (string) $parsed['descriptive_title']);
    $program = student_csv_import_resolve_program($pdo, (string) $parsed['section_text']);
    $facultyResolution = student_csv_import_resolve_faculty($pdo, (string) $parsed['instructor_name']);

    $context = [
        'file_name' => $fileName,
        'source_sheet_name' => (string) $parsed['source_sheet_name'],
        'subject_code' => (string) $subject['sub_code'],
        'subject_id' => (int) $subject['sub_id'],
        'descriptive_title' => trim((string) ($subject['sub_description'] ?? '')) !== ''
            ? (string) $subject['sub_description']
            : (string) $parsed['descriptive_title'],
        'section_text' => (string) $parsed['section_text'],
        'program_id' => (int) $program['program_id'],
        'program_code' => (string) $program['program_code'],
        'program_name' => (string) $program['program_name'],
        'college_id' => (int) ($program['college_id'] ?? 0),
        'campus_id' => 1,
        'faculty_id' => (int) $facultyResolution['faculty']['faculty_id'],
        'faculty_name' => (string) $facultyResolution['faculty_name'],
        'faculty_match_note' => (string) ($facultyResolution['match_note'] ?? ''),
        'instructor_name' => (string) $parsed['instructor_name'],
        'room_text' => (string) $parsed['room_text'],
        'schedule_text' => (string) $parsed['schedule_text'],
        'year_level' => (int) $parsed['year_level'],
        'ay_id' => (int) $term['ay_id'],
        'academic_year_label' => (string) $term['ay'],
        'semester' => (int) $term['semester'],
        'semester_label' => format_semester($term['semester']),
    ];

    return student_csv_import_execute($pdo, $context, $parsed['students'], $uploadedByUserId);
}

function student_csv_import_preview_file(PDO $pdo, string $filePath, string $fileName, int $ayId, int $semester): array
{
    $term = student_csv_import_resolve_term($pdo, $ayId, $semester);
    $rows = student_csv_import_read_rows($filePath);
    $parsed = student_csv_import_parse_document($rows, $fileName, $term);
    $subject = student_csv_import_resolve_subject($pdo, (string) $parsed['subject_code'], (string) $parsed['descriptive_title']);
    $program = student_csv_import_resolve_program($pdo, (string) $parsed['section_text']);
    $facultyResolution = student_csv_import_resolve_faculty($pdo, (string) $parsed['instructor_name']);

    return [
        'file_name' => $fileName,
        'academic_year_label' => (string) $term['ay'],
        'semester_label' => format_semester($term['semester']),
        'subject_code' => (string) $subject['sub_code'],
        'subject_id' => (int) $subject['sub_id'],
        'descriptive_title' => (string) ($subject['sub_description'] ?? ''),
        'section_text' => (string) $parsed['section_text'],
        'program_code' => (string) $program['program_code'],
        'program_id' => (int) $program['program_id'],
        'faculty_name' => (string) $facultyResolution['faculty_name'],
        'faculty_id' => (int) $facultyResolution['faculty']['faculty_id'],
        'faculty_match_note' => (string) ($facultyResolution['match_note'] ?? ''),
        'room_text' => (string) $parsed['room_text'],
        'schedule_text' => (string) $parsed['schedule_text'],
        'year_level' => (int) $parsed['year_level'],
        'students' => count($parsed['students']),
        'first_student' => $parsed['students'][0] ?? null,
    ];
}

function student_csv_import_uploaded_file_name(array $uploadedFile): string
{
    $fileName = basename((string) ($uploadedFile['name'] ?? ''));

    if ($fileName === '') {
        throw new StudentCsvImportException('Please choose a CSV file first.');
    }

    if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'csv') {
        throw new StudentCsvImportException('Please upload a valid CSV file.');
    }

    return $fileName;
}

function student_csv_import_uploaded_file_path(array $uploadedFile): string
{
    $errorCode = (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new StudentCsvImportException(student_csv_import_upload_error_message($errorCode));
    }

    $temporaryPath = (string) ($uploadedFile['tmp_name'] ?? '');

    if ($temporaryPath === '' || !is_file($temporaryPath)) {
        throw new StudentCsvImportException('The uploaded CSV file could not be read.');
    }

    return $temporaryPath;
}

function student_csv_import_upload_error_message(int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded CSV file is too large.';
        case UPLOAD_ERR_PARTIAL:
            return 'The CSV file upload was interrupted. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'Please choose a CSV file first.';
        default:
            return 'Unable to upload the CSV file right now. Please try again.';
    }
}

function student_csv_import_resolve_term(PDO $pdo, int $ayId, int $semester): array
{
    if ($ayId <= 0) {
        throw new StudentCsvImportException('Please choose an academic year for the import.');
    }

    if (!array_key_exists($semester, student_csv_import_semester_options())) {
        throw new StudentCsvImportException('Please choose a valid semester for the import.');
    }

    $statement = $pdo->prepare(
        "SELECT ay_id, ay, status
           FROM tbl_academic_years
          WHERE ay_id = :ay_id
          LIMIT 1"
    );
    $statement->execute(['ay_id' => $ayId]);
    $row = $statement->fetch();

    if (!$row) {
        throw new StudentCsvImportException('The selected academic year could not be found.');
    }

    $row['semester'] = $semester;

    return $row;
}

function student_csv_import_read_rows(string $filePath): array
{
    $handle = @fopen($filePath, 'rb');

    if ($handle === false) {
        throw new StudentCsvImportException('Unable to open the uploaded CSV file.');
    }

    $rows = [];
    $lineNumber = 0;

    try {
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($lineNumber === 1 && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]) ?? (string) $row[0];
            }

            $rows[] = array_map('student_csv_import_clean_cell', $row);
        }
    } finally {
        fclose($handle);
    }

    if ($rows === []) {
        throw new StudentCsvImportException('The uploaded CSV file is empty.');
    }

    return $rows;
}

function student_csv_import_clean_cell($value): string
{
    $value = (string) $value;
    $value = str_replace("\xc2\xa0", ' ', $value);

    if (!preg_match('//u', $value)) {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if (is_string($converted) && $converted !== '' && preg_match('//u', $converted)) {
                $value = $converted;
            }
        } elseif (function_exists('iconv')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
            if ($converted !== false && $converted !== '' && preg_match('//u', $converted)) {
                $value = $converted;
            }
        }
    }

    $value = trim($value);
    return preg_replace('/\s+/u', ' ', $value) ?? $value;
}

function student_csv_import_parse_document(array $rows, string $fileName, array $term): array
{
    $subjectSectionValue = '';
    $descriptiveTitle = '';
    $instructorName = '';
    $roomText = '';
    $scheduleText = '';
    $headerIndex = null;

    foreach ($rows as $index => $row) {
        if ($subjectSectionValue === '') {
            $subjectSectionValue = (string) student_csv_import_row_label_value($row, 'Subject/Section');
        }

        if ($descriptiveTitle === '') {
            $descriptiveTitle = (string) student_csv_import_row_label_value($row, 'Descriptive Title');
        }

        if ($instructorName === '') {
            $instructorName = (string) student_csv_import_row_label_value($row, 'Instructor(s)');
        }

        if ($scheduleText === '') {
            $scheduleText = (string) student_csv_import_row_label_value($row, 'Class Schedule');
        }

        if ($roomText === '') {
            $roomText = (string) student_csv_import_row_label_value($row, 'Building/Room');
        }

        if (
            $headerIndex === null
            && strtolower((string) ($row[0] ?? '')) === 'no.'
            && stripos((string) ($row[1] ?? ''), 'student') !== false
            && stripos((string) ($row[4] ?? ''), 'id') !== false
        ) {
            $headerIndex = $index;
        }
    }

    if ($subjectSectionValue === '') {
        throw new StudentCsvImportException('The CSV file does not contain a "Subject/Section" value.');
    }

    if ($headerIndex === null) {
        throw new StudentCsvImportException('The CSV file does not contain a student list header.');
    }

    if ($instructorName === '') {
        throw new StudentCsvImportException('The CSV file does not contain an "Instructor(s)" value.');
    }

    $subjectSection = student_csv_import_parse_subject_section($subjectSectionValue);
    $students = [];
    $seenEmails = [];
    $seenStudentNumbers = [];
    $errors = [];

    for ($index = $headerIndex + 1, $rowCount = count($rows); $index < $rowCount; $index++) {
        $row = $rows[$index];

        if (student_csv_import_row_is_empty($row)) {
            continue;
        }

        $rawName = trim((string) ($row[1] ?? ''));
        $studentNumber = student_csv_import_parse_student_number((string) ($row[4] ?? ''));

        if ($rawName === '' && $studentNumber === 0) {
            continue;
        }

        if ($rawName === '' || $studentNumber === 0) {
            $errors[] = 'Row ' . ($index + 1) . ' has an incomplete student entry.';
            continue;
        }

        $nameParts = student_csv_import_parse_student_name($rawName, $index + 1);
        $emailAddress = student_csv_import_generate_student_email(
            (string) $nameParts['first_name'],
            (string) $nameParts['last_name']
        );

        if (isset($seenEmails[$emailAddress])) {
            $errors[] = 'Row ' . ($index + 1) . ' duplicates student email ' . $emailAddress . ' inside the uploaded file.';
            continue;
        }

        if (isset($seenStudentNumbers[$studentNumber])) {
            $errors[] = 'Row ' . ($index + 1) . ' duplicates student number ' . $studentNumber . ' inside the uploaded file.';
            continue;
        }

        $seenEmails[$emailAddress] = true;
        $seenStudentNumbers[$studentNumber] = true;

        $students[] = [
            'source_row_number' => $index + 1,
            'student_number' => $studentNumber,
            'raw_name' => $rawName,
            'last_name' => $nameParts['last_name'],
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'suffix_name' => $nameParts['suffix_name'],
            'email_address' => $emailAddress,
        ];
    }

    if ($errors !== []) {
        throw new StudentCsvImportException('The CSV file has student rows that need attention before importing.', $errors);
    }

    if ($students === []) {
        throw new StudentCsvImportException('No student rows were found in the uploaded CSV file.');
    }

    return [
        'file_name' => $fileName,
        'source_sheet_name' => $subjectSection['subject_code'] . ' - ' . $subjectSection['section_text'],
        'subject_code' => $subjectSection['subject_code'],
        'section_text' => $subjectSection['section_text'],
        'year_level' => $subjectSection['year_level'],
        'descriptive_title' => $descriptiveTitle,
        'instructor_name' => $instructorName,
        'room_text' => $roomText,
        'schedule_text' => $scheduleText,
        'ay_id' => (int) ($term['ay_id'] ?? 0),
        'academic_year_label' => (string) ($term['ay'] ?? ''),
        'semester' => (int) ($term['semester'] ?? 0),
        'students' => $students,
    ];
}

function student_csv_import_row_label_value(array $row, string $label): ?string
{
    foreach ($row as $index => $cell) {
        if (strcasecmp(trim((string) $cell), $label) !== 0) {
            continue;
        }

        for ($cursor = $index + 1, $count = count($row); $cursor < $count; $cursor++) {
            $value = trim((string) $row[$cursor]);

            if ($value === '' || $value === ':') {
                continue;
            }

            return $value;
        }

        return null;
    }

    return null;
}

function student_csv_import_row_is_empty(array $row): bool
{
    foreach ($row as $cell) {
        if (trim((string) $cell) !== '') {
            return false;
        }
    }

    return true;
}

function student_csv_import_parse_subject_section(string $value): array
{
    $value = student_csv_import_clean_cell($value);

    if (!preg_match('/^\s*(.+?)\s*-\s*(.+?)\s*$/', $value, $matches)) {
        throw new StudentCsvImportException('Unable to read the subject and section from "' . $value . '".');
    }

    $subjectCode = strtoupper(student_csv_import_clean_cell($matches[1]));
    $sectionText = strtoupper(student_csv_import_clean_cell(str_replace('-', ' ', $matches[2])));
    $sectionText = preg_replace('/\s+/', ' ', $sectionText) ?? $sectionText;

    if ($subjectCode === '' || $sectionText === '') {
        throw new StudentCsvImportException('The uploaded CSV file has an incomplete Subject/Section value.');
    }

    if (!preg_match('/\b([1-9])\b/', preg_replace('/([A-Z]+)(\d+)/i', '$1 $2', $sectionText) ?? $sectionText, $yearLevelMatch)) {
        if (!preg_match('/([1-9])/', $sectionText, $yearLevelMatch)) {
            throw new StudentCsvImportException('Unable to determine the year level from section "' . $sectionText . '".');
        }
    }

    return [
        'subject_code' => $subjectCode,
        'section_text' => $sectionText,
        'year_level' => (int) $yearLevelMatch[1],
    ];
}

function student_csv_import_parse_student_number(string $value): int
{
    $digits = preg_replace('/\D+/', '', $value) ?? '';

    return $digits === '' ? 0 : (int) $digits;
}

function student_csv_import_parse_student_name(string $rawName, int $rowNumber): array
{
    $rawName = student_csv_import_clean_cell($rawName);

    if (strpos($rawName, ',') === false) {
        throw new StudentCsvImportException('Student row ' . $rowNumber . ' has an invalid name format.', [
            'Expected "LAST NAME, FIRST NAME MIDDLE NAME" but found "' . $rawName . '".',
        ]);
    }

    [$lastName, $givenNames] = array_map('trim', explode(',', $rawName, 2));

    if ($lastName === '' || $givenNames === '') {
        throw new StudentCsvImportException('Student row ' . $rowNumber . ' has an incomplete name.', [
            'Expected "LAST NAME, FIRST NAME MIDDLE NAME" but found "' . $rawName . '".',
        ]);
    }

    $tokens = preg_split('/\s+/', $givenNames) ?: [];
    $tokens = array_values(array_filter(array_map(static function ($token): string {
        return trim((string) $token, " \t\n\r\0\x0B.");
    }, $tokens), static function (string $token): bool {
        return $token !== '';
    }));

    $suffixName = '';
    if ($tokens !== []) {
        $suffixCandidate = strtoupper((string) end($tokens));
        if (in_array($suffixCandidate, ['JR', 'SR', 'II', 'III', 'IV', 'V'], true)) {
            $suffixName = (string) array_pop($tokens);
        }
    }

    if ($tokens === []) {
        throw new StudentCsvImportException('Student row ' . $rowNumber . ' has an incomplete given name.', [
            'Unable to determine the first name from "' . $rawName . '".',
        ]);
    }

    $firstName = '';
    $middleName = '';

    if (count($tokens) === 1) {
        $firstName = $tokens[0];
    } else {
        $middleName = (string) array_pop($tokens);
        $firstName = implode(' ', $tokens);
    }

    return [
        'last_name' => strtoupper($lastName),
        'first_name' => strtoupper($firstName),
        'middle_name' => strtoupper($middleName),
        'suffix_name' => strtoupper($suffixName),
    ];
}

function student_csv_import_generate_student_email(string $firstName, string $lastName): string
{
    $localPart = strtolower(student_csv_import_ascii_slug($firstName . $lastName));

    if ($localPart === '') {
        throw new StudentCsvImportException('Unable to generate the student email address from the uploaded name.');
    }

    return $localPart . '@sksu.edu.ph';
}

function student_csv_import_ascii_slug(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? $value;
}

function student_csv_import_normalize_lookup(string $value): string
{
    $value = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtoupper($value);
    $value = preg_replace('/\b(MR|MS|MRS|MISS|DR|PROF|ENGR|SIR|MADAM|MAAM)\b\.?/i', '', $value) ?? $value;
    return preg_replace('/[^A-Z0-9]+/', '', $value) ?? $value;
}

function student_csv_import_resolve_subject(PDO $pdo, string $subjectCode, string $descriptiveTitle): array
{
    static $subjectCache = null;

    if (!is_array($subjectCache)) {
        $statement = $pdo->query(
            "SELECT sub_id, sub_code, sub_description, status
               FROM tbl_subject_masterlist
              WHERE status = 'active'
              ORDER BY sub_id ASC"
        );

        $subjectCache = [
            'by_code' => [],
            'by_title' => [],
        ];

        foreach ($statement->fetchAll() as $row) {
            $codeKey = student_csv_import_normalize_lookup((string) ($row['sub_code'] ?? ''));
            $titleKey = student_csv_import_normalize_lookup((string) ($row['sub_description'] ?? ''));

            if ($codeKey !== '' && !isset($subjectCache['by_code'][$codeKey])) {
                $subjectCache['by_code'][$codeKey] = $row;
            }

            if ($titleKey !== '' && !isset($subjectCache['by_title'][$titleKey])) {
                $subjectCache['by_title'][$titleKey] = $row;
            }
        }
    }

    $codeKey = student_csv_import_normalize_lookup($subjectCode);

    if ($codeKey !== '' && isset($subjectCache['by_code'][$codeKey])) {
        return $subjectCache['by_code'][$codeKey];
    }

    $titleKey = student_csv_import_normalize_lookup($descriptiveTitle);

    if ($titleKey !== '' && isset($subjectCache['by_title'][$titleKey])) {
        return $subjectCache['by_title'][$titleKey];
    }

    throw new StudentCsvImportException('The subject could not be matched to tbl_subject_masterlist.', [
        'CSV subject code: ' . $subjectCode,
        $descriptiveTitle !== '' ? 'CSV descriptive title: ' . $descriptiveTitle : '',
    ]);
}

function student_csv_import_resolve_program(PDO $pdo, string $sectionText): array
{
    static $programIndex = null;

    if (!is_array($programIndex)) {
        $statement = $pdo->query(
            "SELECT
                p.program_id,
                p.college_id,
                p.program_code,
                p.program_name,
                p.status,
                COALESCE(es.import_total, 0) AS import_total
             FROM tbl_program p
             LEFT JOIN (
                SELECT program_id, COUNT(*) AS import_total
                  FROM tbl_student_management_enrolled_subjects
                 GROUP BY program_id
             ) es ON es.program_id = p.program_id
             WHERE p.status = 'active'
             ORDER BY es.import_total DESC, p.program_id ASC"
        );

        $programIndex = [];

        foreach ($statement->fetchAll() as $row) {
            $codeKey = student_csv_import_normalize_lookup((string) ($row['program_code'] ?? ''));

            if ($codeKey === '') {
                continue;
            }

            if (
                !isset($programIndex[$codeKey])
                || (int) ($row['import_total'] ?? 0) > (int) ($programIndex[$codeKey]['import_total'] ?? 0)
            ) {
                $programIndex[$codeKey] = $row;
            }
        }
    }

    $normalizedSection = student_csv_import_normalize_lookup($sectionText);
    $bestMatch = null;
    $bestLength = -1;

    foreach ($programIndex as $codeKey => $program) {
        if (strpos($normalizedSection, $codeKey) !== 0) {
            continue;
        }

        $codeLength = strlen($codeKey);

        if ($codeLength <= $bestLength) {
            continue;
        }

        $bestMatch = $program;
        $bestLength = $codeLength;
    }

    if ($bestMatch !== null) {
        return $bestMatch;
    }

    throw new StudentCsvImportException('The section could not be matched to tbl_program.', [
        'CSV section: ' . $sectionText,
    ]);
}

function student_csv_import_resolve_faculty(PDO $pdo, string $instructorName): array
{
    static $facultyRows = null;

    if (!is_array($facultyRows)) {
        $statement = $pdo->query(
            "SELECT faculty_id, first_name, middle_name, last_name, ext_name, status
               FROM tbl_faculty
              WHERE status = 'active'
              ORDER BY faculty_id ASC"
        );

        $facultyRows = [];

        foreach ($statement->fetchAll() as $row) {
            $firstName = student_csv_import_clean_cell((string) ($row['first_name'] ?? ''));
            $middleName = student_csv_import_clean_cell((string) ($row['middle_name'] ?? ''));
            $lastName = student_csv_import_clean_cell((string) ($row['last_name'] ?? ''));
            $extName = student_csv_import_clean_cell((string) ($row['ext_name'] ?? ''));
            $displayName = trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $extName);

            $facultyRows[] = [
                'faculty' => $row,
                'display_name' => preg_replace('/\s+/', ' ', trim($displayName)) ?? trim($displayName),
                'first_name_key' => student_csv_import_normalize_lookup($firstName),
                'first_token_key' => student_csv_import_normalize_lookup((string) strtok($firstName, ' ')),
                'variants' => array_values(array_unique(array_filter([
                    student_csv_import_normalize_lookup($firstName . ' ' . $lastName),
                    student_csv_import_normalize_lookup($firstName . ' ' . $middleName . ' ' . $lastName),
                    student_csv_import_normalize_lookup($firstName . ' ' . $lastName . ' ' . $extName),
                    student_csv_import_normalize_lookup($lastName . ' ' . $firstName),
                ], static function (string $value): bool {
                    return $value !== '';
                }))),
            ];
        }
    }

    $segments = student_csv_import_instructor_segments($instructorName);

    foreach ($segments as $segment) {
        $segmentKey = student_csv_import_normalize_lookup($segment);

        foreach ($facultyRows as $candidate) {
            if (in_array($segmentKey, $candidate['variants'], true)) {
                return [
                    'faculty' => $candidate['faculty'],
                    'faculty_name' => $candidate['display_name'],
                    'match_note' => '',
                ];
            }
        }
    }

    $bestMatch = null;
    $bestDistance = PHP_INT_MAX;
    $secondBestDistance = PHP_INT_MAX;

    foreach ($segments as $segment) {
        $segmentKey = student_csv_import_normalize_lookup($segment);
        $segmentFirstToken = student_csv_import_normalize_lookup((string) strtok(student_csv_import_clean_cell($segment), ' '));

        foreach ($facultyRows as $candidate) {
            if ($segmentFirstToken !== '' && $candidate['first_token_key'] !== '' && $segmentFirstToken !== $candidate['first_token_key']) {
                continue;
            }

            foreach ($candidate['variants'] as $variant) {
                $distance = levenshtein($segmentKey, $variant);

                if ($distance < $bestDistance) {
                    $secondBestDistance = $bestDistance;
                    $bestDistance = $distance;
                    $bestMatch = $candidate;
                } elseif ($distance < $secondBestDistance) {
                    $secondBestDistance = $distance;
                }
            }
        }
    }

    if ($bestMatch !== null && $bestDistance <= 3 && $bestDistance < $secondBestDistance) {
        return [
            'faculty' => $bestMatch['faculty'],
            'faculty_name' => $bestMatch['display_name'],
            'match_note' => 'Faculty master matched by close spelling from "' . $instructorName . '" to "' . $bestMatch['display_name'] . '".',
        ];
    }

    $suggestions = student_csv_import_faculty_suggestions($facultyRows, $instructorName);
    throw new StudentCsvImportException('The instructor could not be matched to tbl_faculty.', $suggestions);
}

function student_csv_import_instructor_segments(string $instructorName): array
{
    $instructorName = trim(student_csv_import_clean_cell($instructorName));

    if ($instructorName === '') {
        return [];
    }

    $segments = [$instructorName];
    $splitPattern = '/\s*(?:\/|&|;| and )\s*/i';
    $parts = preg_split($splitPattern, $instructorName) ?: [];

    foreach ($parts as $part) {
        $part = trim($part);

        if ($part !== '') {
            $segments[] = $part;
        }
    }

    return array_values(array_unique($segments));
}

function student_csv_import_faculty_suggestions(array $facultyRows, string $instructorName): array
{
    $segmentKey = student_csv_import_normalize_lookup($instructorName);
    $scores = [];

    foreach ($facultyRows as $candidate) {
        $distance = PHP_INT_MAX;

        foreach ($candidate['variants'] as $variant) {
            $distance = min($distance, levenshtein($segmentKey, $variant));
        }

        $scores[] = [
            'distance' => $distance,
            'display_name' => (string) $candidate['display_name'],
        ];
    }

    usort($scores, static function (array $left, array $right): int {
        if ($left['distance'] === $right['distance']) {
            return strcmp((string) $left['display_name'], (string) $right['display_name']);
        }

        return (int) $left['distance'] <=> (int) $right['distance'];
    });

    $details = ['CSV instructor: ' . $instructorName];

    foreach (array_slice($scores, 0, 3) as $score) {
        $details[] = 'Possible faculty match: ' . (string) $score['display_name'];
    }

    return $details;
}

function student_csv_import_execute(PDO $pdo, array $context, array $students, int $uploadedByUserId): array
{
    $batchKey = date('YmdHis') . '-' . substr(bin2hex(random_bytes(12)), 0, 12);
    $warnings = [];

    if (trim((string) ($context['faculty_match_note'] ?? '')) !== '') {
        $warnings[] = (string) $context['faculty_match_note'];
    }

    $studentSelectByEmail = $pdo->prepare(
        "SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            suffix_name,
            email_address,
            program_id,
            ay_id,
            semester,
            year_level
         FROM tbl_student_management
         WHERE LOWER(email_address) = LOWER(:email_address)
         ORDER BY updated_at DESC, student_id DESC"
    );
    $studentSelectByNumber = $pdo->prepare(
        "SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            suffix_name,
            email_address,
            program_id,
            ay_id,
            semester,
            year_level
         FROM tbl_student_management
         WHERE student_number = :student_number
         ORDER BY updated_at DESC, student_id DESC"
    );
    $studentInsert = $pdo->prepare(
        "INSERT INTO tbl_student_management (
            ay_id,
            semester,
            source_sheet_name,
            source_file_name,
            year_level,
            student_number,
            last_name,
            first_name,
            middle_name,
            suffix_name,
            email_address,
            program_id,
            uploaded_by,
            source_row_number
        ) VALUES (
            :ay_id,
            :semester,
            :source_sheet_name,
            :source_file_name,
            :year_level,
            :student_number,
            :last_name,
            :first_name,
            :middle_name,
            :suffix_name,
            :email_address,
            :program_id,
            :uploaded_by,
            :source_row_number
        )"
    );
    $studentUpdate = $pdo->prepare(
        "UPDATE tbl_student_management
            SET ay_id = :ay_id,
                semester = :semester,
                source_sheet_name = :source_sheet_name,
                source_file_name = :source_file_name,
                year_level = :year_level,
                student_number = :student_number,
                last_name = :last_name,
                first_name = :first_name,
                middle_name = :middle_name,
                suffix_name = :suffix_name,
                email_address = :email_address,
                program_id = :program_id,
                uploaded_by = :uploaded_by,
                source_row_number = :source_row_number,
                updated_at = NOW()
          WHERE student_id = :student_id
          LIMIT 1"
    );
    $scopeRowsStatement = $pdo->prepare(
        "SELECT student_enrollment_id, student_id, is_active
           FROM tbl_student_management_enrolled_subjects
          WHERE program_id = :program_id
            AND ay_id = :ay_id
            AND semester = :semester
            AND subject_id = :subject_id
            AND section_text = :section_text
          ORDER BY student_enrollment_id DESC"
    );
    $deactivateScopeStatement = $pdo->prepare(
        "UPDATE tbl_student_management_enrolled_subjects
            SET is_active = 0,
                updated_at = NOW()
          WHERE program_id = :program_id
            AND ay_id = :ay_id
            AND semester = :semester
            AND subject_id = :subject_id
            AND section_text = :section_text
            AND is_active = 1"
    );
    $enrollmentInsert = $pdo->prepare(
        "INSERT INTO tbl_student_management_enrolled_subjects (
            import_batch_key,
            source_file_name,
            campus_id,
            college_id,
            program_id,
            ay_id,
            semester,
            year_level,
            section_id,
            section_text,
            offering_id,
            subject_id,
            subject_code,
            descriptive_title,
            student_id,
            faculty_id,
            room_id,
            room_text,
            schedule_text,
            source_row_number,
            uploaded_by,
            is_active
        ) VALUES (
            :import_batch_key,
            :source_file_name,
            :campus_id,
            :college_id,
            :program_id,
            :ay_id,
            :semester,
            :year_level,
            0,
            :section_text,
            0,
            :subject_id,
            :subject_code,
            :descriptive_title,
            :student_id,
            :faculty_id,
            0,
            :room_text,
            :schedule_text,
            :source_row_number,
            :uploaded_by,
            1
        )"
    );
    $enrollmentUpdate = $pdo->prepare(
        "UPDATE tbl_student_management_enrolled_subjects
            SET import_batch_key = :import_batch_key,
                source_file_name = :source_file_name,
                campus_id = :campus_id,
                college_id = :college_id,
                program_id = :program_id,
                ay_id = :ay_id,
                semester = :semester,
                year_level = :year_level,
                section_id = 0,
                section_text = :section_text,
                offering_id = 0,
                subject_id = :subject_id,
                subject_code = :subject_code,
                descriptive_title = :descriptive_title,
                student_id = :student_id,
                faculty_id = :faculty_id,
                room_id = 0,
                room_text = :room_text,
                schedule_text = :schedule_text,
                source_row_number = :source_row_number,
                uploaded_by = :uploaded_by,
                is_active = 1,
                updated_at = NOW()
          WHERE student_enrollment_id = :student_enrollment_id
          LIMIT 1"
    );

    $scopeParameters = [
        'program_id' => (int) $context['program_id'],
        'ay_id' => (int) $context['ay_id'],
        'semester' => (int) $context['semester'],
        'subject_id' => (int) $context['subject_id'],
        'section_text' => (string) $context['section_text'],
    ];

    $report = [
        'file_name' => (string) $context['file_name'],
        'source_sheet_name' => (string) $context['source_sheet_name'],
        'subject_code' => (string) $context['subject_code'],
        'descriptive_title' => (string) $context['descriptive_title'],
        'section_text' => (string) $context['section_text'],
        'faculty_name' => (string) $context['faculty_name'],
        'program_code' => (string) $context['program_code'],
        'program_name' => (string) $context['program_name'],
        'academic_year_label' => (string) $context['academic_year_label'],
        'semester_label' => (string) $context['semester_label'],
        'student_count' => count($students),
        'inserted_students' => 0,
        'updated_students' => 0,
        'reused_students' => 0,
        'inserted_enrollments' => 0,
        'updated_enrollments' => 0,
        'reactivated_enrollments' => 0,
        'deactivated_enrollments' => 0,
        'warnings' => $warnings,
        'batch_key' => $batchKey,
    ];

    $pdo->beginTransaction();

    try {
        $scopeRowsStatement->execute($scopeParameters);
        $scopeRows = $scopeRowsStatement->fetchAll();
        $existingEnrollmentByStudentId = [];

        foreach ($scopeRows as $scopeRow) {
            $studentId = (int) ($scopeRow['student_id'] ?? 0);

            if ($studentId > 0 && !isset($existingEnrollmentByStudentId[$studentId])) {
                $existingEnrollmentByStudentId[$studentId] = $scopeRow;
            }
        }

        $deactivateScopeStatement->execute($scopeParameters);
        $report['deactivated_enrollments'] = $deactivateScopeStatement->rowCount();

        foreach ($students as $student) {
            $studentRecord = student_csv_import_upsert_student(
                $pdo,
                $studentSelectByEmail,
                $studentSelectByNumber,
                $studentInsert,
                $studentUpdate,
                $student,
                $context,
                $uploadedByUserId
            );

            $report[$studentRecord['report_key']]++;
            $studentId = (int) $studentRecord['student_id'];

            $enrollmentParameters = [
                'import_batch_key' => $batchKey,
                'source_file_name' => (string) $context['file_name'],
                'campus_id' => (int) $context['campus_id'],
                'college_id' => (int) $context['college_id'],
                'program_id' => (int) $context['program_id'],
                'ay_id' => (int) $context['ay_id'],
                'semester' => (int) $context['semester'],
                'year_level' => (int) $context['year_level'],
                'section_text' => (string) $context['section_text'],
                'subject_id' => (int) $context['subject_id'],
                'subject_code' => (string) $context['subject_code'],
                'descriptive_title' => (string) $context['descriptive_title'],
                'student_id' => $studentId,
                'faculty_id' => (int) $context['faculty_id'],
                'room_text' => (string) $context['room_text'],
                'schedule_text' => (string) $context['schedule_text'],
                'source_row_number' => (int) ($student['source_row_number'] ?? 0),
                'uploaded_by' => $uploadedByUserId > 0 ? $uploadedByUserId : null,
            ];

            if (isset($existingEnrollmentByStudentId[$studentId])) {
                $existingEnrollment = $existingEnrollmentByStudentId[$studentId];
                $enrollmentParameters['student_enrollment_id'] = (int) $existingEnrollment['student_enrollment_id'];
                $enrollmentUpdate->execute($enrollmentParameters);

                if ((int) ($existingEnrollment['is_active'] ?? 0) === 1) {
                    $report['updated_enrollments']++;
                } else {
                    $report['reactivated_enrollments']++;
                }

                continue;
            }

            $enrollmentInsert->execute($enrollmentParameters);
            $report['inserted_enrollments']++;
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    return $report;
}

function student_csv_import_upsert_student(
    PDO $pdo,
    PDOStatement $studentSelectByEmail,
    PDOStatement $studentSelectByNumber,
    PDOStatement $studentInsert,
    PDOStatement $studentUpdate,
    array $student,
    array $context,
    int $uploadedByUserId
): array {
    $emailAddress = (string) ($student['email_address'] ?? '');
    $studentNumber = (int) ($student['student_number'] ?? 0);

    $studentSelectByEmail->execute(['email_address' => $emailAddress]);
    $emailMatches = $studentSelectByEmail->fetchAll();
    $matchedByEmail = student_csv_import_best_student_match($emailMatches, $student);

    $studentSelectByNumber->execute(['student_number' => $studentNumber]);
    $numberMatches = $studentSelectByNumber->fetchAll();
    $matchedByNumber = student_csv_import_best_student_match($numberMatches, $student);

    if ($matchedByEmail === null && $emailMatches !== []) {
        $emailConflicts = array_map(static function (array $row): string {
            return student_csv_import_student_display_name($row) . ' [' . (string) ($row['email_address'] ?? '') . ']';
        }, $emailMatches);

        throw new StudentCsvImportException(
            'Student email ' . $emailAddress . ' already belongs to a different student record.',
            $emailConflicts
        );
    }

    if ($matchedByEmail !== null && $matchedByNumber !== null && (int) $matchedByEmail['student_id'] !== (int) $matchedByNumber['student_id']) {
        throw new StudentCsvImportException(
            'Student identity conflict detected for ' . $emailAddress . '.',
            [
                'Email match resolves to student ID ' . (int) $matchedByEmail['student_id'] . '.',
                'Student number ' . $studentNumber . ' resolves to a different student ID ' . (int) $matchedByNumber['student_id'] . '.',
            ]
        );
    }

    $existingStudent = $matchedByEmail ?? $matchedByNumber;

    if ($existingStudent === null && $numberMatches !== []) {
        $conflictingNames = array_map(static function (array $row): string {
            return student_csv_import_student_display_name($row) . ' [' . (string) ($row['email_address'] ?? '') . ']';
        }, $numberMatches);

        throw new StudentCsvImportException(
            'Student number ' . $studentNumber . ' is already assigned to another student record.',
            $conflictingNames
        );
    }

    $studentPayload = [
        'ay_id' => (int) $context['ay_id'],
        'semester' => (int) $context['semester'],
        'source_sheet_name' => (string) $context['source_sheet_name'],
        'source_file_name' => (string) $context['file_name'],
        'year_level' => (int) $context['year_level'],
        'student_number' => $studentNumber,
        'last_name' => (string) ($student['last_name'] ?? ''),
        'first_name' => (string) ($student['first_name'] ?? ''),
        'middle_name' => (string) ($student['middle_name'] ?? ''),
        'suffix_name' => (string) ($student['suffix_name'] ?? ''),
        'email_address' => $emailAddress,
        'program_id' => (int) $context['program_id'],
        'uploaded_by' => $uploadedByUserId > 0 ? $uploadedByUserId : null,
        'source_row_number' => (int) ($student['source_row_number'] ?? 0),
    ];

    if ($existingStudent !== null) {
        $studentPayload['student_id'] = (int) $existingStudent['student_id'];
        $studentUpdate->execute($studentPayload);

        return [
            'student_id' => (int) $existingStudent['student_id'],
            'report_key' => student_csv_import_student_changed($existingStudent, $studentPayload) ? 'updated_students' : 'reused_students',
        ];
    }

    $studentInsert->execute($studentPayload);

    return [
        'student_id' => (int) $pdo->lastInsertId(),
        'report_key' => 'inserted_students',
    ];
}

function student_csv_import_best_student_match(array $rows, array $student): ?array
{
    if ($rows === []) {
        return null;
    }

    $matchingRows = array_values(array_filter($rows, static function (array $row) use ($student): bool {
        return student_csv_import_student_names_compatible($row, $student);
    }));

    if ($matchingRows !== []) {
        return $matchingRows[0];
    }

    return null;
}

function student_csv_import_student_names_compatible(array $existingStudent, array $incomingStudent): bool
{
    $existingLast = student_csv_import_normalize_lookup((string) ($existingStudent['last_name'] ?? ''));
    $incomingLast = student_csv_import_normalize_lookup((string) ($incomingStudent['last_name'] ?? ''));

    if ($existingLast === '' || $incomingLast === '' || $existingLast !== $incomingLast) {
        return false;
    }

    $existingFirst = student_csv_import_normalize_lookup((string) ($existingStudent['first_name'] ?? ''));
    $incomingFirst = student_csv_import_normalize_lookup((string) ($incomingStudent['first_name'] ?? ''));
    $existingFullGiven = student_csv_import_normalize_lookup(
        trim((string) ($existingStudent['first_name'] ?? '') . ' ' . (string) ($existingStudent['middle_name'] ?? ''))
    );
    $incomingFullGiven = student_csv_import_normalize_lookup(
        trim((string) ($incomingStudent['first_name'] ?? '') . ' ' . (string) ($incomingStudent['middle_name'] ?? ''))
    );

    if (
        $existingFirst !== $incomingFirst
        && $existingFullGiven !== $incomingFullGiven
        && $existingFirst !== $incomingFullGiven
        && $incomingFirst !== $existingFullGiven
    ) {
        return false;
    }

    $existingSuffix = student_csv_import_normalize_lookup((string) ($existingStudent['suffix_name'] ?? ''));
    $incomingSuffix = student_csv_import_normalize_lookup((string) ($incomingStudent['suffix_name'] ?? ''));

    return $existingSuffix === '' || $incomingSuffix === '' || $existingSuffix === $incomingSuffix;
}

function student_csv_import_student_changed(array $existingStudent, array $payload): bool
{
    $columns = [
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix_name',
        'email_address',
        'program_id',
        'ay_id',
        'semester',
        'year_level',
    ];

    foreach ($columns as $column) {
        if ((string) ($existingStudent[$column] ?? '') !== (string) ($payload[$column] ?? '')) {
            return true;
        }
    }

    return false;
}

function student_csv_import_student_display_name(array $student): string
{
    return person_full_name(
        $student['last_name'] ?? '',
        $student['first_name'] ?? '',
        $student['middle_name'] ?? '',
        $student['suffix_name'] ?? ''
    );
}
