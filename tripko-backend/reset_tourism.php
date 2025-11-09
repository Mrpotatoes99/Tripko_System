<?php
$host = 'localhost';
$dbname = 'tripko_db';
$dbuser = 'root';
$dbpass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Credentials to create/update
    $username = 'agnotourism';
    $password = 'agno123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Find a town_id to assign the tourism officer to (pick first town)
    $townStmt = $conn->query("SELECT town_id FROM towns LIMIT 1");
    $townRow = $townStmt->fetch(PDO::FETCH_ASSOC);
    $town_id = $townRow ? $townRow['town_id'] : null;

    if (!$town_id) {
        echo "No towns found in the database. Please create a town first.";
        exit;
    }

    // Check if user exists
    $check = $conn->prepare("SELECT user_id FROM user WHERE username = :username");
    $check->bindParam(':username', $username);
    $check->execute();
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing user to be tourism officer with new password and town
        $sql = "UPDATE user SET password = :password, user_type_id = 3, town_id = :town_id, user_status_id = 1 WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':town_id', $town_id);
        $stmt->bindParam(':user_id', $existing['user_id']);
        $stmt->execute();
        echo "Tourism officer account updated successfully.\n";
    } else {
        // Insert new tourism officer
        $sql = "INSERT INTO user (username, password, user_type_id, town_id, user_status_id) VALUES (:username, :password, 3, :town_id, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':town_id', $town_id);
        $stmt->execute();
        echo "Tourism officer account created successfully.\n";
    }

    echo "Username: " . $username . "<br>Password: " . $password;

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>
