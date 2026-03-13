<?php
// /api/api_key_api.php — manage API keys (session-authenticated)
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Session expired.']); exit;
}
$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'] ?? '';
$action  = $_POST['action'] ?? '';

// Ensure api_key belongs to current user
function own_key($conn, $key_id, $user_id, $role) {
    if (in_array($role, ['super_admin','admin'])) return true;
    $id  = (int)$key_id;
    $uid = (int)$user_id;
    return (bool)$conn->query("SELECT id FROM api_keys WHERE id='$id' AND user_id='$uid'")->fetch_assoc();
}

// ── Create API Key ────────────────────────────────────────────
if ($action === 'create_key') {
    $name        = trim($_POST['key_name']    ?? '');
    $perms_arr   = $_POST['permissions']      ?? [];
    $rate_limit  = max(1, min(1000, (int)($_POST['rate_limit'] ?? 60)));
    $allowed_ips = trim($_POST['allowed_ips'] ?? '');

    if (empty($name)) { echo json_encode(['status'=>'error','message'=>'Key name is required.']); exit; }
    if (empty($perms_arr)) { echo json_encode(['status'=>'error','message'=>'Select at least one permission.']); exit; }

    $valid_perms = ['create_license','read_license','update_license','delete_license','validate_license'];
    $perms_arr   = array_filter($perms_arr, fn($p) => in_array($p, $valid_perms));
    $perms_str   = implode(',', $perms_arr);

    // Generate cryptographically secure key and secret
    $api_key    = 'gd_' . bin2hex(random_bytes(16)); // 35 chars
    $api_secret = bin2hex(random_bytes(32));           // 64 chars

    $name_esc = $conn->real_escape_string($name);
    $key_esc  = $conn->real_escape_string($api_key);
    $sec_esc  = $conn->real_escape_string($api_secret);
    $perm_esc = $conn->real_escape_string($perms_str);
    $ip_esc   = $conn->real_escape_string($allowed_ips);

    $conn->query("INSERT INTO api_keys (user_id, key_name, api_key, api_secret, permissions, rate_limit, allowed_ips)
                  VALUES ('$my_id', '$name_esc', '$key_esc', '$sec_esc', '$perm_esc', '$rate_limit', " . (empty($ip_esc) ? 'NULL' : "'$ip_esc'") . ")");

    if ($conn->affected_rows > 0) {
        echo json_encode([
            'status'     => 'success',
            'message'    => 'API key created successfully!',
            'api_key'    => $api_key,
            'api_secret' => $api_secret,
        ]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to create key: '.$conn->error]);
    }
    exit;
}

// ── Get Key Details (for edit modal) ─────────────────────────
if ($action === 'get_key') {
    $id = (int)($_POST['id'] ?? 0);
    if (!own_key($conn, $id, $my_id, $my_role)) { echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit; }
    $k = $conn->query("SELECT id, key_name, permissions, rate_limit, status, allowed_ips FROM api_keys WHERE id='$id'")->fetch_assoc();
    echo json_encode($k ? ['status'=>'success','data'=>$k] : ['status'=>'error','message'=>'Key not found.']);
    exit;
}

// ── Get Key with Secret (view modal, one-time reveal) ────────
if ($action === 'reveal_key') {
    $id = (int)($_POST['id'] ?? 0);
    if (!own_key($conn, $id, $my_id, $my_role)) { echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit; }
    $k = $conn->query("SELECT api_key, api_secret, key_name FROM api_keys WHERE id='$id'")->fetch_assoc();
    echo json_encode($k ? ['status'=>'success','data'=>$k] : ['status'=>'error','message'=>'Key not found.']);
    exit;
}

// ── Update API Key ────────────────────────────────────────────
if ($action === 'update_key') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['key_name']    ?? '');
    $perms_arr   = $_POST['permissions']      ?? [];
    $rate_limit  = max(1, min(1000, (int)($_POST['rate_limit'] ?? 60)));
    $allowed_ips = trim($_POST['allowed_ips'] ?? '');

    if (!own_key($conn, $id, $my_id, $my_role)) { echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit; }
    if (empty($name)) { echo json_encode(['status'=>'error','message'=>'Key name required.']); exit; }
    if (empty($perms_arr)) { echo json_encode(['status'=>'error','message'=>'Select at least one permission.']); exit; }

    $valid_perms = ['create_license','read_license','update_license','delete_license','validate_license'];
    $perms_arr   = array_filter($perms_arr, fn($p) => in_array($p, $valid_perms));
    $perms_str   = implode(',', $perms_arr);

    $name_esc = $conn->real_escape_string($name);
    $perm_esc = $conn->real_escape_string($perms_str);
    $ip_esc   = $conn->real_escape_string($allowed_ips);
    $ip_val   = empty($ip_esc) ? 'NULL' : "'$ip_esc'";

    $conn->query("UPDATE api_keys SET key_name='$name_esc', permissions='$perm_esc', rate_limit='$rate_limit', allowed_ips=$ip_val WHERE id='$id'");
    echo json_encode(['status'=>'success','message'=>'API key updated!']);
    exit;
}

// ── Toggle Key Status ─────────────────────────────────────────
if ($action === 'toggle_key') {
    $id = (int)($_POST['id'] ?? 0);
    if (!own_key($conn, $id, $my_id, $my_role)) { echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit; }
    $cur = $conn->query("SELECT status FROM api_keys WHERE id='$id'")->fetch_assoc();
    $new = $cur['status'] === 'active' ? 'revoked' : 'active';
    $conn->query("UPDATE api_keys SET status='$new' WHERE id='$id'");
    echo json_encode(['status'=>'success','message'=>"Key " . ($new==='active'?'activated':'revoked') . "."]);
    exit;
}

// ── Delete Key ───────────────────────────────────────────────
if ($action === 'delete_key') {
    $id = (int)($_POST['id'] ?? 0);
    if (!own_key($conn, $id, $my_id, $my_role)) { echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit; }
    $conn->query("DELETE FROM api_keys WHERE id='$id'");
    echo json_encode(['status'=>'success','message'=>'API key deleted.']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action.']);
ob_end_flush();
?>
