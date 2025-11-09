<?php
// Simple test endpoint to verify connectivity
// Suppress all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    echo json_encode([
        'status' => 'OK',
        'message' => 'Backend connection working',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Test endpoint error: ' . $e->getMessage()
    ]);
}
?>