<?php
// 1. Password variable define karein
$plain_password = "123456";

// 2. Password ko hash karein (PASSWORD_BCRYPT standard hai)
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// 3. Result ko echo karein
echo "Original Password: " or die($plain_password);
echo "<br>";
echo "Hashed Password: " . $hashed_password;
?>