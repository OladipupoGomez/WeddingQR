<?php
require_once __DIR__ . '/bootstrap.php';

$message = '';
$message_type = 'error';

if (isset($_GET['sent'])) {
  $message = DEMO_MODE
    ? 'Thank you. Your RSVP is saved for the demo. Email confirmations are not sent in demo mode.'
    : (isset($_GET['email_error'])
      ? 'Your RSVP was saved, but its email confirmation could not be sent. Please contact the wedding hosts.'
      : "Thank you! Your RSVP is confirmed. We’ve sent a confirmation to your email address (please check your spam folder if you don't see it shortly). See you on November 14th!");
  $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $phone = trim($_POST['phone'] ?? '');
  $label = $_POST['label'] ?? '';
  $attending = true;

  if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    $message = 'Your form expired. Refresh the page and try again.';
  } elseif (trim($_POST['website'] ?? '') !== '') {
    header('Location: /register?sent=1');
    exit;
  } elseif ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Please enter your name and a valid email address.';
  } elseif (!is_valid_phone_number($phone)) {
    $message = 'Please enter a valid phone number with 11 to 15 digits, or leave it blank.';
  } elseif (!in_array($label, get_account_labels(), true)) {
    $message = 'Please select your guest category.';
  } else {
    $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
    $existing_guest = false;
    $email_matches = [];
    foreach ($guests as $guest) {
      if (strtolower(trim((string)($guest['email'] ?? ''))) === $email) {
        $email_matches[] = $guest;
      }
    }

    if (count($email_matches) > 1) {
      $message = 'This email address is already linked to more than one guest. Please contact the wedding hosts.';
    } elseif ($email_matches) {
      $existing_guest = $email_matches[0];
    }

    if ($message === '') {
      if (find_guest_duplicate($name, $email, $phone, $guests, $existing_guest['id'] ?? null)) {
        $message = 'An RSVP already exists with this name, email address, or phone number. Please check your details or contact the wedding hosts.';
      }
    }

    if ($message === '') {
      $guest_id = $existing_guest ? $existing_guest['id'] : bin2hex(random_bytes(16));
      $guest = [
        'id' => $guest_id,
        'nome' => $name,
        'numero' => 1,
        'email' => $email,
        'telefone' => $phone,
        'enviado' => $existing_guest['enviado'] ?? false,
        'auth_user_id' => $existing_guest['auth_user_id'] ?? null,
        'label' => $label,
        'attending' => $attending,
      ];

      if ($existing_guest) {
        replace_guest_by_id($guest_id, $guest, $guests);
      } else {
        $guests[] = $guest;
      }

      if (save_data_to_dir(DATA_DIR . '/guests.json', $guests)) {
        $email_sent = send_rsvp_confirmation($guest);
        header('Location: /register?sent=1' . (($email_sent === false) ? '&email_error=1' : ''));
        exit;
      }
      $message = 'We could not save your RSVP. Please try again later.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#f5f2eb">
  <title>RSVP | ADE &amp; OLA</title>
  <link rel="icon" type="image/png" href="/assets/adeola-2026-logo.png?v=2">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/wedding.css">
</head>
<body>
  <main class="rsvp-page">
    <header class="rsvp-header">
      <a class="wordmark" href="/" aria-label="ADE & OLA home"><img class="rsvp-logo" src="/assets/adeola-2026-logo.png" alt="Ade and Ola wedding logo"></a>
      <a href="/">Return to invitation</a>
    </header>
    <div class="rsvp-layout">
      <section class="rsvp-intro">
        <p class="eyebrow">THE WEDDING OF THE CENTURY</p>
        <h1>Save your<br>place.</h1>
        <p class="rsvp-script">We look forward to celebrating with you</p>
        <p>Please reply for yourself. You can update your response later using the same email address.</p>
      </section>
      <section class="rsvp-form-wrap" aria-labelledby="rsvp-title">
        <h2 id="rsvp-title"><?= isset($_GET['sent']) ? 'Thank you for your Response' : 'Kindly RSVP'; ?></h2>
        <?php if ($message !== '') : ?>
          <div class="rsvp-notice <?= $message_type === 'error' ? 'error' : ''; ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!isset($_GET['sent'])) : ?>
          <form class="rsvp-form" method="post" action="/register">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="honeypot" aria-hidden="true">
              <label for="website">Leave this field empty</label>
              <input id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-field">
              <label for="name">Your name</label>
              <input id="name" name="name" maxlength="120" autocomplete="name" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-field">
              <label for="email">Email address</label>
              <input id="email" name="email" type="email" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-field">
              <label for="phone">Phone number <span>(optional)</span></label>
              <input id="phone" name="phone" type="tel" maxlength="25" autocomplete="tel" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-field">
              <label for="label">Guest category</label>
              <select id="label" name="label" required>
                <option value="" disabled <?= empty($_POST['label']) ? 'selected' : ''; ?>>Select your guest category</option>
                <?php foreach (get_account_labels() as $account_label) : ?>
                  <option value="<?= htmlspecialchars($account_label, ENT_QUOTES, 'UTF-8'); ?>" <?= (($_POST['label'] ?? '') === $account_label) ? 'selected' : ''; ?>><?= htmlspecialchars($account_label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="button button-dark rsvp-submit" type="submit">Send RSVP <span aria-hidden="true">↗</span></button>
          </form>
        <?php endif; ?>
      </section>
    </div>
  </main>
</body>
</html>
