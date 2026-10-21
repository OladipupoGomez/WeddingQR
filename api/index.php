<?php
$project_root = dirname(__DIR__);
chdir($project_root);

$request_path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (str_starts_with($request_path, '/assets/')) {
    $asset_root = realpath($project_root . '/assets');
    $asset_path = realpath($project_root . $request_path);
    if ($asset_root === false || $asset_path === false || !str_starts_with($asset_path, $asset_root . DIRECTORY_SEPARATOR) || !is_file($asset_path)) {
        http_response_code(404);
        exit('Not found.');
    }

    $asset_mime_type = mime_content_type($asset_path) ?: 'application/octet-stream';
    if (strtolower(pathinfo($asset_path, PATHINFO_EXTENSION)) === 'js') {
        $asset_mime_type = 'application/javascript; charset=utf-8';
    }
    header('Content-Type: ' . $asset_mime_type);
    header('Cache-Control: public, max-age=3600');
    readfile($asset_path);
    exit;
}

$entrypoints = [
    '/' => 'index.php',
    '/index.php' => 'index.php',
    '/register' => 'rsvp.php',
    '/admin' => 'admin.php',
    '/invite' => 'invite.php',
    '/login.php' => 'login.php',
    '/get_qr.php' => 'get_qr.php',
    '/get_csv.php' => 'get_csv.php',
];

if (!isset($entrypoints[$request_path])) {
    http_response_code(404);
    exit('Not found.');
}

require $project_root . '/' . $entrypoints[$request_path];
