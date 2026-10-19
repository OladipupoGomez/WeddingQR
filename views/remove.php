<?php
$status = false;
$message = '';

if (isset($_GET['remove'])) {

  if (!csrf_token_is_valid($_GET['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $id = $_GET['remove'];

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];

  if (isset($_GET['really_remove'])) {
    $guest = get_guest_by_id($id, $guests);
    if (!$guest) {
      $message = 'No guest was found for removal.';
    } elseif (!DEMO_MODE && send_guest_uninvitation($guest) === false) {
      $message = 'The guest was not removed because the uninvitation email could not be sent: ' . (supabase_last_error() ?: 'Email delivery failed.');
    } elseif (delete_data_record(DATA_DIR . '/guests.json', $id)) {
      $status = true;
      $message = DEMO_MODE
        ? 'Guest removed from the demo. No email was sent.'
        : 'Guest removed and an uninvitation email was sent.';
    } else {
      $message = 'The uninvitation email was sent, but the guest record could not be removed: ' . (supabase_last_error() ?: 'Storage request failed.');
    }
  } else {
    // Confirm removal
    if (!$guest = get_guest_by_id($id, $guests)) {
      $status = false;
      $message = 'No guest was found with this ID.';
    } else {
      echo '<script>if(confirm("Are you sure you want to delete guest ' . htmlspecialchars($guest['nome'], ENT_QUOTES, 'UTF-8') . '?"))'
        . 'window.location.href = "?remove=' . rawurlencode($id) . '&really_remove&csrf_token=' . rawurlencode($_GET['csrf_token']) . '"; '
        . 'else window.location.href = "?"; </script>';
    }
  }
} else {
  header('Location: /');
}


if ($message != '') {
  $status_label = ($status) ? 'SUCCESS' : 'ERROR';
  echo '<script>alert("' . $status_label . '\n' . $message . '");'
    . 'window.location.href = "?";</script>';
}
