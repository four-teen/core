<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Administrator Dashboard';
$pageDescription = 'Administrator dashboard for faculty evaluation operations.';
$activeAdminPage = 'dashboard';

$overview = [];
$currentTerm = null;
$recentEvaluations = [];
$databaseError = null;

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $currentTerm = dashboard_current_term($pdo);
    $recentEvaluations = dashboard_recent_evaluations($pdo);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load dashboard data. ' . $exception->getMessage()
        : 'Unable to load dashboard data. Please confirm the database connection settings in .env.';
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-primary mb-3">Dashboard</span>
            <h3 class="mb-2">Administrator overview for the faculty evaluation system.</h3>
            <p class="mb-0">
              This page now stays focused on monitoring. Faculty management is in the Faculty page,
              and student search plus preview mode now live in the Students page.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Current Data Slice</span>
              <?php if ($currentTerm !== null): ?>
                <h4 class="mb-1"><?= h((string) $currentTerm['academic_year_label']) ?></h4>
                <p class="mb-1"><?= h(format_semester($currentTerm['semester'])) ?></p>
                <small>
                  <?= h(format_number($currentTerm['students'])) ?> students,
                  <?= h(format_number($currentTerm['faculty'])) ?> faculty,
                  <?= h(format_number($currentTerm['enrollment_rows'])) ?> enrollment rows
                </small>
              <?php else: ?>
                <h4 class="mb-1">No term data yet</h4>
                <p class="mb-0">Import student enrollment rows to populate this dashboard.</p>
              <?php endif; ?>
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
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user"></i></span>
        <div class="metric-value"><?= h(format_number($overview['total_students'] ?? 0)) ?></div>
        <div class="metric-label">Students in tbl_student_management</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-spreadsheet"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_enrollment_rows'] ?? 0)) ?></div>
        <div class="metric-label">Active enrollment rows</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-warning"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_faculty_master'] ?? 0)) ?></div>
        <div class="metric-label">Active faculty in master table</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-book-content"></i></span>
        <div class="metric-value"><?= h(format_number($overview['active_subjects'] ?? 0)) ?></div>
        <div class="metric-label">Active subjects in master list</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">Evaluation Status</h5>
        <div class="d-flex justify-content-between mb-2">
          <span>Submitted</span>
          <strong><?= h(format_number($overview['submitted_evaluations'] ?? 0)) ?></strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Draft</span>
          <strong><?= h(format_number($overview['draft_evaluations'] ?? 0)) ?></strong>
        </div>
        <div class="d-flex justify-content-between">
          <span>Faculty already evaluated</span>
          <strong><?= h(format_number($overview['evaluated_faculty'] ?? 0)) ?></strong>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-2">Quick Access</h5>
        <p class="mb-3">
          Use the dedicated administrator pages below to manage faculty-related data and preview student portals.
        </p>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="module-note h-100">
              <span class="badge bg-label-primary mb-2">Faculty Page</span>
              <div class="mb-3">Open the faculty page for teaching-load summaries and subject section monitoring.</div>
              <a href="<?= h(base_url('administrator/faculty.php')) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-right-arrow-alt me-1"></i>
                Open Faculty Page
              </a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="module-note h-100">
              <span class="badge bg-label-info mb-2">Students Page</span>
              <div class="mb-3">Open the students page for search, listing, and previewing the student dashboard.</div>
              <a href="<?= h(base_url('administrator/students.php')) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-right-arrow-alt me-1"></i>
                Open Students Page
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Recent Evaluation Activity</h5>
          <small class="text-muted">Latest records from tbl_student_faculty_evaluations</small>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Student Number</th>
              <th>Average Rating</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentEvaluations === []): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No evaluation submissions have been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentEvaluations as $row): ?>
              <tr>
                <td><?= h((string) $row['faculty_name']) ?></td>
                <td><?= h((string) $row['student_number']) ?></td>
                <td><?= h(format_average($row['average_rating'])) ?></td>
                <td>
                  <span class="badge <?= ($row['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                    <?= h(ucfirst((string) ($row['submission_status'] ?? 'draft'))) ?>
                  </span>
                </td>
                <td><?= h(format_datetime((string) ($row['updated_at'] ?? $row['completed_at'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
