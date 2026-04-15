<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Students';
$pageDescription = 'Students page with student listing and preview mode for the administrator module.';
$activeAdminPage = 'students';

$overview = [];
$studentLookup = trim((string) ($_GET['student_lookup'] ?? ''));
$previewStudents = [];
$databaseError = null;

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $previewStudents = dashboard_student_preview_list($pdo, $studentLookup);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load student data. ' . $exception->getMessage()
        : 'Unable to load student data right now. Please try again.';
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
            <h3 class="mb-2">Student listing and preview dashboard.</h3>
            <p class="mb-0">
              Search students, review their program and enrollment counts, and open the student dashboard in preview mode
              without logging in as the student.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Student Coverage</span>
              <h4 class="mb-1"><?= h(format_number($overview['total_students'] ?? 0)) ?> students</h4>
              <p class="mb-1"><?= h(format_number($overview['active_enrollment_rows'] ?? 0)) ?> active enrollment rows</p>
              <small><?= h(format_number($overview['submitted_evaluations'] ?? 0)) ?> submitted evaluations in the system</small>
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

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-group"></i></span>
        <div class="metric-value"><?= h(format_number($overview['total_students'] ?? 0)) ?></div>
        <div class="metric-label">Students in master table</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-list-ul"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_enrollment_rows'] ?? 0)) ?></div>
        <div class="metric-label">Active enrolled-subject rows</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-check-circle"></i></span>
        <div class="metric-value"><?= h(format_number($overview['submitted_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Submitted evaluations</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card student-preview-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="mb-0">Students List and Preview Mode</h5>
          <small class="text-muted">Search a student and open the student portal without signing in as that student.</small>
        </div>
        <a href="<?= h(base_url('student/login.php')) ?>" class="btn btn-outline-primary btn-sm">
          <i class="bx bx-link-external me-1"></i>
          Student Login Page
        </a>
      </div>
      <div class="card-body">
        <form method="get" action="<?= h(base_url('administrator/students.php')) ?>" class="student-preview-form mb-4">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input
              type="text"
              class="form-control"
              name="student_lookup"
              value="<?= h($studentLookup) ?>"
              placeholder="Search by student number, email, first name, or last name"
            />
            <button type="submit" class="btn btn-primary">Search</button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-borderless mb-0">
            <thead>
              <tr>
                <th>Student</th>
                <th>Program</th>
                <th>Subjects</th>
                <th>Faculty</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($previewStudents === []): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    No students matched your search.
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($previewStudents as $row): ?>
                <tr>
                  <td>
                    <strong><?= h((string) $row['full_name']) ?></strong>
                    <div class="text-muted small">#<?= h((string) $row['student_number']) ?> | <?= h((string) $row['email_address']) ?></div>
                    <div class="text-muted small"><?= h((string) $row['academic_year_label']) ?> | <?= h(format_semester($row['semester'])) ?> | <?= h(format_year_level($row['year_level'])) ?></div>
                  </td>
                  <td>
                    <strong><?= h((string) $row['program_code']) ?></strong>
                    <div class="text-muted small"><?= h(truncate_text((string) $row['program_name'], 60)) ?></div>
                  </td>
                  <td><?= h(format_number($row['enrolled_subjects'])) ?></td>
                  <td><?= h(format_number($row['faculty_count'])) ?></td>
                  <td class="text-end">
                    <a
                      href="<?= h(base_url('student/index.php?preview_student_id=' . (string) $row['student_id'])) ?>"
                      class="btn btn-outline-primary btn-sm"
                    >
                      <i class="bx bx-show me-1"></i>
                      Preview
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
