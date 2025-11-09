<?php
require_once('../../config/Database.php');
header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

try {
    // Check if tourist_spots table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tourist_spots'");
    if ($tableCheck->num_rows === 0) {
        // Create tourist_spots table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS tourist_spots (
            spot_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            location VARCHAR(255),
            contact_info VARCHAR(100),
            operating_hours VARCHAR(255),
            entrance_fee VARCHAR(100),
            image_path VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            town_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (town_id) REFERENCES towns(town_id)
        )";
        $conn->query($createTable);
    }

    // Check table structure
    $columns = $conn->query("SHOW COLUMNS FROM tourist_spots");
    $columnData = [];
    while($col = $columns->fetch_assoc()) {
        $columnData[] = $col;
    }

    // Check for existing spots
    $spots = $conn->query("SELECT COUNT(*) as count FROM tourist_spots WHERE town_id = 1");
    $spotCount = $spots->fetch_assoc()['count'];

    // If no spots exist for Agno (town_id = 1), add a sample spot
    if ($spotCount == 0) {
        $sampleSpot = "INSERT INTO tourist_spots (
            name, description, category, location, town_id, status
        ) VALUES (
            'Agno Beach',
            'Beautiful beach in Agno with white sand and clear waters',
            'Beach',
            'Agno, Pangasinan',
            1,
            'active'
        )";
        $conn->query($sampleSpot);
    }

    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columnData,
        'existing_spots' => $spotCount,
        'message' => 'Tourist spots table verified and sample data added if needed'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
