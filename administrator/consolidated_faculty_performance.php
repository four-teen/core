<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Consolidated Faculty Performance';
$pageDescription = 'Official consolidated faculty performance report for the administrator module.';
$activeAdminPage = 'consolidated_faculty_performance';

$requestedTermKey = trim((string) ($_GET['term_key'] ?? ''));
$requestedCampus = trim((string) ($_GET['campus'] ?? 'ISULAN'));
if ($requestedCampus === '') {
    $requestedCampus = 'ISULAN';
}
$selectedTermKey = '';
$termOptions = [];
$report = null;
$databaseError = null;

try {
    $pdo = db();
    $termOptions = consolidated_faculty_performance_term_options($pdo);
    $selectedTermKey = $requestedTermKey;
    $defaultTermKey = '';
    $defaultTermCount = -1;

    foreach ($termOptions as $option) {
        if ($defaultTermKey === '' || (int) ($option['evaluation_count'] ?? 0) > $defaultTermCount) {
            $defaultTermKey = (string) ($option['term_key'] ?? '');
            $defaultTermCount = (int) ($option['evaluation_count'] ?? 0);
        }
    }

    if ($selectedTermKey === '' && $termOptions !== []) {
        $selectedTermKey = $defaultTermKey;
    }

    $termFilter = null;
    if ($selectedTermKey !== '') {
        $termFilter = individual_faculty_performance_parse_term_key($selectedTermKey);

        if ($termFilter === null) {
            $selectedTermKey = $defaultTermKey;
            $termFilter = $selectedTermKey !== ''
                ? individual_faculty_performance_parse_term_key($selectedTermKey)
                : null;
        }
    }

    $report = consolidated_faculty_performance_report($pdo, $termFilter, $termOptions);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load consolidated faculty performance data. ' . $exception->getMessage()
        : 'Unable to load consolidated faculty performance data right now. Please try again.';
}

$bagongPilipinasLogoUrl = asset_url('assets/docs/consolidated-faculty-performance-bagong-pilipinas.png');
$sksuSealLogoUrl = asset_url('assets/docs/consolidated-faculty-performance-sksu-seal.png');
$cfpeTemplateImageUrl = asset_url('assets/docs/consolidated-faculty-performance-background.jpg');
$campusDisplay = consolidated_faculty_performance_display_campus($requestedCampus);
$unclassifiedFaculty = $report['unclassified'] ?? [];
$includedCount = (int) ($report['included_count'] ?? 0);
$evaluatedCount = (int) ($report['evaluated_count'] ?? 0);
$excludedCount = count($unclassifiedFaculty);

require __DIR__ . '/_start.php';
?>
<?php if ($databaseError !== null): ?>
  <div class="alert alert-danger" role="alert"><?= h($databaseError) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4 cfpe-screen-controls">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Show Consolidated Faculty Performance</h5>
        <small class="text-muted">Groups faculty by classification and uses the same rating computations from the individual faculty report.</small>
      </div>
      <div class="card-body">
        <form method="get" action="<?= h(base_url('administrator/consolidated_faculty_performance.php')) ?>">
          <div class="row g-3 align-items-end">
            <div class="col-lg-4">
              <label for="term_key" class="form-label">Semester / A.Y.</label>
              <select class="form-select" id="term_key" name="term_key" <?= $termOptions === [] ? 'disabled' : '' ?>>
                <?php if ($termOptions === []): ?>
                  <option value="">No submitted terms yet</option>
                <?php else: ?>
                  <?php foreach ($termOptions as $termOption): ?>
                    <?php $termKey = (string) ($termOption['term_key'] ?? ''); ?>
                    <option value="<?= h($termKey) ?>" <?= $termKey === $selectedTermKey ? 'selected' : '' ?>>
                      <?= h((string) ($termOption['term_label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-lg-4">
              <label for="campus" class="form-label">Campus Label</label>
              <input
                type="text"
                class="form-control"
                id="campus"
                name="campus"
                value="<?= h($requestedCampus) ?>"
                placeholder="Example: Tacurong"
              />
            </div>

            <div class="col-lg-4">
              <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary flex-fill" <?= $termOptions === [] ? 'disabled' : '' ?>>
                  <i class="bx bx-show me-1"></i>
                  Show Consolidated Report
                </button>
                <?php if ($report !== null && $includedCount > 0): ?>
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

<?php if ($report !== null && $includedCount > 0): ?>
  <div class="card mb-4 cfpe-screen-controls">
    <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1"><?= h((string) ($report['term_scope']['term_label'] ?? '')) ?></h5>
        <p class="mb-0 text-muted">
          <?= h((string) ($report['term_scope']['note'] ?? '')) ?>
          <?php if ($requestedCampus !== ''): ?>
            | <?= h($campusDisplay) ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a
          href="<?= h(base_url('administrator/individual_faculty_performance.php' . ($selectedTermKey !== '' ? '?term_key=' . rawurlencode($selectedTermKey) : ''))) ?>"
          class="btn btn-outline-primary"
        >
          <i class="bx bx-file me-1"></i>
          Individual Report Page
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="bx bx-printer me-1"></i>
          Print Report
        </button>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4 cfpe-screen-controls">
    <div class="col-sm-6 col-xl-4">
      <div class="card metric-card">
        <div class="card-body">
          <span class="metric-icon bg-label-primary"><i class="bx bx-file"></i></span>
          <div class="metric-value"><?= h(format_number($includedCount)) ?></div>
          <div class="metric-label">Faculty included in the consolidated print report</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card metric-card">
        <div class="card-body">
          <span class="metric-icon bg-label-warning"><i class="bx bx-error-circle"></i></span>
          <div class="metric-value"><?= h(format_number($excludedCount)) ?></div>
          <div class="metric-label">Evaluated faculty excluded because classification is not set</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card metric-card">
        <div class="card-body">
          <span class="metric-icon bg-label-success"><i class="bx bx-user-check"></i></span>
          <div class="metric-value"><?= h(format_number($evaluatedCount)) ?></div>
          <div class="metric-label">Evaluated faculty detected in the selected term scope</div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($report !== null && $excludedCount > 0): ?>
  <div class="alert alert-warning cfpe-screen-controls" role="alert">
    <?= h(format_number($excludedCount)) ?> evaluated faculty member<?= $excludedCount === 1 ? '' : 's' ?> are not printed yet because their
    classification is still blank in the Program Chair page.
  </div>
<?php endif; ?>

<?php if ($report !== null && $includedCount > 0): ?>
  <div class="cfpe-print-shell">
    <section class="cfpe-paper" style="--cfpe-template-image: url('<?= h($cfpeTemplateImageUrl) ?>')">
      <div class="cfpe-template-band" aria-hidden="true"></div>
      <div class="cfpe-page-header">
        <div class="cfpe-letterhead">
          <img src="<?= h($bagongPilipinasLogoUrl) ?>" alt="Bagong Pilipinas" class="cfpe-letterhead-bagong" />
          <img src="<?= h($sksuSealLogoUrl) ?>" alt="Sultan Kudarat State University Seal" class="cfpe-letterhead-seal" />
          <div class="cfpe-letterhead-copy">
            <div>Republic of the Philippines</div>
            <strong>SULTAN KUDARAT STATE UNIVERSITY</strong>
            <em>EJC Montilla, City of Tacurong, 9800</em>
            <em>Province of Sultan Kudarat</em>
          </div>
          <div class="cfpe-letterhead-code">
            <span>SKSU-INS-EFP-04</span>
            <span>Revision: 00</span>
            <span>Effective Date: July 07, 2025</span>
          </div>
        </div>

        <h1 class="cfpe-title">CONSOLIDATED FACULTY PERFORMANCE EVALUATION</h1>
        <p class="cfpe-term-line"><?= h((string) ($report['term_scope']['term_label'] ?? '')) ?></p>
        <p class="cfpe-campus-line"><?= h($campusDisplay) ?></p>
      </div>

      <div class="cfpe-paper-content">
        <table class="cfpe-report-table">
          <colgroup>
            <col class="cfpe-table-no" />
            <col class="cfpe-table-name" />
            <col class="cfpe-table-rating" />
            <col class="cfpe-table-rating" />
            <col class="cfpe-table-total" />
          </colgroup>
          <thead>
            <tr>
              <th rowspan="2">No</th>
              <th rowspan="2">NAME OF FACULTY</th>
              <th colspan="3">FACULTY PERFORMANCE RATING</th>
            </tr>
            <tr>
              <th>Students<br />Rating (60%)</th>
              <th>Supervisors Rating<br />(40%)</th>
              <th>Total<br />(100%)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($report['sections'] ?? []) as $section): ?>
              <tr class="cfpe-section-row">
                <td colspan="5"><?= h((string) ($section['title'] ?? '')) ?></td>
              </tr>

              <?php if (($section['rows'] ?? []) === []): ?>
                <?php for ($emptyIndex = 1; $emptyIndex <= 5; $emptyIndex++): ?>
                  <tr>
                    <td><?= h((string) $emptyIndex) ?>.</td>
                    <td>&nbsp;</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>
                <?php endfor; ?>
              <?php endif; ?>

              <?php foreach (($section['rows'] ?? []) as $row): ?>
                <tr>
                  <td><?= h((string) (($row['row_number'] ?? 0))) ?>.</td>
                  <td><?= h((string) ($row['faculty_name'] ?? '')) ?></td>
                  <td><?= h(individual_faculty_performance_format_percentage($row['student_weighted_percentage'] ?? null)) ?></td>
                  <td><?= h(individual_faculty_performance_format_percentage($row['supervisor_weighted_percentage'] ?? null)) ?></td>
                  <td><?= h(individual_faculty_performance_format_percentage($row['total_percentage'] ?? null)) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="cfpe-signature-stack">
          <div class="cfpe-signature-block">
            <p>Prepared by:</p>
            <div class="cfpe-signature-line"></div>
            <span>Chairperson, Committee on Faculty evaluation</span>
          </div>

          <div class="cfpe-signature-block">
            <p>Checked:</p>
            <div class="cfpe-signature-line cfpe-signature-line-short"></div>
            <span>College Dean</span>
          </div>

          <div class="cfpe-signature-block">
            <p>Recommending Approval:</p>
            <div class="cfpe-signature-line cfpe-signature-line-medium"></div>
            <span>Campus Director</span>
          </div>

          <div class="cfpe-signature-block">
            <p>Approved:</p>
            <div class="cfpe-signature-line cfpe-signature-line-long"></div>
            <span>Vice President for Academic Affairs</span>
          </div>
        </div>
      </div>
    </section>
  </div>

  <script>
  (() => {
    const paper = document.querySelector('.cfpe-paper');
    const content = paper ? paper.querySelector('.cfpe-paper-content') : null;
    const sourceHeader = paper ? paper.querySelector('.cfpe-page-header') : null;
    const pageCandidateSelector = [
      '.cfpe-report-table tbody tr',
      '.cfpe-signature-block'
    ].join(', ');

    if (!paper || !content || !sourceHeader) {
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

    const removePageSpacers = () => {
      content.querySelectorAll('.cfpe-page-spacer').forEach((spacer) => {
        spacer.remove();
      });
    };

    const removeRepeatedHeaders = () => {
      paper.querySelectorAll('.cfpe-page-header-repeat').forEach((header) => {
        header.remove();
      });
    };

    const relativeTop = (element) => element.getBoundingClientRect().top - paper.getBoundingClientRect().top;

    const insertPageSpacers = (pageHeight, paddingTop, paddingBottom) => {
      const safeContentHeight = Math.max(1, pageHeight - paddingTop - paddingBottom);
      const maxPasses = 80;
      const tolerance = 1.5;

      for (let pass = 0; pass < maxPasses; pass += 1) {
        let inserted = false;
        const candidates = Array.from(content.querySelectorAll(pageCandidateSelector));

        for (const candidate of candidates) {
          if (candidate.classList.contains('cfpe-page-spacer')) {
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
          spacer.className = 'cfpe-page-spacer';
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
      removeRepeatedHeaders();
      removePageSpacers();
      paper.style.setProperty('--cfpe-print-height', '297mm');

      const pageHeight = measureA4PageHeight();
      const contentStyle = window.getComputedStyle(content);
      const paddingTop = (sourceHeader.getBoundingClientRect().height || 0) + (parseFloat(contentStyle.paddingTop) || 0);
      const paddingBottom = parseFloat(contentStyle.paddingBottom) || 0;
      const pageTolerance = 12;

      insertPageSpacers(pageHeight, paddingTop, paddingBottom);

      const contentHeight = Math.max(
        content.scrollHeight,
        content.getBoundingClientRect().height
      );
      const pageCount = Math.max(1, Math.ceil((contentHeight + pageTolerance) / pageHeight));

      paper.style.setProperty('--cfpe-print-height', `${pageCount * 297}mm`);

      for (let pageIndex = 1; pageIndex < pageCount; pageIndex += 1) {
        const header = sourceHeader.cloneNode(true);
        header.classList.add('cfpe-page-header-repeat');
        header.setAttribute('aria-hidden', 'true');
        header.style.top = `calc(${pageIndex} * 297mm + var(--cfpe-header-top, 0mm))`;
        paper.appendChild(header);
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
<?php elseif ($databaseError === null): ?>
  <div class="card cfpe-screen-controls">
    <div class="card-body text-center py-5">
      <span class="avatar-initial rounded-circle bg-label-primary mb-3">
        <i class="bx bx-table"></i>
      </span>
      <h5 class="mb-2">No consolidated report is ready yet.</h5>
      <p class="mb-0 text-muted">
        Set faculty classifications in the Program Chair page and make sure there are submitted evaluation records in the selected term.
      </p>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_end.php'; ?>
