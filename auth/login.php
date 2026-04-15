<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_admin_authenticated()) {
    redirect_to('administrator/index.php');
}

$errorMessage = flash('error');
$noticeMessage = flash('notice');
$allowedDomain = primary_administrator_domain();
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
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title><?= h(app_name()) ?> | Administrator Login</title>

    <meta name="description" content="Administrator sign in for CORE Faculty Evaluation." />

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
    <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="../assets/css/app.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
  </head>

  <body>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <div class="card auth-card-clean">
            <div class="card-body">
              <div class="app-brand justify-content-center mb-4">
                <a href="<?= h(base_url()) ?>" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                    <span class="brand-icon-shell">
                      <i class="bx bx-bar-chart-square"></i>
                    </span>
                  </span>
                  <span class="app-brand-text text-body fw-bolder"><?= h(app_name()) ?></span>
                </a>
              </div>

              <div class="text-center mb-4">
                <span class="badge bg-label-primary mb-3">Administrator Access</span>
                <h4 class="mb-2">Sign in with Google</h4>
                <p class="mb-0">
                  Use your administrator Google account to access the faculty evaluation dashboard.
                </p>
              </div>

              <?php if ($noticeMessage !== null): ?>
                <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
              <?php endif; ?>

              <?php if ($errorMessage !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
              <?php endif; ?>

              <div class="auth-callout mb-4">
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <i class="bx bx-shield-quarter text-primary fs-3"></i>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-1">Authorized administrators only</h6>
                    <p class="mb-0">
                      <?php if ($allowedDomain !== null): ?>
                        Access is currently limited to the <strong><?= h($allowedDomain) ?></strong> Google domain.
                      <?php else: ?>
                        Set the allowed administrator emails or domains in <code>.env</code> before login.
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
              </div>

              <a href="<?= h(base_url('auth/google_login.php')) ?>" class="btn btn-google-login w-100">
                <i class="bx bxl-google fs-4"></i>
                <span>Continue with Google</span>
              </a>

              <div class="text-center mt-4">
                <small class="text-muted">
                  Redirect URI:
                  <code><?= h(google_redirect_uri()) ?></code>
                </small>
              </div>

              <div class="text-center mt-3">
                <a href="<?= h(base_url('student/login.php')) ?>" class="text-muted">
                  Student portal sign in
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
