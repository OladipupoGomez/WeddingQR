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
    <link rel="icon" type="image/png" href="/assets/adeola-favicon.png?v=3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/style.css">
  </head>
  <body class="login">
    <main class="container py-5 col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
      <img class="admin-logo" src="/assets/adeola-2026-logo.png" alt="Ade and Ola wedding logo">
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

if (isset($_GET['invitation_sent'])) {
  http_response_code(410);
  exit('Invitation sent tracking is temporarily disabled.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['really_remove'])) {
  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Your session expired. Refresh the guest list and try again.');
  }

  $remove_id = $_POST['remove'] ?? '';
  $guest = is_string($remove_id) ? get_guest_record_by_id($remove_id) : null;

  if (!$guest) {
    $_SESSION['admin_removal_notice'] = [
      'success' => false,
      'message' => 'No guest was found. The guest list may already have been updated.',
    ];
  } elseif (!DEMO_MODE && send_guest_uninvitation($guest) === false) {
    $_SESSION['admin_removal_notice'] = [
      'success' => false,
      'message' => 'The guest was kept because the uninvitation email could not be sent. ' . (supabase_last_error() ?: 'Email delivery failed.'),
    ];
  } elseif (delete_data_record(DATA_DIR . '/guests.json', $remove_id)) {
    $_SESSION['admin_removal_notice'] = [
      'success' => true,
      'message' => DEMO_MODE
        ? 'The guest was removed from the demo. No email was sent.'
        : 'The uninvitation email was sent, and the guest record and QR access were removed.',
    ];
  } else {
    $_SESSION['admin_removal_notice'] = [
      'success' => false,
      'message' => 'The email was sent, but the guest record could not be removed. ' . (supabase_last_error() ?: 'Storage request failed.'),
    ];
  }

  header('Location: /admin');
  exit;
}

$checkin_notice = '';
$removal_notice = $_SESSION['admin_removal_notice'] ?? null;
unset($_SESSION['admin_removal_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['scan_guest']) || isset($_POST['ajax_checkin']))) {
  header('Content-Type: application/json; charset=utf-8');

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Your session expired. Refresh the scanner and try again.']);
    exit;
  }

  $guest_id = trim(is_string($_POST['guest_id'] ?? null) ? $_POST['guest_id'] : '');
  if (!is_valid_md5($guest_id)) $guest_id = md5($guest_id);
  if (isset($_POST['scan_guest'])) {
    $lookup = get_guest_with_checkin_by_id($guest_id);
    if (!$lookup) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'message' => 'Guest not found. This QR code may have been disabled.']);
      exit;
    }

    $guest = $lookup['guest'];
    echo json_encode([
      'ok' => true,
      'status' => $lookup['already_checked_in'] ? 'already_checked_in' : 'needs_confirmation',
      'guest' => [
        'id' => $guest['id'] ?? $guest_id,
        'name' => $guest['name'] ?? '',
        'email' => $guest['email'] ?? '',
        'phone' => $guest['phone'] ?? '',
        'category' => $guest['label'] ?? '',
        'group_size' => max(1, (int)($guest['group_size'] ?? 1)),
      ],
    ]);
    exit;
  }

  $group_size = max(1, (int)($_POST['group_size'] ?? 1));
  $result = create_checkin_record($guest_id, $group_size);
  if ($result === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Check-in could not be saved. ' . (supabase_last_error() ?: 'Please try again.')]);
    exit;
  }

  echo json_encode([
    'ok' => true,
    'status' => $result === true ? 'checked_in' : 'already_checked_in',
    'message' => $result === true ? 'Guest checked in.' : 'This guest has already been checked in.',
  ]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in_guest'])) {
  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $guest_id = trim($_POST['guest_id'] ?? '');
  $guest = get_guest_record_by_id($guest_id);

  if (!$guest) {
    $checkin_notice = 'Guest not found.';
  } else {
    $guest_count = max(1, (int)($guest['group_size'] ?? 1));
    $result = create_checkin_record($guest_id, $guest_count);
    if ($result === true) {
      $checkin_notice = 'Checked in ' . $guest['name'] . '.';
    } elseif ($result === 'exists') {
      $checkin_notice = $guest['name'] . ' is already checked in.';
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
  <link rel="stylesheet" href="/assets/style.css?v=4">
  <link rel="icon" type="image/png" href="/assets/adeola-favicon.png?v=3">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</head>

<body>
  <header class="py-3 border-bottom">
    <div class="container d-flex align-items-center justify-content-between">
      <div class="home-link d-flex me-auto">
        <a href="/admin" aria-label="Admin home">
          <img class="admin-logo me-0 me-md-1" src="/assets/adeola-2026-logo.png" alt="Ade and Ola wedding logo">
        </a>
        <div class="tagline ms-0 ms-md-3 mt-0 mt-md-2">The wedding of the century.</div>
      </div>
      <a href="/?" class="me-3" aria-label="View wedding website" title="Wedding website"><span class="bi-house"></span></a>
      <a href="/admin?csv" class="me-3" aria-label="Guest data" title="Guest data"><span class="bi-database"></span></a>
      <a href="/login.php?logout"><span class="bi-box-arrow-right me-1"></span>Log out</a>
    </div>
  </header>
  <div class="container pt-4 pb-5">
    <?php if ($checkin_notice !== '') : ?>
      <div class="alert alert-info" role="status"><?= htmlspecialchars($checkin_notice, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (is_array($removal_notice) && isset($removal_notice['message'])) : ?>
      <div class="alert <?= !empty($removal_notice['success']) ? 'alert-success' : 'alert-danger'; ?> admin-removal-notice" role="status" aria-live="polite">
        <span class="bi-<?= !empty($removal_notice['success']) ? 'check-circle' : 'exclamation-circle'; ?> me-2" aria-hidden="true"></span>
        <strong><?= !empty($removal_notice['success']) ? 'Guest removed' : 'Guest not removed'; ?></strong>
        <span><?= htmlspecialchars($removal_notice['message'], ENT_QUOTES, 'UTF-8'); ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
      </div>
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

    if (in_array($option, ['register', 'edit', 'remove', 'csv', 'import_csv'], true) || isset($_GET['invitation_sent']))
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
</body>

</html>
