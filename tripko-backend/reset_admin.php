<?php
$host = 'localhost';
$dbname = 'tripko_db';
$dbuser = 'root';
$dbpass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = 'admin';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE user SET password = :password WHERE username = :username AND user_type_id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':password', $hash);
    $stmt->bindParam(':username', $username);
    
    if ($stmt->execute()) {
        echo "Admin password reset successfully. You can now login with:<br>";
        echo "Username: admin<br>";
        echo "Password: admin123";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>