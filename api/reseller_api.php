<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

if ($my_role !== 'super_admin' && $my_role !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'save_reseller') {
    $name     = clean_input($_POST['name']);
    $username = clean_input($_POST['username']);
    $mobile   = clean_input($_POST['mobile']);
    $email    = clean_input($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists!']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO users (name, username, mobile, email, password, role, parent_id, status) VALUES (?, ?, ?, ?, ?, 'reseller', ?, 'active')");
    $stmt->bind_param("sssssi", $name, $username, $mobile, $email, $password, $my_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Reseller account created!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    $stmt->close();
}
if (in_array($action, ['toggle_user_status', 'update_reseller', 'delete_user'])) {
    $target_id = intval($_POST['id']);

    if ($my_role === 'super_admin') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'reseller'");
        $stmt->bind_param("i", $target_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND parent_id = ? AND role = 'reseller'");
        $stmt->bind_param("ii", $target_id, $my_id);
    }
    
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized! You do not own this reseller account.']);
        exit;
    }
    $stmt->close();

    if ($action == 'toggle_user_status') {
        $current = clean_input($_POST['status']);
        $new_status = ($current == 'active') ? 'blocked' : 'active';
        
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $target_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Status updated to ' . $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
    }

    if ($action == 'update_reseller') {
        $name   = clean_input($_POST['name']);
        $mobile = clean_input($_POST['mobile']);
        $email  = clean_input($_POST['email']);
        
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, mobile=?, email=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $mobile, $email, $pass, $target_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, mobile=?, email=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $mobile, $email, $target_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reseller updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
    }

    if ($action == 'delete_user') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reseller deleted forever!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
        }
    }
}

ob_end_flush();
?>