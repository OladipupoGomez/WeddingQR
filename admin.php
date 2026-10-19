<?php
require_once __DIR__ . '/bootstrap.php';

if (!refresh_session_profile()) {
  header('Location: /login.php?return_to=%2Fadmin');
  exit;
}
if (!is_admin()) {
  http_response_code(403);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin access required | ADE &amp; OLA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/style.css">
  </head>
  <body class="login">
    <main class="container py-5 col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
      <div class="brand fs-2 fw-light lh-1">ADE &amp; OLA</div>
      <h1 class="mt-4">Admin access required</h1>
      <p>This account is signed in, but it does not have the administrator role. Sign out and log in with your admin account.</p>
      <a class="btn btn-primary" href="/login.php?logout">Sign out</a>
      <a class="btn btn-outline-secondary ms-2" href="/">Wedding invitation</a>
    </main>
  </body>
  </html>
  <?php
  exit;
}

if (isset($_GET['rm_checkin'])) {
  http_response_code(403);
  exit('Check-ins cannot be undone.');
}

if (isset($_GET['enviado'])) {
  http_response_code(410);
  exit('Invitation sent tracking is temporarily disabled.');
}

$checkin_notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in_guest'])) {
  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $guest_id = trim($_POST['guest_id'] ?? '');
  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
  $guest = get_guest_by_id($guest_id, $guests);
  $checkins = get_data_from_dir(DATA_DIR . '/checkin.json') ?? [];
  $checkin = get_guest_by_id($guest_id, $checkins);

  if (!$guest) {
    $checkin_notice = 'Guest not found.';
  } elseif ($checkin) {
    $checkin_notice = $guest['nome'] . ' is already checked in.';
  } else {
    $guest_count = max(1, (int)($guest['numero'] ?? 1));
    $checkins[] = [
      'id' => $guest_id,
      'numero' => $guest_count,
      'updates' => [$guest_count . ';' . date('Y-m-d H:i:s')],
    ];
    if (save_data_to_dir(DATA_DIR . '/checkin.json', $checkins)) {
      $checkin_notice = 'Checked in ' . $guest['nome'] . '.';
    } else {
      $checkin_notice = 'Could not update check-in status: ' . (supabase_last_error() ?: 'storage request failed.');
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ADE &amp; OLA | Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/assets/style.css?v=0">
  <link rel="shortcut icon" href="/assets/favicon.svg" type="image/svg">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</head>

<body>
  <header class="py-3 border-bottom">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="/admin" class="home-link d-flex me-auto">
        <div class="brand fs-2 fw-light lh-1 me-0 me-md-1">ADE &amp; OLA</div>
        <div class="tagline ms-0 ms-md-3 mt-0 mt-md-2">The wedding of the century.</div>
      </a>
      <a href="/?" class="me-3" aria-label="View wedding website" title="Wedding website"><span class="bi-house"></span></a>
      <a href="/admin?csv" class="me-3" aria-label="Guest data" title="Guest data"><span class="bi-database"></span></a>
      <a href="/login.php?logout"><span class="bi-box-arrow-right me-1"></span>Log out</a>
    </div>
  </header>
  <div class="container pt-4 pb-5">
    <?php if ($checkin_notice !== '') : ?>
      <div class="alert alert-info" role="status"><?= htmlspecialchars($checkin_notice, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php
    $option = 'home';

    if (isset($_GET['register']))
      $option = 'register';
    if (isset($_GET['remove']))
      $option = 'remove';
    if (isset($_GET['edit']))
      $option = 'edit';
    if (isset($_GET['checkin']))
      $option = 'checkin';
    if (isset($_GET['scanner']))
      $option = 'scanner';
    if (isset($_GET['rm_checkin']))
      $option = 'rm_checkin';
    if (isset($_GET['csv']))
      $option = 'csv';
    if (isset($_GET['import_csv']))
      $option = 'import_csv';

    if (in_array($option, ['register', 'edit', 'remove', 'csv', 'import_csv'], true) || isset($_GET['enviado']))
      require_admin();

    if ($option === 'edit') : ?>
      <div class="alert alert-info" role="status">
        Guest editing is temporarily disabled.
        <a href="/admin" class="alert-link">Return to the dashboard</a>
      </div>
    <?php else :
      include "views/$option.php";
    endif;
    ?>
  </div>
  <footer class="py-3">
    <div class="container"><div>ADE &amp; OLA@2026</div></div>
  </footer>
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
