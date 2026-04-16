<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Program Chair';
$pageDescription = 'Program chair faculty eligibility management page for the administrator module.';
$activeAdminPage = 'program_chair';

$noticeMessage = flash('notice');
$errorMessage = flash('error');
$databaseError = null;
$managementError = null;
$facultyOptions = [];
$selectedFaculty = [];
$stats = [
    'master_faculty' => 0,
    'eligible_faculty' => 0,
];

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add_faculty') {
            program_chair_faculty_add(
                $pdo,
                (int) ($_POST['faculty_id'] ?? 0),
                (int) ($administrator['user_management_id'] ?? 0)
            );

            flash('notice', 'Faculty member was added to the program chair evaluation list.');
            redirect_to('administrator/program_chair.php');
        }

        if ($action === 'remove_faculty') {
            program_chair_faculty_remove($pdo, (int) ($_POST['program_chair_faculty_id'] ?? 0));
            flash('notice', 'Faculty member was removed from the program chair evaluation list.');
            redirect_to('administrator/program_chair.php');
        }

        throw new RuntimeException('The requested program chair action is not supported.');
    }
} catch (Throwable $exception) {
    $managementError = is_local_env()
        ? 'Unable to update program chair faculty list. ' . $exception->getMessage()
        : $exception->getMessage();
}

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);
    $stats['master_faculty'] = program_chair_master_faculty_count($pdo);
    $stats['eligible_faculty'] = program_chair_faculty_list_count($pdo);
    $facultyOptions = program_chair_faculty_options($pdo);
    $selectedFaculty = program_chair_selected_faculty_list($pdo);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load program chair faculty data. ' . $exception->getMessage()
        : 'Unable to load program chair faculty data right now. Please try again.';
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-info mb-3">Program Chair</span>
            <h3 class="mb-2">Faculty list for supervisory evaluations.</h3>
            <p class="mb-0">
              Select faculty records from <code>tbl_faculty</code>. Program chair accounts can evaluate only faculty
              members included in this list.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Program Chair Coverage</span>
              <h4 class="mb-1"><?= h(format_number($stats['eligible_faculty'])) ?> eligible faculty</h4>
              <p class="mb-0"><?= h(format_number($stats['master_faculty'])) ?> active faculty in master list</p>
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
  <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
<?php endif; ?>

<?php if ($managementError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($managementError) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($stats['master_faculty'])) ?></div>
        <div class="metric-label">Active faculty in tbl_faculty</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-user-check"></i></span>
        <div class="metric-value"><?= h(format_number($stats['eligible_faculty'])) ?></div>
        <div class="metric-label">Faculty available to program chairs</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-list-plus"></i></span>
        <div class="metric-value"><?= h(format_number(count($facultyOptions))) ?></div>
        <div class="metric-label">Faculty available to add</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Add Faculty</h5>
        <small class="text-muted">Choose from active records in the faculty master list.</small>
      </div>
      <div class="card-body">
        <form method="post" action="<?= h(base_url('administrator/program_chair.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="add_faculty" />

          <div class="mb-4">
            <label for="faculty_id" class="form-label">Faculty</label>
            <select class="form-select" id="faculty_id" name="faculty_id" required <?= $facultyOptions === [] ? 'disabled' : '' ?>>
              <option value="">Select faculty</option>
              <?php foreach ($facultyOptions as $facultyOption): ?>
                <option value="<?= h((string) ($facultyOption['faculty_id'] ?? '')) ?>">
                  <?= h((string) ($facultyOption['faculty_name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-primary" <?= $facultyOptions === [] ? 'disabled' : '' ?>>
            <i class="bx bx-plus me-1"></i>
            Add to Program Chair List
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Faculty Available for Program Chair Evaluation</h5>
        <small class="text-muted">Only this selected list appears in the program chair module.</small>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Status</th>
              <th>Evaluations</th>
              <th>Average</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($selectedFaculty === []): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No faculty members have been added for program chair evaluation yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($selectedFaculty as $faculty): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($faculty['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted">Faculty ID <?= h((string) ($faculty['faculty_id'] ?? '')) ?></small>
                </td>
                <td>
                  <span class="badge <?= (string) ($faculty['status'] ?? '') === 'active' ? 'bg-label-success' : 'bg-label-secondary' ?>">
                    <?= h(ucfirst((string) ($faculty['status'] ?? 'inactive'))) ?>
                  </span>
                </td>
                <td>
                  <div><?= h(format_number($faculty['evaluation_count'] ?? 0)) ?> total</div>
                  <small class="text-muted">
                    <?= h(format_number($faculty['submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($faculty['draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td><?= h(format_average($faculty['average_rating'])) ?></td>
                <td class="text-end">
                  <form
                    method="post"
                    action="<?= h(base_url('administrator/program_chair.php')) ?>"
                    onsubmit="return confirm('Remove this faculty member from the program chair evaluation list?');"
                  >
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                    <input type="hidden" name="action" value="remove_faculty" />
                    <input
                      type="hidden"
                      name="program_chair_faculty_id"
                      value="<?= h((string) ($faculty['program_chair_faculty_id'] ?? '0')) ?>"
                    />
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="bx bx-trash me-1"></i>
                      Remove
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_end.php'; ?>
