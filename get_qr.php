<?php
require_once __DIR__ . '/bootstrap.php';

if (!refresh_session_profile()) {
  header('Location: login.php');
  exit;
}

if (isset($_GET['id'])) {
  $id = $_GET['id'];

  $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
  
  if (!$guest = get_guest_by_id($id, $guests)) {
    http_response_code(410);
    exit('This guest QR code is no longer active.');
  } else {
    $nome = $guest['nome'];
    $email = $guest['email'];
    $telefone = $guest['telefone'];
    $numero = $guest['numero'];
  }
} 
else {
  http_response_code(404);
  exit('Guest QR code not found.');
}

$qr_png = generate_guest_qr_png($id);
if ($qr_png === false) {
  http_response_code(500);
  exit('Could not generate the guest QR code.');
}

// Directly output the QR code
$filename = str_replace(' ', '-', $nome) . '.png';
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: image/png');
echo $qr_png;
