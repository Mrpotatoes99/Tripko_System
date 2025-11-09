<?php
session_start();
// Simple role routing stub to replace missing legacy super admin dashboard
// Ensures login redirect for user_type_id=1 does not 404
if (!isset($_SESSION['user_id'])) {
    header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php');
    exit;
}

$role = (int)($_SESSION['user_type_id'] ?? 0);
if ($role === 1) {
    header('Location: /tripko-system/tripko-frontend/file_html/admin/dashboard.php');
    exit;
} elseif ($role === 3) {
    header('Location: /tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php');
    exit;
} else {
    // Fallback: send other roles (if any) to login or a neutral page
    header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?auth=role');
    exit;
}
?>