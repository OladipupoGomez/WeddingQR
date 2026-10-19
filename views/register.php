<?php
require_admin();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['nome'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $phone = trim($_POST['telefone'] ?? '');
  $label = $_POST['label'] ?? '';

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    $message = 'Your session expired. Refresh the page and try again.';
  } elseif ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Enter a name and valid email address.';
  } elseif (!is_valid_phone_number($phone)) {
    $message = 'Enter a valid phone number with 11 to 15 digits, or leave it blank.';
  } elseif (!in_array($label, get_account_labels(), true)) {
    $message = 'Please select a valid guest category.';
  } else {
    $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
    if (find_guest_duplicate($name, $email, $phone, $guests)) {
      $message = 'A user already exists with these details.';
    } else {
      $id = bin2hex(random_bytes(16));
      $guest = [
        'id' => $id,
        'nome' => $name,
        'numero' => 1,
        'email' => $email,
        'telefone' => $phone,
        'label' => $label,
        'attending' => true,
        'enviado' => false,
        'auth_user_id' => null,
      ];
      if (DEMO_MODE) {
        $guests[] = $guest;
        if (!save_data_to_dir(DATA_DIR . '/guests.json', $guests)) {
          $message = 'Guest could not be saved: ' . (supabase_last_error() ?: 'Supabase request failed.');
        } else {
          $message = 'Demo guest saved locally. No invitation email was sent.';
          $success = true;
        }
      } else {
        $existing_profile = get_profile_by_email($email);
        if ($existing_profile) {
          $guest['auth_user_id'] = $existing_profile['id'];
          $guests[] = $guest;
          if (save_data_to_dir(DATA_DIR . '/guests.json', $guests)) {
            $message = 'Guest registered and linked to the existing account.';
            $success = true;
          } else {
            $message = 'Guest could not be saved: ' . (supabase_last_error() ?: 'Supabase request failed.');
          }
        } else {
          $invitation = invite_guest_account($email, $name, $label);
          if ($invitation !== false) {
            $guest['auth_user_id'] = $invitation['id'] ?? $invitation['user']['id'] ?? null;
            $guests[] = $guest;
            if (save_data_to_dir(DATA_DIR . '/guests.json', $guests)) {
              $message = 'Guest registered. An account invitation email has been sent.';
              $success = true;
            } else {
              $message = 'The invitation email was sent, but the guest could not be saved: ' . (supabase_last_error() ?: 'Supabase request failed.');
            }
          } else {
            $message = 'Guest was not added because the invitation email could not be sent: ' . (supabase_last_error() ?: 'Supabase request failed.');
          }
        }
      }
    }
  }
}
?>

<?php if ($message !== '') : ?>
  <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> admin-removal-notice" role="status" aria-live="polite">
    <span class="bi-<?= $success ? 'check-circle' : 'exclamation-circle'; ?> me-2" aria-hidden="true"></span>
    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
  </div>
<?php endif; ?>

<a href="/admin" class="btn btn-outline-primary mb-3 small">Back to dashboard</a>

<form action="" method="post">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <h1>Register guest</h1>
  <div class="mb-3">
    <label for="nome" class="form-label">Name</label>
    <input type="text" class="form-control" id="nome" name="nome" maxlength="120" placeholder="Person or group" value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="guest@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
  </div>
  <div class="mb-3">
    <label for="label" class="form-label">Guest category</label>
    <select class="form-select" id="label" name="label" required>
      <option value="" selected disabled>Select a guest category</option>
      <?php foreach (get_account_labels() as $account_label) : ?>
        <option value="<?= htmlspecialchars($account_label, ENT_QUOTES, 'UTF-8'); ?>" <?= (($_POST['label'] ?? '') === $account_label) ? 'selected' : ''; ?>><?= htmlspecialchars($account_label, ENT_QUOTES, 'UTF-8'); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label for="telefone" class="form-label">Phone</label>
    <input type="tel" class="form-control" id="telefone" name="telefone" maxlength="25" placeholder="555 555 5555" value="<?= htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <button type="submit" class="btn btn-primary">Register guest and send invitation</button>
</form>
