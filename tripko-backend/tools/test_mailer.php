<?php
// Simple test for Mailer::send with debug output
// Usage (CLI): php tools/test_mailer.php youremail@example.com
// Usage (web): /tripko-backend/tools/test_mailer.php?to=youremail@example.com

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/Mailer.php';

    // Allow overriding via GET/CLI
    $to = $_GET['to'] ?? ($argv[1] ?? '');
    if (!$to) {
        echo json_encode(['ok'=>false,'error'=>'missing_to','hint'=>'Provide ?to=address or CLI arg']);
        exit;
    }

    // Force debug on for this test run (won't persist)
    putenv('SMTP_DEBUG=true');

    $subject = 'TripKo Mailer test ' . date('c');
    $body    = "This is a test email from TripKo's Mailer.\nIf you receive this, SMTP configuration is working.";

    $sent = Mailer::send($to, $subject, $body);

    // Determine debug file path (match Mailer default)
    $cfgPath = __DIR__ . '/../config/mail_config.php';
    $cfg = file_exists($cfgPath) ? (require $cfgPath) : [];
    $debugFile = $cfg['SMTP_DEBUG_FILE'] ?? '';
    if (!$debugFile) { $debugFile = __DIR__ . '/../mail_debug.log'; }

    echo json_encode([
        'ok' => $sent,
        'message' => $sent ? 'Mailer sent successfully' : 'Mailer send failed',
        'last_error' => Mailer::$lastError,
        'debug_file_exists' => file_exists($debugFile),
        'debug_file' => $debugFile,
        'config' => [
            'SMTP_ENABLED' => (bool)($cfg['SMTP_ENABLED'] ?? false),
            'SMTP_HOST'    => $cfg['SMTP_HOST'] ?? '',
            'SMTP_PORT'    => $cfg['SMTP_PORT'] ?? '',
            'SMTP_SECURE'  => $cfg['SMTP_SECURE'] ?? '',
            'FROM_EMAIL'   => $cfg['FROM_EMAIL'] ?? '',
            'FROM_NAME'    => $cfg['FROM_NAME'] ?? '',
            'ENV' => [
                'SMTP_USERNAME' => getenv('SMTP_USERNAME') ? 'set' : 'not-set',
                'SMTP_PASSWORD' => getenv('SMTP_PASSWORD') ? 'set' : 'not-set',
            ]
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>'exception','message'=>$e->getMessage()]);
}
