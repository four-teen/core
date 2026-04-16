<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$previewStudentId = isset($_GET['preview_student_id']) ? (int) $_GET['preview_student_id'] : 0;
$isPreviewMode = $previewStudentId > 0;
$administrator = null;
$studentSession = null;

if ($isPreviewMode) {
    require_admin_authentication();
    $administrator = administrator_profile();
    $targetStudentId = $previewStudentId;
} else {
    require_student_authentication();
    $studentSession = student_profile();
    $targetStudentId = (int) ($studentSession['student_id'] ?? 0);
}

$studentRecord = null;
$summary = [];
$subjects = [];
$evaluations = [];
$studentQrPayload = '';
$studentQrSvg = '';
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    $studentRecord = student_profile_record($pdo, $targetStudentId);

    if ($studentRecord === null) {
        if ($isPreviewMode) {
            flash('error', 'That student record could not be found for preview.');
            redirect_to('administrator/students.php');
        }

        logout_student();
        flash('error', 'Your student record could not be loaded. Please sign in again.');
        redirect_to('auth/login.php');
    }

    $summary = student_portal_summary($pdo, $targetStudentId);
    $subjects = student_portal_subjects($pdo, $targetStudentId);
    $evaluations = student_portal_evaluations($pdo, $targetStudentId);

    if ($studentRecord !== null) {
        $studentQrPayload = student_portal_qr_payload($studentRecord);
        $studentQrSvg = student_portal_qr_svg($studentQrPayload);
    }
} catch (Throwable $exception) {
    $pageError = is_local_env()
        ? 'Unable to load the student portal. ' . $exception->getMessage()
        : 'Unable to load the student portal right now. Please try again.';
}
?>
<!DOCTYPE html>
<html
  lang="en"
  class="light-style"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0"
    />
    <title><?= h(app_name()) ?> | Student Portal</title>
    <meta name="description" content="Student portal for faculty evaluation details." />
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/css/app.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>
  <body class="student-portal-page">
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <div class="layout-page">
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme">
            <div class="navbar-brand d-flex align-items-center gap-2">
              <span class="brand-icon-shell student-brand-shell">
                <i class="bx bx-user-circle"></i>
              </span>
              <div class="portal-navbar-brand-copy">
                <div class="fw-bolder"><?= h(app_name()) ?></div>
                <small class="text-muted">Student Portal</small>
              </div>
            </div>
            <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
              <?php if ($isPreviewMode): ?>
                <div class="d-flex align-items-center gap-3 portal-navbar-actions">
                  <div class="text-end portal-navbar-user">
                    <div class="fw-semibold">Preview by <?= h($administrator['name'] ?? 'Administrator') ?></div>
                    <small class="text-muted portal-navbar-meta"><?= h($administrator['email'] ?? '') ?></small>
                  </div>
                  <a href="<?= h(base_url('administrator/students.php')) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-left-arrow-alt me-1"></i>
                    Back to Students
                  </a>
                </div>
              <?php else: ?>
                <div class="d-flex align-items-center gap-3 portal-navbar-actions">
                  <div class="text-end portal-navbar-user">
                    <div class="fw-semibold"><?= h($studentSession['name'] ?? 'Student') ?></div>
                    <small class="text-muted portal-navbar-meta"><?= h($studentSession['email'] ?? '') ?></small>
                  </div>
                  <a href="<?= h(base_url('student/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-log-out-circle me-1"></i>
                    Logout
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </nav>

          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
              <?php if ($isPreviewMode): ?>
                <div class="alert alert-warning" role="alert">
                  <strong>Preview Mode:</strong> You are viewing this student portal as an administrator. Evaluation actions are disabled in this view.
                </div>
              <?php endif; ?>

              <?php if ($noticeMessage !== null): ?>
                <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
              <?php endif; ?>

              <?php if ($errorMessage !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
              <?php endif; ?>

              <?php if ($pageError !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($pageError) ?></div>
              <?php endif; ?>

              <?php if ($studentRecord !== null): ?>
                <div class="row g-4 mb-4">
                  <div class="col-12">
                    <div class="card hero-panel student-hero-card">
                      <div class="card-body">
                        <div class="student-hero-layout">
                          <div class="student-hero-content">
                            <span class="badge student-hero-kicker mb-3">Student Portal</span>
                            <h3 class="mb-2"><?= h($studentRecord['full_name']) ?></h3>
                            <p class="student-hero-subtitle mb-3">
                              Review your active subjects and continue your faculty evaluations.
                            </p>
                            <div class="student-hero-meta">
                              <span class="student-hero-chip">
                                <i class="bx bx-id-card"></i>
                                Student No. <?= h((string) $studentRecord['student_number']) ?>
                              </span>
                              <span class="student-hero-chip">
                                <i class="bx bx-medal"></i>
                                <?= h(format_year_level($studentRecord['year_level'])) ?>
                              </span>
                            </div>
                          </div>

                          <div class="student-qr-check">
                            <div class="student-qr-frame">
                              <?= $studentQrSvg ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row g-4 mb-4">
                  <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card student-metric-card h-100">
                      <div class="card-body">
                        <span class="metric-icon student-metric-icon student-metric-icon-blue"><i class="bx bx-book-open"></i></span>
                        <div class="metric-value"><?= h(format_number($summary['enrolled_subjects'] ?? 0)) ?></div>
                        <div class="metric-label">Enrolled subjects</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card student-metric-card h-100">
                      <div class="card-body">
                        <span class="metric-icon student-metric-icon student-metric-icon-teal"><i class="bx bx-user-pin"></i></span>
                        <div class="metric-value"><?= h(format_number($summary['faculty_count'] ?? 0)) ?></div>
                        <div class="metric-label">Faculty handling</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card student-metric-card h-100">
                      <div class="card-body">
                        <span class="metric-icon student-metric-icon student-metric-icon-green"><i class="bx bx-check-circle"></i></span>
                        <div class="metric-value"><?= h(format_number($summary['submitted_evaluations'] ?? 0)) ?></div>
                        <div class="metric-label">Submitted evaluations</div>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card student-metric-card h-100">
                      <div class="card-body">
                        <span class="metric-icon student-metric-icon student-metric-icon-amber"><i class="bx bx-edit"></i></span>
                        <div class="metric-value"><?= h(format_number($summary['pending_evaluations'] ?? 0)) ?></div>
                        <div class="metric-label">Pending evaluations</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row g-4 mb-4">
                  <div class="col-12">
                    <div class="card student-section-card">
                      <div class="card-header">
                        <h5 class="mb-0">Your Subjects</h5>
                      </div>
                      <div class="card-body">
                        <div class="row g-4">
                          <?php if ($subjects === []): ?>
                            <div class="col-12">
                              <div class="text-center text-muted py-4">No active enrolled subjects found for this student.</div>
                            </div>
                          <?php endif; ?>
                          <?php foreach ($subjects as $row): ?>
                            <?php
                            $hasFaculty = (int) $row['faculty_id'] !== 0;
                            $isSubmittedSubject = ($row['submission_status'] ?? '') === 'submitted';
                            $isDraftSubject = ($row['submission_status'] ?? '') === 'draft';
                            $buttonLabel = 'Evaluate Teacher';
                            $buttonClass = 'btn-primary';
                            $buttonIcon = 'bx-edit-alt';
                            if ($isSubmittedSubject) {
                                $buttonLabel = 'Submitted';
                                $buttonClass = 'btn-success';
                                $buttonIcon = 'bx-check-circle';
                            } elseif ($isDraftSubject) {
                                $buttonLabel = 'Edit Evaluation';
                                $buttonClass = 'btn-warning';
                                $buttonIcon = 'bx-edit';
                            }
                            ?>
                            <div class="col-12 col-lg-6">
                              <div class="card subject-card student-subject-card h-100">
                                <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                      <span class="badge bg-label-primary mb-2"><?= h((string) $row['subject_code']) ?></span>
                                      <h5 class="mb-1"><?= h((string) $row['descriptive_title']) ?></h5>
                                      <div class="text-muted small"><?= h((string) $row['section_text']) ?></div>
                                    </div>
                                    <?php if ($isSubmittedSubject): ?>
                                      <span class="badge bg-label-success">Submitted</span>
                                    <?php elseif ($isDraftSubject): ?>
                                      <span class="badge bg-label-warning">Draft</span>
                                    <?php else: ?>
                                      <span class="badge bg-label-secondary">Pending</span>
                                    <?php endif; ?>
                                  </div>

                                  <div class="subject-meta-list">
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Faculty</span>
                                      <strong><?= h((string) $row['faculty_name']) ?></strong>
                                    </div>
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Schedule</span>
                                      <span><?= h((string) $row['schedule_text']) ?></span>
                                    </div>
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Room</span>
                                      <span><?= h((string) $row['room_text']) ?></span>
                                    </div>
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Academic Term</span>
                                      <span><?= h((string) $row['term_label']) ?></span>
                                    </div>
                                  </div>

                                  <?php if ($isSubmittedSubject): ?>
                                    <div class="text-muted small mt-3">
                                      Average rating: <?= h(format_average($row['average_rating'])) ?>
                                    </div>
                                  <?php endif; ?>

                                  <div class="mt-4">
                                    <?php if ($isPreviewMode): ?>
                                      <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                        Preview Only
                                      </button>
                                    <?php elseif (!$hasFaculty): ?>
                                      <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                        No Faculty Assigned
                                      </button>
                                    <?php else: ?>
                                      <a
                                        href="<?= h(base_url('student/evaluate.php?enrollment_id=' . (string) $row['student_enrollment_id'])) ?>"
                                        class="btn <?= h($buttonClass) ?> w-100"
                                      >
                                        <i class="bx <?= h($buttonIcon) ?> me-1"></i>
                                        <?= h($buttonLabel) ?>
                                      </a>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row g-4">
                  <div class="col-12">
                    <div class="card student-section-card">
                      <div class="card-header">
                        <h5 class="mb-0">Evaluation Records</h5>
                      </div>
                      <div class="card-body">
                        <div class="row g-4">
                          <?php if ($evaluations === []): ?>
                            <div class="col-12">
                              <div class="text-center text-muted py-4">No evaluation records found yet.</div>
                            </div>
                          <?php endif; ?>
                          <?php foreach ($evaluations as $row): ?>
                            <div class="col-12 col-lg-6">
                              <div class="card evaluation-history-card student-record-card h-100">
                                <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                      <h5 class="mb-1"><?= h((string) $row['faculty_name']) ?></h5>
                                      <div class="text-muted small"><?= h((string) $row['term_label']) ?></div>
                                    </div>
                                    <span class="badge <?= ($row['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                                      <?= h(ucfirst((string) ($row['submission_status'] ?? 'draft'))) ?>
                                    </span>
                                  </div>
                                  <div class="subject-meta-list">
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Average Rating</span>
                                      <strong><?= h(format_average($row['average_rating'])) ?></strong>
                                    </div>
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Subject</span>
                                      <span><?= h((string) $row['subject_summary']) ?></span>
                                    </div>
                                    <div class="subject-meta-item">
                                      <span class="subject-meta-label">Updated</span>
                                      <span><?= h(format_datetime((string) $row['updated_at'])) ?></span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
