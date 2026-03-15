<?php
// api/auth/login/activate.php
require_once '../../../include/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Access-Token, access-token');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ── Parse JSON body ───────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'License Key is missing.', 'msg_id' => 'missing_fields']);
    exit;
}

$license_key = trim($data['email']);

// ── Device identifier ─────────────────────────────────────────
// Extension should send chromeStoreID in login payload.
// Fallback: hash of User-Agent (less reliable but graceful).
$chrome_id = trim($data['chromeStoreID'] ?? $data['device_id'] ?? '');
if (empty($chrome_id)) {
    $chrome_id = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
}

// ── Fetch license ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->bind_param('s', $license_key);
$stmt->execute();
$license = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$license) {
    echo json_encode(['success' => false, 'message' => 'Invalid License Key.', 'msg_id' => 'user_login_notFund']);
    exit;
}

if ($license['status'] === 'blocked') {
    echo json_encode(['success' => false, 'message' => 'This license has been blocked.', 'msg_id' => 'license_blocked']);
    exit;
}

// ── Expiry check ──────────────────────────────────────────────
if (!empty($license['expiry_date']) && strtotime($license['expiry_date']) < time()) {
    // Auto-update status
    $conn->query("UPDATE licenses SET status='expired' WHERE id=" . intval($license['id']));
    echo json_encode(['success' => false, 'message' => 'This license has expired.', 'msg_id' => 'plan_expired']);
    exit;
}

if ($license['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'License is not active.', 'msg_id' => 'license_inactive']);
    exit;
}

// ── Device strict mode check ──────────────────────────────────
$strict_mode    = get_config('ext_device_strict_mode') ?: '0';
$bound_device   = trim($license['device_id'] ?? '');     // stored chromeStoreID
$stored_token   = trim($license['filed_1']   ?? '');     // stored access_token_plugin

if ($strict_mode === '1') {
    // Device is already bound to a DIFFERENT device
    if (!empty($bound_device) && $bound_device !== $chrome_id) {
        echo json_encode([
            'success' => false,
            'message' => 'This license is already in use on another device. Please contact support to switch devices.',
            'msg_id'  => 'device_mismatch',
        ]);
        exit;
    }
}

// ── Generate tokens ───────────────────────────────────────────
$bearer_token = bin2hex(random_bytes(32));
$plugin_token = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.'
              . rtrim(base64_encode(json_encode(['sub' => $license['id'], 'iat' => time(), 'dev' => substr($chrome_id, 0, 16)])), '=')
              . '.gdcrm_sig';

// ── Bind device + store session token ────────────────────────
// device_id  → chrome extension ID (device lock)
// filed_1    → access_token_plugin (session validation)
// filed_2    → last login timestamp
$login_time = date('Y-m-d H:i:s');
$upd = $conn->prepare("UPDATE licenses SET device_id = ?, filed_1 = ?, filed_2 = ? WHERE id = ?");
$upd->bind_param('sssi', $chrome_id, $plugin_token, $login_time, $license['id']);
$upd->execute();
$upd->close();

// ── Respond ───────────────────────────────────────────────────
$wl_id = get_config('ext_wl_id') ?: 'gdcrm';

echo json_encode([
    'success' => true,
    'message' => 'Seja bem-vindo de volta, ' . ($license['client_name'] ?? 'User') . '!',
    'msg_id'  => 'successfully_login',
    'user'    => [
        'user_id'             => (string)$license['id'],
        'email'               => $license['client_email'] ?? '',
        'name'                => $license['client_name']  ?? 'User',
        'access_token_plugin' => $plugin_token,
        'bearer_token'        => $bearer_token,
        'device_id'           => $chrome_id,
        'user_premium'        => true,
        'wl_id'               => $wl_id,
        'user_status'         => 'premium',
        'dataCadastro'        => date('c', strtotime($license['created_at'] ?? 'now')),
        'whatsapp_registro'   => $license['client_mobile'] ?? '',
    ],
    'user_status' => 'active',
    'auth_google' => false,
], JSON_PRETTY_PRINT);

$conn->close();
exit;
?>
