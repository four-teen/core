<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/student_csv_import.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Student CSV Import';
$pageDescription = 'Upload permit-style CSV files and sync student master and enrolled-subject rows.';
$activeAdminPage = 'students';

$noticeMessage = flash('notice');
$errorMessage = flash('error');
$databaseError = null;
$importErrorDetails = [];
$importReport = student_csv_import_consume_report();
$academicYearOptions = [];
$semesterOptions = student_csv_import_semester_options();
$form = [
    'ay_id' => '',
    'semester' => '2',
];

try {
    $pdo = db();
    $academicYearOptions = student_csv_import_academic_year_options($pdo);
    $defaultAcademicYearId = student_csv_import_default_ay_id($pdo);

    if ($defaultAcademicYearId > 0) {
        $form['ay_id'] = (string) $defaultAcademicYearId;
    }
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load import options. ' . $exception->getMessage()
        : 'Unable to load import options right now. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $databaseError === null) {
    $form['ay_id'] = trim((string) ($_POST['ay_id'] ?? $form['ay_id']));
    $form['semester'] = trim((string) ($_POST['semester'] ?? $form['semester']));

    try {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        $pdo = db();
        $report = student_csv_import_process_upload(
            $pdo,
            $_FILES['csv_file'] ?? [],
            (int) $form['ay_id'],
            (int) $form['semester'],
            (int) ($administrator['user_management_id'] ?? 0)
        );

        student_csv_import_store_report($report);
        flash(
            'notice',
            'Imported ' . format_number($report['student_count'] ?? 0)
            . ' students for ' . (string) ($report['subject_code'] ?? '')
            . ' - ' . (string) ($report['section_text'] ?? '') . '.'
        );
        redirect_to('administrator/student_import.php');
    } catch (StudentCsvImportException $exception) {
        $errorMessage = $exception->getMessage();
        $importErrorDetails = $exception->details();
    } catch (Throwable $exception) {
        $errorMessage = is_local_env()
            ? 'Unable to import the CSV file. ' . $exception->getMessage()
            : 'Unable to import the CSV file right now. Please try again.';
    }
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-info mb-3">Students</span>
            <h3 class="mb-2">Upload class-list CSV files into the student tables.</h3>
            <p class="mb-3">
              This importer reads the permit-style CSV layout, generates one student email per student name,
              reuses existing student rows when possible, and syncs active rows in
              <code>tbl_student_management_enrolled_subjects</code> for the uploaded subject, section, term, and program.
            </p>
            <div class="d-flex flex-wrap gap-2">
              <a href="<?= h(base_url('administrator/students.php')) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-left-arrow-alt me-1"></i>
                Back to Students
              </a>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Import Rules</span>
              <h4 class="mb-1">One student record per generated email</h4>
              <p class="mb-1">Email format: <code>firstnamelastname@sksu.edu.ph</code></p>
              <small>The importer validates the subject master, faculty master, and program before writing any row.</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($databaseError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($databaseError) ?></div>
<?php endif; ?>

<?php if ($noticeMessage !== null): ?>
  <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
  <div class="alert alert-danger" role="alert">
    <div class="fw-semibold mb-1"><?= h($errorMessage) ?></div>
    <?php if ($importErrorDetails !== []): ?>
      <ul class="mb-0 ps-3">
        <?php foreach ($importErrorDetails as $detail): ?>
          <li><?= h((string) $detail) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($importReport !== null): ?>
  <div class="card mb-4">
    <div class="card-header">
      <div>
        <h5 class="mb-0">Last Import Result</h5>
        <small class="text-muted">
          <?= h((string) ($importReport['subject_code'] ?? '')) ?> |
          <?= h((string) ($importReport['section_text'] ?? '')) ?> |
          <?= h((string) ($importReport['academic_year_label'] ?? '')) ?> |
          <?= h((string) ($importReport['semester_label'] ?? '')) ?>
        </small>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <span class="metric-icon bg-label-primary"><i class="bx bx-group"></i></span>
              <div class="metric-value"><?= h(format_number($importReport['student_count'] ?? 0)) ?></div>
              <div class="metric-label">Students in uploaded file</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <span class="metric-icon bg-label-success"><i class="bx bx-user-plus"></i></span>
              <div class="metric-value"><?= h(format_number($importReport['inserted_students'] ?? 0)) ?></div>
              <div class="metric-label">New student rows inserted</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <span class="metric-icon bg-label-info"><i class="bx bx-refresh"></i></span>
              <div class="metric-value"><?= h(format_number(($importReport['updated_students'] ?? 0) + ($importReport['reused_students'] ?? 0))) ?></div>
              <div class="metric-label">Existing student rows reused</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <span class="metric-icon bg-label-warning"><i class="bx bx-list-check"></i></span>
              <div class="metric-value"><?= h(format_number(($importReport['inserted_enrollments'] ?? 0) + ($importReport['updated_enrollments'] ?? 0) + ($importReport['reactivated_enrollments'] ?? 0))) ?></div>
              <div class="metric-label">Active enrolled-subject rows synced</div>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <tbody>
            <tr>
              <th scope="row">File</th>
              <td><?= h((string) ($importReport['file_name'] ?? '')) ?></td>
              <th scope="row">Batch Key</th>
              <td><?= h((string) ($importReport['batch_key'] ?? '')) ?></td>
            </tr>
            <tr>
              <th scope="row">Subject</th>
              <td><?= h((string) ($importReport['subject_code'] ?? '')) ?> | <?= h((string) ($importReport['descriptive_title'] ?? '')) ?></td>
              <th scope="row">Section</th>
              <td><?= h((string) ($importReport['section_text'] ?? '')) ?></td>
            </tr>
            <tr>
              <th scope="row">Program</th>
              <td><?= h((string) ($importReport['program_code'] ?? '')) ?> | <?= h((string) ($importReport['program_name'] ?? '')) ?></td>
              <th scope="row">Faculty</th>
              <td><?= h((string) ($importReport['faculty_name'] ?? '')) ?></td>
            </tr>
            <tr>
              <th scope="row">Student Rows</th>
              <td>
                <?= h(format_number($importReport['inserted_students'] ?? 0)) ?> inserted,
                <?= h(format_number($importReport['updated_students'] ?? 0)) ?> updated,
                <?= h(format_number($importReport['reused_students'] ?? 0)) ?> reused
              </td>
              <th scope="row">Enrollment Rows</th>
              <td>
                <?= h(format_number($importReport['inserted_enrollments'] ?? 0)) ?> inserted,
                <?= h(format_number($importReport['updated_enrollments'] ?? 0)) ?> updated,
                <?= h(format_number($importReport['reactivated_enrollments'] ?? 0)) ?> reactivated,
                <?= h(format_number($importReport['deactivated_enrollments'] ?? 0)) ?> deactivated
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php if (($importReport['warnings'] ?? []) !== []): ?>
        <div class="alert alert-warning mt-4 mb-0" role="alert">
          <div class="fw-semibold mb-1">Import Warnings</div>
          <ul class="mb-0 ps-3">
            <?php foreach (($importReport['warnings'] ?? []) as $warning): ?>
              <li><?= h((string) $warning) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="card h-100">
      <div class="card-header">
        <div>
          <h5 class="mb-0">Upload CSV File</h5>
          <small class="text-muted">Use the same permit-style CSV layout as the sample you shared.</small>
        </div>
      </div>
      <div class="card-body">
        <form method="post" action="<?= h(base_url('administrator/student_import.php')) ?>" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />

          <div class="mb-3">
            <label for="csv_file" class="form-label">CSV File</label>
            <input
              type="file"
              class="form-control"
              id="csv_file"
              name="csv_file"
              accept=".csv,text/csv"
              required
            />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="ay_id" class="form-label">Academic Year</label>
              <select class="form-select" id="ay_id" name="ay_id" required>
                <option value="">Select academic year</option>
                <?php foreach ($academicYearOptions as $option): ?>
                  <?php $optionAyId = (string) ($option['ay_id'] ?? ''); ?>
                  <option value="<?= h($optionAyId) ?>" <?= $form['ay_id'] === $optionAyId ? 'selected' : '' ?>>
                    <?= h((string) ($option['ay'] ?? '')) ?><?= (string) ($option['status'] ?? '') === 'active' ? ' (Active)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="semester" class="form-label">Semester</label>
              <select class="form-select" id="semester" name="semester" required>
                <?php foreach ($semesterOptions as $semesterValue => $semesterLabel): ?>
                  <option value="<?= h((string) $semesterValue) ?>" <?= $form['semester'] === (string) $semesterValue ? 'selected' : '' ?>>
                    <?= h($semesterLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-4" role="alert">
            The importer will deactivate the current active rows for the same subject, section, academic year,
            and semester before reactivating or inserting the uploaded class list. This prevents duplicate active loads
            for the same class.
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bx bx-upload me-1"></i>
            Import CSV
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="card h-100">
      <div class="card-header">
        <div>
          <h5 class="mb-0">What This Page Checks</h5>
          <small class="text-muted">Import validation is strict so the linked tables stay clean.</small>
        </div>
      </div>
      <div class="card-body">
        <ul class="ps-3 mb-4">
          <li>Reads the metadata rows for subject, descriptive title, instructor, schedule, and room.</li>
          <li>Resolves the subject through <code>tbl_subject_masterlist</code>.</li>
          <li>Resolves the faculty through <code>tbl_faculty</code>, including close-spelling matching when one clear match exists.</li>
          <li>Resolves the program and year level from the section text, then writes the linked <code>program_id</code>.</li>
          <li>Generates student emails using <code>firstnamelastname@sksu.edu.ph</code> and avoids duplicate student rows.</li>
        </ul>

        <div class="border rounded p-3 bg-lighter">
          <div class="fw-semibold mb-2">Expected CSV Layout</div>
          <div class="small text-muted mb-2">The importer looks for these labels inside the file:</div>
          <div class="small">
            <div><code>Subject/Section</code> like <code>GEC 005 - BSIT-1A</code></div>
            <div><code>Descriptive Title</code> like <code>The Contemporary World /Ang Kasalukuyang Daigdig</code></div>
            <div><code>Instructor(s)</code> and <code>Building/Room</code></div>
            <div><code>No.</code>, <code>Student's Name</code>, and <code>ID No.</code> in the student table header</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
