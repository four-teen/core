<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (!function_exists('administrator_program_chair_is_ajax_request')) {
    function administrator_program_chair_is_ajax_request(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}

if (!function_exists('administrator_program_chair_render_partial')) {
    function administrator_program_chair_render_partial(string $template, array $variables = []): string
    {
        extract($variables, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/' . $template;
        return (string) ob_get_clean();
    }
}

require_admin_authentication();

$administrator = administrator_profile();
$pageTitle = 'Program Chair';
$pageDescription = 'Program chair faculty eligibility management page for the administrator module.';
$activeAdminPage = 'program_chair';
$isAjaxRequest = administrator_program_chair_is_ajax_request();

$noticeMessage = flash('notice');
$errorMessage = flash('error');
$databaseError = null;
$managementError = null;
$facultyOptions = [];
$selectedFaculty = [];
$addFacultyForm = [
    'faculty_id' => '',
    'faculty_classification' => '',
];
$stats = [
    'master_faculty' => 0,
    'eligible_faculty' => 0,
];
$ajaxNoticeMessage = null;

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && (string) ($_GET['action'] ?? '') === 'faculty_evaluation_details') {
        $facultyId = (int) ($_GET['faculty_id'] ?? 0);
        $evaluationType = (string) ($_GET['evaluation_type'] ?? 'student');
        $evaluationType = $evaluationType === 'supervisory' ? 'supervisory' : 'student';
        $details = program_chair_admin_faculty_evaluation_details($pdo, $facultyId, $evaluationType);

        header('Content-Type: application/json; charset=UTF-8');
        $json = json_encode(
            [
                'ok' => true,
                'faculty_id' => $facultyId,
                'evaluation_type' => $evaluationType,
                'evaluations' => $details,
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
        echo $json !== false ? $json : '{"ok":false,"error":"Unable to encode evaluation details."}';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Unable to verify the request. Please refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add_faculty') {
            $addFacultyForm = [
                'faculty_id' => trim((string) ($_POST['faculty_id'] ?? '')),
                'faculty_classification' => trim((string) ($_POST['faculty_classification'] ?? '')),
            ];
            program_chair_faculty_add(
                $pdo,
                (int) $addFacultyForm['faculty_id'],
                $addFacultyForm['faculty_classification'],
                (int) ($administrator['user_management_id'] ?? 0)
            );

            $ajaxNoticeMessage = 'Faculty member was added to the program chair evaluation list.';
            if (!$isAjaxRequest) {
                flash('notice', $ajaxNoticeMessage);
                redirect_to('administrator/program_chair.php');
            }
        } elseif ($action === 'update_faculty_classification') {
            program_chair_faculty_update_classification(
                $pdo,
                (int) ($_POST['program_chair_faculty_id'] ?? 0),
                trim((string) ($_POST['faculty_classification'] ?? ''))
            );
            $ajaxNoticeMessage = 'Faculty classification was updated successfully.';
            if (!$isAjaxRequest) {
                flash('notice', $ajaxNoticeMessage);
                redirect_to('administrator/program_chair.php');
            }
        } elseif ($action === 'remove_faculty') {
            program_chair_faculty_remove($pdo, (int) ($_POST['program_chair_faculty_id'] ?? 0));
            $ajaxNoticeMessage = 'Faculty member was removed from the program chair evaluation list.';
            if (!$isAjaxRequest) {
                flash('notice', $ajaxNoticeMessage);
                redirect_to('administrator/program_chair.php');
            }
        } elseif ($action === 'update_evaluation') {
            $evaluationType = (string) ($_POST['evaluation_type'] ?? 'student');
            $evaluationType = in_array($evaluationType, ['student', 'supervisory', 'role'], true) ? $evaluationType : 'student';
            program_chair_admin_update_evaluation(
                $pdo,
                $evaluationType,
                (int) ($_POST['evaluation_id'] ?? 0),
                $_POST
            );
            $ajaxNoticeMessage = 'Evaluation row was updated successfully.';
            if (!$isAjaxRequest) {
                flash('notice', $ajaxNoticeMessage);
                redirect_to('administrator/program_chair.php');
            }
        } elseif ($action === 'delete_evaluation') {
            $evaluationType = (string) ($_POST['evaluation_type'] ?? 'student');
            $evaluationType = in_array($evaluationType, ['student', 'supervisory', 'role'], true) ? $evaluationType : 'student';
            program_chair_admin_delete_evaluation(
                $pdo,
                $evaluationType,
                (int) ($_POST['evaluation_id'] ?? 0)
            );
            $ajaxNoticeMessage = 'Evaluation row was deleted successfully.';
            if (!$isAjaxRequest) {
                flash('notice', $ajaxNoticeMessage);
                redirect_to('administrator/program_chair.php');
            }
        } else {
            throw new RuntimeException('The requested program chair action is not supported.');
        }
    }
} catch (Throwable $exception) {
    $managementError = is_local_env()
        ? 'Unable to update program chair faculty list. ' . $exception->getMessage()
        : $exception->getMessage();
}

try {
    $pdo = db();
    ensure_program_chair_tables($pdo);
    $stats['master_faculty'] = program_chair_master_faculty_count($pdo);
    $stats['eligible_faculty'] = program_chair_faculty_list_count($pdo);
    $facultyOptions = program_chair_faculty_options($pdo);
    $selectedFaculty = program_chair_selected_faculty_list($pdo);
} catch (Throwable $exception) {
    $databaseError = is_local_env()
        ? 'Unable to load program chair faculty data. ' . $exception->getMessage()
        : 'Unable to load program chair faculty data right now. Please try again.';
}

if ($isAjaxRequest && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $responsePayload = [
        'ok' => $databaseError === null && $managementError === null,
        'notice' => $databaseError === null && $managementError === null ? (string) ($ajaxNoticeMessage ?? '') : '',
        'error' => $databaseError ?? $managementError ?? '',
        'metrics_html' => administrator_program_chair_render_partial(
            '_program_chair_metrics.php',
            [
                'stats' => $stats,
                'facultyOptions' => $facultyOptions,
            ]
        ),
        'management_html' => administrator_program_chair_render_partial(
            '_program_chair_management_panels.php',
            [
                'facultyOptions' => $facultyOptions,
                'selectedFaculty' => $selectedFaculty,
                'addFacultyForm' => $addFacultyForm,
            ]
        ),
        'hero' => [
            'eligible' => format_number($stats['eligible_faculty']) . ' eligible faculty',
            'master' => format_number($stats['master_faculty']) . ' active faculty in master list',
        ],
    ];

    header('Content-Type: application/json; charset=UTF-8');
    $json = json_encode($responsePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    echo $json !== false ? $json : '{"ok":false,"error":"Unable to encode the program chair response."}';
    exit;
}

require __DIR__ . '/_start.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card hero-panel mb-4">
      <div class="card-body">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <span class="badge bg-label-info mb-3">Program Chair</span>
            <h3 class="mb-2">Faculty list for supervisory evaluations.</h3>
            <p class="mb-0">
              Select faculty records from <code>tbl_faculty</code>. Program chair accounts can evaluate only faculty
              members included in this list.
            </p>
          </div>
          <div class="col-lg-4">
            <div class="hero-stat-card">
              <span class="hero-stat-label">Program Chair Coverage</span>
              <h4 class="mb-1" data-program-chair-hero-eligible><?= h(format_number($stats['eligible_faculty'])) ?> eligible faculty</h4>
              <p class="mb-0" data-program-chair-hero-master><?= h(format_number($stats['master_faculty'])) ?> active faculty in master list</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="programChairFlashes">
  <?php if ($databaseError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= h($databaseError) ?></div>
  <?php endif; ?>

  <?php if ($noticeMessage !== null): ?>
    <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
  <?php endif; ?>

  <?php if ($errorMessage !== null): ?>
    <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
  <?php endif; ?>

  <?php if ($managementError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= h($managementError) ?></div>
  <?php endif; ?>
</div>

<div id="programChairMetrics" class="program-chair-admin-fragment">
  <?= administrator_program_chair_render_partial(
      '_program_chair_metrics.php',
      [
          'stats' => $stats,
          'facultyOptions' => $facultyOptions,
      ]
  ) ?>
</div>

<div id="programChairManagementPanels" class="program-chair-admin-fragment">
  <?= administrator_program_chair_render_partial(
      '_program_chair_management_panels.php',
      [
          'facultyOptions' => $facultyOptions,
          'selectedFaculty' => $selectedFaculty,
          'addFacultyForm' => $addFacultyForm,
      ]
  ) ?>
</div>
<div class="modal fade" id="programChairClassificationModal" tabindex="-1" aria-labelledby="programChairClassificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <form method="post" action="<?= h(base_url('administrator/program_chair.php')) ?>" id="programChairClassificationForm">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="programChairClassificationModalLabel">Update Faculty Classification</h5>
            <small class="text-muted d-block" id="programChairClassificationFacultyName">Faculty</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="programChairClassificationError"></div>
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="update_faculty_classification" />
          <input type="hidden" name="program_chair_faculty_id" id="programChairClassificationFacultyId" value="" />

          <div class="mb-0">
            <label for="programChairClassificationSelect" class="form-label">Faculty Classification</label>
            <select class="form-select" id="programChairClassificationSelect" name="faculty_classification" required>
              <option value="">Select classification</option>
              <?php foreach (program_chair_faculty_classification_options() as $classificationValue => $classificationLabel): ?>
                <option value="<?= h($classificationValue) ?>"><?= h($classificationLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="programChairEvaluationDetailsModal" tabindex="-1" aria-labelledby="programChairEvaluationDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="programChairEvaluationDetailsModalLabel">Evaluation Details</h5>
          <small class="text-muted" id="programChairEvaluationDetailsSubtitle"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Evaluator / Student</th>
                <th>Subject / Term</th>
                <th>Score</th>
                <th>Status</th>
                <th>Updated</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="programChairEvaluationDetailsBody">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Select an evaluation count to load details.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="programChairEvaluationEditModal" tabindex="-1" aria-labelledby="programChairEvaluationEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <form method="post" action="<?= h(base_url('administrator/program_chair.php')) ?>" id="programChairEvaluationEditForm">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="programChairEvaluationEditModalLabel">Edit Evaluation</h5>
            <small class="text-muted" id="programChairEvaluationEditSubtitle"></small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="programChairEvaluationEditError"></div>
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="update_evaluation" />
          <input type="hidden" name="evaluation_type" id="programChairEvaluationEditType" value="" />
          <input type="hidden" name="evaluation_id" id="programChairEvaluationEditId" value="" />
          <div class="row g-3">
            <div class="col-md-4">
              <label for="programChairEvaluationEditStatus" class="form-label">Status</label>
              <select class="form-select" id="programChairEvaluationEditStatus" name="submission_status" required>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
              </select>
            </div>
            <div class="col-md-4" data-supervisory-edit-field>
              <label for="programChairEvaluationEditDate" class="form-label">Evaluation Date</label>
              <input type="date" class="form-control" id="programChairEvaluationEditDate" name="evaluation_date" />
            </div>
            <div class="col-md-4" data-supervisory-edit-field>
              <label for="programChairEvaluationEditTime" class="form-label">Evaluation Time</label>
              <input type="time" class="form-control" id="programChairEvaluationEditTime" name="evaluation_time" />
            </div>
            <div class="col-12">
              <label for="programChairEvaluationEditComment" class="form-label">Comment</label>
              <textarea class="form-control" id="programChairEvaluationEditComment" name="comment_text" rows="5"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    var panelsContainer = document.getElementById('programChairManagementPanels');
    var metricsContainer = document.getElementById('programChairMetrics');
    var flashContainer = document.getElementById('programChairFlashes');
    var heroEligible = document.querySelector('[data-program-chair-hero-eligible]');
    var heroMaster = document.querySelector('[data-program-chair-hero-master]');
    var modalElement = document.getElementById('programChairClassificationModal');
    var modalForm = document.getElementById('programChairClassificationForm');
    var modalFacultyId = document.getElementById('programChairClassificationFacultyId');
    var modalFacultyName = document.getElementById('programChairClassificationFacultyName');
    var modalSelect = document.getElementById('programChairClassificationSelect');
    var modalError = document.getElementById('programChairClassificationError');
    var evaluationDetailsModalElement = document.getElementById('programChairEvaluationDetailsModal');
    var evaluationDetailsTitle = document.getElementById('programChairEvaluationDetailsModalLabel');
    var evaluationDetailsSubtitle = document.getElementById('programChairEvaluationDetailsSubtitle');
    var evaluationDetailsBody = document.getElementById('programChairEvaluationDetailsBody');
    var evaluationEditModalElement = document.getElementById('programChairEvaluationEditModal');
    var evaluationEditForm = document.getElementById('programChairEvaluationEditForm');
    var evaluationEditSubtitle = document.getElementById('programChairEvaluationEditSubtitle');
    var evaluationEditType = document.getElementById('programChairEvaluationEditType');
    var evaluationEditId = document.getElementById('programChairEvaluationEditId');
    var evaluationEditStatus = document.getElementById('programChairEvaluationEditStatus');
    var evaluationEditDate = document.getElementById('programChairEvaluationEditDate');
    var evaluationEditTime = document.getElementById('programChairEvaluationEditTime');
    var evaluationEditComment = document.getElementById('programChairEvaluationEditComment');
    var evaluationEditError = document.getElementById('programChairEvaluationEditError');
    var activeEvaluationContext = null;
    var loadedEvaluations = {};

    if (!panelsContainer || !metricsContainer || !flashContainer || !modalElement || !modalForm || !evaluationDetailsModalElement || !evaluationEditModalElement || !evaluationEditForm || typeof bootstrap === 'undefined' || !window.fetch) {
      return;
    }

    var classificationModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    var evaluationDetailsModal = bootstrap.Modal.getOrCreateInstance(evaluationDetailsModalElement);
    var evaluationEditModal = bootstrap.Modal.getOrCreateInstance(evaluationEditModalElement);

    function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, function (character) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        }[character];
      });
    }

    function setLoadingState(isLoading) {
      panelsContainer.classList.toggle('is-loading', isLoading);
      metricsContainer.classList.toggle('is-loading', isLoading);
    }

    function renderFlash(type, message) {
      if (!message) {
        flashContainer.innerHTML = '';
        return;
      }

      var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
      flashContainer.innerHTML = '<div class="alert ' + alertClass + '" role="alert">' + escapeHtml(message) + '</div>';
    }

    function evaluationKey(type, id) {
      return type + ':' + String(id);
    }

    function formatAverage(value) {
      var number = Number(value || 0);
      return number.toFixed(2);
    }

    function statusBadge(status) {
      var normalizedStatus = String(status || 'draft');
      var badgeClass = normalizedStatus === 'submitted' ? 'bg-label-success' : 'bg-label-warning';
      return '<span class="badge ' + badgeClass + '">' + escapeHtml(normalizedStatus.charAt(0).toUpperCase() + normalizedStatus.slice(1)) + '</span>';
    }

    function rowUpdatedLabel(value) {
      if (!value) {
        return 'Not available';
      }

      var date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
    }

    function renderEvaluationRows(rows) {
      loadedEvaluations = {};

      if (!evaluationDetailsBody) {
        return;
      }

      if (!Array.isArray(rows) || rows.length === 0) {
        evaluationDetailsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No evaluation rows found.</td></tr>';
        return;
      }

      evaluationDetailsBody.innerHTML = rows.map(function (row) {
        var key = evaluationKey(row.type, row.id);
        var maxScore = Number(row.questionCount || 0) * 5;
        var subject = row.subject || 'No subject recorded';
        var term = row.term || (row.date ? row.date + (row.time ? ' ' + row.time : '') : '');
        loadedEvaluations[key] = row;

        return '<tr>' +
          '<td><div class="fw-semibold">' + escapeHtml(row.actor || 'Not recorded') + '</div>' +
          (row.actorMeta ? '<small class="text-muted">' + escapeHtml(row.actorMeta) + '</small>' : '') + '</td>' +
          '<td><div class="fw-semibold">' + escapeHtml(subject) + '</div>' +
          (term ? '<small class="text-muted">' + escapeHtml(term) + '</small>' : '') +
          (row.comment ? '<small class="text-muted d-block">Comment: ' + escapeHtml(row.comment) + '</small>' : '') + '</td>' +
          '<td><div>' + escapeHtml(row.totalScore || 0) + ' / ' + escapeHtml(maxScore) + '</div><small class="text-muted">' + escapeHtml(formatAverage(row.averageRating)) + ' average</small></td>' +
          '<td>' + statusBadge(row.status) + '</td>' +
          '<td>' + escapeHtml(rowUpdatedLabel(row.updatedAt)) + '</td>' +
          '<td class="text-end"><div class="d-inline-flex flex-wrap justify-content-end gap-2">' +
          '<button type="button" class="btn btn-outline-primary btn-sm" data-program-chair-edit-evaluation="' + escapeHtml(key) + '"><i class="bx bx-pencil me-1"></i>Edit</button>' +
          '<button type="button" class="btn btn-outline-danger btn-sm" data-program-chair-delete-evaluation="' + escapeHtml(key) + '"><i class="bx bx-trash me-1"></i>Delete</button>' +
          '</div></td>' +
        '</tr>';
      }).join('');
    }

    function loadEvaluationDetails(facultyId, evaluationType, facultyName) {
      activeEvaluationContext = {
        facultyId: facultyId,
        evaluationType: evaluationType,
        facultyName: facultyName
      };

      if (evaluationDetailsTitle) {
        evaluationDetailsTitle.textContent = (evaluationType === 'student' ? 'Student Evaluations' : 'Supervisory Evaluations');
      }

      if (evaluationDetailsSubtitle) {
        evaluationDetailsSubtitle.textContent = facultyName || 'Faculty';
      }

      if (evaluationDetailsBody) {
        evaluationDetailsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading evaluation rows...</td></tr>';
      }

      evaluationDetailsModal.show();

      fetch('<?= h(base_url('administrator/program_chair.php')) ?>?action=faculty_evaluation_details&faculty_id=' + encodeURIComponent(facultyId) + '&evaluation_type=' + encodeURIComponent(evaluationType), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload.ok) {
            throw new Error(payload.error || 'Unable to load evaluation details.');
          }

          renderEvaluationRows(payload.evaluations || []);
        })
        .catch(function (error) {
          if (evaluationDetailsBody) {
            evaluationDetailsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">' + escapeHtml(error.message) + '</td></tr>';
          }
        });
    }

    function refreshActiveEvaluationDetails() {
      if (!activeEvaluationContext) {
        return;
      }

      loadEvaluationDetails(
        activeEvaluationContext.facultyId,
        activeEvaluationContext.evaluationType,
        activeEvaluationContext.facultyName
      );
    }

    function applyResponse(payload) {
      if (typeof payload.metrics_html === 'string') {
        metricsContainer.innerHTML = payload.metrics_html;
      }

      if (typeof payload.management_html === 'string') {
        panelsContainer.innerHTML = payload.management_html;
      }

      if (payload.hero && heroEligible && typeof payload.hero.eligible === 'string') {
        heroEligible.textContent = payload.hero.eligible;
      }

      if (payload.hero && heroMaster && typeof payload.hero.master === 'string') {
        heroMaster.textContent = payload.hero.master;
      }
    }

    function submitAjaxForm(form, callbacks) {
      setLoadingState(true);

      fetch(form.getAttribute('action') || window.location.href, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(form)
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return {
              ok: response.ok,
              payload: payload
            };
          }).catch(function () {
            throw new Error('Unable to parse the program chair response.');
          });
        })
        .then(function (result) {
          var payload = result.payload || {};

          if (typeof payload === 'object' && payload !== null) {
            applyResponse(payload);
          }

          if (!payload.ok) {
            throw new Error(payload.error || 'Unable to update the program chair list right now.');
          }

          if (callbacks && typeof callbacks.onSuccess === 'function') {
            callbacks.onSuccess(payload);
          }
        })
        .catch(function (error) {
          if (callbacks && typeof callbacks.onError === 'function') {
            callbacks.onError(error.message);
            return;
          }

          renderFlash('error', error.message);
        })
        .finally(function () {
          setLoadingState(false);
        });
    }

    panelsContainer.addEventListener('submit', function (event) {
      var form = event.target;

      if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-program-chair-admin-ajax-form') || event.defaultPrevented) {
        return;
      }

      if (form.getAttribute('data-program-chair-admin-ajax-form') === 'remove') {
        var confirmMessage = form.getAttribute('data-confirm-message') || 'Remove this faculty member from the program chair evaluation list?';
        if (!window.confirm(confirmMessage)) {
          event.preventDefault();
          return;
        }
      }

      event.preventDefault();
      renderFlash('', '');

      submitAjaxForm(form, {
        onSuccess: function (payload) {
          renderFlash('success', payload.notice || 'Program chair list updated successfully.');
        },
        onError: function (message) {
          renderFlash('error', message);
        }
      });
    });

    panelsContainer.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-program-chair-evaluation-details]');

      if (!trigger) {
        return;
      }

      event.preventDefault();
      loadEvaluationDetails(
        trigger.getAttribute('data-faculty-id') || '0',
        trigger.getAttribute('data-evaluation-type') || 'student',
        trigger.getAttribute('data-faculty-name') || 'Faculty'
      );
    });

    evaluationDetailsBody.addEventListener('click', function (event) {
      var editTrigger = event.target.closest('[data-program-chair-edit-evaluation]');
      var deleteTrigger = event.target.closest('[data-program-chair-delete-evaluation]');

      if (editTrigger) {
        var editRow = loadedEvaluations[editTrigger.getAttribute('data-program-chair-edit-evaluation') || ''];

        if (!editRow) {
          return;
        }

        if (evaluationEditError) {
          evaluationEditError.classList.add('d-none');
          evaluationEditError.textContent = '';
        }

        if (evaluationEditSubtitle) {
          evaluationEditSubtitle.textContent = (editRow.actor || 'Evaluation') + ' | ' + (editRow.subject || 'No subject recorded');
        }

        if (evaluationEditType) {
          evaluationEditType.value = editRow.type || 'student';
        }

        if (evaluationEditId) {
          evaluationEditId.value = editRow.id || '';
        }

        if (evaluationEditStatus) {
          evaluationEditStatus.value = editRow.status || 'draft';
        }

        if (evaluationEditDate) {
          evaluationEditDate.value = editRow.type === 'supervisory' ? (editRow.date || '') : '';
        }

        if (evaluationEditTime) {
          evaluationEditTime.value = editRow.type === 'supervisory' ? (editRow.time || '') : '';
        }

        if (evaluationEditComment) {
          evaluationEditComment.value = editRow.comment || '';
        }

        evaluationEditModalElement.querySelectorAll('[data-supervisory-edit-field]').forEach(function (field) {
          field.classList.toggle('d-none', editRow.type !== 'supervisory');
        });

        evaluationEditModal.show();
        return;
      }

      if (!deleteTrigger) {
        return;
      }

      var deleteRow = loadedEvaluations[deleteTrigger.getAttribute('data-program-chair-delete-evaluation') || ''];
      if (!deleteRow || !window.confirm('Delete this evaluation row? This also removes its saved answers.')) {
        return;
      }

      var formData = new FormData();
      formData.append('csrf_token', '<?= h(csrf_token()) ?>');
      formData.append('action', 'delete_evaluation');
      formData.append('evaluation_type', deleteRow.type || 'student');
      formData.append('evaluation_id', String(deleteRow.id || '0'));
      setLoadingState(true);

      fetch('<?= h(base_url('administrator/program_chair.php')) ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (typeof payload === 'object' && payload !== null) {
            applyResponse(payload);
          }

          if (!payload.ok) {
            throw new Error(payload.error || 'Unable to delete the evaluation row.');
          }

          renderFlash('success', payload.notice || 'Evaluation row was deleted successfully.');
          refreshActiveEvaluationDetails();
        })
        .catch(function (error) {
          renderFlash('error', error.message);
        })
        .finally(function () {
          setLoadingState(false);
        });
    });

    modalElement.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;

      if (!(trigger instanceof HTMLElement)) {
        return;
      }

      if (modalFacultyId) {
        modalFacultyId.value = trigger.getAttribute('data-program-chair-faculty-id') || '';
      }

      if (modalFacultyName) {
        modalFacultyName.textContent = trigger.getAttribute('data-program-chair-faculty-name') || 'Faculty';
      }

      if (modalSelect) {
        modalSelect.value = trigger.getAttribute('data-program-chair-faculty-classification') || '';
      }

      if (modalError) {
        modalError.classList.add('d-none');
        modalError.textContent = '';
      }
    });

    modalForm.addEventListener('submit', function (event) {
      event.preventDefault();

      if (modalError) {
        modalError.classList.add('d-none');
        modalError.textContent = '';
      }
      renderFlash('', '');

      submitAjaxForm(modalForm, {
        onSuccess: function (payload) {
          classificationModal.hide();
          renderFlash('success', payload.notice || 'Faculty classification was updated successfully.');
        },
        onError: function (message) {
          if (modalError) {
            modalError.textContent = message;
            modalError.classList.remove('d-none');
          }
        }
      });
    });

    evaluationEditForm.addEventListener('submit', function (event) {
      event.preventDefault();

      if (evaluationEditError) {
        evaluationEditError.classList.add('d-none');
        evaluationEditError.textContent = '';
      }
      renderFlash('', '');

      submitAjaxForm(evaluationEditForm, {
        onSuccess: function (payload) {
          evaluationEditModal.hide();
          renderFlash('success', payload.notice || 'Evaluation row was updated successfully.');
          refreshActiveEvaluationDetails();
        },
        onError: function (message) {
          if (evaluationEditError) {
            evaluationEditError.textContent = message;
            evaluationEditError.classList.remove('d-none');
          }
        }
      });
    });
  });
</script>
<?php require __DIR__ . '/_end.php'; ?>
