<?php
// filepath: c:\xampp\htdocs\tripko-system\tripko-backend\api\festival\fix_table.php
require_once(__DIR__ . '/../../config/db.php');

try {
    // Check if status column exists
    $result = $conn->query("SHOW COLUMNS FROM festivals LIKE 'status'");
    if ($result->num_rows === 0) {
        // Add status column if it doesn't exist
        $sql = "ALTER TABLE festivals ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
        if ($conn->query($sql)) {
            echo "Status column added successfully\n";
        } else {
            echo "Error adding status column: " . $conn->error . "\n";
        }
    } else {
        echo "Status column already exists\n";
    }
    
    // Show current data in festivals table
    $sql = "SELECT * FROM festivals";
    $result = $conn->query($sql);
    
    echo "\nCurrent festivals in database:\n";
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "No festivals found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
