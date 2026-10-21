<?php
$status = false;
$message = '';

if (isset($_GET['rm_checkin'])) {

  if (!csrf_token_is_valid($_GET['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $id = $_GET['id'];
  $update_index = $_GET['update'];

  $checkin_guests = get_data_from_dir(DATA_DIR . '/checkin.json') ?? [];
  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];

  if (isset($_GET['really_remove'])) {
    // Removal confirmed
    $data = get_guest_by_id($id, $checkin_guests);
    unset($data['updates'][$update_index]);

    if (!count($data['updates'])) {
      remove_guest_by_id($id, $checkin_guests);
      delete_data_record(DATA_DIR . '/checkin.json', $id);
    } else {
      $check_in_updates = array_map(function($update) { return explode(';', $update)[0];  }, $data['updates']);
      $data['check_in_count'] = array_sum($check_in_updates);

      replace_guest_by_id($id, $data, $checkin_guests);
    }

    save_data_to_dir(DATA_DIR . '/checkin.json', $checkin_guests);
    $status = true;
    $message = 'Check-in removed successfully.';
  } else {
    // Confirm removal
    if (!$checkin = get_guest_by_id($id, $checkin_guests)) {
      $status = false;
      $message = 'No check-in record was found for this ID.';
    } else if (!$guest = get_guest_by_id($id, $guests)) {
      $status = false;
      $message = 'No check-in record was found for this ID.';
    } else {
      $update = $checkin['updates'][$update_index];
      $update = explode(';', $update);
      $update_label = $update[0] . ' '
        . (($update[0] == 1) ? 'person' : 'people');

      echo '<script>if(confirm("Are you sure you want to delete this check-in for ' . htmlspecialchars($guest['name'], ENT_QUOTES, 'UTF-8') . '? ' .
        '\n' . $update_label . ' - ' . date('d/m/Y H:i:s', strtotime($update[1])) . '"))'
        . 'window.location.href = "?rm_checkin&id=' . rawurlencode($id) . '&update=' . (int)$update_index . '&really_remove&csrf_token=' . rawurlencode($_GET['csrf_token']) . '"; '
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
