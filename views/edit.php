<?php
$status = null;
$message = '';
$redirect = true;

if (isset($_GET['edit'])) {
  
  $edit_id = $_GET['edit'];

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
  
  if (!$guest = get_guest_by_id($edit_id, $guests)) {
    $status = false;
    $message = 'No guest was found.';
  } else {
    $name = $guest['name'];
    $email = $guest['email'];
    $phone = $guest['phone'];
    $group_size = $guest['group_size'];
  }

} else {
  header('Location: /');
}

// data received
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
  }

  $id = md5($_POST['name']);

  $data = array(
    'id' => $id,
    ...$_POST
  );

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];

  // If user changed id (changed name), check if guest exists with name
  if ($id != $edit_id && guest_exists($id, $guests)) {
    $status = false;
    $message = 'A guest with this name already exists. Please use a different name or edit the existing record.';
    $redirect = false;
  } else {
    $existing_guest = get_guest_by_id($edit_id, $guests);
    $data['id'] = $edit_id;
    $data['invitation_sent'] = $existing_guest['invitation_sent'] ?? false;
    $data['auth_user_id'] = $existing_guest['auth_user_id'] ?? null;
    replace_guest_by_id($edit_id, $data, $guests);
    
    save_data_to_dir(DATA_DIR . '/guests.json', $guests);
    
    $status = true;
    $message = 'Guest updated successfully.';
  }
}

if ($message != '') {
  $status_label = ($status) ? 'SUCCESS' : 'ERROR';
  echo '<script>alert("' . $status_label . '\n' . $message . '");';
  echo ($redirect) ? 'window.location.href = "?";</script>' :'</script>';
}

?>

<a href="?" class="btn btn-outline-primary mb-3 small">Back to home</a>

<form action="" method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <h1>Edit guest</h1>
  <div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name" placeholder="Person or group" value="<?= $_POST['name'] ?? $name ?? ''; ?>" required>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="email@example.com" value="<?= $_POST['email'] ?? $email ?? ''; ?>">
  </div>
  <div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input type="tel" class="form-control" id="phone" name="phone" placeholder="555 555 5555" value="<?= $_POST['phone'] ?? $phone ?? ''; ?>">
  </div>
  <div class="mb-3">
    <label for="group_size" class="form-label">Number of people in group</label>
    <input type="number" class="form-control" id="group_size" name="group_size" required value="<?= $_POST['group_size'] ?? $group_size ?? ''; ?>">
  </div>
  <div class="d-flex align-items-center">
    <button type="submit" class="btn btn-primary">Save changes</button>
  </div>
</form>