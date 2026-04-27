<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Individual Faculty Performance';
$pageDescription = 'Official individual faculty performance report for the administrator module.';
$activeAdminPage = 'individual_faculty_performance';

$selectedFacultyId = isset($_GET['faculty_id']) ? (int) $_GET['faculty_id'] : 0;
$requestedTermKey = trim((string) ($_GET['term_key'] ?? ''));
$selectedTermKey = '';
$facultyOptions = [];
$termOptions = [];
$report = null;
$databaseError = null;

try {
    $pdo = db();
    $facultyOptions = individual_faculty_performance_faculty_options($pdo);

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

        $report = individual_faculty_performance_report($pdo, $selectedFacultyId, $termFilter, $termOptions);
    }
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load individual faculty performance data. ' . $exception->getMessage()
        : 'Unable to load individual faculty performance data right now. Please try again.';
}

$templateImageUrl = asset_url('assets/docs/individual-faculty-performance-template.jpg');
$bagongPilipinasLogoUrl = asset_url('assets/docs/ifpe-bagong-pilipinas.png');
$sksuSealLogoUrl = asset_url('assets/docs/ifpe-sksu-seal.png');

require __DIR__ . '/_start.php';
?>
<?php if ($databaseError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($databaseError) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4 ifpe-screen-controls">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Select Faculty and Show Performance</h5>
        <small class="text-muted">The official sheet below uses the attached PDF background as one unified report.</small>
      </div>
      <div class="card-body">
        <form method="get" action="<?= h(base_url('administrator/individual_faculty_performance.php')) ?>">
          <div class="row g-3 align-items-end">
            <div class="col-lg-5">
              <label for="faculty_id" class="form-label">Faculty</label>
              <select class="form-select" id="faculty_id" name="faculty_id" required>
                <option value="">Select faculty</option>
                <?php foreach ($facultyOptions as $facultyOption): ?>
                  <?php
                  $optionFacultyId = (int) ($facultyOption['faculty_id'] ?? 0);
                  $studentCount = (int) ($facultyOption['student_evaluation_count'] ?? 0);
                  $supervisorCount = (int) ($facultyOption['supervisor_evaluation_count'] ?? 0);
                  ?>
                  <option value="<?= h((string) $optionFacultyId) ?>" <?= $optionFacultyId === $selectedFacultyId ? 'selected' : '' ?>>
                    <?= h((string) ($facultyOption['faculty_name'] ?? '')) ?>
                    (<?= h(format_number($studentCount)) ?> student, <?= h(format_number($supervisorCount)) ?> supervisor)
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
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                  <i class="bx bx-show me-1"></i>
                  Show Performance
                </button>
                <?php if ($report !== null): ?>
                  <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bx bx-printer"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($selectedFacultyId > 0 && $report === null && $databaseError === null): ?>
  <div class="alert alert-warning ifpe-screen-controls" role="alert">
    The selected faculty member could not be found in the active faculty master list or has no submitted evaluation yet.
  </div>
<?php endif; ?>

<?php if ($report !== null): ?>
  <?php
  $student = $report['student'];
  $supervisor = $report['supervisor'];
  $studentCategories = array_values($student['categories']);
  $supervisorCategories = array_values($supervisor['categories']);
  $currentPercentage = $report['current_percentage'];
  $categoryClassMap = [
      'commitment' => 'ifpe-cell-commitment',
      'knowledge_of_subject_matter' => 'ifpe-cell-knowledge',
      'teaching_for_independent_learning' => 'ifpe-cell-teaching',
      'management_of_learning' => 'ifpe-cell-management',
      'management_of_teaching_and_learning' => 'ifpe-cell-management',
      'content_knowledge_pedagogy_and_technology' => 'ifpe-cell-knowledge',
      'commitment_and_transparency' => 'ifpe-cell-commitment',
  ];
  ?>

  <?php if (!$report['is_complete']): ?>
    <div class="alert alert-warning ifpe-screen-controls" role="alert">
      This report is partial. The official total is most useful after both submitted student ratings and submitted
      supervisor ratings are available.
    </div>
  <?php endif; ?>

  <div class="card mb-4 ifpe-screen-controls">
    <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1"><?= h((string) $report['faculty']['faculty_name']) ?></h5>
        <p class="mb-0 text-muted"><?= h((string) $report['term_scope']['note']) ?></p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a
          href="<?= h(base_url('administrator/consolidated_faculty_performance.php' . ($selectedTermKey !== '' ? '?term_key=' . rawurlencode($selectedTermKey) : ''))) ?>"
          class="btn btn-outline-primary"
        >
          <i class="bx bx-table me-1"></i>
          Consolidated Report
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="bx bx-printer me-1"></i>
          Print Report
        </button>
      </div>
    </div>
  </div>

  <div class="ifpe-print-shell">
    <section class="ifpe-paper" style="--ifpe-template-image: url('<?= h($templateImageUrl) ?>');">
      <div class="ifpe-letterhead">
        <img src="<?= h($bagongPilipinasLogoUrl) ?>" alt="Bagong Pilipinas" class="ifpe-letterhead-bagong" />
        <img src="<?= h($sksuSealLogoUrl) ?>" alt="Sultan Kudarat State University Seal" class="ifpe-letterhead-seal" />
        <div class="ifpe-letterhead-copy">
          <div>Republic of the Philippines</div>
          <strong>SULTAN KUDARAT STATE UNIVERSITY</strong>
          <em>EJC Montilla, City of Tacurong, 9800</em>
          <em>Province of Sultan Kudarat</em>
        </div>
        <div class="ifpe-letterhead-code">
          <span>SKSU-INS-EFP-05</span>
          <span>Revision: 0</span>
          <span>Effective Date: July 07, 2025</span>
        </div>
      </div>
      <div class="ifpe-paper-content">
        <h1 class="ifpe-title">INDIVIDUAL FACULTY PERFORMANCE EVALUATION</h1>
        <p class="ifpe-term-line">
          <?= h((string) $report['term_scope']['semester_label']) ?>, A.Y <?= h((string) $report['term_scope']['academic_year_label']) ?>
        </p>

        <div class="ifpe-faculty-line">
          <span>Name of Faculty:</span>
          <strong><?= h((string) $report['faculty']['faculty_name']) ?></strong>
        </div>

        <table class="ifpe-official-table ifpe-performance-table">
          <colgroup>
            <col style="width: 19.91%;" />
            <col style="width: 20.37%;" />
            <col style="width: 18.06%;" />
            <col style="width: 20.83%;" />
            <col style="width: 20.83%;" />
          </colgroup>
          <tbody>
            <tr>
              <th colspan="4" class="ifpe-main-header">FACULTY PERFORMANCE RATING</th>
              <th rowspan="3" class="ifpe-total-cell">
                <span>Total (100%)</span>
                <strong><?= h(individual_faculty_performance_format_percentage($currentPercentage)) ?></strong>
              </th>
            </tr>
            <tr>
              <th colspan="2" class="ifpe-main-header">Students Rating (60%)</th>
              <th colspan="2" class="ifpe-main-header">Supervisors Rating (40%)</th>
            </tr>
            <tr>
              <td>
                <span>Mean</span>
                <strong><?= h(individual_faculty_performance_format_mean($student['overall_mean'])) ?></strong>
              </td>
              <td>
                <span>Percentage</span>
                <strong><?= h(individual_faculty_performance_format_percentage($student['weighted_percentage'])) ?></strong>
              </td>
              <td>
                <span>Mean</span>
                <strong><?= h(individual_faculty_performance_format_mean($supervisor['overall_mean'])) ?></strong>
              </td>
              <td>
                <span>Percentage</span>
                <strong><?= h(individual_faculty_performance_format_percentage($supervisor['weighted_percentage'])) ?></strong>
              </td>
            </tr>
          </tbody>
        </table>

        <table class="ifpe-official-table ifpe-rating-table">
          <colgroup>
            <?php $studentColumnWidth = 100 / max(1, count($studentCategories) + 1); ?>
            <?php foreach ($studentCategories as $category): ?>
              <col style="width: <?= h(number_format($studentColumnWidth, 2)) ?>%;" />
            <?php endforeach; ?>
            <col style="width: <?= h(number_format($studentColumnWidth, 2)) ?>%;" />
          </colgroup>
          <tbody>
            <tr>
              <th colspan="<?= h((string) (count($studentCategories) + 1)) ?>" class="ifpe-section-title">STUDENT RATING</th>
            </tr>
            <tr>
              <?php foreach ($studentCategories as $category): ?>
                <?php $categoryClass = $categoryClassMap[(string) ($category['key'] ?? '')] ?? ''; ?>
                <th class="<?= h($categoryClass) ?>">
                  <?= h((string) $category['title']) ?>
                  <?php if (($category['weight'] ?? null) !== null): ?>
                    (<?= h(format_number($category['weight'])) ?>%)
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
              <th class="ifpe-cell-overall">Overall Mean</th>
            </tr>
            <tr>
              <?php foreach ($studentCategories as $category): ?>
                <?php $categoryClass = $categoryClassMap[(string) ($category['key'] ?? '')] ?? ''; ?>
                <td class="<?= h($categoryClass) ?>">
                  <span>Mean</span>
                  <strong><?= h(individual_faculty_performance_format_mean($category['mean'])) ?></strong>
                </td>
              <?php endforeach; ?>
              <td class="ifpe-cell-overall">
                <span>Mean</span>
                <strong><?= h(individual_faculty_performance_format_mean($student['overall_mean'])) ?></strong>
              </td>
            </tr>
          </tbody>
        </table>

        <table class="ifpe-official-table ifpe-rating-table">
          <colgroup>
            <?php $supervisorColumnWidth = 100 / max(1, count($supervisorCategories) + 1); ?>
            <?php foreach ($supervisorCategories as $category): ?>
              <col style="width: <?= h(number_format($supervisorColumnWidth, 2)) ?>%;" />
            <?php endforeach; ?>
            <col style="width: <?= h(number_format($supervisorColumnWidth, 2)) ?>%;" />
          </colgroup>
          <tbody>
            <tr>
              <th colspan="<?= h((string) (count($supervisorCategories) + 1)) ?>" class="ifpe-section-title">SUPERVISOR RATING</th>
            </tr>
            <tr>
              <?php foreach ($supervisorCategories as $category): ?>
                <?php $categoryClass = $categoryClassMap[(string) ($category['key'] ?? '')] ?? ''; ?>
                <th class="<?= h($categoryClass) ?>">
                  <?= h((string) $category['title']) ?>
                  <?php if (($category['weight'] ?? null) !== null): ?>
                    (<?= h(format_number($category['weight'])) ?>%)
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
              <th class="ifpe-cell-overall">Overall Mean</th>
            </tr>
            <tr>
              <?php foreach ($supervisorCategories as $category): ?>
                <?php $categoryClass = $categoryClassMap[(string) ($category['key'] ?? '')] ?? ''; ?>
                <td class="<?= h($categoryClass) ?>">
                  <span>Mean</span>
                  <strong><?= h(individual_faculty_performance_format_mean($category['mean'])) ?></strong>
                </td>
              <?php endforeach; ?>
              <td class="ifpe-cell-overall">
                <span>Mean</span>
                <strong><?= h(individual_faculty_performance_format_mean($supervisor['overall_mean'])) ?></strong>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="ifpe-comments-block">
          <h2>COMMENT/S (<em>Un edited</em>):</h2>
          <div class="ifpe-comments-box">
            <ol class="ifpe-comments-list">
            <?php if ($report['comments'] === []): ?>
              <li class="ifpe-empty-comment">&nbsp;</li>
            <?php endif; ?>
            <?php foreach ($report['comments'] as $comment): ?>
              <li>
                <?php if ((string) ($comment['source'] ?? '') !== 'Student'): ?>
                  <strong class="ifpe-comment-source"><?= h((string) ($comment['source'] ?? 'Supervisor')) ?>:</strong>
                <?php endif; ?>
                <?= h((string) $comment['text']) ?>
              </li>
            <?php endforeach; ?>
            </ol>
          </div>
        </div>

        <div class="ifpe-signature-grid">
          <div class="ifpe-signature-block ifpe-signature-block-evaluated">
            <p>Evaluated by:</p>
            <strong class="ifpe-signature-name">
              <?= h(trim((string) ($report['evaluated_by_name'] ?? '')) !== '' ? (string) $report['evaluated_by_name'] : ' ') ?>
            </strong>
            <div class="ifpe-signature-line"></div>
            <span class="ifpe-signature-role">Chairperson, Faculty Performance Evaluation</span>
          </div>
          <div class="ifpe-signature-block">
            <p>Recommending Approval:</p>
            <strong class="ifpe-signature-name">ELBREN O. ANTONIO, DIT</strong>
            <div class="ifpe-signature-line"></div>
            <span class="ifpe-signature-role">College Dean</span>
          </div>
          <div class="ifpe-signature-block">
            <p>Approved:</p>
            <strong class="ifpe-signature-name">ENG. ROMMEL M. LAGUMEN</strong>
            <div class="ifpe-signature-line"></div>
            <span class="ifpe-signature-role">Campus Director</span>
          </div>
        </div>
      </div>
    </section>
  </div>
  <script>
  (() => {
    const paper = document.querySelector('.ifpe-paper');
    const content = paper ? paper.querySelector('.ifpe-paper-content') : null;
    const sourceLetterhead = paper ? paper.querySelector('.ifpe-letterhead') : null;
    const pageCandidateSelector = [
      '.ifpe-title',
      '.ifpe-term-line',
      '.ifpe-faculty-line',
      '.ifpe-official-table',
      '.ifpe-comments-block h2',
      '.ifpe-comments-list > li',
      '.ifpe-signature-block'
    ].join(', ');

    if (!paper || !content || !sourceLetterhead) {
      return;
    }

    let syncFrame = 0;

    const measureA4PageHeight = () => {
      const marker = document.createElement('div');
      marker.style.cssText = 'position:absolute;visibility:hidden;pointer-events:none;height:297mm;width:1mm;top:0;left:0;';
      document.body.appendChild(marker);
      const height = marker.getBoundingClientRect().height;
      marker.remove();

      return height || 1122;
    };

    const removeRepeatedLetterheads = () => {
      paper.querySelectorAll('.ifpe-letterhead-repeat').forEach((letterhead) => {
        letterhead.remove();
      });
    };

    const removePageSpacers = () => {
      content.querySelectorAll('.ifpe-page-spacer').forEach((spacer) => {
        spacer.remove();
      });
    };

    const relativeTop = (element) => element.getBoundingClientRect().top - paper.getBoundingClientRect().top;

    const insertPageSpacers = (pageHeight, paddingTop, paddingBottom) => {
      const safeContentHeight = Math.max(1, pageHeight - paddingTop - paddingBottom);
      const maxPasses = 40;
      const tolerance = 1.5;

      for (let pass = 0; pass < maxPasses; pass += 1) {
        let inserted = false;
        const candidates = Array.from(content.querySelectorAll(pageCandidateSelector));

        for (const candidate of candidates) {
          if (candidate.classList.contains('ifpe-page-spacer')) {
            continue;
          }

          const candidateHeight = candidate.getBoundingClientRect().height;
          if (candidateHeight <= 0 || candidateHeight >= safeContentHeight - tolerance) {
            continue;
          }

          const top = relativeTop(candidate);
          const bottom = top + candidateHeight;
          const pageIndex = Math.max(0, Math.floor(top / pageHeight));
          const pageSafeTop = (pageIndex * pageHeight) + paddingTop;
          const pageSafeBottom = ((pageIndex + 1) * pageHeight) - paddingBottom;
          let spacerHeight = 0;

          if (pageIndex > 0 && top < pageSafeTop - tolerance) {
            spacerHeight = pageSafeTop - top;
          } else if (top >= pageSafeTop - tolerance && bottom > pageSafeBottom + tolerance) {
            spacerHeight = (((pageIndex + 1) * pageHeight) + paddingTop) - top;
          }

          if (spacerHeight <= tolerance || !candidate.parentNode) {
            continue;
          }

          const spacer = document.createElement('div');
          spacer.className = 'ifpe-page-spacer';
          spacer.setAttribute('aria-hidden', 'true');
          spacer.style.height = `${spacerHeight}px`;
          candidate.parentNode.insertBefore(spacer, candidate);
          inserted = true;
          break;
        }

        if (!inserted) {
          break;
        }
      }
    };

    const applyPageLayout = () => {
      syncFrame = 0;
      removeRepeatedLetterheads();
      removePageSpacers();
      paper.style.setProperty('--ifpe-print-height', '297mm');

      const pageHeight = measureA4PageHeight();
      const contentStyle = window.getComputedStyle(content);
      const paddingTop = parseFloat(contentStyle.paddingTop) || 0;
      const paddingBottom = parseFloat(contentStyle.paddingBottom) || 0;
      const pageTolerance = 12;

      insertPageSpacers(pageHeight, paddingTop, paddingBottom);

      const contentHeight = Math.max(
        content.scrollHeight,
        content.getBoundingClientRect().height
      );
      const pageCount = Math.max(1, Math.ceil((contentHeight + pageTolerance) / pageHeight));

      paper.style.setProperty('--ifpe-print-height', `${pageCount * 297}mm`);

      for (let pageIndex = 1; pageIndex < pageCount; pageIndex += 1) {
        const letterhead = sourceLetterhead.cloneNode(true);
        letterhead.classList.add('ifpe-letterhead-repeat');
        letterhead.setAttribute('aria-hidden', 'true');
        letterhead.style.top = `calc(${pageIndex} * 297mm + var(--ifpe-letterhead-top, 9mm))`;
        paper.appendChild(letterhead);
      }
    };

    const syncPageLayout = (immediate = false) => {
      if (syncFrame !== 0) {
        window.cancelAnimationFrame(syncFrame);
        syncFrame = 0;
      }

      if (immediate) {
        applyPageLayout();
        return;
      }

      syncFrame = window.requestAnimationFrame(applyPageLayout);
    };

    window.addEventListener('load', () => syncPageLayout());
    window.addEventListener('resize', () => syncPageLayout());
    window.addEventListener('beforeprint', () => syncPageLayout(true));
    window.addEventListener('afterprint', () => syncPageLayout());
    syncPageLayout();
  })();
  </script>
<?php else: ?>
  <div class="card ifpe-screen-controls">
    <div class="card-body text-center py-5">
      <span class="avatar-initial rounded-circle bg-label-primary mb-3">
        <i class="bx bx-file"></i>
      </span>
      <h5 class="mb-2">Select a faculty member to generate the report.</h5>
      <p class="mb-0 text-muted">
        The official sheet will appear here with the PDF background and computed performance values.
      </p>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_end.php'; ?>
