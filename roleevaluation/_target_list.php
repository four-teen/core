<?php
declare(strict_types=1);

function role_evaluation_target_card_button(array $target): array
{
    $status = (string) ($target['submission_status'] ?? '');
    $button = [
        'label' => 'Evaluate',
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

function role_evaluation_render_target_list(array $targetList, string $targetSearch = ''): void
{
    if ($targetList === []) {
        ?>
        <div class="text-center text-muted py-4">
          <?= $targetSearch !== '' ? 'No assigned accounts matched your search.' : 'No accounts are assigned to you for evaluation yet.' ?>
        </div>
        <?php
        return;
    }

    foreach ($targetList as $target):
        $button = role_evaluation_target_card_button($target);
        $statusText = (string) ($target['evaluation_status_text'] ?? 'Not started');
        ?>
        <div class="program-chair-faculty-card">
          <div class="program-chair-faculty-row">
            <div class="program-chair-faculty-main">
              <span class="badge bg-label-info mb-2"><?= h(user_management_role_label((string) ($target['account_role'] ?? ''))) ?></span>
              <h5 class="program-chair-faculty-name"><?= h((string) ($target['target_name'] ?? '')) ?></h5>

              <div class="program-chair-faculty-details">
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Email</span>
                  <span><?= h((string) ($target['email_address'] ?? '')) ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Evaluation Date</span>
                  <span><?= h(trim((string) ($target['evaluation_date'] ?? '')) !== '' ? (string) $target['evaluation_date'] : '') ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Average Rating</span>
                  <span><?= trim((string) ($target['average_rating'] ?? '')) !== '' && (float) ($target['average_rating'] ?? 0) > 0 ? h(format_average($target['average_rating'])) : '' ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Status</span>
                  <span><?= h($statusText) ?></span>
                </div>
                <div class="subject-meta-item">
                  <span class="subject-meta-label">Updated</span>
                  <span><?= $statusText !== 'Not started' ? h(format_datetime((string) ($target['evaluation_updated_at'] ?? ''))) : '' ?></span>
                </div>
              </div>
            </div>

            <div class="program-chair-faculty-action">
              <a
                href="<?= h(base_url('roleevaluation/evaluate.php?target_user_id=' . (string) ($target['target_user_management_id'] ?? '0'))) ?>"
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
