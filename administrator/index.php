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
$evaluationCategoryAverages = [
    'labels' => [],
    'series' => [],
    'colors' => [],
    'details' => [],
    'highest' => null,
    'hasData' => false,
];
$evaluationProgramCompletion = [
    'labels' => [],
    'series' => [],
    'colors' => [],
    'details' => [],
    'totalEvaluated' => 0,
    'totalCompleted' => 0,
    'totalEligible' => 0,
    'hasData' => false,
];
$databaseError = null;
$noticeMessage = flash('notice');
$errorMessage = flash('error');

try {
    $pdo = db();
    $overview = dashboard_overview($pdo);
    $currentTerm = dashboard_current_term($pdo);
    $evaluationCategoryAverages = dashboard_evaluation_category_averages($pdo);
    $evaluationProgramCompletion = dashboard_evaluation_program_completion($pdo);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load dashboard data. ' . $exception->getMessage()
        : 'Unable to load dashboard data. Please confirm the database connection settings in .env.';
}

$evaluationCategoryAveragesJson = json_encode(
    $evaluationCategoryAverages,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
if ($evaluationCategoryAveragesJson === false) {
    $evaluationCategoryAveragesJson = '{"labels":[],"series":[],"colors":[],"details":[],"highest":null,"hasData":false}';
}

$evaluationProgramCompletionJson = json_encode(
    $evaluationProgramCompletion,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
if ($evaluationProgramCompletionJson === false) {
    $evaluationProgramCompletionJson = '{"labels":[],"series":[],"colors":[],"details":[],"totalEvaluated":0,"totalCompleted":0,"totalEligible":0,"hasData":false}';
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
            <span class="badge bg-label-primary mb-2">Evaluation Category Scores</span>
            <h5 class="mb-1">Ranked average rating by category</h5>
            <p class="text-muted mb-0">Submitted evaluation ratings sorted from highest to lowest.</p>
          </div>
          <div class="dashboard-chart-actions">
            <?php if (($evaluationCategoryAverages['highest'] ?? null) !== null): ?>
              <div class="dashboard-highest-pill">
                <span>Highest</span>
                <strong>
                  <?= h((string) $evaluationCategoryAverages['highest']['name']) ?>
                  <?= h(format_average($evaluationCategoryAverages['highest']['average'])) ?>
                </strong>
              </div>
            <?php endif; ?>
            <a href="<?= h(base_url('administrator/evaluations.php')) ?>" class="btn btn-outline-primary btn-sm">
              <i class="bx bx-time-five me-1"></i>
              View Activity
            </a>
          </div>
        </div>
        <div id="evaluationCategoryAverageChart" class="evaluation-trend-chart"></div>
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
            <span class="badge bg-label-info mb-2">Program Evaluation Completion</span>
            <h5 class="mb-1">Distinct students who evaluated by program</h5>
            <p class="text-muted mb-0">Completed-all-subjects versus partial evaluation progress.</p>
          </div>
          <?php if (($evaluationProgramCompletion['totalEligible'] ?? 0) > 0): ?>
            <div class="dashboard-highest-pill">
              <span>Evaluated</span>
              <strong>
                <?= h(format_number($evaluationProgramCompletion['totalEvaluated'] ?? 0)) ?>
                /
                <?= h(format_number($evaluationProgramCompletion['totalEligible'] ?? 0)) ?>
                students
              </strong>
            </div>
          <?php endif; ?>
        </div>
        <div id="evaluationProgramCompletionChart" class="evaluation-trend-chart evaluation-program-chart"></div>
      </div>
    </div>
  </div>
</div>
<script src="<?= h(asset_url('assets/vendor/libs/apex-charts/apexcharts.js')) ?>"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
      return;
    }

    var categoryData = <?= $evaluationCategoryAveragesJson ?>;
    var programData = <?= $evaluationProgramCompletionJson ?>;

    function normalizeLabels(labels) {
      return (Array.isArray(labels) ? labels : []).map(function (label) {
        return Array.isArray(label) ? label.join(' ') : label;
      });
    }

    function renderRatingBarChart(selector, chartData, emptyText) {
      var chartElement = document.querySelector(selector);

      if (!chartElement) {
        return;
      }

      var hasData = Boolean(chartData.hasData);
      var xMin = Number(chartData.xMin);
      var xMax = Number(chartData.xMax);
      var tickAmount = Number(chartData.tickAmount);
      var axisLabels = normalizeLabels(chartData.labels);
      var colors = Array.isArray(chartData.colors) && chartData.colors.length
        ? chartData.colors
        : ['#696cff', '#03c3ec', '#f29900', '#34a853'];

      var chart = new ApexCharts(chartElement, {
      chart: {
        type: 'bar',
        height: Math.max(260, axisLabels.length * 60 + 120),
        fontFamily: 'Public Sans, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: {
          enabled: true,
          easing: 'easeinout',
          speed: 700
        }
      },
      series: hasData ? chartData.series : [],
      colors: colors,
      plotOptions: {
        bar: {
          horizontal: true,
          distributed: true,
          borderRadius: 8,
          barHeight: '54%',
          dataLabels: {
            position: 'right'
          }
        }
      },
      stroke: {
        width: 0
      },
      dataLabels: {
        enabled: true,
        offsetX: 12,
        formatter: function (value) {
          if (value === null || typeof value === 'undefined') {
            return '';
          }

          return Number(value).toFixed(2);
        },
        style: {
          colors: ['#566a7f'],
          fontSize: '12px',
          fontWeight: 700
        }
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
        show: false
      },
      xaxis: {
        categories: axisLabels,
        min: Number.isFinite(xMin) ? xMin : 0,
        max: Number.isFinite(xMax) ? xMax : 5,
        tickAmount: Number.isFinite(tickAmount) ? tickAmount : 5,
        labels: {
          formatter: function (value) {
            return Number(value).toFixed(1);
          },
          style: { colors: '#8592a3', fontSize: '12px' }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          maxWidth: 280,
          style: {
            colors: '#566a7f',
            fontSize: '12px',
            fontWeight: 600
          }
        }
      },
      tooltip: {
        y: {
          formatter: function (value, options) {
            if (value === null || typeof value === 'undefined') {
              return 'No rating';
            }

            var details = Array.isArray(chartData.details) ? chartData.details : [];
            var detail = details[options.dataPointIndex] || {};
            var meta = [];

            if (detail.evaluations) {
              meta.push(detail.evaluations + ' evaluations');
            }

            if (detail.students) {
              meta.push(detail.students + ' students');
            }

            return Number(value).toFixed(2) + (meta.length ? ' | ' + meta.join(', ') : '');
          }
        }
      },
      noData: {
        text: emptyText,
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
    }

    function renderProgramCompletionChart(selector, chartData, emptyText) {
      var chartElement = document.querySelector(selector);

      if (!chartElement) {
        return;
      }

      var hasData = Boolean(chartData.hasData);
      var axisLabels = normalizeLabels(chartData.labels);
      var colors = Array.isArray(chartData.colors) && chartData.colors.length
        ? chartData.colors
        : ['#34a853', '#f29900'];

      var chart = new ApexCharts(chartElement, {
        chart: {
          type: 'bar',
          stacked: true,
          height: Math.max(280, axisLabels.length * 64 + 135),
          fontFamily: 'Public Sans, sans-serif',
          toolbar: { show: false },
          zoom: { enabled: false },
          animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 700
          }
        },
        series: hasData ? chartData.series : [],
        colors: colors,
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 8,
            barHeight: '56%',
            dataLabels: {
              total: {
                enabled: true,
                offsetX: 8,
                style: {
                  color: '#566a7f',
                  fontSize: '12px',
                  fontWeight: 700
                }
              }
            }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: function (value) {
            return value > 0 ? String(value) : '';
          },
          style: {
            colors: ['#ffffff'],
            fontSize: '12px',
            fontWeight: 700
          }
        },
        grid: {
          borderColor: '#eef1f6',
          strokeDashArray: 4,
          padding: {
            top: 4,
            right: 24,
            bottom: 4,
            left: 8
          }
        },
        legend: {
          show: true,
          position: 'top',
          horizontalAlign: 'left',
          fontSize: '13px',
          fontWeight: 600,
          labels: {
            colors: '#566a7f'
          },
          markers: {
            width: 10,
            height: 10,
            radius: 10
          }
        },
        xaxis: {
          categories: axisLabels,
          labels: {
            formatter: function (value) {
              return String(Math.round(Number(value)));
            },
            style: { colors: '#8592a3', fontSize: '12px' }
          },
          axisBorder: { show: false },
          axisTicks: { show: false },
          title: {
            text: 'Distinct students',
            style: {
              color: '#8592a3',
              fontSize: '12px',
              fontWeight: 600
            }
          }
        },
        yaxis: {
          labels: {
            maxWidth: 220,
            style: {
              colors: '#566a7f',
              fontSize: '12px',
              fontWeight: 700
            }
          }
        },
        tooltip: {
          y: {
            formatter: function (value, options) {
              var details = Array.isArray(chartData.details) ? chartData.details : [];
              var detail = details[options.dataPointIndex] || {};
              var suffix = '';

              if (detail.eligibleStudents) {
                suffix = ' | ' + detail.evaluatedStudents + ' evaluated, ' + detail.notStartedStudents + ' not started of ' + detail.eligibleStudents + ' eligible';
              }

              if (detail.completionRate || detail.completionRate === 0) {
                suffix += ' | ' + Number(detail.completionRate).toFixed(1) + '% complete';
              }

              return String(value) + ' students' + suffix;
            }
          }
        },
        noData: {
          text: emptyText,
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
    }

    renderRatingBarChart(
      '#evaluationCategoryAverageChart',
      categoryData,
      'No submitted category ratings yet'
    );
    renderProgramCompletionChart(
      '#evaluationProgramCompletionChart',
      programData,
      'No program evaluation progress yet'
    );
  });
</script>
<?php require __DIR__ . '/_end.php'; ?>
