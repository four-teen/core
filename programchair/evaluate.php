<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_program_chair_authentication();

$programChair = administrator_profile();
$programChairUserId = (int) ($programChair['user_management_id'] ?? 0);
$facultyId = isset($_GET['faculty_id']) ? (int) $_GET['faculty_id'] : (isset($_POST['faculty_id']) ? (int) $_POST['faculty_id'] : 0);

if ($facultyId <= 0) {
    flash('error', 'Select a faculty card first before opening the supervisory evaluation form.');
    redirect_to('programchair/index.php');
}

$pageTitle = 'Supervisory Evaluation';
$pageDescription = 'Program chair supervisory faculty evaluation form.';
$activeProgramChairPage = 'dashboard';

$context = null;
$evaluation = null;
$answers = [];
$subjectOptions = [];
$subjectKey = '';
$subjectText = '';
$evaluationDate = date('Y-m-d');
$evaluationTime = date('H:i');
$commentText = '';
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    $subjectOptions = program_chair_subject_options($pdo);
    $context = program_chair_evaluation_context($pdo, $facultyId, $programChairUserId);

    if ($context === null) {
        flash('error', 'That faculty member is not available for program chair evaluation.');
        redirect_to('programchair/index.php');
    }

    $evaluation = program_chair_find_evaluation_by_context($pdo, $programChairUserId, $facultyId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        if (($evaluation['submission_status'] ?? '') === 'submitted') {
            flash('error', 'This supervisory evaluation has already been submitted and can no longer be changed.');
            redirect_to('programchair/evaluate.php?faculty_id=' . (string) $facultyId);
        }

        $action = (string) ($_POST['action'] ?? '');
        $subjectKey = trim((string) ($_POST['subject_key'] ?? ''));
        $evaluationDate = trim((string) ($_POST['evaluation_date'] ?? ''));
        $evaluationTime = trim((string) ($_POST['evaluation_time'] ?? ''));
        $commentText = trim((string) ($_POST['comment_text'] ?? ''));
        $submittedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];
        $answers = program_chair_evaluation_submitted_answer_values($submittedAnswers);

        if ($action !== 'save_draft' && $action !== 'submit_evaluation') {
            throw new RuntimeException('Invalid supervisory evaluation action.');
        }

        if (
            $action === 'save_draft'
            && $subjectKey === ''
            && $evaluationDate === ''
            && $evaluationTime === ''
            && $commentText === ''
            && !program_chair_evaluation_has_any_answer($submittedAnswers)
        ) {
            throw new RuntimeException('Select at least one rating or add evaluation details before saving a draft.');
        }

        $status = $action === 'submit_evaluation' ? 'submitted' : 'draft';
        $evaluation = program_chair_create_or_get_evaluation($pdo, $context);
        $evaluation = program_chair_save_evaluation_submission(
            $pdo,
            $evaluation,
            $context,
            $submittedAnswers,
            $subjectKey,
            $evaluationDate,
            $evaluationTime,
            $commentText,
            $status
        );

        if ($status === 'submitted') {
            flash('notice', 'The supervisory faculty evaluation has been submitted successfully.');
            redirect_to('programchair/index.php');
        }

        flash('notice', 'Your supervisory evaluation draft has been saved.');
        redirect_to('programchair/evaluate.php?faculty_id=' . (string) $facultyId);
    }

    if ($evaluation !== null) {
        $answers = program_chair_find_evaluation_answers($pdo, (int) $evaluation['program_chair_evaluation_id']);
        $subjectKey = program_chair_subject_key_for_saved_evaluation($pdo, $evaluation);
        $subjectText = (string) ($evaluation['subject_text'] ?? '');
        $evaluationDate = trim((string) ($evaluation['evaluation_date'] ?? '')) !== ''
            ? (string) $evaluation['evaluation_date']
            : $evaluationDate;
        $evaluationTime = trim((string) ($evaluation['evaluation_time'] ?? '')) !== ''
            ? substr((string) $evaluation['evaluation_time'], 0, 5)
            : $evaluationTime;
        $commentText = (string) ($evaluation['comment_text'] ?? '');
    }
} catch (Throwable $exception) {
    $pageError = $_SERVER['REQUEST_METHOD'] === 'POST'
        ? $exception->getMessage()
        : (is_local_env()
        ? 'Unable to load the supervisory evaluation form. ' . $exception->getMessage()
        : 'Unable to load the supervisory evaluation form right now. Please try again.');
}

$questionBank = program_chair_evaluation_question_bank();
$scaleOptions = program_chair_evaluation_scale_options();
$isSubmitted = is_array($evaluation) && (($evaluation['submission_status'] ?? '') === 'submitted');
$extraHeadContent = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
$extraBodyScripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.select2) {
      return;
    }

    jQuery('.program-chair-subject-select').select2({
      width: '100%',
      placeholder: 'Search subject',
      allowClear: true
    });
  });
</script>
HTML;

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-end mb-3">
      <a href="<?= h(base_url('programchair/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-left-arrow-alt me-1"></i>
        Back to Faculty List
      </a>
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

<?php if ($context !== null): ?>
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card hero-panel h-100">
        <div class="card-body">
          <span class="badge bg-label-info mb-3">Supervisory Rating</span>
          <h3 class="mb-2"><?= h((string) $context['faculty_name']) ?></h3>
          <p class="mb-3">Faculty Performance Evaluation Tool for Program Chair supervisory rating.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="module-note">
                <span class="badge bg-label-primary mb-2">Evaluator</span>
                <div><?= h($programChair['name'] ?? 'Program Chair') ?></div>
                <div class="text-muted small"><?= h($programChair['email'] ?? '') ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="module-note">
                <span class="badge bg-label-success mb-2">Faculty</span>
                <div><?= h((string) $context['faculty_name']) ?></div>
                <div class="text-muted small">Faculty ID <?= h((string) $context['faculty_id']) ?></div>
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
      This supervisory evaluation was submitted on <?= h(format_datetime((string) ($evaluation['final_submitted_at'] ?? $evaluation['updated_at'] ?? ''))) ?>.
    </div>
  <?php endif; ?>

  <form method="post" action="<?= h(base_url('programchair/evaluate.php?faculty_id=' . (string) $facultyId)) ?>">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
    <input type="hidden" name="faculty_id" value="<?= h((string) $facultyId) ?>" />

    <div class="row g-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Evaluation Details</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="subject_key" class="form-label">Subject</label>
                <select
                  class="form-select program-chair-subject-select"
                  id="subject_key"
                  name="subject_key"
                  data-placeholder="Search subject"
                  <?= $isSubmitted ? 'disabled' : '' ?>
                >
                  <option value=""></option>
                  <?php if ($subjectKey === '' && trim($subjectText) !== ''): ?>
                    <option value="" selected><?= h($subjectText) ?></option>
                  <?php endif; ?>
                  <?php foreach ($subjectOptions as $subjectOption): ?>
                    <?php $optionSubjectKey = (string) ($subjectOption['subject_key'] ?? ''); ?>
                    <option value="<?= h($optionSubjectKey) ?>" <?= $subjectKey === $optionSubjectKey ? 'selected' : '' ?>>
                      <?= h((string) ($subjectOption['subject_label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($isSubmitted): ?>
                  <input type="hidden" name="subject_key" value="<?= h($subjectKey) ?>" />
                <?php endif; ?>
              </div>
              <div class="col-md-3">
                <label for="evaluation_date" class="form-label">Date</label>
                <input
                  type="date"
                  class="form-control"
                  id="evaluation_date"
                  name="evaluation_date"
                  value="<?= h($evaluationDate) ?>"
                  <?= $isSubmitted ? 'readonly' : '' ?>
                />
              </div>
              <div class="col-md-3">
                <label for="evaluation_time" class="form-label">Time</label>
                <input
                  type="time"
                  class="form-control"
                  id="evaluation_time"
                  name="evaluation_time"
                  value="<?= h($evaluationTime) ?>"
                  <?= $isSubmitted ? 'readonly' : '' ?>
                />
              </div>
            </div>
          </div>
        </div>
      </div>

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
                  <?php $selectedValue = program_chair_evaluation_answer_value($answers, $category, $position); ?>
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
            <h5 class="mb-0">Comments/Suggestions</h5>
          </div>
          <div class="card-body">
            <textarea
              class="form-control"
              name="comment_text"
              rows="5"
              placeholder="Share supervisory comments or suggestions here."
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
<?php require __DIR__ . '/_end.php'; ?>
