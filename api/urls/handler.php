<?php
// ============================================================
// api/urls/handler.php
// Called by Chrome extension background.js — NO session needed
//
//  GET /api/urls/install/{ext_id}   → JSON {success, url}
//  GET /api/urls/uninstall/{ext_id} → silent log
//  GET /api/urls/notes/{ext_id}     → redirect to notes URL
// ============================================================

require_once __DIR__ . '/../../include/config.php';

$action = $_GET['action'] ?? '';
$ext_id = trim($_GET['ext_id'] ?? '');
$ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip     = explode(',', $ip)[0];
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Validate ext_id — Chrome extension IDs are 32 lowercase letters
if (empty($ext_id) || !preg_match('/^[a-zA-Z0-9_-]{1,128}$/', $ext_id)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid extension ID.']);
    exit;
}

$ext_esc = $conn->real_escape_string($ext_id);
$ip_esc  = $conn->real_escape_string($ip);
$ua_esc  = $conn->real_escape_string(substr($ua, 0, 500));

// ── INSTALL ──────────────────────────────────────────────────
if ($action === 'install') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    // Log the install event
    $conn->query("INSERT INTO extension_installs (ext_id, event, ip_address, user_agent)
                  VALUES ('$ext_esc', 'install', '$ip_esc', '$ua_esc')");

    // Get configured redirect URL
    $redirect_url = get_config('ext_install_redirect_url') ?: 'https://crm.waclick.in/user_login';

    echo json_encode([
        'success' => true,
        'url'     => $redirect_url,
    ]);
    exit;
}

// ── UNINSTALL ─────────────────────────────────────────────────
if ($action === 'uninstall') {
    // No body needed — Chrome just fires this via setUninstallURL
    // Log it
    $conn->query("INSERT INTO extension_installs (ext_id, event, ip_address, user_agent)
                  VALUES ('$ext_esc', 'uninstall', '$ip_esc', '$ua_esc')");

    // Optionally redirect to a goodbye/feedback page
    $goodbye_url = get_config('ext_uninstall_redirect_url');
    if ($goodbye_url) {
        header('Location: ' . $goodbye_url);
    } else {
        http_response_code(200);
        echo 'ok';
    }
    exit;
}

// ── NOTES (update page) ──────────────────────────────────────
if ($action === 'notes') {
    // Log update event
    $conn->query("INSERT INTO extension_installs (ext_id, event, ip_address, user_agent)
                  VALUES ('$ext_esc', 'update', '$ip_esc', '$ua_esc')");

    // Redirect to notes/changelog page
    $notes_url = get_config('ext_update_notes_url') ?: 'https://crm.waclick.in/extension_notes';
    header('Location: ' . $notes_url);
    exit;
}

http_response_code(404);
echo 'Not found.';
?>