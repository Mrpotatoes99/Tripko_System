<?php
$password = "admin123"; // Replace with your desired password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password Hash: " . $hash;
?>