<?php
// api/device_management_api.php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
}

$action  = $_POST['action']  ?? '';
$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

// ── Auth helper: check if caller can manage this license ──────
function can_manage(mysqli $conn, int $license_id, int $user_id, string $role): bool {
    if ($role === 'super_admin') return true;
    $lid = intval($license_id);
    $uid = intval($user_id);
    $res = $conn->query(
        "SELECT l.id FROM licenses l
         LEFT JOIN users u ON l.created_by = u.id
         WHERE l.id = $lid
           AND (l.created_by = $uid OR u.parent_id = $uid)"
    );
    return $res && $res->num_rows > 0;
}

// ── Toggle strict mode ────────────────────────────────────────
if ($action === 'toggle_strict_mode') {
    if ($my_role !== 'super_admin' && $my_role !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }
    $val = ($_POST['value'] ?? '0') === '1' ? '1' : '0';
    $key = 'ext_device_strict_mode';

    $exists = $conn->query("SELECT id FROM app_config WHERE config_key='$key'")->fetch_assoc();
    if ($exists) {
        $conn->query("UPDATE app_config SET config_value='$val' WHERE config_key='$key'");
    } else {
        $conn->query("INSERT INTO app_config (config_key,config_value) VALUES ('$key','$val')");
    }

    $label = $val === '1' ? 'enabled' : 'disabled';
    echo json_encode(['status' => 'success', 'message' => "Device strict mode $label successfully."]);
    exit;
}

// ── Unlock single device ──────────────────────────────────────
if ($action === 'unlock_device') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']); exit; }

    if (!can_manage($conn, $id, $my_id, $my_role)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }

    // Clear device_id (unlock) and filed_1 (invalidate session token)
    $stmt = $conn->prepare("UPDATE licenses SET device_id = NULL, filed_1 = NULL WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Device unlocked. User must log in again.']);
    exit;
}

// ── Bulk unlock ───────────────────────────────────────────────
if ($action === 'unlock_bulk') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No IDs provided.']); exit;
    }

    $unlocked = 0;
    foreach ($ids as $raw_id) {
        $id = intval($raw_id);
        if (!$id) continue;
        if (!can_manage($conn, $id, $my_id, $my_role)) continue;

        $stmt = $conn->prepare("UPDATE licenses SET device_id = NULL, filed_1 = NULL WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $unlocked++;
    }

    echo json_encode(['status' => 'success', 'message' => "$unlocked device(s) unlocked successfully."]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
ob_end_flush();
?>
