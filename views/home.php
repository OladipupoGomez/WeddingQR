<?php
$guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
$checkin_guests = get_data_from_dir(DATA_DIR . '/checkin.json') ?? [];
?>

<?php if (is_admin()) : ?>
  <a href="?register" class="btn btn-outline-primary">Add guests</a>
<?php endif; ?>
<a href="?scanner" class="btn btn-outline-primary ms-2">QR code scanner</a>

<?php /* if (is_admin()) : ?>
<section class="mt-4 mb-5" aria-labelledby="account-directory-title">
  <h2 id="account-directory-title">Accounts</h2>
  <?php if ($accounts) : ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th scope="col">Email</th>
            <th scope="col">Name</th>
            <th scope="col">Guest category</th>
            <th scope="col">Created</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($accounts as $account) : ?>
            <tr>
              <td><?= htmlspecialchars($account['email'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?= htmlspecialchars($account['display_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?= htmlspecialchars($account['label'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?= htmlspecialchars($account['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else : ?>
    <p>No accounts have been created yet.</p>
  <?php endif; ?>
</section>
<?php endif; */ ?>

<?php
$s = '';
if (isset($_GET['s']) && $_GET['s'] != '') {
  $s = is_string($_GET['s']) ? $_GET['s'] : '';

  $guests = filter_guests($s, $guests) ?: [];

  $search_args = "s=$s";
}
$order = $_GET['order'] ?? '';
if (!is_string($order)) $order = '';
if (in_array($order, ['AZ', 'ZA'], true)) {
  $guests = order_guests_by_name($guests, $order);
}
$filter_checkin = $_GET['filter_checkin'] ?? '';
if (!is_string($filter_checkin)) $filter_checkin = '';
$filter_category = $_GET['filter_category'] ?? '';
if (!is_string($filter_category) || !in_array($filter_category, get_account_labels(), true)) $filter_category = '';
if ($filter_checkin === '1') {
  $guests = filter_guests_that_checked_in(true, $guests, $checkin_guests);
} elseif ($filter_checkin === '2') {
  $guests = filter_guests_that_checked_in(false, $guests, $checkin_guests);
}
if ($filter_category !== '') {
  $guests = array_values(array_filter($guests ?: [], function ($guest) use ($filter_category) {
    return ($guest['label'] ?? '') === $filter_category;
  }));
}
?>

<form class="home-heading d-flex flex-column flex-sm-row align-items-center justify-content-between mb-3">
  <div class="title">
    <small><a href="/admin">Refresh</a></small>
    <h2>All guests</h2>
  </div>
  <div class="search d-flex align-items-center">
    <a class="bi-arrow-clockwise" href="/admin" title="Refresh"></a>
    <input type="hidden" name="order" value="<?= htmlspecialchars($order, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="filter_checkin" value="<?= htmlspecialchars($filter_checkin, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="filter_category" value="<?= htmlspecialchars($filter_category, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" class="form-control my-2 my-sm-0" name="s" value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search">
    <button type="submit" class="bi-search"></button>
  </div>
</form>
<form class="guest-filters row g-2 align-items-end mb-3" method="get" action="/admin">
  <?php if ($s !== '') : ?><input type="hidden" name="s" value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <div class="col-12 col-sm-4 col-md-3">
    <label class="form-label mb-1" for="guest-order">Name order</label>
    <select class="form-select" id="guest-order" name="order">
      <option value="" <?= $order === '' ? 'selected' : ''; ?>>Default</option>
      <option value="AZ" <?= $order === 'AZ' ? 'selected' : ''; ?>>A to Z</option>
      <option value="ZA" <?= $order === 'ZA' ? 'selected' : ''; ?>>Z to A</option>
    </select>
  </div>
  <div class="col-12 col-sm-4 col-md-3">
    <label class="form-label mb-1" for="guest-checkin-filter">Check-in</label>
    <select class="form-select" id="guest-checkin-filter" name="filter_checkin">
      <option value="" <?= $filter_checkin === '' ? 'selected' : ''; ?>>All guests</option>
      <option value="1" <?= $filter_checkin === '1' ? 'selected' : ''; ?>>Checked in</option>
      <option value="2" <?= $filter_checkin === '2' ? 'selected' : ''; ?>>Not checked in</option>
    </select>
  </div>
  <div class="col-12 col-sm-4 col-md-3">
    <label class="form-label mb-1" for="guest-category-filter">Guest category</label>
    <select class="form-select" id="guest-category-filter" name="filter_category">
      <option value="" <?= $filter_category === '' ? 'selected' : ''; ?>>All categories</option>
      <?php foreach (get_account_labels() as $category) : ?>
        <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?= $filter_category === $category ? 'selected' : ''; ?>><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-auto"><button class="btn btn-outline-primary" type="submit">Apply filters</button></div>
</form>
<div class="table-responsive">
<table class="table table-hover admin-guests-table">

  <thead>
    <tr>
    <th class="name">Name</th>
    <th class="guest-category">Guest category</th>
    <th class="email">Email</th>
    <th class="phone">Phone</th>
    <th class="checkin">Check-in</th>
    <th class="checkin-status">Check-in status</th>
    <th class="actions"></th>
    </tr>
  </thead>
  <tbody>
    <?php
    if ($guests) :
    ?>
      <?php
      foreach ($guests as $guest) :

        $id = $guest['id'];
        $name = $guest['name'] ?? '';
        $group_size = $guest['group_size'];
        $email = $guest['email'] ?? '';
        $phone = $guest['phone'] ?? '';
        $label = $guest['label'] ?? '';

        $check_in_count = '-';
        $updates = [];

        if ($checkin = get_guest_by_id($id, $checkin_guests)) {
          $check_in_count = $checkin['check_in_count'];
          $updates = $checkin['updates'];
        }

      ?>
        <tr>
          <td class="name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="guest-category"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="email"><?= htmlspecialchars($email ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="phone"><?= htmlspecialchars($phone ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="checkin">
            <?php if (!$checkin) : ?>
              <form method="post" action="/admin" class="checkin-toggle-form">
                <input type="hidden" name="check_in_guest" value="1">
                <input type="hidden" name="guest_id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Check in <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">Check in</button>
              </form>
            <?php else : ?>
              <a href="?checkin=<?= rawurlencode($id); ?>" class="small" title="Adjust checked-in guest count">Adjust</a>
            <?php endif; ?>
          </td>
          <!-- <td class="group-size d-none d-sm-table-cell"><?= $group_size; ?></td> -->
          <td class="checkin-status">
            <?php if ($checkin) : ?>
              <span class="bi-check-lg checkin-status-mark" role="img" aria-label="Checked in"></span>
            <?php else : ?>
              <span class="checkin-status-empty" aria-label="Not checked in">-</span>
            <?php endif; ?>
            <?php if (count($updates)) : ?>
              <div class="dropdown" title="Check-in history">
                <a class="bi-clock" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                </a>

                <ul class="dropdown-menu">
                  <li>
                    <h5 class="dropdown-header">Check-in history</h5>
                  </li>
                  <?php foreach ($updates as $i => $update) :
                    $update = explode(';', $update);
                  ?>
                    <li class="dropdown-item">
                      <span class="bi-check-lg checkin-history-mark" aria-hidden="true"></span>
                      <span class="checkin-history-date"><?= date('d/m/Y H:i', strtotime($update[1])); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </td>
          <td class="actions">
            <div class="wrapper">
              <a href="?remove=<?= rawurlencode($id); ?>" class="bi-trash3" title="Remove"></a>
              <a href="/get_qr.php?id=<?= $id; ?>" download class="bi-qr-code" title="Download QR code"></a>
            </div>
          </td>
        </tr>
      <?php
      endforeach;
      ?>
    <?php
    else :
    ?>
      <tr>
        <td colspan="7" class="small fs-italic">
          No guests found
        </td>
      </tr>
    <?php
    endif;
    ?>
  </tbody>


</table>
</div>
