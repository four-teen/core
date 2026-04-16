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
$evaluationTrend = [
    'labels' => [],
    'series' => [],
    'hasData' => false,
];
$databaseError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $currentTerm = dashboard_current_term($pdo);
    $evaluationTrend = dashboard_evaluation_rating_trend($pdo, 12);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load dashboard data. ' . $exception->getMessage()
        : 'Unable to load dashboard data. Please confirm the database connection settings in .env.';
}

$evaluationTrendJson = json_encode(
    $evaluationTrend,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
if ($evaluationTrendJson === false) {
    $evaluationTrendJson = '{"labels":[],"series":[],"hasData":false}';
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
              Monitor current data coverage here, then use the dedicated pages for faculty, student,
              user access, and evaluation activity workflows.
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
  <div class="col-12">
    <div class="card dashboard-trend-card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
          <div>
            <span class="badge bg-label-primary mb-2">Evaluation Rating Trends</span>
            <h5 class="mb-1">Category averages over the last 12 months</h5>
            <p class="text-muted mb-0">Submitted evaluation ratings grouped by month and criteria.</p>
          </div>
          <a href="<?= h(base_url('administrator/evaluations.php')) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bx bx-time-five me-1"></i>
            View Activity
          </a>
        </div>
        <div id="evaluationRatingTrendChart" class="evaluation-trend-chart"></div>
      </div>
    </div>
  </div>
</div>
<script src="<?= h(asset_url('assets/vendor/libs/apex-charts/apexcharts.js')) ?>"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    var chartElement = document.querySelector('#evaluationRatingTrendChart');

    if (!chartElement || typeof ApexCharts === 'undefined') {
      return;
    }

    var trendData = <?= $evaluationTrendJson ?>;
    var hasTrendData = Boolean(trendData.hasData);

    var chart = new ApexCharts(chartElement, {
      chart: {
        type: 'line',
        height: 380,
        fontFamily: 'Public Sans, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: {
          enabled: true,
          easing: 'easeinout',
          speed: 700
        }
      },
      series: hasTrendData ? trendData.series : [],
      colors: ['#696cff', '#03c3ec', '#f29900', '#34a853', '#ff6b35'],
      stroke: {
        curve: 'smooth',
        width: 3,
        lineCap: 'round'
      },
      markers: {
        size: 4,
        strokeWidth: 3,
        hover: { size: 7 }
      },
      grid: {
        borderColor: '#eef1f6',
        strokeDashArray: 4,
        padding: {
          top: 4,
          right: 16,
          bottom: 4,
          left: 8
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'left',
        fontSize: '13px',
        markers: {
          width: 10,
          height: 10,
          radius: 10
        }
      },
      xaxis: {
        categories: trendData.labels || [],
        labels: {
          style: { colors: '#8592a3', fontSize: '12px' }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        min: 1,
        max: 5,
        tickAmount: 4,
        labels: {
          formatter: function (value) {
            return Number(value).toFixed(1);
          },
          style: { colors: '#8592a3', fontSize: '12px' }
        }
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (value) {
            if (value === null || typeof value === 'undefined') {
              return 'No rating';
            }

            return Number(value).toFixed(2);
          }
        }
      },
      noData: {
        text: 'No submitted evaluation ratings yet',
        align: 'center',
        verticalAlign: 'middle',
        style: {
          color: '#8592a3',
          fontSize: '14px',
          fontFamily: 'Public Sans, sans-serif'
        }
      }
    });

    chart.render();
  });
</script>
<?php require __DIR__ . '/_end.php'; ?>
