<?php
require_once(__DIR__ . '/config/db.php');

try {
    // Add status column if not exists
    $alterSql = "ALTER TABLE festivals ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'";
    if ($conn->query($alterSql)) {
        echo "Status column added or already exists.\n";
        
        // Set existing records to active
        $updateSql = "UPDATE festivals SET status = 'active' WHERE status IS NULL";
        if ($conn->query($updateSql)) {
            echo "Existing records updated to active status.\n";
        } else {
            echo "Error updating existing records: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding status column: " . $conn->error . "\n";
    }
    
    // Show current data
    $result = $conn->query("SELECT * FROM festivals");
    echo "\nCurrent festivals data:\n";
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
