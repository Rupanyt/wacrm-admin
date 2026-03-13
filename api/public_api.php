<?php
// ============================================================
// PUBLIC API — /api/public_api.php
// All external API calls go through this file
// ============================================================

$start_time = microtime(true);

// No session needed — API key auth only
require_once __DIR__ . '/../include/config.php';

// ── CORS Headers ─────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, X-API-Secret, Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_respond(400, false, 'Only POST requests are accepted.');
}

// ── Parse body (JSON or form-encoded) ────────────────────────
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($content_type, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $body = $_POST;
}

$action      = trim($body['action'] ?? '');
$ip_address  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip_address  = explode(',', $ip_address)[0]; // first IP if proxied

// ── Authenticate ─────────────────────────────────────────────
$api_key_val    = $_SERVER['HTTP_X_API_KEY']    ?? '';
$api_secret_val = $_SERVER['HTTP_X_API_SECRET'] ?? '';

// Also support Authorization: Bearer key:secret
if (empty($api_key_val) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (preg_match('/^Bearer\s+(.+):(.+)$/i', $auth, $m)) {
        $api_key_val    = $m[1];
        $api_secret_val = $m[2];
    }
}

if (empty($api_key_val) || empty($api_secret_val)) {
    api_respond(401, false, 'Missing API Key or Secret. Include X-API-Key and X-API-Secret headers.');
}

// Fetch key from DB
$ks  = $conn->real_escape_string($api_key_val);
$key = $conn->query("SELECT * FROM api_keys WHERE api_key='$ks' LIMIT 1")->fetch_assoc();

if (!$key) {
    api_respond(401, false, 'Invalid API Key.');
}

// Verify secret (constant time compare)
if (!hash_equals($key['api_secret'], $api_secret_val)) {
    api_respond(401, false, 'Invalid API Secret.');
}

if ($key['status'] !== 'active') {
    api_respond(403, false, 'This API key has been revoked. Contact your admin.');
}

// IP whitelist check
if (!empty($key['allowed_ips'])) {
    $allowed = array_map('trim', explode(',', $key['allowed_ips']));
    if (!in_array($ip_address, $allowed)) {
        api_respond(403, false, 'Request from IP ' . $ip_address . ' is not allowed for this key.');
    }
}

$key_id  = $key['id'];
$user_id = $key['user_id'];
$perms   = explode(',', $key['permissions']);

// Load user
$u = $conn->query("SELECT * FROM users WHERE id='$user_id' AND status='active'")->fetch_assoc();
if (!$u) {
    api_respond(403, false, 'User account is inactive or not found.');
}
$my_role = $u['role'];

// ── Rate Limiting ─────────────────────────────────────────────
$rate_limit = (int)$key['rate_limit'];
$minute_ago = date('Y-m-d H:i:s', time() - 60);
$recent     = $conn->query("SELECT COUNT(*) FROM api_logs WHERE api_key_id='$key_id' AND created_at >= '$minute_ago'")->fetch_row()[0];

header('X-RateLimit-Limit: '     . $rate_limit);
header('X-RateLimit-Remaining: ' . max(0, $rate_limit - $recent));
header('X-RateLimit-Reset: '     . (time() + 60));

if ($recent >= $rate_limit) {
    api_respond(429, false, "Rate limit exceeded ({$rate_limit} requests/min). Please slow down.");
}

// ── Permission helper ─────────────────────────────────────────
function has_perm($required) {
    global $perms;
    return in_array($required, $perms);
}

// ── License ownership helper ──────────────────────────────────
function can_touch_license($license_id, $user_id, $my_role) {
    global $conn;
    $id = (int)$license_id;
    $uid = (int)$user_id;
    if ($my_role === 'super_admin') return true;
    $lic = $conn->query("SELECT created_by FROM licenses WHERE id='$id'")->fetch_assoc();
    if (!$lic) return false;
    if ($lic['created_by'] == $uid) return true;
    if ($my_role === 'admin') {
        $r = $conn->query("SELECT id FROM users WHERE id='{$lic['created_by']}' AND parent_id='$uid'")->fetch_assoc();
        return (bool)$r;
    }
    return false;
}

// ── Route action ─────────────────────────────────────────────

// ── 1. VALIDATE LICENSE ──────────────────────────────────────
if ($action === 'validate_license') {
    if (!has_perm('validate_license')) api_respond(403, false, 'Permission denied: validate_license not granted for this key.');

    $license_key = trim($body['license_key'] ?? '');
    if (empty($license_key)) api_respond(400, false, 'license_key is required.');

    $lk = $conn->real_escape_string($license_key);
    $stmt = $conn->prepare("SELECT l.*, u.username as owner FROM licenses l LEFT JOIN users u ON l.created_by=u.id WHERE l.license_key=? LIMIT 1");
    $stmt->bind_param('s', $license_key);
    $stmt->execute();
    $lic = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$lic) {
        api_respond(404, false, 'License not found.', ['valid' => false]);
    }

    $is_expired = !empty($lic['expiry_date']) && date('Y-m-d') > $lic['expiry_date'];
    $is_active  = $lic['status'] === 'active';
    $valid      = $is_active && !$is_expired;

    $data = [
        'valid'   => $valid,
        'license' => [
            'id'            => (int)$lic['id'],
            'key'           => $lic['license_key'],
            'client_name'   => $lic['client_name'],
            'client_mobile' => $lic['client_mobile'],
            'software_name' => $lic['software_name'],
            'status'        => $lic['status'],
            'expiry_date'   => $lic['expiry_date'],
            'is_expired'    => $is_expired,
            'created_at'    => $lic['created_at'],
        ],
    ];

    if (!$valid) {
        api_respond(200, false, $is_expired ? 'License has expired.' : 'License is blocked/inactive.', $data);
    }
    api_respond(200, true, 'License is valid and active.', $data);
}

// ── 2. CREATE LICENSE ────────────────────────────────────────
if ($action === 'create_license') {
    if (!has_perm('create_license')) api_respond(403, false, 'Permission denied: create_license not granted.');

    // Quota check
    include_once __DIR__ . '/../include/quota_check.php';
    $quota = check_reseller_quota($conn, $user_id);
    if (!$quota['allowed']) {
        api_respond(422, false, $quota['message'] . " (Used: {$quota['used']}/{$quota['limit']})");
    }

    $software = trim($body['software_name'] ?? '');
    $client   = trim($body['client_name']   ?? '');
    $mobile   = trim($body['client_mobile'] ?? '');
    $expiry   = trim($body['expiry_date']   ?? '');

    if (empty($software)) api_respond(400, false, 'software_name is required.');
    if (empty($client))   api_respond(400, false, 'client_name is required.');

    // Validate date
    if (!empty($expiry) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
        api_respond(400, false, 'expiry_date must be in YYYY-MM-DD format.');
    }
    if (!empty($expiry) && $expiry < date('Y-m-d')) {
        api_respond(400, false, 'expiry_date cannot be in the past.');
    }

    $key_raw   = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
    $final_key = implode('-', str_split($key_raw, 4));
    $expiry_db = !empty($expiry) ? $expiry : null;

    $stmt = $conn->prepare("INSERT INTO licenses (license_key, software_name, client_name, client_mobile, created_by, status, expiry_date) VALUES (?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param('ssssis', $final_key, $software, $client, $mobile, $user_id, $expiry_db);

    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $stmt->close();
        api_respond(200, true, 'License created successfully.', [
            'license_key' => $final_key,
            'id'          => $new_id,
        ]);
    }
    $stmt->close();
    api_respond(500, false, 'Database error creating license.');
}

// ── 3. LIST LICENSES ─────────────────────────────────────────
if ($action === 'list_licenses') {
    if (!has_perm('read_license')) api_respond(403, false, 'Permission denied: read_license not granted.');

    $page     = max(1, (int)($body['page']     ?? 1));
    $per_page = min(100, max(1, (int)($body['per_page'] ?? 20)));
    $offset   = ($page - 1) * $per_page;
    $status   = trim($body['status'] ?? '');
    $search   = trim($body['search'] ?? '');

    // Build WHERE
    $where = ($my_role === 'super_admin') ? "1=1" : "l.created_by='$user_id'";
    if ($my_role === 'admin') {
        $where = "l.created_by IN (SELECT id FROM users WHERE id='$user_id' OR parent_id='$user_id')";
    }
    if (!empty($status) && in_array($status, ['active','blocked'])) {
        $se = $conn->real_escape_string($status);
        $where .= " AND l.status='$se'";
    }
    if (!empty($search)) {
        $ss = $conn->real_escape_string($search);
        $where .= " AND (l.client_name LIKE '%$ss%' OR l.client_mobile LIKE '%$ss%' OR l.license_key LIKE '%$ss%' OR l.software_name LIKE '%$ss%')";
    }

    $total = $conn->query("SELECT COUNT(*) FROM licenses l WHERE $where")->fetch_row()[0];
    $rows  = $conn->query("SELECT l.id, l.license_key, l.client_name, l.client_mobile, l.software_name, l.status, l.expiry_date, l.created_at FROM licenses l WHERE $where ORDER BY l.id DESC LIMIT $per_page OFFSET $offset");

    $licenses = [];
    while ($r = $rows->fetch_assoc()) {
        $r['id']        = (int)$r['id'];
        $r['is_expired']= !empty($r['expiry_date']) && date('Y-m-d') > $r['expiry_date'];
        $licenses[]     = $r;
    }

    api_respond(200, true, 'Licenses retrieved.', [
        'total'    => (int)$total,
        'page'     => $page,
        'per_page' => $per_page,
        'licenses' => $licenses,
    ]);
}

// ── 4. GET LICENSE ───────────────────────────────────────────
if ($action === 'get_license') {
    if (!has_perm('read_license')) api_respond(403, false, 'Permission denied: read_license not granted.');

    $id  = (int)($body['id'] ?? 0);
    $lk  = trim($body['license_key'] ?? '');

    if (!$id && empty($lk)) api_respond(400, false, 'Provide either id or license_key.');

    $where = $id ? "l.id='$id'" : "l.license_key='" . $conn->real_escape_string($lk) . "'";
    $lic   = $conn->query("SELECT l.*, u.username as owner FROM licenses l LEFT JOIN users u ON l.created_by=u.id WHERE $where LIMIT 1")->fetch_assoc();

    if (!$lic) api_respond(404, false, 'License not found.');
    if (!can_touch_license($lic['id'], $user_id, $my_role)) api_respond(403, false, 'This license does not belong to your account.');

    $lic['id']         = (int)$lic['id'];
    $lic['is_expired'] = !empty($lic['expiry_date']) && date('Y-m-d') > $lic['expiry_date'];
    unset($lic['created_by']);

    api_respond(200, true, 'License retrieved.', ['license' => $lic]);
}

// ── 5. UPDATE LICENSE ────────────────────────────────────────
if ($action === 'update_license') {
    if (!has_perm('update_license')) api_respond(403, false, 'Permission denied: update_license not granted.');

    $id = (int)($body['id'] ?? 0);
    if (!$id) api_respond(400, false, 'id is required.');
    if (!can_touch_license($id, $user_id, $my_role)) api_respond(403, false, 'License not found or unauthorized.');

    $fields = [];
    if (!empty($body['software_name'])) $fields[] = "software_name='" . $conn->real_escape_string($body['software_name']) . "'";
    if (!empty($body['client_name']))   $fields[] = "client_name='"   . $conn->real_escape_string($body['client_name'])   . "'";
    if (isset($body['client_mobile']))  $fields[] = "client_mobile='" . $conn->real_escape_string($body['client_mobile']) . "'";
    if (!empty($body['status']) && in_array($body['status'],['active','blocked'])) {
        $fields[] = "status='" . $body['status'] . "'";
    }
    if (isset($body['expiry_date'])) {
        $exp = trim($body['expiry_date']);
        if (!empty($exp) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
            api_respond(400, false, 'expiry_date must be YYYY-MM-DD.');
        }
        $fields[] = empty($exp) ? "expiry_date=NULL" : "expiry_date='" . $conn->real_escape_string($exp) . "'";
    }

    if (empty($fields)) api_respond(400, false, 'No fields to update. Provide at least one of: software_name, client_name, client_mobile, expiry_date, status.');

    $conn->query("UPDATE licenses SET " . implode(', ', $fields) . " WHERE id='$id'");
    api_respond(200, true, 'License updated successfully.');
}

// ── 6. TOGGLE LICENSE ────────────────────────────────────────
if ($action === 'toggle_license') {
    if (!has_perm('update_license')) api_respond(403, false, 'Permission denied: update_license not granted.');

    $id = (int)($body['id'] ?? 0);
    if (!$id) api_respond(400, false, 'id is required.');
    if (!can_touch_license($id, $user_id, $my_role)) api_respond(403, false, 'License not found or unauthorized.');

    $cur = $conn->query("SELECT status FROM licenses WHERE id='$id'")->fetch_assoc();
    $new = $cur['status'] === 'active' ? 'blocked' : 'active';
    $conn->query("UPDATE licenses SET status='$new' WHERE id='$id'");
    api_respond(200, true, "License status changed to $new.", ['new_status' => $new]);
}

// ── 7. DELETE LICENSE ────────────────────────────────────────
if ($action === 'delete_license') {
    if (!has_perm('delete_license')) api_respond(403, false, 'Permission denied: delete_license not granted.');

    $id = (int)($body['id'] ?? 0);
    if (!$id) api_respond(400, false, 'id is required.');
    if (!can_touch_license($id, $user_id, $my_role)) api_respond(403, false, 'License not found or unauthorized.');

    $conn->query("DELETE FROM licenses WHERE id='$id'");
    if ($conn->affected_rows > 0) {
        api_respond(200, true, 'License deleted successfully.');
    }
    api_respond(404, false, 'License not found.');
}

// Unknown action
api_respond(400, false, "Unknown action: '{$action}'. Valid actions: validate_license, create_license, list_licenses, get_license, update_license, toggle_license, delete_license.");

// ════════════════════════════════════════════════════════════
// Response helper — logs every call then exits
// ════════════════════════════════════════════════════════════
function api_respond(int $http_code, bool $success, string $message, array $extra = []): void {
    global $conn, $key_id, $user_id, $action, $ip_address, $start_time, $key;

    $duration  = (int)((microtime(true) - $start_time) * 1000);
    $payload   = array_merge(['status' => $success ? 'success' : 'error', 'message' => $message], $extra);
    $response  = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    http_response_code($http_code);
    echo $response;

    // Log the call
    if (!empty($key_id)) {
        $act_esc = $conn->real_escape_string($action ?? '');
        $ip_esc  = $conn->real_escape_string($ip_address ?? '');
        $msg_esc = $conn->real_escape_string(substr($message, 0, 500));
        $conn->query("INSERT INTO api_logs (api_key_id, user_id, endpoint, method, response_code, response_msg, ip_address, duration_ms)
                      VALUES ('$key_id', '$user_id', '$act_esc', 'POST', '$http_code', '$msg_esc', '$ip_esc', '$duration')");
        $conn->query("UPDATE api_keys SET last_used_at=NOW(), total_calls=total_calls+1 WHERE id='$key_id'");
    }

    exit;
}
?>
