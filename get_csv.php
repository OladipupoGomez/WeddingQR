<?php
require_once __DIR__ . '/bootstrap.php';

if (!refresh_session_profile()) {
  header('Location: login.php');
  exit;
}
require_admin();

$data_type = isset($_GET['data_type']) ? $_GET['data_type'] : 'guests';

// Function to convert JSON to CSV
function convert_to_csv($data)
{
  
  // Initialize CSV content
  $csv = '';

  if (!$data || !is_array($data)) {
    return $csv; 
  }

  // Write the CSV header row with the keys from the first record in the JSON data
  $csv .= implode(',', array_keys($data[0])) . "\r\n";

  // Write the data rows
  foreach ($data as $row) {
    // Escape and convert the values to UTF-8
    foreach ($row as $key => $value) {
      $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    $csv .= implode(',', $row) . "\r\n";
  }

  // Convert the CSV content to UTF-8 with BOM
  $csv = "\xEF\xBB\xBF" . $csv;

  return $csv;
}

$data = get_data_from_dir(DATA_DIR . "/$data_type.json") ?? [];

try {
  $csv = convert_to_csv($data);

  $filename = "$data_type.csv";

  // Set appropriate headers to force download
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  // Output the CSV content to the browser
  echo $csv;
} catch (Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
