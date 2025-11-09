<?php
require_once(__DIR__ . '/../../config/db.php');

try {
    // Add status column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM festivals LIKE 'status'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE festivals ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
        if ($conn->query($sql)) {
            echo "Status column added successfully\n";
            
            // Update existing records to active
            $conn->query("UPDATE festivals SET status = 'active' WHERE status IS NULL");
            echo "Existing records updated to active status\n";
        } else {
            echo "Error adding status column: " . $conn->error . "\n";
        }
    } else {
        echo "Status column already exists\n";
    }
    
    // Show current data
    $result = $conn->query("SELECT f.*, t.town_name FROM festivals f LEFT JOIN towns t ON f.town_id = t.town_id");
    while ($row = $result->fetch_assoc()) {
        echo "\nFestival data:\n";
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
