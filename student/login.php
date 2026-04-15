<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_student_authenticated()) {
    redirect_to('student/index.php');
}

$errorMessage = flash('error');
$noticeMessage = flash('notice');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($email === '') {
        $errorMessage = 'Please enter your enrolled student email address.';
    } else {
        try {
            $student = find_student_for_login(db(), $email);

            if ($student === null) {
                $errorMessage = 'No enrolled student record was found for that email address.';
            } else {
                login_student($student);
                redirect_to('student/index.php');
            }
        } catch (Throwable $exception) {
            $errorMessage = is_local_env()
                ? 'Unable to sign in. ' . $exception->getMessage()
                : 'Unable to sign in right now. Please try again.';
        }
    }
}
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
    <title><?= h(app_name()) ?> | Student Login</title>
    <meta name="description" content="Student portal login for CORE Faculty Evaluation." />
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
                <a href="<?= h(base_url('student/login.php')) ?>" class="app-brand-link gap-2">
                  <span class="app-brand-logo demo">
                    <span class="brand-icon-shell student-brand-shell">
                      <i class="bx bx-user-circle"></i>
                    </span>
                  </span>
                  <span class="app-brand-text text-body fw-bolder"><?= h(app_name()) ?></span>
                </a>
              </div>

              <div class="text-center mb-4">
                <span class="badge bg-label-info mb-3">Student Portal</span>
                <h4 class="mb-2">Sign in with your enrolled email</h4>
                <p class="mb-0">
                  Your email is validated against <code>tbl_student_management.email_address</code>.
                </p>
              </div>

              <?php if ($noticeMessage !== null): ?>
                <div class="alert alert-success" role="alert"><?= h($noticeMessage) ?></div>
              <?php endif; ?>

              <?php if ($errorMessage !== null): ?>
                <div class="alert alert-danger" role="alert"><?= h($errorMessage) ?></div>
              <?php endif; ?>

              <form method="post" action="<?= h(base_url('student/login.php')) ?>">
                <div class="mb-3">
                  <label for="email" class="form-label">Student Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?= h($email) ?>"
                    placeholder="name@sksu.edu.ph"
                    required
                    autofocus
                  />
                </div>
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">Open Student Portal</button>
                </div>
              </form>

              <div class="text-center mt-4">
                <a href="<?= h(base_url('auth/login.php')) ?>" class="text-muted">
                  Administrator sign in
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
