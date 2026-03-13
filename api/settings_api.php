<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired.']); exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

if (!in_array($my_role, ['super_admin', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']); exit;
}

$action = $_POST['action'] ?? '';

// ── Save Settings by Section ───────────────────────────────────────────────
if ($action === 'save_settings_section') {
    $section = $_POST['section'] ?? '';

    $allowed_keys_by_section = [
        'general'  => ['site_name', 'theme_color', 'default_software', 'support_email', 'support_whatsapp', 'rect_logo_path', 'circle_logo_path'],
        'payment'  => ['bank_transfer_enabled', 'currency', 'currency_symbol', 'bank_account_name', 'bank_name', 'bank_account_no', 'bank_ifsc'],
        'razorpay' => ['razorpay_enabled', 'razorpay_key_id', 'razorpay_key_secret'],
    ];

    if (!isset($allowed_keys_by_section[$section])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid section.']); exit;
    }

    $allowed = $allowed_keys_by_section[$section];
    $errors  = 0;

    foreach ($allowed as $key) {
        if (!isset($_POST[$key])) continue;
        $val  = $conn->real_escape_string(trim($_POST[$key]));
        $key_safe = $conn->real_escape_string($key);

        $stmt = $conn->prepare("INSERT INTO app_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->bind_param("ss", $key, $val);
        if (!$stmt->execute()) $errors++;
        $stmt->close();
    }

    if ($errors === 0) {
        echo json_encode(['status' => 'success', 'message' => ucfirst($section) . ' settings saved successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Some settings could not be saved.']);
    }
}

// ── Legacy: save all settings at once (backward compat) ───────────────────
if ($action === 'save_settings') {
    $allowed_keys = ['site_name', 'theme_color', 'default_software', 'support_email', 'support_whatsapp', 'rect_logo_path', 'circle_logo_path'];
    $errors = 0;
    foreach ($allowed_keys as $key) {
        if (!isset($_POST[$key])) continue;
        $val = $conn->real_escape_string(trim($_POST[$key]));
        $stmt = $conn->prepare("INSERT INTO app_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->bind_param("ss", $key, $val);
        if (!$stmt->execute()) $errors++;
        $stmt->close();
    }
    echo $errors === 0
        ? json_encode(['status' => 'success', 'message' => 'Settings saved successfully!'])
        : json_encode(['status' => 'error', 'message' => 'Some settings could not be saved.']);
}

ob_end_flush();
?>
