<?php
require_once __DIR__ . '/bootstrap.php';

$message = '';
$invite_ready = false;

if (DEMO_MODE) {
  http_response_code(404);
  exit('Email invitations are unavailable in demo mode.');
}

if (isset($_SESSION['invite_pending'])) {
  $invite_ready = true;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
      $message = 'Your session expired. Reopen the invitation email and try again.';
    } elseif (strlen($password) < 8) {
      $message = 'Password must be at least 8 characters long.';
    } else {
      $updated_user = supabase_request('PUT', '/auth/v1/user', ['password' => $password], 'user');
      if ($updated_user !== false) {
        unset($_SESSION['invite_pending']);
        header('Location: /');
        exit;
      }
      $message = supabase_last_error() ?: 'Could not set your password. Please reopen the invitation email and try again.';
    }
  }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $access_token = $_POST['access_token'] ?? '';
  if ($access_token === '') {
    $message = 'Invitation link is missing its confirmation token. Open the link from your email again.';
  } else {
    $auth_user = supabase_request('GET', '/auth/v1/user', null, 'user', $access_token);
    $profile = ($auth_user !== false && isset($auth_user['id'])) ? get_auth_profile($auth_user['id']) : false;
    if (!$profile) {
      $message = supabase_last_error() ?: 'This invitation is invalid or has expired.';
    } else {
      session_regenerate_id(true);
      $_SESSION['access_token'] = $access_token;
      $_SESSION['invite_pending'] = true;
      $_SESSION['user'] = [
        'id' => $profile['id'],
        'email' => $profile['email'],
        'username' => $profile['email'],
        'display_name' => $profile['display_name'],
        'label' => $profile['label'],
        'role' => $profile['role'],
        'login_time' => date('Y-m-d H:i:s'),
      ];
      $invite_ready = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accept invitation | ADE &amp; OLA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login">
  <div class="container py-3 col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
    <div class="brand fs-2 fw-light lh-1">ADE &amp; OLA</div>
    <h1>Accept invitation</h1>
    <?php if ($message !== '') : ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($invite_ready) : ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mb-3">
          <label for="password" class="form-label">Create a password</label>
          <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Set password and continue</button>
      </form>
    <?php else : ?>
      <p>Validating your invitation...</p>
      <form id="invite-token-form" method="post" hidden>
        <input type="hidden" name="access_token" id="access_token">
      </form>
      <script>
        const inviteToken = new URLSearchParams(window.location.hash.slice(1)).get('access_token');
        if (inviteToken) {
          document.getElementById('access_token').value = inviteToken;
          document.getElementById('invite-token-form').submit();
        } else {
          document.querySelector('p').textContent = 'Invitation token not found. Open the latest invitation email link again.';
        }
      </script>
    <?php endif; ?>
  </div>
  <script type="module">
    import { injectSpeedInsights } from '/assets/speed-insights.mjs';
    injectSpeedInsights();
  </script>
  <script type="module">
    import { inject } from '/assets/analytics.mjs';
    inject();
  </script>
</body>
</html>
