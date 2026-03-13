<?php
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized! Only Super Admin can manage Admins.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';


if ($action == 'save_admin') {
    $name = clean_input($_POST['name']);
    $username = clean_input($_POST['username']);
    $mobile = clean_input($_POST['mobile']);
    $email = clean_input($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $parent = $_SESSION['user_id']; 
    
   
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already taken!']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (name, username, mobile, email, password, role, parent_id, status) VALUES (?, ?, ?, ?, ?, 'admin', ?, 'active')");
    $stmt->bind_param("sssssi", $name, $username, $mobile, $email, $password, $parent);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'New Admin created!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error occurred']);
    }
    $stmt->close();
}

if ($action == 'toggle_admin_status') {
    $id = intval($_POST['id']);
    $current = clean_input($_POST['status']);
    $new_status = ($current == 'active') ? 'blocked' : 'active';
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Admin status updated to ' . $new_status]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Status update failed']);
    }
    $stmt->close();
}

if ($action == 'update_admin') {
    $id = intval($_POST['id']);
    $name = clean_input($_POST['name']);
    $mobile = clean_input($_POST['mobile']);
    $email = clean_input($_POST['email']);
    
    if (!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, mobile=?, email=?, password=? WHERE id=? AND role='admin'");
        $stmt->bind_param("ssssi", $name, $mobile, $email, $pass, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, mobile=?, email=? WHERE id=? AND role='admin'");
        $stmt->bind_param("sssi", $name, $mobile, $email, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Admin details updated!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
    $stmt->close();
}

if ($action == 'delete_admin') {
    $id = intval($_POST['id']);
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Admin removed successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
    }
    $stmt->close();
}
?>