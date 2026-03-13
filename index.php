<?php
// Check karein agar session pehle se start nahi hai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar user logged in nahi hai, toh login.php par bhej dein
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
} else {
    // Agar user logged in hai, toh dashboard par bhej dein
    header("Location: dashboard");
    exit();
}
?>