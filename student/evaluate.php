<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_student_authentication();

$studentSession = student_profile();
$studentId = (int) ($studentSession['student_id'] ?? 0);
$enrollmentId = isset($_GET['enrollment_id']) ? (int) $_GET['enrollment_id'] : (isset($_POST['enrollment_id']) ? (int) $_POST['enrollment_id'] : 0);

if ($enrollmentId <= 0) {
    flash('error', 'Select a subject card first before opening the evaluation form.');
    redirect_to('student/index.php');
}

$context = null;
$evaluation = null;
$answers = [];
$commentText = '';
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    $context = student_evaluation_context($pdo, $studentId, $enrollmentId);

    if ($context === null) {
        flash('error', 'That enrolled subject could not be found for this student.');
        redirect_to('student/index.php');
    }

    if ((int) $context['faculty_id'] === 0) {
        flash('error', 'This subject does not have an assigned faculty record yet.');
        redirect_to('student/index.php');
    }

    $evaluation = find_evaluation_by_context($pdo, (int) $context['student_enrollment_id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($evaluation['submission_status'] ?? '') === 'submitted') {
            flash('error', 'This evaluation has already been submitted and can no longer be changed.');
            redirect_to('student/evaluate.php?enrollment_id=' . (string) $enrollmentId);
        }

        $action = (string) ($_POST['action'] ?? '');
        $commentText = trim((string) ($_POST['comment_text'] ?? ''));
        $submittedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];
        $answers = evaluation_submitted_answer_values($submittedAnswers);

        if ($action !== 'save_draft' && $action !== 'submit_evaluation') {
            throw new RuntimeException('Invalid evaluation action.');
        }

        if ($action === 'save_draft' && trim($commentText) === '' && !evaluation_has_any_answer($submittedAnswers)) {
            throw new RuntimeException('Select at least one rating or add a comment before saving a draft.');
        }

        $status = $action === 'submit_evaluation' ? 'submitted' : 'draft';
        normalize_evaluation_answers($submittedAnswers, $status === 'submitted');
        $evaluation = create_or_get_evaluation($pdo, $context);
        $evaluation = save_evaluation_submission(
            $pdo,
            $evaluation,
            $context,
            $submittedAnswers,
            $commentText,
            $status
        );

        if ($status === 'submitted') {
            flash('notice', 'Your faculty evaluation has been submitted successfully.');
            redirect_to('student/index.php');
        }

        flash('notice', 'Your evaluation draft has been saved.');
        redirect_to('student/evaluate.php?enrollment_id=' . (string) $enrollmentId);
    }

    if ($evaluation !== null) {
        $answers = find_evaluation_answers($pdo, (int) $evaluation['evaluation_id']);
        $commentText = (string) ($evaluation['comment_text'] ?? '');
    }
} catch (Throwable $exception) {
    $pageError = $_SERVER['REQUEST_METHOD'] === 'POST'
        ? $exception->getMessage()
        : (is_local_env()
        ? 'Unable to load the evaluation form. ' . $exception->getMessage()
        : 'Unable to load the evaluation form right now. Please try again.');
}

$questionBank = evaluation_question_bank();
$scaleOptions = evaluation_scale_options();
$isSubmitted = is_array($evaluation) && (($evaluation['submission_status'] ?? '') === 'submitted');
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
    <title><?= h(app_name()) ?> | Faculty Evaluation</title>
    <meta name="description" content="Faculty evaluation form for students." />
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
  <body class="student-evaluation-page">
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <div class="layout-page">
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme">
            <div class="navbar-brand d-flex align-items-center gap-2">
              <span class="brand-icon-shell student-brand-shell">
                <i class="bx bx-edit-alt"></i>
              </span>
              <div class="portal-navbar-brand-copy">
                <div class="fw-bolder"><?= h(app_name()) ?></div>
                <small class="text-muted">Faculty Evaluation</small>
              </div>
            </div>
            <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
              <div class="d-flex align-items-center gap-3 portal-navbar-actions">
                <a href="<?= h(base_url('student/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="bx bx-left-arrow-alt me-1"></i>
                  Back to Student Portal
                </a>
              </div>
            </div>
          </nav>

          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
              <?php if ($noticeMessage !== null): ?>
                <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
              <?php endif; ?>

              <?php if ($errorMessage !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
              <?php endif; ?>

              <?php if ($pageError !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($pageError) ?></div>
              <?php endif; ?>

              <?php if ($context !== null): ?>
                <div class="row g-4 mb-4">
                  <div class="col-lg-8">
                    <div class="card hero-panel h-100">
                      <div class="card-body">
                        <span class="badge bg-label-primary mb-3">Evaluation Form</span>
                        <h3 class="mb-2"><?= h((string) $context['faculty_name']) ?></h3>
                        <p class="mb-3">
                          <?= h((string) $context['term_label']) ?>
                        </p>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <div class="module-note">
                              <span class="badge bg-label-info mb-2">Student</span>
                              <div><?= h((string) $context['student_name']) ?></div>
                              <div class="text-muted small">#<?= h((string) $context['student_number']) ?></div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="module-note">
                              <span class="badge bg-label-success mb-2">Subject</span>
                              <div><?= h((string) $context['subject_summary']) ?></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="card h-100">
                      <div class="card-body">
                        <h5 class="card-title mb-3">Rating Scale</h5>
                        <?php foreach ($scaleOptions as $score => $item): ?>
                          <div class="scale-option-card mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                              <div>
                                <strong><?= h((string) $score) ?> - <?= h($item['label']) ?></strong>
                                <div class="text-muted small"><?= h($item['description']) ?></div>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <?php if ($isSubmitted): ?>
                  <div class="alert alert-success" role="alert">
                    This evaluation was submitted on <?= h(format_datetime((string) ($evaluation['final_submitted_at'] ?? $evaluation['updated_at'] ?? ''))) ?>.
                  </div>
                <?php endif; ?>

                <form method="post" action="<?= h(base_url('student/evaluate.php?enrollment_id=' . (string) $enrollmentId)) ?>">
                  <input type="hidden" name="enrollment_id" value="<?= h((string) $enrollmentId) ?>" />

                  <div class="row g-4">
                    <?php foreach ($questionBank as $category): ?>
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h5 class="mb-0"><?= h($category['title']) ?></h5>
                          </div>
                          <div class="card-body">
                            <div class="evaluation-question-list">
                              <?php $position = 1; ?>
                              <?php foreach ($category['questions'] as $questionText): ?>
                                <?php $questionKey = evaluation_question_key($category, $position); ?>
                                <?php $selectedValue = evaluation_answer_value($answers, $category, $position); ?>
                                <div class="evaluation-question-card">
                                  <div class="evaluation-question-text">
                                    <strong><?= h((string) $position) ?>.</strong>
                                    <?= h($questionText) ?>
                                  </div>
                                  <div class="evaluation-rating-group">
                                    <?php foreach ($scaleOptions as $score => $item): ?>
                                      <label class="evaluation-rating-option <?= $selectedValue === $score ? 'active' : '' ?>">
                                        <input
                                          type="radio"
                                          name="answers[<?= h($questionKey) ?>]"
                                          value="<?= h((string) $score) ?>"
                                          <?= $selectedValue === $score ? 'checked' : '' ?>
                                          <?= $isSubmitted ? 'disabled' : '' ?>
                                        />
                                        <span><?= h((string) $score) ?></span>
                                      </label>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                                <?php $position++; ?>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>

                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="mb-0">Comments</h5>
                        </div>
                        <div class="card-body">
                          <textarea
                            class="form-control"
                            name="comment_text"
                            rows="5"
                            placeholder="Share your comments about the faculty member here."
                            <?= $isSubmitted ? 'readonly' : '' ?>
                          ><?= h($commentText) ?></textarea>
                        </div>
                      </div>
                    </div>

                    <?php if (!$isSubmitted): ?>
                      <div class="col-12">
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-end">
                          <button type="submit" name="action" value="save_draft" class="btn btn-outline-primary">
                            <i class="bx bx-save me-1"></i>
                            Save Draft
                          </button>
                          <button type="submit" name="action" value="submit_evaluation" class="btn btn-primary">
                            <i class="bx bx-check-circle me-1"></i>
                            Submit Evaluation
                          </button>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </form>
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
