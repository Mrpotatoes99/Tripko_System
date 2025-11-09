<?php
/**
 * Utility: Bulk-verify admin (user_type_id=1) and tourism officer (user_type_id=3) accounts.
 * Sets is_email_verified = 1 and email_verified_at = NOW() where not already verified.
 * Optional: assign placeholder Gmail addresses to accounts missing an email (disabled by default).
 *
 * SECURITY NOTE: Delete or protect this file after use (e.g., move outside web root or add a simple shared secret check).
 */
ini_set('display_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/config/Database.php';

// Simple access guard (optional). Set a shared secret in the query string: ?key=YOUR_SECRET
$REQUIRED_KEY = null; // set to a string like 'MySecret123' then call /verify_admin_tourism_accounts.php?key=MySecret123
if ($REQUIRED_KEY !== null) {
    $provided = $_GET['key'] ?? '';
    if (hash_equals($REQUIRED_KEY, $provided) === false) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Forbidden']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('DB connection failed');

    // Detect if email_verified_at column exists (optional enhancement)
    $hasEmailVerifiedAt = false;
    if ($cols = $conn->query("SHOW COLUMNS FROM user LIKE 'email_verified_at'")) {
        if ($cols->num_rows > 0) $hasEmailVerifiedAt = true;
        $cols->free();
    }

    // Build update statement
    $setParts = ["is_email_verified=1"]; // mandatory
    if ($hasEmailVerifiedAt) {
        $setParts[] = "email_verified_at=NOW()";
    }
    $setClause = implode(',', $setParts);

    // Update admins and tourism officers not yet verified
    $sql = "UPDATE user SET $setClause WHERE is_email_verified<>1 AND user_type_id IN (1,3)";
    if (!$conn->query($sql)) {
        throw new Exception('Update failed: '.$conn->error);
    }
    $affected = $conn->affected_rows;

    // (Optional) Assign placeholder emails to accounts missing one (disabled by default)
    $assignPlaceholder = false; // set true if needed
    $placeholders = 0;
    if ($assignPlaceholder) {
        $res = $conn->query("SELECT user_id, username FROM user WHERE (email IS NULL OR email='') AND user_type_id IN (1,3)");
        while($row = $res && $row = $res->fetch_assoc()) {
            $uid = (int)$row['user_id'];
            $local = preg_replace('/[^a-z0-9]+/i','', strtolower($row['username']));
            if ($local === '') $local = 'user'.$uid;
            $email = $conn->real_escape_string($local.'@example.local');
            $conn->query("UPDATE user SET email='$email' WHERE user_id=$uid LIMIT 1");
            $placeholders++;
        }
        if($res) $res->free();
    }

    echo json_encode([
        'success' => true,
        'verified_now' => $affected,
        'placeholder_emails_assigned' => $placeholders,
        'note' => 'Remove this script after running to reduce exposure.'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}