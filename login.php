<?php
require_once __DIR__ . '/bootstrap.php';

$message = '';
$message_type = 'danger';
$is_registering = isset($_GET['setup']) && DEMO_MODE && count(get_demo_accounts()) === 0;
$return_to = (($_GET['return_to'] ?? $_POST['return_to'] ?? '') === '/admin') ? '/admin' : '';

if (isset($_GET['register'])) {
  header('Location: /register');
  exit;
}

if (isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header('Location: /login.php');
  exit;
}

if (isset($_SESSION['user'])) {
  if (refresh_session_profile()) {
    header('Location: ' . ($return_to ?: (($_SESSION['user']['role'] === 'admin') ? '/admin' : '/')));
    exit;
  }
}

if (!DEMO_MODE && !supabase_is_configured()) {
  $message = 'Supabase is not configured yet. Copy config.example.php to config.local.php and add your project keys.';
} elseif (DEMO_MODE) {
  $message = 'Demo mode is active. Accounts are stored locally and no confirmation or invitation emails are sent.';
  $message_type = 'info';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (DEMO_MODE || supabase_is_configured())) {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    $message = 'Your session expired. Refresh the page and try again.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Enter a valid email address.';
  } elseif ($is_registering) {
    $label = $_POST['label'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');

    if (strlen($password) < 8) {
      $message = 'Password must be at least 8 characters long.';
    } elseif (!in_array($label, get_account_labels(), true)) {
      $message = 'Please select a valid guest category.';
    } elseif (DEMO_MODE) {
      if (!save_demo_account($email, $password, $display_name, $label)) {
        $message = 'An account with this email already exists.';
      } else {
        $account = get_demo_account_by_email($email);
        session_regenerate_id(true);
        $_SESSION['user'] = [
          'id' => $account['id'],
          'email' => $account['email'],
          'username' => $account['email'],
          'display_name' => $account['display_name'],
          'label' => $account['label'],
          'role' => $account['role'],
          'login_time' => date('Y-m-d H:i:s'),
        ];
        header('Location: ' . ($return_to ?: (($account['role'] === 'admin') ? '/admin' : '/')));
        exit;
      }
    } else {
      $signup = supabase_request('POST', '/auth/v1/signup?redirect_to=' . rawurlencode(APP_URL . '/login.php?confirmed=1'), [
        'email' => $email,
        'password' => $password,
        'data' => [
          'display_name' => $display_name,
          'label' => $label,
        ],
      ], 'anon');

      if ($signup !== false) {
        $message = 'Registration received. Check your email to confirm your account, then log in.';
        $message_type = 'success';
      } else {
        $message = supabase_last_error() ?: 'Account registration failed.';
      }
    }
  } else {
    if (DEMO_MODE) {
      $account = get_demo_account_by_email($email);
      if ($account && password_verify($password, $account['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
          'id' => $account['id'],
          'email' => $account['email'],
          'username' => $account['email'],
          'display_name' => $account['display_name'],
          'label' => $account['label'],
          'role' => $account['role'],
          'login_time' => date('Y-m-d H:i:s'),
        ];
          header('Location: ' . ($return_to ?: (($account['role'] === 'admin') ? '/admin' : '/')));
        exit;
      }
      $message = 'Incorrect email or password.';
    } else {
      $auth = supabase_request('POST', '/auth/v1/token?grant_type=password', [
        'email' => $email,
        'password' => $password,
      ], 'anon');

      if ($auth !== false && isset($auth['user']['id'], $auth['access_token'])) {
        $profile = get_auth_profile($auth['user']['id']);
        if (!$profile) {
          $profile_error = supabase_last_error();
          $message = 'Your profile could not be loaded. Confirm your email and check that the ' . SUPABASE_SCHEMA . ' schema is exposed in Supabase API settings.';
          if ($profile_error) $message .= ' Supabase: ' . $profile_error;
        } else {
          session_regenerate_id(true);
          $_SESSION['access_token'] = $auth['access_token'];
          $_SESSION['refresh_token'] = $auth['refresh_token'] ?? '';
          $_SESSION['user'] = [
            'id' => $profile['id'],
            'email' => $profile['email'],
            'username' => $profile['email'],
            'display_name' => $profile['display_name'],
            'label' => $profile['label'],
            'role' => $profile['role'],
            'login_time' => date('Y-m-d H:i:s'),
          ];
          header('Location: ' . ($return_to ?: (($profile['role'] === 'admin') ? '/admin' : '/')));
          exit;
        }
      } else {
        $message = supabase_last_error() ?: 'Incorrect email or password. Confirm your email address if you just registered.';
      }
    }
  }
}

if (isset($_GET['confirmed'])) {
  $message = 'Email confirmed. You can now log in.';
  $message_type = 'success';
} elseif (isset($_GET['invited'])) {
  $message = 'Your invitation is confirmed. You can now log in.';
  $message_type = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_registering ? 'Create account' : 'Log in'; ?> | ADE &amp; OLA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/style.css">
</head>

<body class="login">
  <div class="container py-3 col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
    <div class="brand fs-2 fw-light lh-1">ADE &amp; OLA</div>
    <form action="" method="post">
      <h1><?= $is_registering ? 'Create account' : 'Log in'; ?></h1>
      <?php if ($message !== '') : ?>
        <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <?php if ($return_to !== '') : ?>
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php if ($is_registering) : ?>
        <div class="mb-3">
          <label for="display_name" class="form-label">Name</label>
          <input type="text" class="form-control" id="display_name" name="display_name" maxlength="120" autocomplete="name">
        </div>
      <?php endif; ?>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="8" autocomplete="<?= $is_registering ? 'new-password' : 'current-password'; ?>" required>
      </div>
      <?php if ($is_registering) : ?>
        <div class="mb-3">
          <label for="label" class="form-label">Guest category</label>
          <select class="form-select" id="label" name="label" required>
            <option value="" selected disabled>Select a group</option>
            <?php foreach (get_account_labels() as $label) : ?>
              <option value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary"><?= $is_registering ? 'Create account' : 'Log in'; ?></button>
      </div>
    </form>
    <p class="mt-3">
      <?php if ($is_registering) : ?>
        Already have an account? <a href="/login.php">Log in</a>
      <?php else : ?>
        <?php if (DEMO_MODE && count(get_demo_accounts()) === 0) : ?>
          <a href="/login.php?setup=1">Set up the demo admin account</a>
        <?php else : ?>
          <a href="/register">RSVP to the wedding</a>
        <?php endif; ?>
      <?php endif; ?>
    </p>
  </div>
  <script type="module">
    import { injectSpeedInsights } from '/assets/speed-insights.mjs';
    injectSpeedInsights();
  </script>
</body>

</html>
