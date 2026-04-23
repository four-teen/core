<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/_target_list.php';

require_role_evaluator_authentication();

$roleEvaluator = administrator_profile();
$roleEvaluatorUserId = (int) ($roleEvaluator['user_management_id'] ?? 0);
$roleEvaluatorRole = administrator_profile_role();
$roleEvaluatorLabel = user_management_role_label($roleEvaluatorRole);
$targetLabel = role_evaluation_target_label_for($roleEvaluatorRole);
$pageTitle = $roleEvaluatorLabel . ' Evaluations';
$pageDescription = $roleEvaluatorLabel . ' assigned role evaluation module.';

$summary = [];
$targetList = [];
$recentEvaluations = [];
$targetSearch = trim((string) ($_GET['target_search'] ?? ''));
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    ensure_role_evaluation_tables($pdo);
    $summary = role_evaluation_summary($pdo, $roleEvaluatorUserId, $roleEvaluatorRole);
    $targetList = role_evaluation_targets_for_evaluation($pdo, $roleEvaluatorUserId, $roleEvaluatorRole, $targetSearch);
    $recentEvaluations = role_evaluation_recent_evaluations($pdo, $roleEvaluatorUserId, $roleEvaluatorRole, 10);
} catch (Throwable $exception) {
    $pageError = is_local_env()
        ? 'Unable to load the evaluation module. ' . $exception->getMessage()
        : 'Unable to load the evaluation module right now. Please try again.';
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-info mb-3"><?= h($roleEvaluatorLabel) ?></span>
            <h3 class="mb-2"><?= h($targetLabel) ?> evaluation.</h3>
            <p class="mb-0">
              Evaluate only the <?= h(strtolower($targetLabel)) ?> account assignments configured by the administrator.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Your Evaluation Progress</span>
              <h4 class="mb-1"><?= h(format_number($summary['submitted_evaluations'] ?? 0)) ?> submitted</h4>
              <p class="mb-0"><?= h(format_number($summary['draft_evaluations'] ?? 0)) ?> drafts in progress</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($noticeMessage !== null): ?>
  <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
<?php endif; ?>

<?php if ($pageError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($pageError) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-check"></i></span>
        <div class="metric-value"><?= h(format_number($summary['assigned_targets'] ?? 0)) ?></div>
        <div class="metric-label">Assigned <?= h($targetLabel) ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-check-circle"></i></span>
        <div class="metric-value"><?= h(format_number($summary['submitted_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Submitted evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-warning"><i class="bx bx-edit"></i></span>
        <div class="metric-value"><?= h(format_number($summary['draft_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Draft evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-star"></i></span>
        <div class="metric-value"><?= h(format_average($summary['average_rating'] ?? 0)) ?></div>
        <div class="metric-label">Average submitted rating</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h5 class="mb-0"><?= h($targetLabel) ?> for Evaluation</h5>
          <small class="text-muted">This list is controlled by administrator assignments.</small>
        </div>
        <form method="get" action="<?= h(base_url('roleevaluation/index.php')) ?>">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input
              type="text"
              class="form-control"
              name="target_search"
              value="<?= h($targetSearch) ?>"
              placeholder="Search name or email"
              autocomplete="off"
            />
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($targetSearch !== ''): ?>
              <a href="<?= h(base_url('roleevaluation/index.php')) ?>" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="card-body">
        <div class="program-chair-faculty-list">
          <?php role_evaluation_render_target_list($targetList, $targetSearch); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Your Evaluation Records</h5>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Date/Time</th>
              <th>Average</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentEvaluations === []): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  No role evaluations have been started yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentEvaluations as $evaluation): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($evaluation['target_name'] ?? '')) ?></div>
                  <small class="text-muted"><?= h((string) ($evaluation['email_address'] ?? '')) ?></small>
                </td>
                <td><?= h(user_management_role_label((string) ($evaluation['evaluatee_role'] ?? ''))) ?></td>
                <td>
                  <div><?= h(trim((string) ($evaluation['evaluation_date'] ?? '')) !== '' ? (string) $evaluation['evaluation_date'] : 'Not set') ?></div>
                  <small class="text-muted"><?= h(role_evaluation_format_time($evaluation['evaluation_time'] ?? '')) ?></small>
                </td>
                <td><?= h(format_average($evaluation['average_rating'] ?? 0)) ?></td>
                <td>
                  <span class="badge <?= ($evaluation['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                    <?= h(ucfirst((string) ($evaluation['submission_status'] ?? 'draft'))) ?>
                  </span>
                </td>
                <td><?= h(format_datetime((string) ($evaluation['updated_at'] ?? ''))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
