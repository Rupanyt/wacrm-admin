<?php
// =============================================================
//  api/auth/validation.php
//
//  POST /api/auth/validation/{chromeStoreID}
//  Header : access-token: {cript_key}
//           Content-Type: application/json
//
//  Body   : { "email": "<license_key>", "access_token_plugin": "..." }
//
//  Returns: { success, user, auth_google, user_status }
//
//  "email" field is the license key — naming kept to match
//  extension's existing payload structure.
// =============================================================

require_once '../../../include/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Token, access-token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ── Validate cript_key header ─────────────────────────────────
$expected = get_config('services_cript_key') ?: 'ffce211a-7b07-4d91-ba5d-c40bb4034a83';
$headers  = array_change_key_case(getallheaders(), CASE_LOWER);
$token    = trim($headers['access-token'] ?? '');

if (!hash_equals($expected, $token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg_id' => 'invalid_token', 'message' => 'Unauthorized.']);
    exit;
}

// ── chromeStoreID from URL (set by .htaccess) ─────────────────
$chrome_id = trim($_GET['chrome_id'] ?? '');

// ── Parse body ────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body) || empty($body['email']) || empty($body['access_token_plugin'])) {
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'missing_fields',
        'message'     => 'email and access_token_plugin are required.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

$license_key  = trim($body['email']);
$token_in     = trim($body['access_token_plugin']);

// ── Fetch license ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->bind_param('s', $license_key);
$stmt->execute();
$license = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Not found ─────────────────────────────────────────────────
if (!$license) {
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'user_login_notFund',
        'message'     => 'License not found.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

// ── Status checks ─────────────────────────────────────────────
if ($license['status'] === 'blocked') {
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'license_blocked',
        'message'     => 'License has been blocked.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) < time()) {
    $conn->query("UPDATE licenses SET status='expired' WHERE id=" . intval($license['id']));
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'plan_expired',
        'message'     => 'License has expired.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

if ($license['status'] !== 'active') {
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'license_inactive',
        'message'     => 'License is not active.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

// ── Token validation (filed_1 = stored access_token_plugin) ──
$stored_token = trim($license['filed_1'] ?? '');

if (empty($stored_token) || !hash_equals($stored_token, $token_in)) {
    echo json_encode([
        'success'     => false,
        'msg_id'      => 'invalid_session',
        'message'     => 'Session token is invalid. Please login again.',
        'auth_google' => false,
        'user_status' => 'free',
        'user'        => null,
    ]);
    exit;
}

// ── Device strict mode check ──────────────────────────────────
$strict_mode  = get_config('ext_device_strict_mode') ?: '0';
$bound_device = trim($license['device_id'] ?? '');

if ($strict_mode === '1' && !empty($chrome_id) && !empty($bound_device)) {
    if ($bound_device !== $chrome_id) {
        echo json_encode([
            'success'     => false,
            'msg_id'      => 'device_mismatch',
            'message'     => 'This session is bound to a different device.',
            'auth_google' => false,
            'user_status' => 'free',
            'user'        => null,
        ]);
        exit;
    }
}

// ── All checks passed → return valid session ──────────────────
$wl_id = get_config('ext_wl_id') ?: 'gdcrm';

echo json_encode([
    'success'     => true,
    'msg_id'      => 'session_valid',
    'message'     => 'Session is valid.',
    'auth_google' => false,
    'user_status' => 'premium',
    'user'        => [
        'user_id'             => (string)$license['id'],
        'email'               => $license['client_email'] ?? '',
        'name'                => $license['client_name']  ?? 'User',
        'access_token_plugin' => $token_in,
        'user_premium'        => true,
        'wl_id'               => $wl_id,
        'device_id'           => $bound_device,
        'dataCadastro'        => date('c', strtotime($license['created_at'] ?? 'now')),
        'whatsapp_registro'   => $license['client_mobile'] ?? '',
        'user_status'         => 'premium',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

exit;
?>
