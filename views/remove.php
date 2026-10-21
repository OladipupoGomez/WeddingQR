<?php
$remove_id = $_GET['remove'] ?? '';

if (!is_string($remove_id) || $remove_id === '') {
  http_response_code(400);
  echo '<div class="alert alert-danger" role="alert">A valid guest was not specified.</div>';
  return;
}

$guest = get_guest_record_by_id($remove_id);
if (!$guest) {
  http_response_code(404);
  ?>
  <section class="admin-remove-panel" aria-labelledby="remove-guest-title">
    <div class="admin-remove-icon is-error"><span class="bi-person-x" aria-hidden="true"></span></div>
    <p class="admin-eyebrow">Guest management</p>
    <h2 id="remove-guest-title">Guest not found</h2>
    <p>This guest may already have been removed from the list.</p>
    <a href="/admin" class="btn btn-outline-secondary">Back to guests</a>
  </section>
  <?php
  return;
}

$guest_name = $guest['name'] ?? 'this guest';
$guest_email = $guest['email'] ?? '';
?>
<section class="admin-remove-panel" aria-labelledby="remove-guest-title">
  <div class="admin-remove-icon"><span class="bi-person-dash" aria-hidden="true"></span></div>
  <p class="admin-eyebrow">Guest management</p>
  <h2 id="remove-guest-title">Remove this guest?</h2>
  <p class="admin-remove-intro">This will remove the guest from your list and disable their QR code.</p>

  <div class="admin-remove-guest">
    <span class="bi-person-circle" aria-hidden="true"></span>
    <div>
      <strong><?= htmlspecialchars($guest_name, ENT_QUOTES, 'UTF-8'); ?></strong>
      <?php if ($guest_email !== '') : ?>
        <span><?= htmlspecialchars($guest_email, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" action="/admin" class="admin-remove-actions">
    <input type="hidden" name="remove" value="<?= htmlspecialchars($remove_id, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="really_remove" value="1">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <a href="/admin" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-danger"><span class="bi-trash3 me-2" aria-hidden="true"></span>Send notice &amp; remove</button>
  </form>
</section>
