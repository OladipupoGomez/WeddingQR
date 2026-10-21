<a class="btn btn-outline-primary" href="/admin">Back to dashboard</a>

<?php
$message = '';

// data received
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $file_tmp_name = $_FILES['file']['tmp_name'];
  $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

  if ($file_extension != 'csv') {
    die("Invalid file type $file_extension - must be csv");
  }

  $csv_data = array_map('str_getcsv', file($file_tmp_name));
  $keys = array_shift($csv_data);

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
  foreach ($csv_data as $row) {
    $row = array_slice(array_pad($row, count($keys), null), 0, count($keys));

    $new_guest = normalize_guest_record(array_combine($keys, $row));
    $new_guest['group_size'] = (int)($new_guest['group_size'] ?? 0);
    $new_guest['id'] = md5($new_guest['name']);


    if ($found_guest = get_guest_by_id($new_guest['id'], $guests)) {
      if (!isset($new_guest['invitation_sent']))
        $new_guest['invitation_sent'] = (isset($found_guest['invitation_sent']) && $found_guest['invitation_sent'] != '') ? $found_guest['invitation_sent'] : false;
      else {
        $new_guest['invitation_sent'] = boolval($new_guest['invitation_sent']);
      }
      replace_guest_by_id($new_guest['id'], $new_guest, $guests);
    } else {
      array_push($guests, $new_guest);
    }
  }

  if (save_data_to_dir(DATA_DIR . '/guests.json', $guests)) {
    $message = 'Import completed successfully.';
  } else {
    $message = 'Import failed: ' . (supabase_last_error() ?: 'storage request failed.');
  }

}

?>
<?php if ($message !== '') : ?>
  <div class="alert alert-info" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
