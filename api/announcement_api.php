<?php
// api/announcement_api.php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit;
}

$action  = $_POST['action'] ?? '';
$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

// ── Helpers ───────────────────────────────────────────────────
function valid_viewer(string $v): bool {
    return in_array($v, ['NOTIFY','MODAL','INBOX','EXTERNAL_PAGE']);
}
function valid_audience(string $a): bool {
    return in_array($a, ['all','premium','free']);
}
function to_dt(?string $v): ?string {
    if (empty(trim($v ?? ''))) return null;
    $ts = strtotime($v);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

// ── CREATE ────────────────────────────────────────────────────
if ($action === 'create') {
    $title      = trim($_POST['title']      ?? '');
    $viewer     = trim($_POST['viewer']     ?? '');
    $audience   = trim($_POST['audience']   ?? 'all');
    $statement  = trim($_POST['statement']  ?? '');
    $link       = trim($_POST['link']       ?? '');
    $btn_name   = trim($_POST['btn_name']   ?? '');
    $data_val   = $_POST['data'] !== '' ? intval($_POST['data']) : null;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $start_at   = to_dt($_POST['start_at'] ?? '');
    $end_at     = to_dt($_POST['end_at']   ?? '');
    $is_active  = intval($_POST['is_active'] ?? 1) ? 1 : 0;

    if (empty($title))          { echo json_encode(['status'=>'error','message'=>'Title is required.']); exit; }
    if (!valid_viewer($viewer)) { echo json_encode(['status'=>'error','message'=>'Invalid viewer type.']); exit; }
    if (!valid_audience($audience)) { echo json_encode(['status'=>'error','message'=>'Invalid audience.']); exit; }

    $stmt = $conn->prepare(
        "INSERT INTO announcements (title,viewer,audience,statement,link,btn_name,data,sort_order,start_at,end_at,is_active,created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param('ssssssiissii',
        $title, $viewer, $audience, $statement, $link, $btn_name,
        $data_val, $sort_order, $start_at, $end_at, $is_active, $my_id
    );
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Announcement created successfully.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Database error: '.$stmt->error]);
    }
    $stmt->close(); exit;
}

// ── UPDATE ────────────────────────────────────────────────────
if ($action === 'update') {
    $id         = intval($_POST['id'] ?? 0);
    $title      = trim($_POST['title']      ?? '');
    $viewer     = trim($_POST['viewer']     ?? '');
    $audience   = trim($_POST['audience']   ?? 'all');
    $statement  = trim($_POST['statement']  ?? '');
    $link       = trim($_POST['link']       ?? '');
    $btn_name   = trim($_POST['btn_name']   ?? '');
    $data_val   = (isset($_POST['data']) && $_POST['data'] !== '') ? intval($_POST['data']) : null;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $start_at   = to_dt($_POST['start_at'] ?? '');
    $end_at     = to_dt($_POST['end_at']   ?? '');
    $is_active  = intval($_POST['is_active'] ?? 1) ? 1 : 0;

    if (!$id || empty($title) || !valid_viewer($viewer) || !valid_audience($audience)) {
        echo json_encode(['status'=>'error','message'=>'Invalid input.']); exit;
    }

    $stmt = $conn->prepare(
        "UPDATE announcements SET title=?,viewer=?,audience=?,statement=?,link=?,btn_name=?,data=?,
         sort_order=?,start_at=?,end_at=?,is_active=? WHERE id=?"
    );
    $stmt->bind_param('ssssssiissii',
        $title, $viewer, $audience, $statement, $link, $btn_name,
        $data_val, $sort_order, $start_at, $end_at, $is_active, $id
    );
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Announcement updated.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Database error.']);
    }
    $stmt->close(); exit;
}

// ── TOGGLE active ─────────────────────────────────────────────
if ($action === 'toggle') {
    $id  = intval($_POST['id']    ?? 0);
    $val = intval($_POST['value'] ?? 0) ? 1 : 0;
    if (!$id) { echo json_encode(['status'=>'error','message'=>'Invalid ID.']); exit; }

    $stmt = $conn->prepare("UPDATE announcements SET is_active=? WHERE id=?");
    $stmt->bind_param('ii', $val, $id);
    $stmt->execute();
    $label = $val ? 'activated' : 'deactivated';
    echo json_encode(['status'=>'success','message'=>"Announcement $label."]);
    $stmt->close(); exit;
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status'=>'error','message'=>'Invalid ID.']); exit; }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Announcement deleted.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Could not delete.']);
    }
    $stmt->close(); exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action.']);
ob_end_flush();
?>
