<?php
require_once(__DIR__ . '/config/db.php');

try {
    // Add status column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM festivals LIKE 'status'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE festivals ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
        if ($conn->query($sql)) {
            // Update existing records to active status
            $conn->query("UPDATE festivals SET status = 'active'");
            echo "Status column added successfully and existing records updated\n";
        } else {
            echo "Error adding status column: " . $conn->error . "\n";
        }
    } else {
        echo "Status column already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Now let's update the read.php query
$read_file = __DIR__ . '/api/festival/read.php';
$content = file_get_contents($read_file);

// Update the SQL query to handle null status values
$new_content = str_replace(
    'SELECT
        f.festival_id,
        f.name,
        f.description,
        f.date,
        f.image_path,
        f.status,
        f.town_id,
        t.town_name',
    'SELECT
        f.festival_id,
        f.name,
        f.description,
        f.date,
        f.image_path,
        COALESCE(f.status, "active") as status,
        f.town_id,
        t.town_name',
    $content
);

if (file_put_contents($read_file, $new_content)) {
    echo "read.php updated successfully\n";
} else {
    echo "Error updating read.php\n";
}

// Show current festivals data
echo "\nCurrent festivals in database:\n";
$result = $conn->query("SELECT * FROM festivals");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "No festivals found\n";
}
