<a href="?" class="btn btn-outline-primary mb-3 small">Back to home</a>
<br>
<a href="get_csv.php?data_type=guests" class="btn btn-primary" download>
  <span class="bi-download me-2"></span>
  <span>Download guest list CSV</span>
</a>
<a href="get_csv.php?data_type=checkin" class="btn btn-primary ms-2" download>
  <span class="bi-download me-2"></span>
  <span>Download check-in CSV</span>
</a>
<br>
<a href="?csv&import" class="btn btn-secondary">
  <span class="bi-upload me-2"></span>
  <span>Upload CSV</span>
</a>

<?php
if (isset($_GET['import'])) :
?>

  <form action="?import_csv" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <h1>Upload CSV</h1>
    <p>A <a href="https://rockcontent.com/br/blog/csv/" target="_blank" title="What CSV is and how to export or import it">CSV</a> file can be exported from Excel or Google Sheets.</p>
    <div class="mb-3">
      <input type="file" class="form-control" id="file" name="file" required>
    </div>
    <div class="d-flex align-items-center">
      <button type="submit" class="btn btn-primary">Continue</button>
    </div>
  </form>

<?php
endif;
?>