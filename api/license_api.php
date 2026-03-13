<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'save_license') {
    $software = clean_input($_POST['software_name']);
    $client   = clean_input($_POST['client_name']);
    $mobile   = clean_input($_POST['client_mobile']);
    $expiry   = clean_input($_POST['expiry_date']);
  
    $key = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
    $final_key = implode("-", str_split($key, 4));

    $stmt = $conn->prepare("INSERT INTO licenses (license_key, software_name, client_name, client_mobile, created_by, status, expiry_date) VALUES (?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("ssssis", $final_key, $software, $client, $mobile, $my_id, $expiry);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'License Generated: ' . $final_key]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
    }
    $stmt->close();
    exit;
}

if (in_array($action, ['update_license', 'delete_license', 'toggle_status'])) {
    $id = intval($_POST['id']);
    $is_authorized = false;

    if ($my_role === 'super_admin') {
        $is_authorized = true; 
    } else {
        $stmt = $conn->prepare("SELECT created_by FROM licenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $license = $result->fetch_assoc();
            $creator_id = $license['created_by'];

            if ($creator_id == $my_id) {
                $is_authorized = true;
            } elseif ($my_role === 'admin') {
                $res_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND parent_id = ?");
                $res_stmt->bind_param("ii", $creator_id, $my_id);
                $res_stmt->execute();
                if ($res_stmt->get_result()->num_rows > 0) {
                    $is_authorized = true;
                }
                $res_stmt->close();
            }
        }
        $stmt->close();
    }

    if (!$is_authorized) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized! Access Denied.']);
        exit;
    }

    if ($action == 'update_license') {
        $software = clean_input($_POST['software_name']);
        $client   = clean_input($_POST['client_name']);
        $status   = clean_input($_POST['status']);
        $expiry   = clean_input($_POST['expiry_date']);

        $stmt = $conn->prepare("UPDATE licenses SET software_name=?, client_name=?, status=?, expiry_date=? WHERE id=?");
        $stmt->bind_param("ssssi", $software, $client, $status, $expiry, $id);
        if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'License updated successfully!']);
        else echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        $stmt->close();
    }

    if ($action == 'delete_license') {
        $stmt = $conn->prepare("DELETE FROM licenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'License removed!']);
        else echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
        $stmt->close();
    }

    if ($action == 'toggle_status') {
        $current_status = clean_input($_POST['status']);
        $new_status = ($current_status == 'active') ? 'blocked' : 'active';
        $stmt = $conn->prepare("UPDATE licenses SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Status: ' . $new_status]);
        else echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        $stmt->close();
    }
}
ob_end_flush();
?>