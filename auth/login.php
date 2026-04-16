<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

if (is_program_chair_authenticated()) {
    redirect_to('programchair/index.php');
}

if (is_student_authenticated()) {
    redirect_to('student/index.php');
}

$errorMessage = flash('error');
$noticeMessage = flash('notice');
$googleReady = google_configuration_is_ready();
$allowedDomain = primary_administrator_domain();
$loginHeading = preg_replace('/^\s*CORE\s+/i', '', app_name()) ?: app_name();
$googleAccessMessage = 'Use one Google sign-in button for authorized users and enrolled students. Administrator and program chair access is managed from User Management, while student access is matched from enrolled records.';
$googleButtonCaption = $allowedDomain !== null
    ? 'Administrator, program chair, and student access use this same Google sign-in'
    : 'The system routes administrators, program chairs, and students automatically';
?>
<!DOCTYPE html>
<html
  lang="en"
  class="light-style customizer-hide"
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

    <title><?= h(app_name()) ?> | Sign In</title>

    <meta name="description" content="Sign in for Faculty Evaluation." />

    <link rel="icon" type="image/x-icon" href="<?= h(asset_url('assets/img/favicon/favicon.ico')) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= h(asset_url('assets/vendor/fonts/boxicons.css')) ?>" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/vendor/css/core.css')) ?>" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/vendor/css/theme-default.css')) ?>" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/demo.css')) ?>" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')) ?>" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/vendor/css/pages/page-auth.css')) ?>" />
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/app.css')) ?>" />
    <script src="<?= h(asset_url('assets/vendor/js/helpers.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/js/config.js')) ?>"></script>
  </head>

  <body class="auth-login-page">
    <div class="auth-login-shell">
      <div class="auth-login-card">
        <div class="auth-login-emblem" aria-hidden="true">
          <div class="auth-login-emblem-ring">
            <div class="auth-login-emblem-core">
              <span class="auth-login-emblem-icon">
                <i class="bx bx-bar-chart-square"></i>
              </span>
              <span class="auth-login-emblem-text">CORE</span>
            </div>
          </div>
        </div>

        <div class="auth-login-panel">
          <div class="auth-login-heading">
            <h2><?= h($loginHeading) ?></h2>
            <p class="auth-login-description">
              Sign in once with Google and the system will open the correct portal for your authorized account.
            </p>
            <p class="auth-login-campus">Sultan Kudarat State University</p>
          </div>

          <?php if ($noticeMessage !== null || $errorMessage !== null): ?>
            <div class="auth-login-flashes">
              <?php if ($noticeMessage !== null): ?>
                <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
              <?php endif; ?>

              <?php if ($errorMessage !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="auth-login-section-label">Authorized Google Sign-In</div>

          <?php if ($googleReady): ?>
            <a href="<?= h(base_url('auth/google_login.php')) ?>" class="btn auth-google-button">
              <span class="auth-google-button-avatar">
                <i class="bx bx-user-circle"></i>
              </span>
              <span class="auth-google-button-copy">
                <strong>Continue with Google</strong>
                <span><?= h($googleButtonCaption) ?></span>
              </span>
              <span class="auth-google-button-tail">
                <i class="bx bx-chevron-down"></i>
                <img src="<?= h(asset_url('assets/img/icons/brands/google.png')) ?>" alt="Google" />
              </span>
            </a>
          <?php else: ?>
            <button type="button" class="btn auth-google-button" disabled>
              <span class="auth-google-button-avatar">
                <i class="bx bx-user-circle"></i>
              </span>
              <span class="auth-google-button-copy">
                <strong>Google sign-in unavailable</strong>
                <span>OAuth configuration is required before registered accounts can continue.</span>
              </span>
              <span class="auth-google-button-tail">
                <img src="<?= h(asset_url('assets/img/icons/brands/google.png')) ?>" alt="Google" />
              </span>
            </button>
          <?php endif; ?>

          <div class="auth-login-footer-note">
            <p><?= h($googleAccessMessage) ?></p>
          </div>
        </div>
      </div>
    </div>

    <script src="<?= h(asset_url('assets/vendor/libs/jquery/jquery.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/vendor/libs/popper/popper.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/vendor/js/bootstrap.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/vendor/js/menu.js')) ?>"></script>
    <script src="<?= h(asset_url('assets/js/main.js')) ?>"></script>
  </body>
</html>
