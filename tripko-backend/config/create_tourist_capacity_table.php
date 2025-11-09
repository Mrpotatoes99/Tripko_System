<?php
// Initialize database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "tripko_db";

$conn = new mysqli($host, $username, $password, $database, 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tourist_capacity table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS tourist_capacity (
    capacity_id INT AUTO_INCREMENT PRIMARY KEY,
    spot_id INT NOT NULL,
    current_capacity INT DEFAULT 0,
    max_capacity INT NOT NULL DEFAULT 100,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(100),
    FOREIGN KEY (spot_id) REFERENCES tourist_spots(spot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($create_table_sql)) {
    echo "Tourist capacity table created successfully<br>";

    // Initialize capacity records for existing tourist spots
    $init_records_sql = "
    INSERT IGNORE INTO tourist_capacity (spot_id, max_capacity)
    SELECT spot_id, 100 FROM tourist_spots
    WHERE spot_id NOT IN (SELECT spot_id FROM tourist_capacity)
    ";

    if ($conn->query($init_records_sql)) {
        echo "Default capacity records created for existing tourist spots<br>";
    } else {
        echo "Error creating default capacity records: " . $conn->error . "<br>";
    }
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>
