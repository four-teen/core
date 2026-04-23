<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-primary"><i class="bx bx-user-pin"></i></span>
        <div class="metric-value"><?= h(format_number($stats['master_faculty'] ?? 0)) ?></div>
        <div class="metric-label">Active faculty in tbl_faculty</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-success"><i class="bx bx-user-check"></i></span>
        <div class="metric-value"><?= h(format_number($stats['eligible_faculty'] ?? 0)) ?></div>
        <div class="metric-label">Faculty available to program chairs</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card metric-card">
      <div class="card-body">
        <span class="metric-icon bg-label-info"><i class="bx bx-list-plus"></i></span>
        <div class="metric-value"><?= h(format_number(count($facultyOptions ?? []))) ?></div>
        <div class="metric-label">Faculty available to add</div>
      </div>
    </div>
  </div>
</div>
