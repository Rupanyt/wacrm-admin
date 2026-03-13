<?php
include '../include/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all fields!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username,name, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            echo json_encode(['status' => 'success', 'message' => 'Login Successful! Redirecting...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Password!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found!']);
    }
    $stmt->close();
}
?>