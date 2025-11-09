<?php
// Central auth & security guard for protected tourism officer pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require DB connection BEFORE including this file or adjust path:
if (!isset($conn)) {
    // Attempt to include db.php relative if not already loaded
    $maybe = __DIR__ . '/db.php';
    if (file_exists($maybe)) {
        require_once $maybe; // provides $conn
    }
}

// Basic presence check
if (!isset($_SESSION['user_id'])) {
    header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php');
    exit;
}

// Enforce tourism officer role (user_type_id = 3) by default; allow override
$requiredRole = $requiredRole ?? 3;
if ((int)($_SESSION['user_type_id'] ?? 0) !== (int)$requiredRole) {
    header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?auth=denied');
    exit;
}

// Session expiry management
if (isset($_SESSION['expires']) && time() > $_SESSION['expires']) {
    session_unset();
    session_destroy();
    header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php?session=expired');
    exit;
}

// Security headers (idempotent)
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=()');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; script-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' https://images.unsplash.com data:;");
}

// Municipality resolution (cached)
$municipality_id = $_SESSION['municipality_id'] ?? 0;
if (!$municipality_id && isset($_SESSION['user_id'])) {
    if ($conn) {
        $user_id = (int)$_SESSION['user_id'];
        if ($stmt = $conn->prepare("SELECT t.town_id FROM towns t INNER JOIN user u ON u.town_id = t.town_id WHERE u.user_id = ? LIMIT 1")) {
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($r = $res->fetch_assoc()) {
                    $municipality_id = (int)$r['town_id'];
                    $_SESSION['municipality_id'] = $municipality_id;
                }
            }
            $stmt->close();
        }
    }
}
