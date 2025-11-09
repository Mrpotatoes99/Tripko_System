<?php
// Set session cookie parameters for better security and compatibility
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_samesite', 'Lax');

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers to allow AJAX requests from the same origin
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize session if not already initialized
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
    $_SESSION['expires'] = time() + (2 * 60 * 60); // 2 hours
}
