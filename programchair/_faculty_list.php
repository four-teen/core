<?php
declare(strict_types=1);

function program_chair_faculty_card_button(array $faculty): array
{
    $status = (string) ($faculty['submission_status'] ?? '');
    $button = [
        'label' => 'Evaluate Faculty',
        'class' => 'btn-primary',
        'icon' => 'bx-edit-alt',
    ];

    if ($status === 'submitted') {
        $button = [
            'label' => 'View Submission',
            'class' => 'btn-success',
            'icon' => 'bx-show',
        ];
    } elseif ($status === 'draft') {
        $button = [
            'label' => 'Continue Draft',
            'class' => 'btn-warning',
            'icon' => 'bx-edit',
        ];
    }

    return $button;
}

function program_chair_render_faculty_list(array $facultyList, string $facultySearch = ''): void
{
    if ($facultyList === []) {
        ?>
        <div class="text-center text-muted py-4">
          <?= $facultySearch !== '' ? 'No faculty members matched your search.' : 'No faculty members are available for program chair evaluation yet.' ?>
        </div>
        <?php
        return;
    }

    foreach ($facultyList as $faculty):
        $button = program_chair_faculty_card_button($faculty);
        $statusText = (string) ($faculty['evaluation_status_text'] ?? '');
        $evaluatedBy = (string) ($faculty['evaluated_by'] ?? '');
        ?>
        <div class="program-chair-faculty-card">
          <div class="program-chair-faculty-row">
            <div class="program-chair-faculty-main">
              <span class="badge bg-label-info mb-2">Faculty ID <?= h((string) ($faculty['faculty_id'] ?? '')) ?></span>
              <h5 class="program-chair-faculty-name"><?= h((string) ($faculty['faculty_name'] ?? '')) ?></h5>

              <div class="program-chair-faculty-details">
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Subject</span>
                  <span><?= h(trim((string) ($faculty['subject_text'] ?? '')) !== '' ? (string) $faculty['subject_text'] : '') ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Evaluation Date</span>
                  <span><?= h(trim((string) ($faculty['evaluation_date'] ?? '')) !== '' ? (string) $faculty['evaluation_date'] : '') ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Average Rating</span>
                  <span><?= trim((string) ($faculty['average_rating'] ?? '')) !== '' && (float) ($faculty['average_rating'] ?? 0) > 0 ? h(format_average($faculty['average_rating'])) : '' ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Evaluated By</span>
                  <span><?= h($evaluatedBy) ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Status</span>
                  <span><?= h($statusText) ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Updated</span>
                  <span><?= $statusText !== '' ? h(format_datetime((string) ($faculty['evaluation_updated_at'] ?? ''))) : '' ?></span>
                </div>
              </div>
            </div>

            <div class="program-chair-faculty-action">
              <a
                href="<?= h(base_url('programchair/evaluate.php?faculty_id=' . (string) ($faculty['faculty_id'] ?? '0'))) ?>"
                class="btn <?= h($button['class']) ?> w-100"
              >
                <i class="bx <?= h($button['icon']) ?> me-1"></i>
                <?= h($button['label']) ?>
              </a>
            </div>
          </div>
        </div>
        <?php
    endforeach;
}
