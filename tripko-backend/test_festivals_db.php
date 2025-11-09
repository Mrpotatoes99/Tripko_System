<?php
// Test database connection and table structure
require_once(__DIR__ . '/config/db.php');

echo "Testing database connection...\n";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected successfully to database\n";

// Check if festivals table exists
$result = $conn->query("SHOW TABLES LIKE 'festivals'");
if ($result->num_rows > 0) {
    echo "Festivals table exists\n";
    
    // Show table structure
    $result = $conn->query("DESCRIBE festivals");
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
    
    // Show sample data
    $result = $conn->query("SELECT * FROM festivals LIMIT 5");
    echo "\nSample data:\n";
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Festivals table does not exist\n";
    
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS festivals (
        festival_id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        date DATE DEFAULT NULL,
        town_id INT(11) DEFAULT NULL,
        image_path TEXT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        PRIMARY KEY (festival_id),
        FOREIGN KEY (town_id) REFERENCES towns(town_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($sql)) {
        echo "Festivals table created successfully\n";
        
        // Insert sample data
        $sampleData = "INSERT INTO festivals (name, description, date, town_id, image_path, status) VALUES
            ('Binungey Festival', 'Celebration of the local delicacy binungey', '2025-05-15', 14, 'binungey-fest.jpg', 'active'),
            ('Hundred Islands Festival', 'Annual celebration of Alaminos culture', '2025-04-30', 3, 'alaminos.jpg', 'active'),
            ('Pista''y Dayat', 'Sea Festival celebrating marine resources', '2025-05-01', 22, NULL, 'active');";
            
        if ($conn->multi_query($sampleData)) {
            echo "Sample data inserted successfully\n";
        } else {
            echo "Error inserting sample data: " . $conn->error . "\n";
        }
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}
