<?php
// api/extension_services_api.php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save_services_config') {

    $fields = [
        'services_cript_key',
        'ext_nome',
        'ext_wl_id',
        'ext_checkout_url',
        'ext_painel_cliente',
        'ext_tutorial_url',
        'ext_support_premium',
        'ext_support_free',
        'ext_phone_lookup_enabled',
    ];

    // Validate crypt key not empty
    if (empty(trim($_POST['services_cript_key'] ?? ''))) {
        echo json_encode(['status'=>'error','message'=>'Crypt Key cannot be empty.']); exit;
    }

    foreach ($fields as $field) {
        $key = $conn->real_escape_string($field);
        $val = $conn->real_escape_string(trim($_POST[$field] ?? ''));
        $exists = $conn->query("SELECT id FROM app_config WHERE config_key='$key'")->fetch_assoc();
        if ($exists) {
            $conn->query("UPDATE app_config SET config_value='$val' WHERE config_key='$key'");
        } else {
            $conn->query("INSERT INTO app_config (config_key, config_value) VALUES ('$key','$val')");
        }
    }

    echo json_encode(['status'=>'success','message'=>'Extension services config saved successfully!']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action.']);
ob_end_flush();
?>