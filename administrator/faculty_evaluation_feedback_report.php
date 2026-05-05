<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Faculty Evaluation Feedback Report';
$pageDescription = 'Official faculty evaluation feedback report form for the administrator module.';
$activeAdminPage = 'faculty_evaluation_feedback_report';

$selectedFacultyId = isset($_GET['faculty_id']) ? (int) $_GET['faculty_id'] : 0;
$requestedTermKey = trim((string) ($_GET['term_key'] ?? ''));
$selectedTermKey = '';
$facultyOptions = [];
$termOptions = [];
$report = null;
$databaseError = null;

try {
    $pdo = db();
    $facultyOptions = faculty_evaluation_feedback_report_faculty_options($pdo);

    if ($selectedFacultyId <= 0 && $facultyOptions !== []) {
        $selectedFacultyId = (int) ($facultyOptions[0]['faculty_id'] ?? 0);
    }

    if ($selectedFacultyId > 0) {
        $termOptions = individual_faculty_performance_term_options($pdo, $selectedFacultyId);
        $selectedTermKey = $requestedTermKey;

        if ($selectedTermKey === '' && $termOptions !== []) {
            $selectedTermKey = (string) ($termOptions[0]['term_key'] ?? '');
        }

        $termFilter = null;
        if ($selectedTermKey !== '' && $selectedTermKey !== 'all') {
            $termFilter = individual_faculty_performance_parse_term_key($selectedTermKey);

            if ($termFilter === null) {
                $selectedTermKey = $termOptions !== [] ? (string) ($termOptions[0]['term_key'] ?? '') : 'all';
                $termFilter = $selectedTermKey !== 'all'
                    ? individual_faculty_performance_parse_term_key($selectedTermKey)
                    : null;
            }
        }

        $report = faculty_evaluation_feedback_report_report($pdo, $selectedFacultyId, $termFilter, $termOptions);
    }
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load faculty evaluation feedback report data. ' . $exception->getMessage()
        : 'Unable to load faculty evaluation feedback report data right now. Please try again.';
}

$templateImageUrl = asset_url('assets/docs/faculty-evaluation-feedback-report-template.jpg');
$bagongPilipinasLogoUrl = asset_url('assets/docs/fefr-bagong-pilipinas.png');
$sksuSealLogoUrl = asset_url('assets/docs/fefr-sksu-seal.png');
$extraHeadContent = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
$extraBodyScripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('facultyEvaluationFeedbackReportFilterForm');
    var facultySelect = document.getElementById('faculty_id');
    var termSelect = document.getElementById('term_key');

    if (!form || !facultySelect) {
      return;
    }

    function submitSelectedFaculty() {
      if (facultySelect.value) {
        form.submit();
      }
    }

    if (window.jQuery && jQuery.fn.select2) {
      jQuery(facultySelect).select2({
        width: '100%',
        placeholder: 'Search faculty name',
        allowClear: false
      });

      jQuery(facultySelect).on('select2:select change', submitSelectedFaculty);
    }

    facultySelect.addEventListener('change', submitSelectedFaculty);

    if (termSelect) {
      termSelect.addEventListener('change', function () {
        if (facultySelect.value) {
          form.submit();
        }
      });
    }
  });
</script>
HTML;

require __DIR__ . '/_start.php';
?>
<?php if ($databaseError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($databaseError) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4 fefr-screen-controls">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Generate Faculty Evaluation Feedback Report</h5>
        <small class="text-muted">Uses the same computed performance data as the individual faculty performance report.</small>
      </div>
      <div class="card-body">
        <form method="get" action="<?= h(base_url('administrator/faculty_evaluation_feedback_report.php')) ?>" id="facultyEvaluationFeedbackReportFilterForm">
          <div class="row g-3 align-items-end">
            <div class="col-lg-5">
              <label for="faculty_id" class="form-label">Faculty</label>
              <select class="form-select fefr-faculty-select" id="faculty_id" name="faculty_id" required>
                <?php foreach ($facultyOptions as $facultyOption): ?>
                  <?php $optionFacultyId = (int) ($facultyOption['faculty_id'] ?? 0); ?>
                  <option value="<?= h((string) $optionFacultyId) ?>" <?= $optionFacultyId === $selectedFacultyId ? 'selected' : '' ?>>
                    <?= h((string) ($facultyOption['faculty_name'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-lg-4">
              <label for="term_key" class="form-label">Semester / A.Y.</label>
              <select class="form-select" id="term_key" name="term_key" <?= $selectedFacultyId <= 0 || $termOptions === [] ? 'disabled' : '' ?>>
                <?php if ($selectedFacultyId <= 0): ?>
                  <option value="">Select faculty first</option>
                <?php elseif ($termOptions === []): ?>
                  <option value="all">No submitted terms yet</option>
                <?php else: ?>
                  <option value="all" <?= $selectedTermKey === 'all' ? 'selected' : '' ?>>All submitted terms</option>
                  <?php foreach ($termOptions as $termOption): ?>
                    <?php $termKey = (string) ($termOption['term_key'] ?? ''); ?>
                    <option value="<?= h($termKey) ?>" <?= $termKey === $selectedTermKey ? 'selected' : '' ?>>
                      <?= h((string) ($termOption['term_label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-lg-3">
              <button type="button" class="btn btn-outline-secondary" onclick="window.print()" <?= $report === null ? 'disabled' : '' ?>>
                <i class="bx bx-printer me-1"></i>
                Print
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($selectedFacultyId > 0 && $report === null && $databaseError === null): ?>
  <div class="alert alert-warning fefr-screen-controls" role="alert">
    The selected faculty member could not be found in the active faculty master list or has no submitted evaluation yet.
  </div>
<?php endif; ?>

<?php if ($report !== null): ?>
  <?php
  $feedback = $report['feedback'];
  $recommendations = $feedback['recommendations'];
  $programChairName = trim((string) ($feedback['program_chair_name'] ?? ''));
  $deanName = trim((string) ($report['college_dean_name'] ?? ''));
  ?>

  <?php if (!$report['is_complete']): ?>
    <div class="alert alert-warning fefr-screen-controls" role="alert">
      This feedback report is partial. One rating source is still missing, so the recommendations should be reviewed after all ratings are submitted.
    </div>
  <?php endif; ?>

  <div class="card mb-4 fefr-screen-controls">
    <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1"><?= h((string) $report['faculty']['faculty_name']) ?></h5>
        <p class="mb-0 text-muted"><?= h((string) $report['term_scope']['note']) ?></p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a
          href="<?= h(base_url('administrator/individual_faculty_performance.php' . ($selectedTermKey !== '' ? '?faculty_id=' . rawurlencode((string) $selectedFacultyId) . '&term_key=' . rawurlencode($selectedTermKey) : '?faculty_id=' . rawurlencode((string) $selectedFacultyId)))) ?>"
          class="btn btn-outline-primary"
        >
          <i class="bx bx-file me-1"></i>
          Individual Report
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="bx bx-printer me-1"></i>
          Print Report
        </button>
      </div>
    </div>
  </div>

  <div class="fefr-print-shell">
    <section class="fefr-paper" style="--fefr-template-image: url('<?= h($templateImageUrl) ?>');">
      <div class="fefr-letterhead">
        <img src="<?= h($bagongPilipinasLogoUrl) ?>" alt="Bagong Pilipinas" class="fefr-letterhead-bagong" />
        <img src="<?= h($sksuSealLogoUrl) ?>" alt="Sultan Kudarat State University Seal" class="fefr-letterhead-seal" />
        <div class="fefr-letterhead-copy">
          <div>Republic of the Philippines</div>
          <strong>SULTAN KUDARAT STATE UNIVERSITY</strong>
          <em>EJC Montilla, City of Tacurong, 9800</em>
          <em>Province of Sultan Kudarat</em>
        </div>
        <div class="fefr-letterhead-code">
          <span>SKSU-INS-EFP-06</span>
          <span>Revision: 00</span>
          <span>Effective Date: July 07, 2025</span>
        </div>
      </div>

      <div class="fefr-paper-content">
        <h1 class="fefr-title">FACULTY EVALUATION FEEDBACK REPORT FORM</h1>

        <section class="fefr-section">
          <h2><span>1.</span> Faculty Information</h2>
          <div class="fefr-field-grid">
            <div class="fefr-field-line">
              <span>Name of Faculty:</span>
              <strong><?= h((string) $report['faculty']['faculty_name']) ?></strong>
            </div>
            <div class="fefr-field-line">
              <span>Department/Program:</span>
              <strong><?= h((string) $feedback['program_label']) ?></strong>
            </div>
            <div class="fefr-field-line">
              <span>Semester/School Year:</span>
              <strong><?= h((string) $feedback['term_line']) ?></strong>
            </div>
            <div class="fefr-field-line fefr-field-line-wide">
              <span>Subject(s) Handled:</span>
              <strong><?= h((string) $feedback['subject_line']) ?></strong>
            </div>
          </div>
        </section>

        <section class="fefr-section">
          <h2><span>2.</span> Evaluation Results</h2>
          <table class="fefr-report-table">
            <colgroup>
              <col class="fefr-source-col" />
              <col class="fefr-area-col" />
              <col class="fefr-remark-col" />
            </colgroup>
            <thead>
              <tr>
                <th>Evaluation Source</th>
                <th>Key Areas Assessed</th>
                <th>Rating/Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Supervisory Evaluation</td>
                <td>
                  <ul class="fefr-key-area-list">
                    <?php foreach ($feedback['key_areas'] as $keyArea): ?>
                      <li><?= h((string) $keyArea) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= h((string) $feedback['supervisor_remark']) ?></td>
              </tr>
              <tr>
                <td>Student Evaluation</td>
                <td>
                  <ul class="fefr-key-area-list">
                    <?php foreach ($feedback['key_areas'] as $keyArea): ?>
                      <li><?= h((string) $keyArea) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= h((string) $feedback['student_remark']) ?></td>
              </tr>
              <tr>
                <td>Overall Performance</td>
                <td>
                  <ul class="fefr-key-area-list">
                    <li>Combined average from supervisor and student results</li>
                  </ul>
                </td>
                <td><?= h((string) $feedback['overall_remark']) ?></td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="fefr-section fefr-response-section">
          <h2><span>3.</span> Strength/s Identified</h2>
          <div class="fefr-response-lines">
            <?= h((string) $feedback['strengths']) ?>
          </div>
        </section>

        <section class="fefr-section fefr-response-section">
          <h2><span>4.</span> Areas for Improvement</h2>
          <div class="fefr-response-lines">
            <?= h((string) $feedback['improvements']) ?>
          </div>
        </section>

        <section class="fefr-section">
          <h2><span>5.</span> Recommendations for Faculty Development</h2>
          <ul class="fefr-recommendation-list">
            <li>
              <span class="fefr-checkbox <?= !empty($recommendations['attend_training']['checked']) ? 'checked' : '' ?>" aria-hidden="true"></span>
              <span>
                Attend Training/Seminar
                <span class="fefr-specify">(Specify: <?= h(trim((string) ($recommendations['attend_training']['specify'] ?? '')) !== '' ? (string) $recommendations['attend_training']['specify'] : '____________________________') ?>)</span>
              </span>
            </li>
            <li>
              <span class="fefr-checkbox <?= !empty($recommendations['peer_mentoring']['checked']) ? 'checked' : '' ?>" aria-hidden="true"></span>
              <span>Peer Mentoring / Coaching</span>
            </li>
            <li>
              <span class="fefr-checkbox <?= !empty($recommendations['reobservation']['checked']) ? 'checked' : '' ?>" aria-hidden="true"></span>
              <span>Re-observation of Classes</span>
            </li>
            <li>
              <span class="fefr-checkbox <?= !empty($recommendations['research_extension']['checked']) ? 'checked' : '' ?>" aria-hidden="true"></span>
              <span>Research/Extension Involvement</span>
            </li>
            <li>
              <span class="fefr-checkbox <?= !empty($recommendations['others']['checked']) ? 'checked' : '' ?>" aria-hidden="true"></span>
              <span>Others: <?= h(trim((string) ($recommendations['others']['text'] ?? '')) !== '' ? (string) $recommendations['others']['text'] : '________________________________________________') ?></span>
            </li>
          </ul>
        </section>

        <section class="fefr-section fefr-acknowledgement">
          <h2><span>6.</span> Acknowledgement</h2>
          <p>I acknowledge that I have received and reviewed the feedback results from the evaluation.</p>

          <div class="fefr-signature-stack">
            <div class="fefr-signature-row">
              <strong>Faculty's Signature:</strong>
              <span class="fefr-signature-line">&nbsp;</span>
              <strong>Date:</strong>
              <span class="fefr-date-line">&nbsp;</span>
            </div>
            <div class="fefr-signature-row">
              <strong>Program Chairperson:</strong>
              <span class="fefr-signature-line"><?= h($programChairName !== '' ? $programChairName : ' ') ?></span>
              <strong>Date:</strong>
              <span class="fefr-date-line">&nbsp;</span>
            </div>
            <div class="fefr-signature-row">
              <strong>Dean/Head of Office:</strong>
              <span class="fefr-signature-line"><?= h($deanName !== '' ? $deanName : ' ') ?></span>
              <strong>Date:</strong>
              <span class="fefr-date-line">&nbsp;</span>
            </div>
          </div>
        </section>
      </div>
    </section>
  </div>
<?php else: ?>
  <div class="card fefr-screen-controls">
    <div class="card-body text-center py-5">
      <span class="avatar-initial rounded-circle bg-label-primary mb-3">
        <i class="bx bx-file"></i>
      </span>
      <h5 class="mb-2">Select a faculty member to generate the feedback report.</h5>
      <p class="mb-0 text-muted">
        The official feedback form will appear here with computed evaluation results.
      </p>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_end.php'; ?>
