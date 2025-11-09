<?php
/**
 * Import SQL file to Railway MySQL Database
 */

echo "=== Railway Database Import ===\n\n";

// Railway credentials
$host = 'shortline.proxy.rlwy.net';
$port = 34658;
$user = 'root';
$pass = 'RRbIYHutzzBeDTcEOwvIWqmDSjRorSDh';
$db = 'railway';
$sqlFile = dirname(__DIR__) . '/tripko_schema_final.sql';

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("❌ ERROR: SQL file not found: $sqlFile\n");
}

$fileSize = filesize($sqlFile);
echo "SQL file: " . basename($sqlFile) . " (" . number_format($fileSize) . " bytes)\n";
echo "Target: $host:$port/$db\n\n";

// Connect to Railway database
echo "Connecting to Railway MySQL...\n";
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}
echo "✓ Connected successfully!\n\n";

// Drop and recreate database for clean import
echo "Preparing database (DROP and CREATE)...\n";
$conn->query("DROP DATABASE IF EXISTS `railway`");
$conn->query("CREATE DATABASE `railway` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('railway');
echo "✓ Database recreated\n\n";

// Disable foreign key checks for import
echo "Disabling foreign key checks...\n";
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
echo "✓ Foreign key checks disabled\n\n";

// Read SQL file
echo "Reading SQL file...\n";
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("❌ ERROR: Could not read SQL file\n");
}
echo "✓ SQL file loaded (" . number_format(strlen($sql)) . " characters)\n\n";

// Execute the entire SQL file
echo "Importing to Railway database...\n";
$startTime = microtime(true);

// Execute multi-query
if ($conn->multi_query($sql)) {
    $success = 0;
    $failed = 0;
    $errors = [];
    
    do {
        // Store result if available
        if ($result = $conn->store_result()) {
            $result->free();
        }
        
        // Check for errors
        if ($conn->errno) {
            $failed++;
            $errors[] = $conn->error;
            // Show first 10 errors
            if ($failed <= 10) {
                echo "  ⚠ Warning: " . $conn->error . "\n";
            }
        } else {
            $success++;
            // Show progress every 10 successful operations
            if ($success % 10 === 0) {
                echo "  Progress: $success queries executed...\n";
            }
        }
        
        // More results? (Continue even if there are errors)
        if (!$conn->more_results()) {
            break;
        }
    } while (@$conn->next_result()); // Suppress errors and continue
    
    $duration = round(microtime(true) - $startTime, 2);
    
    // Re-enable foreign key checks
    echo "\nRe-enabling foreign key checks...\n";
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    echo "✓ Foreign key checks re-enabled\n";
    
    echo "\n=== Import Complete ===\n";
    echo "✓ Success: $success operations\n";
    if ($failed > 0) {
        echo "⚠ Failed: $failed operations\n";
    }
    echo "⏱ Duration: {$duration}s\n\n";
} else {
    die("❌ ERROR: " . $conn->error . "\n");
}

// Verify tables were imported
echo "Verifying imported tables...\n";
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tableCount = $result->num_rows;
    echo "✓ Found $tableCount tables in database:\n";
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Show first 20 tables
    $displayCount = min(20, count($tables));
    for ($i = 0; $i < $displayCount; $i++) {
        echo "  - " . $tables[$i] . "\n";
    }
    if (count($tables) > 20) {
        echo "  ... and " . (count($tables) - 20) . " more\n";
    }
    
    echo "\n✅ SUCCESS: Database imported to Railway!\n";
} else {
    echo "❌ ERROR: Could not verify tables\n";
}

$conn->close();
?>
