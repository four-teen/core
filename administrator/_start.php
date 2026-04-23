<?php
declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Administrator';
}

if (!isset($pageDescription)) {
    $pageDescription = 'Administrator module';
}

if (!isset($activeAdminPage)) {
    $activeAdminPage = 'dashboard';
}
?>
<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0"
    />
    <title><?= h(app_name()) ?> | <?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($pageDescription) ?>" />
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/app.css')) ?>" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="<?= h(base_url('administrator/index.php')) ?>" class="app-brand-link">
              <span class="app-brand-logo demo">
                <span class="brand-icon-shell">
                  <i class="bx bx-bar-chart-square"></i>
                </span>
              </span>
              <span class="app-brand-text demo menu-text fw-bolder ms-2 admin-brand-text">CORE</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <li class="menu-item <?= $activeAdminPage === 'dashboard' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/index.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'faculty' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/faculty.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-pin"></i>
                <div>Faculty</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'program_chair' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/program_chair.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-check"></i>
                <div>Program Chair</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'students' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/students.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div>Students</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'users' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/users.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                <div>User Management</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'evaluations' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/evaluations.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-time-five"></i>
                <div>Recent Evaluation Activity</div>
              </a>
            </li>
            <li class="menu-item <?= $activeAdminPage === 'individual_faculty_performance' ? 'active' : '' ?>">
              <a href="<?= h(base_url('administrator/individual_faculty_performance.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div>Individual Faculty Performance</div>
              </a>
            </li>
          </ul>
        </aside>

        <div class="layout-page">
          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100" id="navbar-collapse">
              <div class="d-flex align-items-center gap-3 admin-navbar-actions">
                <div class="text-end admin-navbar-user">
                  <div class="fw-semibold"><?= h($administrator['name'] ?? 'Administrator') ?></div>
                  <small class="text-muted admin-navbar-meta">
                    <?= h(user_management_role_label((string) ($administrator['role'] ?? 'administrator'))) ?>
                    <?php if (!empty($administrator['email'])): ?>
                      | <?= h($administrator['email']) ?>
                    <?php endif; ?>
                  </small>
                </div>
                <?php if (!empty($administrator['picture'])): ?>
                  <img
                    src="<?= h($administrator['picture']) ?>"
                    alt="Administrator Avatar"
                    class="rounded-circle"
                    width="40"
                    height="40"
                  />
                <?php else: ?>
                  <span class="avatar-initial rounded-circle bg-label-primary">
                    <i class="bx bx-user"></i>
                  </span>
                <?php endif; ?>
                <a href="<?= h(base_url('auth/logout.php')) ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="bx bx-log-out-circle me-1"></i>
                  Logout
                </a>
              </div>
            </div>
          </nav>

          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
