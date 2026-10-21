<?php

$GLOBALS['supabase_error'] = null;

function get_account_labels()
{
    return [
        'Groomsmen',
        'VIP',
        'ATB',
        'Providus',
        'Bridesmaid',
        'Grooms family',
        'Brides family',
    ];
}

function get_accounts()
{
    if (!is_admin()) return [];
    if (DEMO_MODE) return get_demo_accounts();
    return supabase_request('GET', '/rest/v1/profiles?select=email,display_name,label,role,created_at&order=email.asc') ?: [];
}

function is_demo_mode()
{
    return DEMO_MODE;
}

function get_demo_accounts()
{
    $accounts_file = DATA_DIR . '/demo_accounts.php';
    if (!file_exists($accounts_file)) return [];
    $accounts = require $accounts_file;
    return is_array($accounts) ? $accounts : [];
}

function save_demo_account($email, $password, $display_name, $label)
{
    $accounts = get_demo_accounts();
    foreach ($accounts as $account) {
        if (strtolower($account['email']) === strtolower($email)) return false;
    }

    $accounts[] = [
        'id' => bin2hex(random_bytes(16)),
        'email' => $email,
        'display_name' => $display_name,
        'label' => $label,
        'role' => $accounts ? 'user' : 'admin',
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $contents = "<?php\nreturn " . var_export($accounts, true) . ";\n";
    return file_put_contents(DATA_DIR . '/demo_accounts.php', $contents, LOCK_EX) !== false;
}

function get_demo_account_by_email($email)
{
    foreach (get_demo_accounts() as $account) {
        if (strtolower($account['email']) === strtolower($email)) return $account;
    }
    return false;
}

function supabase_is_configured()
{
    return !DEMO_MODE && SUPABASE_URL !== '' && SUPABASE_ANON_KEY !== '' && SUPABASE_SERVICE_ROLE_KEY !== '';
}

function supabase_request($method, $path, $body = null, $key_type = 'service', $user_token = null, $extra_headers = [])
{
    $key = ($key_type === 'anon') ? SUPABASE_ANON_KEY : SUPABASE_SERVICE_ROLE_KEY;
    if (!supabase_is_configured() || !function_exists('curl_init')) {
        $GLOBALS['supabase_error'] = 'Supabase is not configured or PHP cURL is unavailable.';
        return false;
    }

    $curl = curl_init(SUPABASE_URL . $path);
    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
    ];
    // Select the configured schema for Supabase REST calls.
    if (strpos($path, '/rest/v1/') === 0) {
        $profile_header = in_array(strtoupper($method), ['GET', 'HEAD'], true)
            ? 'Accept-Profile'
            : 'Content-Profile';
        $headers[] = $profile_header . ': ' . SUPABASE_SCHEMA;
    }
    foreach ($extra_headers as $header_name => $header_value) {
        $headers[] = $header_name . ': ' . $header_value;
    }
    if (strpos($path, '/auth/v1/') === 0 && $key_type === 'user') {
        $headers[1] = 'Authorization: Bearer ' . ($user_token ?? ($_SESSION['access_token'] ?? ''));
        $headers[0] = 'apikey: ' . SUPABASE_ANON_KEY;
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== null) {
        if ($method === 'POST' && strpos($path, '/rest/v1/') === 0) {
            $headers[] = 'Prefer: resolution=merge-duplicates,return=minimal';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response_body = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($response_body === false || $http_status < 200 || $http_status >= 300) {
        $error_response = json_decode((string)$response_body, true);
        $GLOBALS['supabase_error'] = $curl_error ?: ($error_response['msg'] ?? $error_response['message'] ?? $error_response['error_description'] ?? $error_response['error'] ?? 'Supabase request failed.');
        return false;
    }

    $GLOBALS['supabase_error'] = null;
    if ($response_body === '') return [];
    $decoded = json_decode($response_body, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : false;
}

class SupabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $sessions = supabase_request('GET', '/rest/v1/app_sessions?select=payload&session_id=eq.' . rawurlencode($id) . '&expires_at=gt.' . rawurlencode(gmdate('c')));
        if (!is_array($sessions)) return false;
        return isset($sessions[0]['payload']) ? (string)$sessions[0]['payload'] : '';
    }

    public function validateId(string $id): bool
    {
        $sessions = supabase_request('GET', '/rest/v1/app_sessions?select=session_id&session_id=eq.' . rawurlencode($id) . '&expires_at=gt.' . rawurlencode(gmdate('c')));
        return is_array($sessions) && isset($sessions[0]['session_id']);
    }

    public function write(string $id, string $data): bool
    {
        $lifetime = max(1, (int)ini_get('session.gc_maxlifetime'));
        return supabase_request('POST', '/rest/v1/app_sessions?on_conflict=session_id', [
            'session_id' => $id,
            'payload' => $data,
            'expires_at' => gmdate('c', time() + $lifetime),
        ]) !== false;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }

    public function destroy(string $id): bool
    {
        return supabase_request('DELETE', '/rest/v1/app_sessions?session_id=eq.' . rawurlencode($id)) !== false;
    }

    public function gc(int $max_lifetime): int|false
    {
        $expiry = gmdate('c', time() - max(1, $max_lifetime));
        $deleted = supabase_request('DELETE', '/rest/v1/app_sessions?expires_at=lt.' . rawurlencode($expiry));
        return $deleted === false ? false : 0;
    }
}

function app_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    session_name('ADEOLA_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    if (!DEMO_MODE && supabase_is_configured() && getenv('VERCEL') === '1') {
        session_set_save_handler(new SupabaseSessionHandler(), true);
    }

    session_start();
}

function supabase_last_error()
{
    return $GLOBALS['supabase_error'] ?? null;
}

function get_auth_profile($user_id)
{
    $profiles = supabase_request('GET', '/rest/v1/profiles?id=eq.' . rawurlencode($user_id) . '&select=id,email,display_name,label,role,created_at');
    return $profiles[0] ?? false;
}

function get_profile_by_email($email)
{
    $profiles = supabase_request('GET', '/rest/v1/profiles?email=eq.' . rawurlencode(strtolower($email)) . '&select=id,email,display_name,label,role,created_at');
    return $profiles[0] ?? false;
}

function refresh_session_profile()
{
    static $checked_this_request = false;
    static $valid_this_request = false;
    if ($checked_this_request) return $valid_this_request;
    $checked_this_request = true;

    if (DEMO_MODE) {
        if (empty($_SESSION['user']['id'])) return false;
        foreach (get_demo_accounts() as $account) {
            if ($account['id'] === $_SESSION['user']['id']) {
                $_SESSION['user'] = [
                    'id' => $account['id'],
                    'email' => $account['email'],
                    'username' => $account['email'],
                    'display_name' => $account['display_name'],
                    'label' => $account['label'],
                    'role' => $account['role'],
                    'login_time' => $_SESSION['user']['login_time'] ?? date('Y-m-d H:i:s'),
                ];
                return $valid_this_request = true;
            }
        }
        session_unset();
        session_destroy();
        return false;
    }

    if (!isset($_SESSION['user']['id'], $_SESSION['access_token'], $_SESSION['refresh_token'])) return false;

    $auth_user = supabase_request('GET', '/auth/v1/user', null, 'user');
    if ($auth_user === false && !empty($_SESSION['refresh_token'])) {
        $tokens = supabase_request('POST', '/auth/v1/token?grant_type=refresh_token', [
            'refresh_token' => $_SESSION['refresh_token'],
        ], 'anon');
        if ($tokens === false || empty($tokens['access_token']) || empty($tokens['refresh_token'])) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['access_token'] = $tokens['access_token'];
        $_SESSION['refresh_token'] = $tokens['refresh_token'];
        $auth_user = $tokens['user'] ?? false;
    }
    if (!$auth_user || ($auth_user['id'] ?? null) !== $_SESSION['user']['id']) {
        session_unset();
        session_destroy();
        return false;
    }

    $profile = get_auth_profile($_SESSION['user']['id']);
    if (!$profile) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['user']['email'] = $profile['email'];
    $_SESSION['user']['username'] = $profile['email'];
    $_SESSION['user']['display_name'] = $profile['display_name'];
    $_SESSION['user']['label'] = $profile['label'];
    $_SESSION['user']['role'] = $profile['role'];
    return $valid_this_request = true;
}

function is_admin()
{
    return refresh_session_profile() && $_SESSION['user']['role'] === 'admin';
}

function require_admin()
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Administrator access required.');
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_is_valid($token)
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function invite_guest_account($email, $display_name, $label)
{
    return supabase_request('POST', '/auth/v1/invite?redirect_to=' . rawurlencode(APP_URL . '/invite'), [
        'email' => $email,
        'data' => [
            'display_name' => $display_name,
            'label' => $label,
            'account_type' => 'guest',
        ],
    ]);
}

function send_rsvp_confirmation($guest)
{
    if (DEMO_MODE) return true;
    if (RSVP_EMAIL_FUNCTION_SECRET === '') {
        $GLOBALS['supabase_error'] = 'RSVP email function secret is not configured.';
        return false;
    }

    $qr_png = generate_guest_qr_png($guest['id']);
    if ($qr_png === false) {
        $GLOBALS['supabase_error'] = 'Could not generate the guest QR code.';
        return false;
    }

    return supabase_request('POST', '/functions/v1/rsvp-confirmation', [
        'email' => $guest['email'],
        'name' => $guest['name'],
        'attending' => (bool)$guest['attending'],
        'maps_url' => EVENT_MAPS_URL,
        'qr_code_base64' => base64_encode($qr_png),
        'qr_code_filename' => 'adeola-guest-' . $guest['id'] . '.png',
    ], 'service', null, ['x-rsvp-secret' => RSVP_EMAIL_FUNCTION_SECRET]);
}

function send_guest_uninvitation($guest)
{
    if (DEMO_MODE) return true;
    if (RSVP_EMAIL_FUNCTION_SECRET === '') {
        $GLOBALS['supabase_error'] = 'RSVP email function secret is not configured.';
        return false;
    }
    if (empty($guest['email'])) {
        $GLOBALS['supabase_error'] = 'This guest has no email address.';
        return false;
    }

    return supabase_request('POST', '/functions/v1/rsvp-confirmation', [
        'event' => 'guest_uninvited',
        'email' => $guest['email'],
        'name' => $guest['name'] ?? 'Guest',
    ], 'service', null, ['x-rsvp-secret' => RSVP_EMAIL_FUNCTION_SECRET]);
}

function qr_png_chunk($type, $data)
{
    $chunk_data = $type . $data;
    return pack('N', strlen($data)) . $chunk_data . hash('crc32b', $chunk_data, true);
}

function render_qr_matrix_png($matrix)
{
    $scale = 8;
    $quiet_zone = 4;
    $module_count = $matrix->getBlockCount();
    $dimension = ($module_count + 2 * $quiet_zone) * $scale;
    $white = "\xff\xff\xff";
    $black = "\x00\x00\x00";
    $white_row = str_repeat($white, $dimension);
    $raw_image = '';

    for ($row = -$quiet_zone; $row < $module_count + $quiet_zone; $row++) {
        if ($row < 0 || $row >= $module_count) {
            $pixel_row = $white_row;
        } else {
            $pixel_row = str_repeat($white, $quiet_zone * $scale);
            for ($column = 0; $column < $module_count; $column++) {
                $pixel = $matrix->getBlockValue($row, $column) === 1 ? $black : $white;
                $pixel_row .= str_repeat($pixel, $scale);
            }
            $pixel_row .= str_repeat($white, $quiet_zone * $scale);
        }

        for ($pixel_row_index = 0; $pixel_row_index < $scale; $pixel_row_index++) {
            $raw_image .= "\x00" . $pixel_row;
        }
    }

    $png = "\x89PNG\r\n\x1a\n";
    $png .= qr_png_chunk('IHDR', pack('NNCCCCC', $dimension, $dimension, 8, 2, 0, 0, 0));
    $png .= qr_png_chunk('IDAT', gzcompress($raw_image, 9));
    $png .= qr_png_chunk('IEND', '');
    return $png;
}

function generate_guest_qr_png($guest_id)
{
    require_once BASE_DIR . '/vendor/autoload.php';

    try {
        $result = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\SvgWriter())
            ->writerOptions([])
            ->data((string)$guest_id)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin())
            ->validateResult(false)
            ->build();

        return render_qr_matrix_png($result->getMatrix());
    } catch (\Throwable $error) {
        return false;
    }
}

function get_data_from_dir($dir)
{
    if (DEMO_MODE) {
        if (!file_exists($dir)) return [];
        $data_json = file_get_contents($dir);
        $records = json_decode($data_json, true);
        if (!is_array($records)) return [];
        $is_checkins = basename($dir) === 'checkin.json';
        return array_map($is_checkins ? 'normalize_checkin_record' : 'normalize_guest_record', $records);
    }

    $table = (basename($dir) === 'checkin.json') ? 'checkins' : 'guests';
    $sort = ($table === 'guests') ? '&order=name.asc' : '';
    $records = supabase_request('GET', '/rest/v1/' . $table . '?select=*' . $sort);
    if (!is_array($records)) return null;

    if ($table === 'guests') {
        return array_map('normalize_guest_record', $records);
    }

    return array_map('normalize_checkin_record', $records);
}

function get_guest_record_by_id($id)
{
    if (DEMO_MODE) {
        $guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];
        return get_guest_by_id($id, $guests);
    }

    $records = supabase_request('GET', '/rest/v1/guests?select=*&id=eq.' . rawurlencode((string)$id) . '&limit=1');
    if (!is_array($records) || !isset($records[0])) return false;
    return normalize_guest_record($records[0]);
}

function normalize_guest_record($record)
{
    $record['name'] = $record['name'] ?? '';
    $record['group_size'] = $record['group_size'] ?? 1;
    $record['phone'] = $record['phone'] ?? '';
    $record['invitation_sent'] = $record['invitation_sent'] ?? false;
    return $record;
}

function normalize_checkin_record($record)
{
    $record['check_in_count'] = $record['check_in_count'] ?? 0;
    return $record;
}

function save_data_to_dir($dir, $records)
{
    if (DEMO_MODE) {
        $is_checkins = basename($dir) === 'checkin.json';
        $records = array_map($is_checkins ? 'normalize_checkin_record' : 'normalize_guest_record', $records);
        return file_put_contents($dir, json_encode(array_values($records), JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }

    $table = (basename($dir) === 'checkin.json') ? 'checkins' : 'guests';
    foreach ($records as $record) {
        if (!isset($record['id'])) continue;
        if ($table === 'guests') {
            $record = [
                'id' => $record['id'],
                'name' => $record['name'] ?? '',
                'email' => $record['email'] ?? '',
                'phone' => $record['phone'] ?? '',
                'label' => $record['label'] ?? 'VIP',
                'attending' => isset($record['attending']) ? (bool)$record['attending'] : true,
                'invitation_sent' => !empty($record['invitation_sent'] ?? false),
                'auth_user_id' => $record['auth_user_id'] ?? null,
            ];
        } else {
            $record = [
                'id' => $record['id'],
                'check_in_count' => (int)($record['check_in_count'] ?? 0),
                'updates' => $record['updates'] ?? [],
            ];
        }
        $saved = supabase_request('POST', '/rest/v1/' . $table . '?on_conflict=id', $record);
        if ($saved === false) return false;
    }
    return true;
}

function delete_data_record($dir, $id)
{
    if (DEMO_MODE) {
        $records = get_data_from_dir($dir) ?? [];
        remove_guest_by_id($id, $records);
        return save_data_to_dir($dir, $records);
    }

    $table = (basename($dir) === 'checkin.json') ? 'checkins' : 'guests';
    return supabase_request('DELETE', '/rest/v1/' . $table . '?id=eq.' . rawurlencode($id)) !== false;
}

function is_valid_md5($md5 = '')
{
    return preg_match('/^[a-f0-9]{32}$/', $md5);
}

function get_filter_query($filter_key, $query_array, $values)
{
    if (!is_array($values))
        return false;

    $current_value = $query_array[$filter_key];
    $index = array_search($current_value, $values);

    $new_value = $values[0];

    if ($index !== false && $index != count($values) - 1) {
        $new_value = $values[$index + 1];
    }

    $query_array[$filter_key] = $new_value;

    return '?' . http_build_query($query_array);
}

function get_guest_data($id = null)
{
    if (!$id)
        return false;

    $id = filter_var(trim($id));

    $stored_guests = get_data_from_dir(DATA_DIR . '/guests.json') ?? [];

    // push to file
    if (!empty($id) && $guest = get_guest_by_id($id, $stored_guests)) {
        return $guest;
    } else {
        return false;
    }
}

function guest_exists($id, $stored_guests)
{
    if ($stored_guests)
        foreach ($stored_guests as $guest) {
            if ($id == $guest['id']) return true;
        }
    return false;
}

function find_guest_duplicate($name, $email, $phone, $stored_guests, $exclude_id = null)
{
    $normalize_name = function ($value) {
        $value = preg_replace('/\s+/u', ' ', trim((string)$value));
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };
    $normalized_name = $normalize_name($name);
    $normalized_email = strtolower(trim((string)$email));
    $normalized_phone = preg_replace('/\D+/', '', (string)$phone);

    foreach ($stored_guests ?: [] as $guest) {
        if ($exclude_id !== null && (string)($guest['id'] ?? '') === (string)$exclude_id) {
            continue;
        }

        $saved_name = $normalize_name($guest['name'] ?? '');
        $saved_email = strtolower(trim((string)($guest['email'] ?? '')));
        $saved_phone = preg_replace('/\D+/', '', (string)($guest['phone'] ?? ''));

        if (($normalized_email !== '' && $saved_email === $normalized_email)
            || ($normalized_name !== '' && $saved_name === $normalized_name)
            || ($normalized_phone !== '' && $saved_phone !== '' && $saved_phone === $normalized_phone)) {
            return $guest;
        }
    }

    return false;
}

function is_valid_phone_number($phone)
{
    $phone = trim((string)$phone);
    if ($phone === '') {
        return true;
    }

    if (!preg_match('/^\+?[0-9](?:[0-9(). -]*[0-9])?$/D', $phone)) {
        return false;
    }

    $digits = preg_replace('/\D+/', '', $phone);
    return strlen($digits) >= 11 && strlen($digits) <= 15;
}

function filter_guests_that_checked_in($bool, $stored_guests, $checkin_guests)
{
    if (!$stored_guests)
        return [];

    $filtered_guests = [];
    foreach ($stored_guests as $guest) {
        if ((bool)guest_exists($guest['id'], $checkin_guests) === (bool)$bool)
            array_push($filtered_guests, $guest);
    }
    return $filtered_guests;
}

function order_guests_by_name($stored_guests, $order = 'AZ')
{
    $direction = ($order === 'ZA') ? -1 : 1;
    usort($stored_guests, function ($a, $b) use ($direction) {
        $name_a = trim((string)($a['name'] ?? ''));
        $name_b = trim((string)($b['name'] ?? ''));
        return $direction * strnatcasecmp($name_a, $name_b);
    });

    return $stored_guests;
}



function get_guest_by_id($id, $stored_guests)
{
    if ($stored_guests)
        foreach ($stored_guests as $guest) {
            if ($id == $guest['id']) return $guest;
        }
    return false;
}

function filter_guests_key_value($filter_key, $filter_value, $type, $stored_guests)
{
    if (!$stored_guests || !isset($filter_key) || !isset($filter_value))
        return false;

    $filtered_guests = [];
    foreach ($stored_guests as $guest) {
        if ($type == 'search') {
            if (stripos($guest[$filter_key], $filter_value) !== false) {
                array_push($filtered_guests, $guest);
            }
        } else if ($type == 'bool') {
            if (!$filter_value && (!isset($guest[$filter_key]) || !$guest[$filter_key]))
                array_push($filtered_guests, $guest);
            else if ($filter_value && isset($guest[$filter_key]) && $guest[$filter_key])
                array_push($filtered_guests, $guest);
        }
    }
    return $filtered_guests;
}

function filter_guests($search_term, $stored_guests)
{
    if (!$stored_guests)
        return false;

    if (strpos($search_term, ':')) {
        $terms = explode(':', $search_term);
        $search_key = $terms[0];
        $search_value = $terms[1];

        return filter_guests_key_value($search_key, $search_value, 'search', $stored_guests);
    }

    $filtered_guests = [];
    foreach ($stored_guests as $guest) {
        foreach ($guest as $key => $value) {
            if (stripos($value, $search_term) !== false) {
                array_push($filtered_guests, $guest);
                break;
            }
        }
    }
    return $filtered_guests;
}

function replace_guest_by_id($id, $new_guest, &$stored_guests)
{
    if ($stored_guests)
        foreach ($stored_guests as &$guest) {
            if ($id == $guest['id']) {
                $guest = $new_guest;
                return true;
            }
        }
    return false;
}

function remove_guest_by_id($id, &$stored_guests)
{
    if ($stored_guests)
        foreach ($stored_guests as $key => $guest) {
            if ($id == $guest['id']) {
                unset($stored_guests[$key]);
                return true;
            }
        }
    return false;
}
