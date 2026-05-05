<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Add Faculty</h5>
        <small class="text-muted">Choose from active records in the faculty master list.</small>
      </div>
      <div class="card-body">
        <form method="post" action="<?= h(base_url('administrator/program_chair.php')) ?>" data-program-chair-admin-ajax-form="add">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
          <input type="hidden" name="action" value="add_faculty" />

          <div class="row g-3">
            <div class="col-lg-4">
              <label for="faculty_id" class="form-label">Faculty</label>
              <select class="form-select" id="faculty_id" name="faculty_id" required <?= ($facultyOptions ?? []) === [] ? 'disabled' : '' ?>>
                <option value="">Select faculty</option>
                <?php foreach (($facultyOptions ?? []) as $facultyOption): ?>
                  <option
                    value="<?= h((string) ($facultyOption['faculty_id'] ?? '')) ?>"
                    <?= ($addFacultyForm['faculty_id'] ?? '') === (string) ($facultyOption['faculty_id'] ?? '') ? 'selected' : '' ?>
                  >
                    <?= h((string) ($facultyOption['faculty_name'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-lg-4">
              <label for="faculty_classification" class="form-label">Faculty Classification</label>
              <select class="form-select" id="faculty_classification" name="faculty_classification" required <?= ($facultyOptions ?? []) === [] ? 'disabled' : '' ?>>
                <option value="">Select classification</option>
                <?php foreach (program_chair_faculty_classification_options() as $classificationValue => $classificationLabel): ?>
                  <option value="<?= h($classificationValue) ?>" <?= ($addFacultyForm['faculty_classification'] ?? '') === $classificationValue ? 'selected' : '' ?>>
                    <?= h($classificationLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-lg-4">
              <label for="faculty_program_code" class="form-label">Faculty Program</label>
              <select class="form-select" id="faculty_program_code" name="faculty_program_code" required <?= ($facultyOptions ?? []) === [] ? 'disabled' : '' ?>>
                <option value="">Select program</option>
                <?php foreach (($programOptions ?? []) as $programCode => $programOption): ?>
                  <option value="<?= h((string) $programCode) ?>" <?= ($addFacultyForm['faculty_program_code'] ?? '') === (string) $programCode ? 'selected' : '' ?>>
                    <?= h((string) ($programOption['program_label'] ?? $programCode)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary" <?= ($facultyOptions ?? []) === [] ? 'disabled' : '' ?>>
              <i class="bx bx-plus me-1"></i>
              Add to Program Chair List
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Program Chair Program Assignments</h5>
        <small class="text-muted">Assign each Program Chair account to BSIT, BSIS, or BSCS.</small>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Program Chair</th>
              <th>Program</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (($programChairPrograms ?? []) === []): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-4">
                  No active Program Chair accounts were found.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach (($programChairPrograms ?? []) as $programChairProgram): ?>
              <?php $assignedProgramCode = program_chair_normalize_program_code((string) ($programChairProgram['program_code'] ?? ''), true); ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($programChairProgram['display_name'] ?? 'Program Chair')) ?></div>
                  <small class="text-muted"><?= h((string) ($programChairProgram['email_address'] ?? '')) ?></small>
                </td>
                <td>
                  <form method="post" action="<?= h(base_url('administrator/program_chair.php')) ?>" class="d-flex flex-column flex-md-row gap-2" data-program-chair-admin-ajax-form="chair-program">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                    <input type="hidden" name="action" value="update_program_chair_program" />
                    <input type="hidden" name="program_chair_user_management_id" value="<?= h((string) ($programChairProgram['user_management_id'] ?? '0')) ?>" />
                    <select class="form-select" name="program_code" required>
                      <option value="">Select program</option>
                      <?php foreach (($programOptions ?? []) as $programCode => $programOption): ?>
                        <option value="<?= h((string) $programCode) ?>" <?= $assignedProgramCode === (string) $programCode ? 'selected' : '' ?>>
                          <?= h((string) ($programOption['program_label'] ?? $programCode)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">
                      Save
                    </button>
                  </form>
                </td>
                <td class="text-end">
                  <span class="badge <?= $assignedProgramCode !== '' ? 'bg-label-primary' : 'bg-label-secondary' ?>">
                    <?= h(program_chair_program_label($assignedProgramCode)) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Faculty Available for Program Chair Evaluation</h5>
        <small class="text-muted">Only this selected list appears in the program chair module. Update classification and program through the edit modal.</small>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Status</th>
              <th>Classification</th>
              <th>Program</th>
              <th>Student Evaluation</th>
              <th>Supervisory Evaluation</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (($selectedFaculty ?? []) === []): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  No faculty members have been added for program chair evaluation yet.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach (($selectedFaculty ?? []) as $faculty): ?>
              <?php $facultyClassification = program_chair_normalize_faculty_classification((string) ($faculty['faculty_classification'] ?? ''), true); ?>
              <?php $facultyProgramCode = program_chair_normalize_program_code((string) ($faculty['faculty_program_code'] ?? ''), true); ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h((string) ($faculty['faculty_name'] ?? '')) ?></div>
                  <small class="text-muted">Faculty ID <?= h((string) ($faculty['faculty_id'] ?? '')) ?></small>
                </td>
                <td>
                  <span class="badge <?= (string) ($faculty['status'] ?? '') === 'active' ? 'bg-label-success' : 'bg-label-secondary' ?>">
                    <?= h(ucfirst((string) ($faculty['status'] ?? 'inactive'))) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?=
                    $facultyClassification === 'REGULAR'
                      ? 'bg-label-primary'
                      : ($facultyClassification === 'CONTRACT OF SERVICE' ? 'bg-label-warning' : 'bg-label-secondary')
                  ?>">
                    <?= h(program_chair_faculty_classification_label($facultyClassification)) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $facultyProgramCode !== '' ? 'bg-label-info' : 'bg-label-secondary' ?>">
                    <?= h(program_chair_program_label($facultyProgramCode)) ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <button
                      type="button"
                      class="btn btn-link p-0 text-primary fw-semibold"
                      data-program-chair-evaluation-details
                      data-faculty-id="<?= h((string) ($faculty['faculty_id'] ?? 0)) ?>"
                      data-faculty-name="<?= h((string) ($faculty['faculty_name'] ?? 'Faculty')) ?>"
                      data-evaluation-type="student"
                    >
                      <?= h(format_number($faculty['student_evaluation_count'] ?? 0)) ?> total
                    </button>
                    <span class="badge bg-label-success"><?= h(format_average($faculty['student_average_rating'] ?? null)) ?></span>
                  </div>
                  <small class="text-muted">
                    <?= h(format_number($faculty['student_submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($faculty['student_draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <button
                      type="button"
                      class="btn btn-link p-0 text-primary fw-semibold"
                      data-program-chair-evaluation-details
                      data-faculty-id="<?= h((string) ($faculty['faculty_id'] ?? 0)) ?>"
                      data-faculty-name="<?= h((string) ($faculty['faculty_name'] ?? 'Faculty')) ?>"
                      data-evaluation-type="supervisory"
                    >
                      <?= h(format_number($faculty['supervisory_evaluation_count'] ?? 0)) ?> total
                    </button>
                    <span class="badge bg-label-warning"><?= h(format_average($faculty['supervisory_average_rating'] ?? null)) ?></span>
                  </div>
                  <small class="text-muted">
                    <?= h(format_number($faculty['supervisory_submitted_count'] ?? 0)) ?> submitted,
                    <?= h(format_number($faculty['supervisory_draft_count'] ?? 0)) ?> draft
                  </small>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#programChairClassificationModal"
                      data-program-chair-faculty-id="<?= h((string) ($faculty['program_chair_faculty_id'] ?? '0')) ?>"
                      data-program-chair-faculty-name="<?= h((string) ($faculty['faculty_name'] ?? 'Faculty')) ?>"
                      data-program-chair-faculty-classification="<?= h($facultyClassification) ?>"
                      data-program-chair-faculty-program="<?= h($facultyProgramCode) ?>"
                    >
                      <i class="bx bx-pencil me-1"></i>
                      Edit Assignment
                    </button>
                    <form
                      method="post"
                      action="<?= h(base_url('administrator/program_chair.php')) ?>"
                      data-program-chair-admin-ajax-form="remove"
                      data-confirm-message="Remove this faculty member from the program chair evaluation list?"
                    >
                      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>" />
                      <input type="hidden" name="action" value="remove_faculty" />
                      <input
                        type="hidden"
                        name="program_chair_faculty_id"
                        value="<?= h((string) ($faculty['program_chair_faculty_id'] ?? '0')) ?>"
                      />
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bx bx-trash me-1"></i>
                        Remove
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
