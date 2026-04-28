<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_role_evaluator_authentication();

$roleEvaluator = administrator_profile();
$roleEvaluatorUserId = (int) ($roleEvaluator['user_management_id'] ?? 0);
$roleEvaluatorRole = administrator_profile_role();
$roleEvaluatorLabel = user_management_role_label($roleEvaluatorRole);
$targetLabel = role_evaluation_target_label_for($roleEvaluatorRole);
$targetUserId = isset($_GET['target_user_id']) ? (int) $_GET['target_user_id'] : (isset($_POST['target_user_id']) ? (int) $_POST['target_user_id'] : 0);

if ($targetUserId <= 0) {
    flash('error', 'Select an assigned account before opening the evaluation form.');
    redirect_to('roleevaluation/index.php');
}

$pageTitle = $roleEvaluatorLabel . ' Evaluation';
$pageDescription = $roleEvaluatorLabel . ' assigned role evaluation form.';

$context = null;
$evaluation = null;
$answers = [];
$evaluationDate = date('Y-m-d');
$evaluationTime = date('H:i');
$commentText = '';
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    ensure_role_evaluation_tables($pdo);
    $context = role_evaluation_context($pdo, $roleEvaluatorUserId, $roleEvaluatorRole, $targetUserId);

    if ($context === null) {
        flash('error', 'That account is not assigned to you for evaluation.');
        redirect_to('roleevaluation/index.php');
    }

    $evaluation = role_evaluation_find_by_context($pdo, $roleEvaluatorUserId, $targetUserId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        if (($evaluation['submission_status'] ?? '') === 'submitted') {
            flash('error', 'This evaluation has already been submitted and can no longer be changed.');
            redirect_to('roleevaluation/evaluate.php?target_user_id=' . (string) $targetUserId);
        }

        $action = (string) ($_POST['action'] ?? '');
        $evaluationDate = trim((string) ($_POST['evaluation_date'] ?? ''));
        $evaluationTime = trim((string) ($_POST['evaluation_time'] ?? ''));
        $commentText = trim((string) ($_POST['comment_text'] ?? ''));
        $submittedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];
        $answers = role_evaluation_submitted_answer_values($submittedAnswers);

        if ($action !== 'save_draft' && $action !== 'submit_evaluation') {
            throw new RuntimeException('Invalid evaluation action.');
        }

        if (
            $action === 'save_draft'
            && $commentText === ''
            && !role_evaluation_has_any_answer($submittedAnswers)
        ) {
            throw new RuntimeException('Select at least one rating or add a comment before saving a draft.');
        }

        $status = $action === 'submit_evaluation' ? 'submitted' : 'draft';
        $evaluation = role_evaluation_create_or_get($pdo, $context);
        $evaluation = role_evaluation_save_submission(
            $pdo,
            $evaluation,
            $context,
            $submittedAnswers,
            $evaluationDate,
            $evaluationTime,
            $commentText,
            $status
        );

        if ($status === 'submitted') {
            flash('notice', 'The evaluation has been submitted successfully.');
            redirect_to('roleevaluation/index.php');
        }

        flash('notice', 'Your evaluation draft has been saved.');
        redirect_to('roleevaluation/evaluate.php?target_user_id=' . (string) $targetUserId);
    }

    if ($evaluation !== null) {
        $answers = role_evaluation_find_answers($pdo, (int) $evaluation['role_evaluation_id']);
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
        ? 'Unable to load the evaluation form. ' . $exception->getMessage()
        : 'Unable to load the evaluation form right now. Please try again.');
}

$questionBank = role_evaluation_question_bank();
$scaleOptions = role_evaluation_scale_options();
$isSubmitted = is_array($evaluation) && (($evaluation['submission_status'] ?? '') === 'submitted');

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-end mb-3">
      <a href="<?= h(base_url('roleevaluation/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bx bx-left-arrow-alt me-1"></i>
        Back to Assigned List
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
          <span class="badge bg-label-info mb-3"><?= h($roleEvaluatorLabel) ?> Rating</span>
          <h3 class="mb-2"><?= h((string) $context['target_name']) ?></h3>
          <p class="mb-3"><?= h($targetLabel) ?> performance evaluation form.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="module-note">
                <span class="badge bg-label-primary mb-2">Evaluator</span>
                <div><?= h($roleEvaluator['name'] ?? $roleEvaluatorLabel) ?></div>
                <div class="text-muted small"><?= h($roleEvaluator['email'] ?? '') ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="module-note">
                <span class="badge bg-label-success mb-2"><?= h($targetLabel) ?></span>
                <div><?= h((string) $context['target_name']) ?></div>
                <div class="text-muted small"><?= h((string) $context['email_address']) ?></div>
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

  <form method="post" action="<?= h(base_url('roleevaluation/evaluate.php?target_user_id=' . (string) $targetUserId)) ?>">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
    <input type="hidden" name="target_user_id" value="<?= h((string) $targetUserId) ?>" />

    <div class="row g-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Evaluation Details</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
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
              <div class="col-md-6">
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

      <?php $benchmarkNumber = 1; ?>
      <?php foreach ($questionBank as $category): ?>
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><?= h($category['title']) ?></h5>
              <?php if (trim((string) ($category['description'] ?? '')) !== ''): ?>
                <small class="text-muted"><?= h((string) $category['description']) ?></small>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="evaluation-question-list">
                <?php $position = 1; ?>
                <?php foreach ($category['questions'] as $questionText): ?>
                  <?php $questionKey = evaluation_question_key($category, $position); ?>
                  <?php $selectedValue = role_evaluation_answer_value($answers, $category, $position); ?>
                  <div class="evaluation-question-card">
                    <div class="evaluation-question-text">
                      <strong><?= h((string) $benchmarkNumber) ?>.</strong>
                      <?= h($questionText) ?>
                      <?php $verificationItems = isset($category['verification'][$position]) && is_array($category['verification'][$position]) ? $category['verification'][$position] : []; ?>
                      <?php if ($verificationItems !== []): ?>
                        <div class="text-muted small mt-2">
                          <span class="fw-semibold">Suggested Means of Verification:</span>
                          <?= h(implode('; ', $verificationItems)) ?>
                        </div>
                      <?php endif; ?>
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
                  <?php $benchmarkNumber++; ?>
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
              placeholder="Share comments or suggestions here."
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
