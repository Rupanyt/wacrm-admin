<?php
// api/extension_tracker_api.php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit;
}

$action = $_POST['action'] ?? '';

// ── Save extension URL config ─────────────────────────────────
if ($action === 'save_ext_urls') {
    $install_url    = trim($_POST['install_url']   ?? '');
    $update_url     = trim($_POST['update_url']    ?? '');
    $uninstall_url  = trim($_POST['uninstall_url'] ?? '');

    // Validate URLs
    if (!empty($install_url) && !filter_var($install_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status'=>'error','message'=>'Invalid Install URL.']); exit;
    }
    if (!empty($update_url) && !filter_var($update_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status'=>'error','message'=>'Invalid Update Notes URL.']); exit;
    }
    if (!empty($uninstall_url) && !filter_var($uninstall_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status'=>'error','message'=>'Invalid Uninstall URL.']); exit;
    }

    $configs = [
        'ext_install_redirect_url'   => $install_url,
        'ext_update_notes_url'       => $update_url,
        'ext_uninstall_redirect_url' => $uninstall_url,
    ];

    foreach ($configs as $key => $val) {
        $k = $conn->real_escape_string($key);
        $v = $conn->real_escape_string($val);
        $exists = $conn->query("SELECT id FROM app_config WHERE config_key='$k'")->fetch_assoc();
        if ($exists) {
            $conn->query("UPDATE app_config SET config_value='$v' WHERE config_key='$k'");
        } else {
            $conn->query("INSERT INTO app_config (config_key, config_value) VALUES ('$k','$v')");
        }
    }

    echo json_encode(['status'=>'success','message'=>'URL configuration saved successfully!']);
    exit;
}

// ── Save domSelector JSON ─────────────────────────────────────
if ($action === 'save_dom_selector') {
    $version = trim($_POST['version'] ?? '');
    $json    = trim($_POST['json']    ?? '');

    if (empty($version)) { echo json_encode(['status'=>'error','message'=>'Version is required.']); exit; }

    // Validate JSON
    $decoded = json_decode($json);
    if ($decoded === null) {
        echo json_encode(['status'=>'error','message'=>'Invalid JSON: '.json_last_error_msg()]); exit;
    }

    // Force version into the JSON
    $decoded->version = $version;
    $json_final = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $v_esc = $conn->real_escape_string($version);
    $j_esc = $conn->real_escape_string($json_final);

    // Update dom_selector_version
    $exists = $conn->query("SELECT id FROM app_config WHERE config_key='dom_selector_version'")->fetch_assoc();
    if ($exists) $conn->query("UPDATE app_config SET config_value='$v_esc' WHERE config_key='dom_selector_version'");
    else $conn->query("INSERT INTO app_config (config_key, config_value) VALUES ('dom_selector_version','$v_esc')");

    // Update dom_selector_json
    $exists2 = $conn->query("SELECT id FROM app_config WHERE config_key='dom_selector_json'")->fetch_assoc();
    if ($exists2) $conn->query("UPDATE app_config SET config_value='$j_esc' WHERE config_key='dom_selector_json'");
    else $conn->query("INSERT INTO app_config (config_key, config_value) VALUES ('dom_selector_json','$j_esc')");

    echo json_encode(['status'=>'success','message'=>'domSelector v'.$version.' published! All extensions will refresh within 10 minutes.']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action.']);
ob_end_flush();
?>