<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Recent Evaluation Activity';
$pageDescription = 'Recent evaluation activity page for the administrator module.';
$activeAdminPage = 'evaluations';

$overview = [];
$evaluationOverview = [];
$facultySummaries = [];
$supervisorSummaries = [];
$roleSummaries = [];
$selectedFacultyId = isset($_GET['faculty_id']) ? (int) $_GET['faculty_id'] : 0;
$selectedFacultyDetails = [];
$selectedFacultyName = '';
$recentEvaluations = [];
$recentSupervisorEvaluations = [];
$recentRoleEvaluations = [];
$databaseError = null;

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $evaluationOverview = dashboard_evaluation_activity_overview($pdo);
    $facultySummaries = dashboard_evaluation_faculty_summary($pdo);
    $supervisorSummaries = dashboard_supervisor_evaluation_summary($pdo);
    $roleSummaries = dashboard_role_evaluation_summary($pdo);
    $recentEvaluations = dashboard_recent_evaluations($pdo, 50);
    $recentSupervisorEvaluations = dashboard_recent_supervisor_evaluations($pdo, 50);
    $recentRoleEvaluations = dashboard_recent_role_evaluations($pdo, 50);

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
              Review student SET, supervisory SEF, and role-based evaluation records across the system.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Evaluation Status</span>
              <h4 class="mb-1"><?= h(format_number($evaluationOverview['submitted_evaluations'] ?? 0)) ?> submitted</h4>
              <p class="mb-0"><?= h(format_number($evaluationOverview['draft_evaluations'] ?? 0)) ?> draft evaluations</p>
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
        <div class="metric-value"><?= h(format_number($evaluationOverview['submitted_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Submitted evaluations, all types</div>
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
        <div class="metric-value"><?= h(format_number($evaluationOverview['draft_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Draft evaluations, all types</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($evaluationOverview['evaluated_targets'] ?? 0)) ?></div>
        <div class="metric-label">Evaluated faculty and role targets</div>
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

  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Supervisory Evaluation Summary</h5>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Evaluations</th>
              <th>Supervisors</th>
              <th>Average Rating</th>
              <th>Last Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($supervisorSummaries === []): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No supervisory evaluation records have been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($supervisorSummaries as $supervisorSummary): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($supervisorSummary['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted">Faculty ID <?= h((string) ($supervisorSummary['faculty_id'] ?? '')) ?></small>
                </td>
                <td>
                  <div><?= h(format_number($supervisorSummary['evaluation_count'] ?? 0)) ?> total</div>
                  <small class="text-muted">
                    <?= h(format_number($supervisorSummary['submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($supervisorSummary['draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td><?= h(format_number($supervisorSummary['supervisor_count'] ?? 0)) ?></td>
                <td><?= h(format_average($supervisorSummary['average_rating'])) ?></td>
                <td><?= h(format_datetime((string) ($supervisorSummary['last_updated'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Role Evaluation Summary</h5>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Evaluatee Role</th>
              <th>Evaluations</th>
              <th>Evaluators</th>
              <th>Average Rating</th>
              <th>Last Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($roleSummaries === []): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No role evaluation records have been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($roleSummaries as $roleSummary): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h(user_management_role_label((string) ($roleSummary['evaluatee_role'] ?? ''))) ?></div>
                  <small class="text-muted"><?= h((string) ($roleSummary['evaluatee_role'] ?? '')) ?></small>
                </td>
                <td>
                  <div><?= h(format_number($roleSummary['evaluation_count'] ?? 0)) ?> total</div>
                  <small class="text-muted">
                    <?= h(format_number($roleSummary['submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($roleSummary['draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td><?= h(format_number($roleSummary['evaluator_count'] ?? 0)) ?></td>
                <td><?= h(format_average($roleSummary['average_rating'])) ?></td>
                <td><?= h(format_datetime((string) ($roleSummary['last_updated'] ?? ''))) ?></td>
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
                <th>Instrument</th>
                <th>Score</th>
                <th>Average Rating</th>
                <th>Status</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($selectedFacultyDetails === []): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
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
                  <td><?= h(dashboard_evaluation_instrument_label((string) ($detail['instrument_version'] ?? ''), 'SET')) ?></td>
                  <td>
                    <?= h(format_number($detail['total_score'] ?? 0)) ?>
                    /
                    <?= h(format_number(((int) ($detail['question_count'] ?? 0)) * 5)) ?>
                    <small class="text-muted d-block"><?= h(format_number($detail['question_count'] ?? 0)) ?> items</small>
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
          <h5 class="mb-0">Recent Student Evaluation Activity</h5>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Student</th>
              <th>Instrument</th>
              <th>Score</th>
              <th>Average Rating</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentEvaluations === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No evaluation submissions have been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentEvaluations as $row): ?>
              <?php
              $studentNumber = trim((string) ($row['student_number'] ?? ''));
              $studentName = trim((string) ($row['student_full_name'] ?? ''));
              $questionCount = (int) ($row['question_count'] ?? 0);
              ?>
              <tr>
                <td><?= h((string) $row['faculty_name']) ?></td>
                <td>
                  <div class="fw-semibold"><?= h($studentNumber !== '' ? $studentNumber : 'No student number') ?></div>
                  <?php if ($studentName !== ''): ?>
                    <small class="text-muted"><?= h($studentName) ?></small>
                  <?php endif; ?>
                </td>
                <td><?= h(dashboard_evaluation_instrument_label((string) ($row['instrument_version'] ?? ''), 'SET')) ?></td>
                <td>
                  <?= h(format_number($row['total_score'] ?? 0)) ?>
                  /
                  <?= h(format_number($questionCount * 5)) ?>
                  <small class="text-muted d-block"><?= h(format_number($questionCount)) ?> items</small>
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

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Recent Supervisory Evaluation Activity</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Supervisor</th>
              <th>Instrument</th>
              <th>Score</th>
              <th>Average Rating</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentSupervisorEvaluations === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No supervisory evaluation activity has been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentSupervisorEvaluations as $row): ?>
              <?php
              $questionCount = (int) ($row['question_count'] ?? 0);
              $supervisorName = trim((string) ($row['supervisor_name'] ?? ''));
              $supervisorEmail = trim((string) ($row['supervisor_email'] ?? ''));
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($row['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted"><?= h((string) ($row['subject_text'] ?? '')) ?></small>
                </td>
                <td>
                  <div class="fw-semibold"><?= h($supervisorName !== '' ? $supervisorName : 'Supervisor') ?></div>
                  <?php if ($supervisorEmail !== ''): ?>
                    <small class="text-muted"><?= h($supervisorEmail) ?></small>
                  <?php endif; ?>
                </td>
                <td><?= h(dashboard_evaluation_instrument_label((string) ($row['instrument_version'] ?? ''), 'SEF')) ?></td>
                <td>
                  <?= h(format_number($row['total_score'] ?? 0)) ?>
                  /
                  <?= h(format_number($questionCount * 5)) ?>
                  <small class="text-muted d-block"><?= h(format_number($questionCount)) ?> items</small>
                </td>
                <td><?= h(format_average($row['average_rating'])) ?></td>
                <td>
                  <span class="badge <?= ($row['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                    <?= h(ucfirst((string) ($row['submission_status'] ?? 'draft'))) ?>
                  </span>
                </td>
                <td><?= h(format_datetime((string) ($row['updated_at'] ?? $row['final_submitted_at'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Recent Role Evaluation Activity</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-borderless mb-0">
          <thead>
            <tr>
              <th>Evaluator</th>
              <th>Evaluatee</th>
              <th>Evaluation Type</th>
              <th>Score</th>
              <th>Average Rating</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentRoleEvaluations === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No role evaluation activity has been recorded yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentRoleEvaluations as $row): ?>
              <?php
              $questionCount = (int) ($row['question_count'] ?? 0);
              $evaluatorName = role_evaluation_user_display_name([
                  'full_name' => $row['evaluator_name'] ?? '',
                  'email_address' => $row['evaluator_email'] ?? '',
              ]);
              $evaluateeName = role_evaluation_user_display_name([
                  'full_name' => $row['evaluatee_user_name'] ?? '',
                  'email_address' => $row['evaluatee_email'] ?? '',
              ]);
              if ($evaluateeName === '') {
                  $evaluateeName = trim((string) ($row['evaluatee_name'] ?? ''));
              }
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h($evaluatorName !== '' ? $evaluatorName : 'Evaluator') ?></div>
                  <small class="text-muted"><?= h(user_management_role_label((string) ($row['evaluator_role'] ?? ''))) ?></small>
                </td>
                <td>
                  <div class="fw-semibold"><?= h($evaluateeName !== '' ? $evaluateeName : 'Evaluatee') ?></div>
                  <small class="text-muted"><?= h(user_management_role_label((string) ($row['evaluatee_role'] ?? ''))) ?></small>
                </td>
                <td>Role Evaluation</td>
                <td>
                  <?= h(format_number($row['total_score'] ?? 0)) ?>
                  /
                  <?= h(format_number($questionCount * 5)) ?>
                  <small class="text-muted d-block"><?= h(format_number($questionCount)) ?> items</small>
                </td>
                <td><?= h(format_average($row['average_rating'])) ?></td>
                <td>
                  <span class="badge <?= ($row['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                    <?= h(ucfirst((string) ($row['submission_status'] ?? 'draft'))) ?>
                  </span>
                </td>
                <td><?= h(format_datetime((string) ($row['updated_at'] ?? $row['final_submitted_at'] ?? $row['completed_at'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
