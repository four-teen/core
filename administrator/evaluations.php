<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Recent Evaluation Activity';
$pageDescription = 'Recent evaluation activity page for the administrator module.';
$activeAdminPage = 'evaluations';

$overview = [];
$facultySummaries = [];
$selectedFacultyId = isset($_GET['faculty_id']) ? (int) $_GET['faculty_id'] : 0;
$selectedFacultyDetails = [];
$selectedFacultyName = '';
$recentEvaluations = [];
$databaseError = null;

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $facultySummaries = dashboard_evaluation_faculty_summary($pdo);
    $recentEvaluations = dashboard_recent_evaluations($pdo, 50);

    if ($selectedFacultyId > 0) {
        $selectedFacultyDetails = dashboard_faculty_evaluation_details($pdo, $selectedFacultyId);

        if ($selectedFacultyDetails !== []) {
            $selectedFacultyName = (string) ($selectedFacultyDetails[0]['faculty_name'] ?? '');
        } else {
            foreach ($facultySummaries as $facultySummary) {
                if ((int) ($facultySummary['faculty_id'] ?? 0) === $selectedFacultyId) {
                    $selectedFacultyName = (string) ($facultySummary['faculty_name'] ?? '');
                    break;
                }
            }
        }
    }
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load evaluation activity. ' . $exception->getMessage()
        : 'Unable to load evaluation activity right now. Please try again.';
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-warning mb-3">Recent Evaluation Activity</span>
            <h3 class="mb-2">Latest faculty evaluation records.</h3>
            <p class="mb-0">
              Review the newest submitted and draft evaluations recorded per enrolled subject.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Evaluation Status</span>
              <h4 class="mb-1"><?= h(format_number($overview['submitted_evaluations'] ?? 0)) ?> submitted</h4>
              <p class="mb-0"><?= h(format_number($overview['draft_evaluations'] ?? 0)) ?> draft evaluations</p>
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
        <span class="metric-icon bg-label-success"><i class="bx bx-check-circle"></i></span>
        <div class="metric-value"><?= h(format_number($overview['submitted_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Submitted evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-user-check"></i></span>
        <div class="metric-value"><?= h(format_number($overview['evaluated_students'] ?? 0)) ?></div>
        <div class="metric-label">Students evaluated</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-warning"><i class="bx bx-edit"></i></span>
        <div class="metric-value"><?= h(format_number($overview['draft_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Draft evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($overview['evaluated_faculty'] ?? 0)) ?></div>
        <div class="metric-label">Faculty already evaluated</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Faculty Evaluation Summary</h5>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Evaluations</th>
              <th>Students</th>
              <th>Subjects</th>
              <th>Average Rating</th>
              <th>Last Updated</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($facultySummaries === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No faculty evaluation records have been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($facultySummaries as $facultySummary): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($facultySummary['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted">Faculty ID <?= h((string) ($facultySummary['faculty_id'] ?? '')) ?></small>
                </td>
                <td>
                  <div><?= h(format_number($facultySummary['evaluation_count'] ?? 0)) ?> total</div>
                  <small class="text-muted">
                    <?= h(format_number($facultySummary['submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($facultySummary['draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td><?= h(format_number($facultySummary['student_count'] ?? 0)) ?></td>
                <td><?= h(format_number($facultySummary['subject_count'] ?? 0)) ?></td>
                <td><?= h(format_average($facultySummary['average_rating'])) ?></td>
                <td><?= h(format_datetime((string) ($facultySummary['last_updated'] ?? ''))) ?></td>
                <td class="text-end">
                  <a
                    href="<?= h(base_url('administrator/evaluations.php?faculty_id=' . (int) ($facultySummary['faculty_id'] ?? 0) . '#faculty-evaluation-details')) ?>"
                    class="btn btn-outline-primary btn-sm"
                  >
                    <i class="bx bx-list-ul me-1"></i>
                    View Details
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($selectedFacultyId > 0): ?>
    <div class="col-12" id="faculty-evaluation-details">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="mb-0">
              Evaluation Details<?= $selectedFacultyName !== '' ? ' - ' . h($selectedFacultyName) : '' ?>
            </h5>
          </div>
          <a href="<?= h(base_url('administrator/evaluations.php')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bx bx-x me-1"></i>
            Clear Details
          </a>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Student</th>
                <th>Subject</th>
                <th>Term</th>
                <th>Score</th>
                <th>Average Rating</th>
                <th>Status</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($selectedFacultyDetails === []): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    No evaluation details were found for this faculty.
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($selectedFacultyDetails as $detail): ?>
                <?php
                $studentNumber = trim((string) ($detail['student_number'] ?? ''));
                $studentName = trim((string) ($detail['student_full_name'] ?? ''));
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= h($studentNumber !== '' ? $studentNumber : 'No student number') ?></div>
                    <?php if ($studentName !== ''): ?>
                      <small class="text-muted"><?= h($studentName) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= h((string) ($detail['subject_code'] ?? '')) ?></div>
                    <small class="text-muted"><?= h((string) ($detail['subject_summary'] ?? '')) ?></small>
                  </td>
                  <td><?= h((string) ($detail['term_label'] ?? '')) ?></td>
                  <td>
                    <?= h(format_number($detail['total_score'] ?? 0)) ?>
                    /
                    <?= h(format_number($detail['question_count'] ?? 0)) ?>
                  </td>
                  <td><?= h(format_average($detail['average_rating'])) ?></td>
                  <td>
                    <span class="badge <?= ($detail['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                      <?= h(ucfirst((string) ($detail['submission_status'] ?? 'draft'))) ?>
                    </span>
                  </td>
                  <td><?= h(format_datetime((string) ($detail['updated_at'] ?? $detail['completed_at'] ?? ''))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Recent Evaluation Activity</h5>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Student</th>
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
              <?php
              $studentNumber = trim((string) ($row['student_number'] ?? ''));
              $studentName = trim((string) ($row['student_full_name'] ?? ''));
              ?>
              <tr>
                <td><?= h((string) $row['faculty_name']) ?></td>
                <td>
                  <div class="fw-semibold"><?= h($studentNumber !== '' ? $studentNumber : 'No student number') ?></div>
                  <?php if ($studentName !== ''): ?>
                    <small class="text-muted"><?= h($studentName) ?></small>
                  <?php endif; ?>
                </td>
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
