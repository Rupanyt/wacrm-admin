<?php
// response/gdcrm/api/auth/login.php
require_once '../../../include/config.php'; 

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Token");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// 1. Headers
$headers = getallheaders();
$accessToken = isset($headers['Access-Token']) ? $headers['Access-Token'] : '';

// 2. Payload reading
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Check: Extension sends 'email' as the License Key
if (!isset($data['email'])) {
    echo json_encode([
        "success" => false,
        "message" => "License Key is missing",
        "msg_id"  => "missing_fields"
    ]);
    exit;
}

$license_key_input = trim($data['email']); 

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "msg_id"  => "db_connection_error"
    ]);
    exit;
}

// FIX 1: Table mein 'status' nahi 'is_enable' column hai
// FIX 2: bind_param mein sirf 1 variable pass hoga
$sql = "SELECT * FROM licenses WHERE license_key = ? AND status = 'active' LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $license_key_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid or Blocked License Key",
        "msg_id"  => "user_login_notFund"
    ]);
    exit;
}

$license = $result->fetch_assoc();

// Expiry Check (Table column: expiry_date)
$today = time();
$expiry_timestamp = strtotime($license['expiry_date']);

if ($license['expiry_date'] !== NULL && $expiry_timestamp < $today) {
    echo json_encode([
        "success" => false,
        "message" => "This license has expired",
        "msg_id"  => "plan_expired"
    ]);
    exit;
}

// ========================================================
// ✅ 10-TIME LOGIN FEATURE (Logic using 'device_id' as counter)
// ========================================================
// Note: Aapke SQL schema mein device_id text column hai
$current_counter = trim($license['device_id']);
$new_count = 0;

if ($current_counter === "" || $current_counter === NULL) {
    $new_count = 9; // Pehla login (10 logins allowed, 1st used)
} else {
    $new_count = intval($current_counter) - 1;
}

if ($new_count < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Login limit exceeded! Maximum 10 logins allowed.",
        "msg_id"  => "limit_exceeded"
    ]);
    exit;
}

// Database Update
$updateStmt = $conn->prepare("UPDATE licenses SET device_id = ? WHERE id = ?");
$update_val = (string)$new_count;
$updateStmt->bind_param("si", $update_val, $license['id']);
$updateStmt->execute();
$updateStmt->close();
// ========================================================

// Tokens Generation
$bearer_token = bin2hex(random_bytes(32)); 
$plugin_token = "eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9." . base64_encode(json_encode(['sub' => $license['id'], 'iat' => time()])) . ".dummy_sig";

$response = [
    "success" => true,
    "message" => "Seja bem-vindo de volta, " . ($license['client_name'] ?? 'User') . "!",
    "msg_id"  => "successfully_login",
    "user"    => [
        "user_id"             => (string)$license['id'],
        "email"               => $license['client_email'] ?? 'user@gmail.com',
        "name"                => $license['client_name'] ?? "User",
        "access_token_plugin" => $plugin_token,
        "bearer_token"        => $bearer_token,
        "device_id"           => (string)$new_count, 
        "user_premium"        => true,
        "wl_id"               => null
    ],
    "user_status" => "active"
];

echo json_encode($response, JSON_PRETTY_PRINT);
$stmt->close();
$conn->close();
exit;
?>