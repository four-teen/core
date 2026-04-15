<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Faculty';
$pageDescription = 'Faculty monitoring page for the administrator module.';
$activeAdminPage = 'faculty';

$overview = [];
$facultyLoad = [];
$subjectSections = [];
$databaseError = null;

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $facultyLoad = dashboard_faculty_load($pdo);
    $subjectSections = dashboard_subject_sections($pdo);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load faculty data. ' . $exception->getMessage()
        : 'Unable to load faculty data right now. Please try again.';
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-primary mb-3">Faculty</span>
            <h3 class="mb-2">Faculty teaching load and section monitoring.</h3>
            <p class="mb-0">
              This page focuses on faculty records resolved through <code>tbl_faculty</code>
              and active subject assignments from <code>tbl_student_management_enrolled_subjects</code>.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Faculty Coverage</span>
              <h4 class="mb-1"><?= h(format_number($overview['active_faculty_master'] ?? 0)) ?> active faculty</h4>
              <p class="mb-1"><?= h(format_number($overview['active_faculty'] ?? 0)) ?> with active load</p>
              <small><?= h(format_number($overview['active_subjects'] ?? 0)) ?> active subjects from master list</small>
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
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_faculty_master'] ?? 0)) ?></div>
        <div class="metric-label">Active faculty in master table</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-warning"><i class="bx bx-briefcase"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_faculty'] ?? 0)) ?></div>
        <div class="metric-label">Faculty currently handling students</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-book-open"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_subjects'] ?? 0)) ?></div>
        <div class="metric-label">Active subjects in subject master</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Faculty Load Snapshot</h5>
          <small class="text-muted">Grouped from active enrolled-subject rows</small>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Students</th>
              <th>Loads</th>
              <th>Assignments</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($facultyLoad === []): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">No faculty load data available.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($facultyLoad as $row): ?>
              <tr>
                <td>
                  <strong><?= h((string) $row['faculty_name']) ?></strong>
                  <div class="text-muted small">
                    <?= h(format_number($row['distinct_subjects'])) ?> distinct subjects
                  </div>
                </td>
                <td><?= h(format_number($row['student_count'])) ?></td>
                <td><?= h(format_number($row['teaching_load'])) ?></td>
                <td class="text-muted small"><?= h(truncate_text((string) ($row['assignments'] ?? ''), 90)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Largest Subject Sections</h5>
          <small class="text-muted">Current teaching sections by student count</small>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Faculty</th>
              <th>Students</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($subjectSections === []): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-4">No section data available.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($subjectSections as $row): ?>
              <tr>
                <td>
                  <strong><?= h((string) $row['subject_code']) ?></strong>
                  <div class="text-muted small">
                    <?= h((string) $row['descriptive_title']) ?> | <?= h((string) $row['section_text']) ?>
                  </div>
                  <div class="text-muted small">
                    <?= h((string) ($row['schedule_text'] ?? 'Schedule pending')) ?>
                  </div>
                </td>
                <td><?= h((string) $row['faculty_name']) ?></td>
                <td><?= h(format_number($row['student_count'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
