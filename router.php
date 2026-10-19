<?php
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($request_path === '/register') {
    require __DIR__ . '/rsvp.php';
    return true;
}

if ($request_path === '/admin') {
    require __DIR__ . '/admin.php';
    return true;
}

if ($request_path === '/invite') {
    require __DIR__ . '/invite.php';
    return true;
}

return false;