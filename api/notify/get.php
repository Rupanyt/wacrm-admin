<?php
// =============================================================
//  api/notify/get.php
//
//  GET /api/notify/get/{premium|free}/{chromeStoreID}
//  Header: access-token: {cript_key}
//          Content-Type: application/json
//          accept: application/json
//
//  Returns all active announcements relevant to the caller's
//  user type (premium / free).
//  viewer values: NOTIFY | MODAL | INBOX | EXTERNAL_PAGE
// =============================================================

require_once '../../include/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Access-Token, access-token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ── Validate access-token ─────────────────────────────────────
$expected = get_config('services_cript_key') ?: 'ffce211a-7b07-4d91-ba5d-c40bb4034a83';
$hdrs     = array_change_key_case(getallheaders(), CASE_LOWER);
$token    = trim($hdrs['access-token'] ?? '');

if (!hash_equals($expected, $token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg_id' => 'invalid_token', 'notify' => []]);
    exit;
}

// ── URL params (set by .htaccess) ─────────────────────────────
$user_type = strtolower(trim($_GET['user_type']  ?? 'free'));   // premium | free
$chrome_id = trim($_GET['chrome_id'] ?? '');

// Normalise — anything not 'premium' is treated as 'free'
if (!in_array($user_type, ['premium', 'free'])) {
    $user_type = 'free';
}

// ── Query active announcements ────────────────────────────────
// audience: 'all' goes to everyone; 'premium' only to premium;
//           'free' only to free users.
$now = date('Y-m-d H:i:s');

$sql = "SELECT id, viewer, statement, link, btn_name, data
        FROM announcements
        WHERE is_active = 1
          AND (audience = 'all' OR audience = ?)
          AND (start_at IS NULL OR start_at <= ?)
          AND (end_at   IS NULL OR end_at   >= ?)
        ORDER BY sort_order DESC, created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $user_type, $now, $now);
$stmt->execute();
$result = $stmt->get_result();

$notify = [];
while ($row = $result->fetch_assoc()) {
    $item = [
        'id'     => (string)$row['id'],
        'viewer' => $row['viewer'],
    ];

    // Only include non-null fields — keep payload lean
    if (!empty($row['statement'])) $item['statement'] = $row['statement'];
    if (!empty($row['link']))      $item['link']      = $row['link'];
    if (!empty($row['btn_name']))  $item['btnName']   = $row['btn_name'];
    if ($row['data'] !== null)     $item['data']      = (int)$row['data'];

    $notify[] = $item;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'notify'  => $notify,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
