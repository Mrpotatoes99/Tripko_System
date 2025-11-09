<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure Database class is available when session checks need DB access
require_once __DIR__ . '/config/Database.php';

// Helper to detect API / AJAX requests so we can return JSON errors instead of HTML redirects
function isApiRequest() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xRequested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    if (stripos($accept, 'application/json') !== false) return true;
    if (strtolower($xRequested) === 'xmlhttprequest') return true;
    if (stripos($uri, '/api/') !== false) return true;

    return false;
}

function checkSession($required_type = null) {
    // Enhanced security check for session fixation
    if (!isset($_SESSION['initialized'])) {
        session_regenerate_id(true);
        $_SESSION['initialized'] = true;
    }

    // Check if session exists and has required data
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type_id']) || !isset($_SESSION['username'])) {
        session_destroy();
        if (isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit();
        }
        header("Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?error=session");
        exit();
    }

    // Check session expiration with grace period
    if (!isset($_SESSION['expires']) || time() > $_SESSION['expires']) {
        session_destroy();
        if (isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Session expired (timeout). Please login again.']);
            exit();
        }
        header("Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?error=timeout");
        exit();
    }

    // Refresh session expiration on activity
    $_SESSION['expires'] = time() + (2 * 60 * 60); // 2 hours

    // If specific user type is required
    if ($required_type !== null) {
        if ($_SESSION['user_type_id'] != $required_type) {
            if (isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Forbidden: insufficient privileges']);
                exit();
            }
            if ($_SESSION['user_type_id'] == 1) {
                header("Location: /tripko-system/tripko-frontend/file_html/dashboard.php");
            } else {
                header("Location: /tripko-system/tripko-frontend/file_html/user/homepage-new.html");
            }
            exit();
        }
    }

    // Extend session timeout
    $_SESSION['expires'] = time() + (2 * 60 * 60);
}

function checkAdminSession() {
    checkSession(1); // 1 is admin type
}

function checkUserSession() {
    checkSession(2); // 2 is regular user type
}

function isAdmin() {
    return isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isTourismOfficer() {
    return isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3;
}

function checkTourismOfficerSession() {
    checkSession(3); // 3 is tourism officer type
    
    // Additionally check and set town_id if not already set
    if (!isset($_SESSION['town_id'])) {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            $query = "SELECT town_id FROM user WHERE user_id = ? AND user_type_id = 3";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $_SESSION['town_id'] = $row['town_id'];
                }
                $stmt->close();
            }
        }
        
        if (!isset($_SESSION['town_id'])) {
            session_destroy();
            if (isApiRequest()) {
                http_response_code(400);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'No town assigned to this tourism officer']);
                exit();
            }
            header("Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?error=no_town");
            exit();
        }
    }
}
?>