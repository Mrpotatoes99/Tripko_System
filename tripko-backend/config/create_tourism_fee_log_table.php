<?php
// Run this script once to create the tourism_fee_log table
require_once 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS tourism_fee_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    municipality_id INT NOT NULL,
    spot_id INT NOT NULL,
    name VARCHAR(100) NULL,
    num_tourists INT NOT NULL,
    visit_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (municipality_id) REFERENCES towns(town_id) ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES tourist_spots(spot_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "tourism_fee_log table created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
