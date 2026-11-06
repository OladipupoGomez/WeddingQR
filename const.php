<?php 
error_reporting(1);

define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('UTIL_DIR', BASE_DIR . '/util');

$local_config = BASE_DIR . '/config.local.php';
if (file_exists($local_config)) {
	require_once $local_config;
}

function app_env($name, $default = '')
{
	$value = getenv($name);
	return ($value === false || $value === '') ? $default : $value;
}

if (!defined('SUPABASE_PROJECT_URL')) define('SUPABASE_PROJECT_URL', app_env('SUPABASE_PROJECT_URL'));
if (!defined('SUPABASE_PUBLIC_KEY')) define('SUPABASE_PUBLIC_KEY', app_env('SUPABASE_PUBLIC_KEY'));
if (!defined('SUPABASE_PRIVATE_SERVICE_KEY')) define('SUPABASE_PRIVATE_SERVICE_KEY', app_env('SUPABASE_PRIVATE_SERVICE_KEY'));
if (!defined('SUPABASE_SCHEMA')) {
	$schema_name = app_env('SUPABASE_SCHEMA', 'adeola_wedding');
	define('SUPABASE_SCHEMA', preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $schema_name) ? $schema_name : 'adeola_wedding');
}
if (!defined('ADEOLA_APP_URL')) define('ADEOLA_APP_URL', app_env('ADEOLA_APP_URL', 'http://localhost:8001'));
if (!defined('ADEOLA_GIFT_REGISTRY_URL')) define('ADEOLA_GIFT_REGISTRY_URL', app_env('ADEOLA_GIFT_REGISTRY_URL', 'https://giftdice.com/'));
if (!defined('ADEOLA_PHOTO_UPLOAD_URL')) define('ADEOLA_PHOTO_UPLOAD_URL', app_env('ADEOLA_PHOTO_UPLOAD_URL'));
if (!defined('ADEOLA_PHOTO_VIEW_URL')) {
	$photo_view_url = app_env('ADEOLA_PHOTO_VIEW_URL');
	if ($photo_view_url === '') $photo_view_url = app_env('ADEOLA_SHARED_PHOTOS_URL', defined('ADEOLA_SHARED_PHOTOS_URL') ? ADEOLA_SHARED_PHOTOS_URL : '');
	define('ADEOLA_PHOTO_VIEW_URL', $photo_view_url);
}
if (!defined('ADEOLA_REGISTRY_BANK_NAME')) define('ADEOLA_REGISTRY_BANK_NAME', app_env('ADEOLA_REGISTRY_BANK_NAME'));
if (!defined('ADEOLA_REGISTRY_ACCOUNT_NAME')) define('ADEOLA_REGISTRY_ACCOUNT_NAME', app_env('ADEOLA_REGISTRY_ACCOUNT_NAME'));
if (!defined('ADEOLA_REGISTRY_ACCOUNT_NUMBER')) define('ADEOLA_REGISTRY_ACCOUNT_NUMBER', app_env('ADEOLA_REGISTRY_ACCOUNT_NUMBER'));

if (!defined('ADEOLA_DEMO_MODE') && app_env('ADEOLA_DEMO_MODE') !== '') {
	define('ADEOLA_DEMO_MODE', filter_var(app_env('ADEOLA_DEMO_MODE'), FILTER_VALIDATE_BOOLEAN));
}

define('SUPABASE_URL', defined('SUPABASE_PROJECT_URL') ? rtrim(SUPABASE_PROJECT_URL, '/') : '');
define('SUPABASE_ANON_KEY', defined('SUPABASE_PUBLIC_KEY') ? SUPABASE_PUBLIC_KEY : '');
define('SUPABASE_SERVICE_ROLE_KEY', defined('SUPABASE_PRIVATE_SERVICE_KEY') ? SUPABASE_PRIVATE_SERVICE_KEY : '');
if (!defined('RSVP_EMAIL_FUNCTION_SECRET')) define('RSVP_EMAIL_FUNCTION_SECRET', app_env('RSVP_EMAIL_FUNCTION_SECRET'));
define('DEMO_MODE', defined('ADEOLA_DEMO_MODE')
	? (bool)ADEOLA_DEMO_MODE
	: !(SUPABASE_URL !== '' && SUPABASE_ANON_KEY !== '' && SUPABASE_SERVICE_ROLE_KEY !== ''));
define('APP_URL', rtrim(ADEOLA_APP_URL, '/'));
define('GIFT_REGISTRY_URL', defined('ADEOLA_GIFT_REGISTRY_URL') ? ADEOLA_GIFT_REGISTRY_URL : 'https://giftdice.com/');
define('PHOTO_UPLOAD_URL', ADEOLA_PHOTO_UPLOAD_URL);
define('PHOTO_VIEW_URL', ADEOLA_PHOTO_VIEW_URL);
define('REGISTRY_BANK_NAME', app_env('ADEOLA_REGISTRY_BANK_NAME', ADEOLA_REGISTRY_BANK_NAME !== '' ? ADEOLA_REGISTRY_BANK_NAME : 'Test Bank'));
define('REGISTRY_ACCOUNT_NAME', app_env('ADEOLA_REGISTRY_ACCOUNT_NAME', ADEOLA_REGISTRY_ACCOUNT_NAME !== '' ? ADEOLA_REGISTRY_ACCOUNT_NAME : 'Ade & Ola (Test)'));
define('REGISTRY_ACCOUNT_NUMBER', app_env('ADEOLA_REGISTRY_ACCOUNT_NUMBER', ADEOLA_REGISTRY_ACCOUNT_NUMBER !== '' ? ADEOLA_REGISTRY_ACCOUNT_NUMBER : '0000000000'));
define('EVENT_MAPS_URL', 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode('Zion Hall and Events, Ire-Akari, Isolo, Lagos State'));
