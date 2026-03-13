<?php
include '../include/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_settings') {
    
    $user_id = $_SESSION['user_id'];
    $site_name = clean_input($_POST['site_name']);
    $theme_color = clean_input($_POST['theme_color']);
    $software_name = clean_input($_POST['software_name']);
    $support_email = clean_input($_POST['support_email']);
    $support_whatsapp = clean_input($_POST['support_whatsapp']);

    // Branding updates
    $conn->query("UPDATE app_config SET config_value = '$site_name' WHERE config_key = 'site_name'");
    $conn->query("UPDATE app_config SET config_value = '$theme_color' WHERE config_key = 'theme_color'");
    $conn->query("UPDATE app_config SET config_value = '$software_name' WHERE config_key = 'default_software'");
    $conn->query("UPDATE app_config SET config_value = '$support_email' WHERE config_key = 'support_email'");
    $conn->query("UPDATE app_config SET config_value = '$support_whatsapp' WHERE config_key = 'support_whatsapp'");

    // ==========================================
    // ACCOUNT SECURITY (Username & Password) Logic
    // ==========================================
    $new_username = clean_input($_POST['new_username']);
    $auth_password = $_POST['auth_password']; // Current password for verification
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if user wants to change sensitive info
    if ($new_username !== $_SESSION['username'] || !empty($new_password)) {
        
        if (empty($auth_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Current password is required to save security changes!']);
            exit;
        }

        // Verify Current Password from DB
        $user_query = $conn->query("SELECT password FROM users WHERE id = '$user_id'");
        $user_data = $user_query->fetch_assoc();

        if (!password_verify($auth_password, $user_data['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Verification failed! Current password is incorrect.']);
            exit;
        }

        // 1. Update Username if changed
        if ($new_username !== $_SESSION['username']) {
            // Check if username already exists
            $check_exists = $conn->query("SELECT id FROM users WHERE username = '$new_username' AND id != '$user_id'");
            if ($check_exists->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username already taken by another user!']);
                exit;
            }
            
            $conn->query("UPDATE users SET username = '$new_username' WHERE id = '$user_id'");
            $_SESSION['username'] = $new_username; // Update session
        }

        // 2. Update Password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                echo json_encode(['status' => 'error', 'message' => 'New passwords do not match!']);
                exit;
            }
            $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hashed_pass' WHERE id = '$user_id'");
        }
    }
    // ==========================================

    // Handle Logo Uploads
    if (!empty($_FILES['rect_logo']['name'])) {
        $upload = uploadImage($_FILES['rect_logo'], "logo-rect-" . time());
        if ($upload['status'] === 'success') {
            $path = $upload['path'];
            $conn->query("UPDATE app_config SET config_value = '$path' WHERE config_key = 'rect_logo_path'");
        } else {
            echo json_encode($upload); exit;
        }
    }

    if (!empty($_FILES['circle_logo']['name'])) {
        $upload = uploadImage($_FILES['circle_logo'], "icon-circle-" . time());
        if ($upload['status'] === 'success') {
            $path = $upload['path'];
            $conn->query("UPDATE app_config SET config_value = '$path' WHERE config_key = 'circle_logo_path'");
        } else {
            echo json_encode($upload); exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Settings and Security updated successfully!']);
}

function uploadImage($file, $target_name) {
    $target_dir = "../assets/";
    
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            return ['status' => 'error', 'message' => "Failed to create assets directory."];
        }
    }

    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_exts = array("jpg", "jpeg", "png");
    
    if (!in_array($file_ext, $allowed_exts)) {
        return ['status' => 'error', 'message' => "Only JPG, JPEG, & PNG files are allowed."];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mimes = array("image/jpeg", "image/png");
    if (!in_array($mime, $allowed_mimes)) {
        return ['status' => 'error', 'message' => "Invalid image content!"];
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        return ['status' => 'error', 'message' => "File size should be less than 2MB."];
    }

    $final_name = $target_name . "." . $file_ext;
    $target_file = $target_dir . $final_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['status' => 'success', 'path' => "assets/" . $final_name];
    }

    return ['status' => 'error', 'message' => "Failed to move uploaded file."];
}
?>