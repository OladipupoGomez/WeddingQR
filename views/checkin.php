<?php
$status = null;
$message = '';
$redirect = true;
$guest = false;
$already_checked_in = false;

if (isset($_GET['checkin'])) {

  $checkin_id = trim($_GET['checkin']);

  if (!is_valid_md5($checkin_id))
    $checkin_id = md5($checkin_id);

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];

  if (!$guest = get_guest_by_id($checkin_id, $guests)) {
    http_response_code(410);
    $status = false;
    $message = 'This guest QR code is no longer active.';
    $redirect = false;
  } else {
    $guest_name = $guest['name'] ?? '';
    $email = $guest['email'] ?? '';
    $phone = $guest['phone'] ?? '';
    $guest_category = $guest['label'] ?? '';
    $attending = $guest['attending'] ?? true;
    $checked_guests = get_data_from_dir(DATA_DIR . '/checkin.json') ?? [];
    $already_checked_in = (bool)get_guest_by_id($checkin_id, $checked_guests);
  }
} else {
  header('Location: /');
}

// data received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  if (!$guest || $status === false) {
    http_response_code(404);
    exit('Guest not found. Scan a valid guest QR code and try again.');
  }

  $id = $guest['id'];
  $checked_guests = get_data_from_dir(DATA_DIR . '/checkin.json') ?? [];
  if (get_guest_by_id($id, $checked_guests)) {
    $already_checked_in = true;
    $status = false;
    $message = 'This guest has already been checked in.';
  } else {
    $data = [
      'id' => $id,
      'check_in_count' => max(1, (int)($guest['group_size'] ?? 1)),
      'updates' => [max(1, (int)($guest['group_size'] ?? 1)) . ';' . date('Y-m-d H:i:s')],
    ];
    $checked_guests[] = $data;

    if (save_data_to_dir(DATA_DIR . '/checkin.json', $checked_guests)) {
      $already_checked_in = true;
      $status = true;
      $message = 'Check-in completed for ' . $guest_name . '.';
    } else {
      $status = false;
      $message = 'Check-in could not be saved: ' . (supabase_last_error() ?: 'storage request failed.');
    }
  }
}

if ($message != '') {
  $status_label = ($status) ? 'SUCCESS' : 'ERROR';
  echo '<script>alert("' . $status_label . '\n' . $message . '");';
  echo ($redirect) ? 'window.location.href = "?";</script>' : '</script>';
}


?>
<a href="?" class="btn btn-outline-primary mb-3 small">Back to home</a>
<?php if ($guest) : ?>
<form action="" method="post" class="checkin">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <h1>Confirm guest</h1>
  <h2>Guest details</h2>
  <div class="mb-3 checkin-data">
    --
    <div class="mb-3 no me">
      <b>Name:</b> <span><?= htmlspecialchars($guest_name, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="mb-3">
      <b>Email:</b> <span><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="mb-3">
      <b>Phone:</b> <span><?= htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="mb-3">
      <b>Guest category:</b> <span><?= htmlspecialchars($guest_category, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="mb-3">
      <b>RSVP:</b> <span><?= $attending ? 'Attending' : 'Declined'; ?></span>
    </div>
    <div class="mb-3">
      <b>Check-in status:</b> <span><?= $already_checked_in ? 'Checked in' : 'Not checked in'; ?></span>
    </div>
    --
  </div>
  <div class="d-flex align-items-center">
    <?php if ($already_checked_in) : ?>
      <span class="btn btn-success disabled" aria-disabled="true">Already checked in</span>
    <?php elseif ($guest) : ?>
      <button type="submit" class="btn btn-primary">Confirm check-in</button>
    <?php endif; ?>
  </div>
</form>
<?php else : ?>
  <p>This guest QR code is no longer active.</p>
  <a href="/admin" class="btn btn-outline-primary">Back to dashboard</a>
<?php endif; ?>
