<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/_faculty_list.php';

require_program_chair_authentication();

$programChair = administrator_profile();
$programChairUserId = (int) ($programChair['user_management_id'] ?? 0);
$pageTitle = 'Program Chair';
$pageDescription = 'Program chair supervisory faculty evaluation module.';
$activeProgramChairPage = 'dashboard';

$summary = [];
$facultyList = [];
$recentEvaluations = [];
$facultySearch = trim((string) ($_GET['faculty_search'] ?? ''));
$pageError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);
    $summary = program_chair_evaluation_summary($pdo, $programChairUserId);
    $facultyList = program_chair_faculty_for_evaluation($pdo, $programChairUserId, $facultySearch);
    $recentEvaluations = program_chair_recent_evaluations($pdo, $programChairUserId, 10);
} catch (Throwable $exception) {
    $pageError = is_local_env()
        ? 'Unable to load the program chair module. ' . $exception->getMessage()
        : 'Unable to load the program chair module right now. Please try again.';
}

$facultySearchEndpointJson = json_encode(
    base_url('programchair/faculty_search.php'),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
if ($facultySearchEndpointJson === false) {
    $facultySearchEndpointJson = '""';
}

$extraBodyScripts = <<<HTML
<script>
  window.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.program-chair-faculty-search');
    var input = document.querySelector('[data-program-chair-faculty-search-input]');
    var clearButton = document.querySelector('[data-program-chair-faculty-search-clear]');
    var list = document.querySelector('[data-program-chair-faculty-list]');
    var endpoint = {$facultySearchEndpointJson};
    var searchTimer = null;
    var activeController = null;

    if (!form || !input || !list || !endpoint) {
      return;
    }

    function setLoading(isLoading) {
      list.classList.toggle('is-loading', isLoading);
      form.classList.toggle('is-loading', isLoading);
    }

    function setClearVisibility() {
      if (clearButton) {
        clearButton.hidden = input.value.trim() === '';
      }
    }

    function fetchFacultyList() {
      var searchValue = input.value.trim();
      var url = endpoint + '?faculty_search=' + encodeURIComponent(searchValue);

      if (activeController) {
        activeController.abort();
      }

      activeController = new AbortController();
      setLoading(true);
      setClearVisibility();

      fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        signal: activeController.signal
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Search failed');
          }

          return response.text();
        })
        .then(function (html) {
          list.innerHTML = html;
        })
        .catch(function (error) {
          if (error.name === 'AbortError') {
            return;
          }

          list.innerHTML = '<div class="alert alert-danger mb-0" role="alert">Unable to search faculty right now. Please try again.</div>';
        })
        .finally(function () {
          setLoading(false);
          activeController = null;
        });
    }

    function queueSearch() {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(fetchFacultyList, 250);
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      window.clearTimeout(searchTimer);
      fetchFacultyList();
    });

    input.addEventListener('input', function () {
      setClearVisibility();
      queueSearch();
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        input.value = '';
        setClearVisibility();
        fetchFacultyList();
        input.focus();
      });
    }

    setClearVisibility();
  });
</script>
HTML;

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-info mb-3">Program Chair</span>
            <h3 class="mb-2">Supervisory faculty evaluation.</h3>
            <p class="mb-0">
              Evaluate faculty members authorized by the administrator using the SKSU supervisory rating form.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Your Evaluation Progress</span>
              <h4 class="mb-1"><?= h(format_number($summary['submitted_evaluations'] ?? 0)) ?> submitted</h4>
              <p class="mb-0"><?= h(format_number($summary['draft_evaluations'] ?? 0)) ?> drafts in progress</p>
            </div>
          </div>
        </div>
      </div>
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

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($summary['eligible_faculty'] ?? 0)) ?></div>
        <div class="metric-label">Faculty available</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-check-circle"></i></span>
        <div class="metric-value"><?= h(format_number($summary['submitted_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Submitted evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-warning"><i class="bx bx-edit"></i></span>
        <div class="metric-value"><?= h(format_number($summary['draft_evaluations'] ?? 0)) ?></div>
        <div class="metric-label">Draft evaluations</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-star"></i></span>
        <div class="metric-value"><?= h(format_average($summary['average_rating'] ?? 0)) ?></div>
        <div class="metric-label">Average submitted rating</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h5 class="mb-0">Faculty for Evaluation</h5>
          <small class="text-muted">This list is managed by the administrator module.</small>
        </div>
        <form method="get" action="<?= h(base_url('programchair/index.php')) ?>" class="program-chair-faculty-search">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input
              type="text"
              class="form-control"
              name="faculty_search"
              value="<?= h($facultySearch) ?>"
              placeholder="Search faculty name, ID, or subject"
              autocomplete="off"
              data-program-chair-faculty-search-input
            />
            <button type="submit" class="btn btn-primary">Search</button>
            <button type="button" class="btn btn-outline-secondary" data-program-chair-faculty-search-clear <?= $facultySearch === '' ? 'hidden' : '' ?>>Clear</button>
          </div>
        </form>
      </div>
      <div class="card-body">
        <div class="program-chair-faculty-list" data-program-chair-faculty-list>
          <?php program_chair_render_faculty_list($facultyList, $facultySearch); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Your Supervisory Evaluation Records</h5>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Subject</th>
              <th>Date/Time</th>
              <th>Average</th>
              <th>Status</th>
              <th>Updated</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentEvaluations === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No supervisory evaluations have been started yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($recentEvaluations as $evaluation): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($evaluation['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted">Faculty ID <?= h((string) ($evaluation['faculty_id'] ?? '')) ?></small>
                </td>
                <td><?= h(trim((string) ($evaluation['subject_text'] ?? '')) !== '' ? (string) $evaluation['subject_text'] : 'Not set') ?></td>
                <td>
                  <div><?= h(trim((string) ($evaluation['evaluation_date'] ?? '')) !== '' ? (string) $evaluation['evaluation_date'] : 'Not set') ?></div>
                  <small class="text-muted"><?= h(program_chair_format_evaluation_time($evaluation['evaluation_time'] ?? '')) ?></small>
                </td>
                <td><?= h(format_average($evaluation['average_rating'] ?? 0)) ?></td>
                <td>
                  <span class="badge <?= ($evaluation['submission_status'] ?? '') === 'submitted' ? 'bg-label-success' : 'bg-label-warning' ?>">
                    <?= h(ucfirst((string) ($evaluation['submission_status'] ?? 'draft'))) ?>
                  </span>
                </td>
                <td><?= h(format_datetime((string) ($evaluation['updated_at'] ?? ''))) ?></td>
                <td>
                  <a
                    href="<?= h(base_url('programchair/evaluate.php?faculty_id=' . (string) ($evaluation['faculty_id'] ?? '0'))) ?>"
                    class="btn btn-outline-primary btn-sm"
                  >
                    <i class="bx bx-edit me-1"></i>
                    Edit
                  </a>
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
