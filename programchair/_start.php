<?php
declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Program Chair';
}

if (!isset($pageDescription)) {
    $pageDescription = 'Program chair module';
}

if (!isset($activeProgramChairPage)) {
    $activeProgramChairPage = 'dashboard';
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
    <link rel="stylesheet" href="../assets/css/app.css" />
    <?php if (isset($extraHeadContent) && is_string($extraHeadContent)): ?>
      <?= $extraHeadContent ?>
    <?php endif; ?>
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body class="program-chair-page">
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="<?= h(base_url('programchair/index.php')) ?>" class="app-brand-link">
              <span class="app-brand-logo demo">
                <span class="brand-icon-shell">
                  <i class="bx bx-user-check"></i>
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
            <li class="menu-item <?= $activeProgramChairPage === 'dashboard' ? 'active' : '' ?>">
              <a href="<?= h(base_url('programchair/index.php')) ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-edit-alt"></i>
                <div>Faculty Evaluations</div>
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
                  <div class="fw-semibold"><?= h($programChair['name'] ?? 'Program Chair') ?></div>
                  <small class="text-muted admin-navbar-meta">
                    <?= h(user_management_role_label((string) ($programChair['role'] ?? 'program_chair'))) ?>
                    <?php if (!empty($programChair['email'])): ?>
                      | <?= h($programChair['email']) ?>
                    <?php endif; ?>
                  </small>
                </div>
                <?php if (!empty($programChair['picture'])): ?>
                  <img
                    src="<?= h($programChair['picture']) ?>"
                    alt="Program Chair Avatar"
                    class="rounded-circle"
                    width="40"
                    height="40"
                  />
                <?php else: ?>
                  <span class="avatar-initial rounded-circle bg-label-info">
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
