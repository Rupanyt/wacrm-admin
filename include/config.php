<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root"; 
$pass = "";   
$dbname = "wacrm"; 

$conn = new mysqli($host, $user, $pass, $dbname);


if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function get_config($key) {
    global $conn;
    $key = $conn->real_escape_string($key);
    $query = "SELECT config_value FROM app_config WHERE config_key = '$key' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['config_value'];
    }
    return null;
}


function clean_input($data) {
    global $conn;
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
}

date_default_timezone_set("Asia/Kolkata");

$base_url = "https://crm.waclick.in/"; 

?>
