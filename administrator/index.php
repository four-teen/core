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
$noticeMessage = flash('notice');
$errorMessage = flash('error');
$userManagementError = null;
$userManagement = [];
$managedUserStats = [
    'total' => 0,
    'active' => 0,
];
$userManagementForm = [
    'user_management_id' => '',
    'email_address' => '',
    'full_name' => '',
    'account_role' => 'staff',
    'is_active' => '1',
];

try {
    $pdo = db();
    ensure_user_management_table($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_user') {
            $userId = trim((string) ($_POST['user_management_id'] ?? ''));

            $userManagementForm = [
                'user_management_id' => $userId,
                'email_address' => trim((string) ($_POST['email_address'] ?? '')),
                'full_name' => trim((string) ($_POST['full_name'] ?? '')),
                'account_role' => (string) ($_POST['account_role'] ?? 'staff'),
                'is_active' => !empty($_POST['is_active']) ? '1' : '0',
            ];

            $savedUserId = user_management_save(
                $pdo,
                [
                    'email_address' => $userManagementForm['email_address'],
                    'full_name' => $userManagementForm['full_name'],
                    'account_role' => $userManagementForm['account_role'],
                    'is_active' => $userManagementForm['is_active'] === '1',
                ],
                $userId !== '' ? (int) $userId : null
            );

            $message = $userId !== ''
                ? 'User access was updated successfully.'
                : 'User access was added successfully.';

            flash('notice', $message);
            redirect_to('administrator/index.php?edit_user_id=' . $savedUserId);
        }

        if ($action === 'delete_user') {
            $deleteUserId = (int) ($_POST['delete_user_id'] ?? 0);

            if ($deleteUserId <= 0) {
                throw new RuntimeException('Please select a valid user to remove.');
            }

            user_management_delete($pdo, $deleteUserId);
            flash('notice', 'User access was removed successfully.');
            redirect_to('administrator/index.php');
        }

        throw new RuntimeException('The requested user management action is not supported.');
    }
} catch (Throwable $exception) {
    $userManagementError = is_local_env()
        ? 'Unable to update user management. ' . $exception->getMessage()
        : $exception->getMessage();
}

try {
    $pdo = db();
    ensure_user_management_table($pdo);
    $overview = dashboard_overview($pdo);
    $currentTerm = dashboard_current_term($pdo);
    $recentEvaluations = dashboard_recent_evaluations($pdo);
    $userManagement = user_management_list($pdo);
    $managedUserStats['total'] = count($userManagement);
    $managedUserStats['active'] = count(array_filter($userManagement, static function (array $row): bool {
        return (int) ($row['is_active'] ?? 0) === 1;
    }));

    $editUserId = isset($_GET['edit_user_id']) ? (int) $_GET['edit_user_id'] : 0;
    if ($editUserId > 0) {
        $editingUser = user_management_find($pdo, $editUserId);

        if ($editingUser === null) {
            if ($errorMessage === null) {
                $errorMessage = 'The selected user could not be found.';
            }
        } elseif ($userManagementError === null) {
            $userManagementForm = [
                'user_management_id' => (string) ($editingUser['user_management_id'] ?? ''),
                'email_address' => (string) ($editingUser['email_address'] ?? ''),
                'full_name' => (string) ($editingUser['full_name'] ?? ''),
                'account_role' => (string) ($editingUser['account_role'] ?? 'staff'),
                'is_active' => (int) ($editingUser['is_active'] ?? 0) === 1 ? '1' : '0',
            ];
        }
    }
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

<?php if ($noticeMessage !== null): ?>
  <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
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
          <div class="col-md-6">
            <div class="module-note h-100">
              <span class="badge bg-label-success mb-2">User Management</span>
              <div class="mb-3">Manage the authorized non-student emails that can open the administrator module.</div>
              <a href="#user-management" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-right-arrow-alt me-1"></i>
                Open User Management
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4" id="user-management">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">User Management</h5>
        <small class="text-muted">Authorize non-student email addresses for Google sign-in.</small>
      </div>
      <div class="card-body">
        <?php if ($userManagementError !== null): ?>
          <div class="alert alert-danger" role="alert"><?= h($userManagementError) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
          <div class="col-sm-6">
            <div class="module-note h-100">
              <span class="badge bg-label-primary mb-2">Authorized Users</span>
              <div class="fs-4 fw-semibold"><?= h(format_number($managedUserStats['total'])) ?></div>
              <small class="text-muted">Rows in <code>tbl_user_management</code></small>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="module-note h-100">
              <span class="badge bg-label-success mb-2">Active Users</span>
              <div class="fs-4 fw-semibold"><?= h(format_number($managedUserStats['active'])) ?></div>
              <small class="text-muted">Accounts currently allowed to sign in</small>
            </div>
          </div>
        </div>

        <form method="post" action="<?= h(base_url('administrator/index.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="save_user" />
          <input type="hidden" name="user_management_id" value="<?= h($userManagementForm['user_management_id']) ?>" />

          <div class="mb-3">
            <label for="email_address" class="form-label">Email Address</label>
            <input
              type="email"
              class="form-control"
              id="email_address"
              name="email_address"
              value="<?= h($userManagementForm['email_address']) ?>"
              placeholder="name@sksu.edu.ph"
              required
            />
          </div>

          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input
              type="text"
              class="form-control"
              id="full_name"
              name="full_name"
              value="<?= h($userManagementForm['full_name']) ?>"
              placeholder="Optional display name"
            />
          </div>

          <div class="mb-3">
            <label for="account_role" class="form-label">Role</label>
            <select class="form-select" id="account_role" name="account_role" required>
              <?php foreach (user_management_role_options() as $roleValue => $roleLabel): ?>
                <option value="<?= h($roleValue) ?>" <?= $userManagementForm['account_role'] === $roleValue ? 'selected' : '' ?>>
                  <?= h($roleLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-check form-switch mb-4">
            <input
              class="form-check-input"
              type="checkbox"
              role="switch"
              id="is_active"
              name="is_active"
              value="1"
              <?= $userManagementForm['is_active'] === '1' ? 'checked' : '' ?>
            />
            <label class="form-check-label" for="is_active">Allow this user to sign in</label>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <?= $userManagementForm['user_management_id'] !== '' ? 'Update User' : 'Add User' ?>
            </button>
            <?php if ($userManagementForm['user_management_id'] !== ''): ?>
              <a href="<?= h(base_url('administrator/index.php')) ?>" class="btn btn-outline-secondary">Clear Form</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Authorized Non-Student Users</h5>
          <small class="text-muted">
            Google login checks this table first. If no match is found, the system falls back to
            <code>tbl_student_management.email_address</code>.
          </small>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($userManagement === []): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  No authorized non-student users have been added yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($userManagement as $managedUser): ?>
              <tr>
                <td>
                  <div class="fw-semibold">
                    <?= h(trim((string) ($managedUser['full_name'] ?? '')) !== '' ? (string) $managedUser['full_name'] : 'No display name') ?>
                  </div>
                  <small class="text-muted">Updated <?= h(format_datetime((string) ($managedUser['updated_at'] ?? ''))) ?></small>
                </td>
                <td><?= h((string) ($managedUser['email_address'] ?? '')) ?></td>
                <td>
                  <span class="badge <?= (string) ($managedUser['account_role'] ?? '') === 'administrator' ? 'bg-label-primary' : 'bg-label-info' ?>">
                    <?= h(user_management_role_label((string) ($managedUser['account_role'] ?? 'staff'))) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= (int) ($managedUser['is_active'] ?? 0) === 1 ? 'bg-label-success' : 'bg-label-secondary' ?>">
                    <?= (int) ($managedUser['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-2">
                    <a
                      href="<?= h(base_url('administrator/index.php?edit_user_id=' . (int) ($managedUser['user_management_id'] ?? 0))) ?>"
                      class="btn btn-outline-primary btn-sm"
                    >
                      Edit
                    </a>
                    <form method="post" action="<?= h(base_url('administrator/index.php')) ?>" onsubmit="return confirm('Remove this user from the access list?');">
                      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                      <input type="hidden" name="action" value="delete_user" />
                      <input type="hidden" name="delete_user_id" value="<?= h((string) ($managedUser['user_management_id'] ?? '0')) ?>" />
                      <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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
