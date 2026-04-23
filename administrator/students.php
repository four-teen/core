<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Students';
$pageDescription = 'Students page with student listing and preview mode for the administrator module.';
$activeAdminPage = 'students';

$noticeMessage = flash('notice');
$errorMessage = flash('error');
$overview = [];
$studentLookup = trim((string) ($_GET['student_lookup'] ?? ''));
$editStudentId = isset($_GET['edit_student_id']) ? (int) $_GET['edit_student_id'] : 0;
$previewStudents = [];
$databaseError = null;
$studentUpdateError = null;
$editingStudent = null;
$studentUpdateForm = [
    'student_id' => '',
    'student_number' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'suffix_name' => '',
    'email_address' => '',
    'current_term' => '',
];

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_student') {
            $editStudentId = (int) ($_POST['student_id'] ?? 0);
            $studentLookup = trim((string) ($_POST['student_lookup'] ?? ''));
            $studentUpdateForm = [
                'student_id' => (string) $editStudentId,
                'student_number' => trim((string) ($_POST['student_number'] ?? '')),
                'first_name' => trim((string) ($_POST['first_name'] ?? '')),
                'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
                'last_name' => trim((string) ($_POST['last_name'] ?? '')),
                'suffix_name' => trim((string) ($_POST['suffix_name'] ?? '')),
                'email_address' => trim((string) ($_POST['email_address'] ?? '')),
                'current_term' => '',
            ];

            if ($editStudentId <= 0) {
                throw new RuntimeException('Please choose a valid student record to update.');
            }

            student_profile_update($pdo, $editStudentId, $studentUpdateForm);

            $redirectParameters = [];
            if ($studentLookup !== '') {
                $redirectParameters['student_lookup'] = $studentLookup;
            }

            flash('notice', 'Student information was updated successfully.');
            $redirectPath = 'administrator/students.php';
            if ($redirectParameters !== []) {
                $redirectPath .= '?' . http_build_query($redirectParameters);
            }
            redirect_to($redirectPath);
        }

        throw new RuntimeException('The requested student action is not supported.');
    }
} catch (Throwable $exception) {
    $studentUpdateError = is_local_env()
        ? 'Unable to update the student record. ' . $exception->getMessage()
        : $exception->getMessage();
}

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $previewStudents = dashboard_student_preview_list($pdo, $studentLookup);

    if ($editStudentId > 0) {
        $editingStudent = student_profile_record($pdo, $editStudentId);

        if ($editingStudent === null) {
            if ($errorMessage === null) {
                $errorMessage = 'The selected student could not be found.';
            }
        } else {
            $studentUpdateForm['current_term'] = trim(sprintf(
                '%s | %s | %s',
                (string) ($editingStudent['academic_year_label'] ?? ''),
                format_semester($editingStudent['semester'] ?? 0),
                format_year_level($editingStudent['year_level'] ?? 0)
            ));

            if ($studentUpdateError === null) {
                $studentUpdateForm = [
                    'student_id' => (string) ($editingStudent['student_id'] ?? ''),
                    'student_number' => (string) ($editingStudent['student_number'] ?? ''),
                    'first_name' => (string) ($editingStudent['first_name'] ?? ''),
                    'middle_name' => (string) ($editingStudent['middle_name'] ?? ''),
                    'last_name' => (string) ($editingStudent['last_name'] ?? ''),
                    'suffix_name' => (string) ($editingStudent['suffix_name'] ?? ''),
                    'email_address' => (string) ($editingStudent['email_address'] ?? ''),
                    'current_term' => $studentUpdateForm['current_term'],
                ];
            }
        }
    }
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load student data. ' . $exception->getMessage()
        : 'Unable to load student data right now. Please try again.';
}

$studentEditDisplayName = person_full_name(
    $studentUpdateForm['last_name'],
    $studentUpdateForm['first_name'],
    $studentUpdateForm['middle_name'],
    $studentUpdateForm['suffix_name']
);
$shouldOpenStudentModal = $studentUpdateForm['student_id'] !== '';

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
            <p class="mb-3">
              Search students, review their program and enrollment counts, and open the student dashboard in preview mode
              without logging in as the student.
            </p>
            <a href="<?= h(base_url('administrator/student_import.php')) ?>" class="btn btn-outline-primary btn-sm">
              <i class="bx bx-upload me-1"></i>
              Open CSV Import
            </a>
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

<?php if ($noticeMessage !== null): ?>
  <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
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
      <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h5 class="mb-0">Students List and Preview Mode</h5>
            <small class="text-muted">Search a student and open the student portal without signing in as that student.</small>
          </div>
          <a href="<?= h(base_url('administrator/student_import.php')) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bx bx-upload me-1"></i>
            Import Class CSV
          </a>
        </div>
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
                <th class="text-end">Actions</th>
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
                    <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                      <?php
                      $rowCurrentTerm = trim(sprintf(
                          '%s | %s | %s',
                          (string) ($row['academic_year_label'] ?? ''),
                          format_semester($row['semester'] ?? 0),
                          format_year_level($row['year_level'] ?? 0)
                      ));
                      ?>
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#studentEditModal"
                        data-student-id="<?= h((string) ($row['student_id'] ?? '')) ?>"
                        data-student-number="<?= h((string) ($row['student_number'] ?? '')) ?>"
                        data-first-name="<?= h((string) ($row['first_name'] ?? '')) ?>"
                        data-middle-name="<?= h((string) ($row['middle_name'] ?? '')) ?>"
                        data-last-name="<?= h((string) ($row['last_name'] ?? '')) ?>"
                        data-suffix-name="<?= h((string) ($row['suffix_name'] ?? '')) ?>"
                        data-email-address="<?= h((string) ($row['email_address'] ?? '')) ?>"
                        data-current-term="<?= h($rowCurrentTerm) ?>"
                        data-display-name="<?= h((string) ($row['full_name'] ?? '')) ?>"
                      >
                        <i class="bx bx-pencil me-1"></i>
                        Edit
                      </button>
                      <a
                        href="<?= h(base_url('student/index.php?preview_student_id=' . (string) $row['student_id'])) ?>"
                        class="btn btn-outline-primary btn-sm"
                      >
                        <i class="bx bx-show me-1"></i>
                        Preview
                      </a>
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
</div>
<div class="modal fade" id="studentEditModal" tabindex="-1" aria-labelledby="studentEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <form method="post" action="<?= h(base_url('administrator/students.php')) ?>">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="studentEditModalLabel">Edit Student Information</h5>
            <small class="text-muted d-block">
              Update the student name and email address for student
              <span id="studentEditStudentNumberLabel"><?= h($studentUpdateForm['student_number'] !== '' ? '#' . $studentUpdateForm['student_number'] : '') ?></span>.
            </small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($studentUpdateError !== null): ?>
            <div class="alert alert-danger" role="alert" id="studentEditErrorAlert"><?= h($studentUpdateError) ?></div>
          <?php else: ?>
            <div class="alert alert-danger d-none" role="alert" id="studentEditErrorAlert"></div>
          <?php endif; ?>

          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="save_student" />
          <input type="hidden" name="student_id" id="student_edit_student_id" value="<?= h($studentUpdateForm['student_id']) ?>" />
          <input type="hidden" name="student_number" id="student_edit_student_number" value="<?= h($studentUpdateForm['student_number']) ?>" />
          <input type="hidden" name="student_lookup" value="<?= h($studentLookup) ?>" />

          <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
              <span class="badge bg-label-primary" id="studentEditDisplayName">
                <?= h($studentEditDisplayName !== '' ? $studentEditDisplayName : 'Selected student') ?>
              </span>
            </div>
            <div class="text-muted small" id="studentEditCurrentTerm">
              <?= h($studentUpdateForm['current_term']) ?>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label for="student_edit_last_name" class="form-label">Last Name</label>
              <input
                type="text"
                class="form-control"
                id="student_edit_last_name"
                name="last_name"
                value="<?= h($studentUpdateForm['last_name']) ?>"
                required
              />
            </div>
            <div class="col-sm-6">
              <label for="student_edit_first_name" class="form-label">First Name</label>
              <input
                type="text"
                class="form-control"
                id="student_edit_first_name"
                name="first_name"
                value="<?= h($studentUpdateForm['first_name']) ?>"
                required
              />
            </div>
            <div class="col-sm-6 col-lg-8">
              <label for="student_edit_middle_name" class="form-label">Middle Name</label>
              <input
                type="text"
                class="form-control"
                id="student_edit_middle_name"
                name="middle_name"
                value="<?= h($studentUpdateForm['middle_name']) ?>"
              />
            </div>
            <div class="col-sm-6 col-lg-4">
              <label for="student_edit_suffix_name" class="form-label">Suffix</label>
              <input
                type="text"
                class="form-control"
                id="student_edit_suffix_name"
                name="suffix_name"
                value="<?= h($studentUpdateForm['suffix_name']) ?>"
                placeholder="Jr., III"
              />
            </div>
            <div class="col-12">
              <label for="student_edit_email_address" class="form-label">Email Address</label>
              <input
                type="email"
                class="form-control"
                id="student_edit_email_address"
                name="email_address"
                value="<?= h($studentUpdateForm['email_address']) ?>"
                required
              />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('studentEditModal');

    if (!modalElement || typeof bootstrap === 'undefined') {
      return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    var errorAlert = document.getElementById('studentEditErrorAlert');
    var studentIdInput = document.getElementById('student_edit_student_id');
    var studentNumberInput = document.getElementById('student_edit_student_number');
    var firstNameInput = document.getElementById('student_edit_first_name');
    var middleNameInput = document.getElementById('student_edit_middle_name');
    var lastNameInput = document.getElementById('student_edit_last_name');
    var suffixNameInput = document.getElementById('student_edit_suffix_name');
    var emailAddressInput = document.getElementById('student_edit_email_address');
    var studentNumberLabel = document.getElementById('studentEditStudentNumberLabel');
    var displayName = document.getElementById('studentEditDisplayName');
    var currentTerm = document.getElementById('studentEditCurrentTerm');

    function setText(element, value) {
      if (!element) {
        return;
      }

      element.textContent = value;
    }

    function populateStudentModal(trigger) {
      if (!trigger) {
        return;
      }

      if (errorAlert) {
        errorAlert.classList.add('d-none');
        errorAlert.textContent = '';
      }

      if (studentIdInput) {
        studentIdInput.value = trigger.getAttribute('data-student-id') || '';
      }
      if (studentNumberInput) {
        studentNumberInput.value = trigger.getAttribute('data-student-number') || '';
      }
      if (firstNameInput) {
        firstNameInput.value = trigger.getAttribute('data-first-name') || '';
      }
      if (middleNameInput) {
        middleNameInput.value = trigger.getAttribute('data-middle-name') || '';
      }
      if (lastNameInput) {
        lastNameInput.value = trigger.getAttribute('data-last-name') || '';
      }
      if (suffixNameInput) {
        suffixNameInput.value = trigger.getAttribute('data-suffix-name') || '';
      }
      if (emailAddressInput) {
        emailAddressInput.value = trigger.getAttribute('data-email-address') || '';
      }

      setText(studentNumberLabel, (trigger.getAttribute('data-student-number') || '') !== '' ? '#' + (trigger.getAttribute('data-student-number') || '') : '');
      setText(displayName, trigger.getAttribute('data-display-name') || 'Selected student');
      setText(currentTerm, trigger.getAttribute('data-current-term') || '');
    }

    modalElement.addEventListener('show.bs.modal', function (event) {
      if (event.relatedTarget) {
        populateStudentModal(event.relatedTarget);
      }
    });

    <?php if ($shouldOpenStudentModal): ?>
    modal.show();
    <?php endif; ?>
  });
</script>
<?php require __DIR__ . '/_end.php'; ?>
