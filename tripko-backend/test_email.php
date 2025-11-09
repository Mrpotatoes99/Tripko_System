<?php
// Test email functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test basic mail function
    $to = 'test@gmail.com';
    $subject = 'TripKo Test Email';
    $message = 'This is a test email from TripKo system.';
    $headers = "From: TripKo <no-reply@localhost>";
    
    // Get PHP mail configuration
    $mailConfig = [
        'sendmail_path' => ini_get('sendmail_path'),
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from')
    ];
    
    // Test if mail function exists
    if (!function_exists('mail')) {
        echo json_encode([
            'status' => 'error',
            'message' => 'mail() function not available',
            'config' => $mailConfig
        ]);
        exit;
    }
    
    // Try to send test email (won't actually send)
    $result = @mail($to, $subject, $message, $headers);
    
    echo json_encode([
        'status' => $result ? 'success' : 'failed',
        'message' => $result ? 'mail() function works' : 'mail() function failed',
        'config' => $mailConfig,
        'error_get_last' => error_get_last()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'config' => $mailConfig ?? []
    ]);
}
?>