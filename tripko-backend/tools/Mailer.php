<?php
// Lightweight mailer wrapper: uses PHPMailer via Composer if available and configured; otherwise falls back to mail()

class Mailer
{
    /**
     * Holds the last error message encountered during send().
     */
    public static $lastError = '';

    public static function send(string $to, string $subject, string $body, array $opts = []): bool
    {
        $configPath = __DIR__ . '/../config/mail_config.php';
        $cfg = file_exists($configPath) ? (require $configPath) : [];
        $fromEmail = $cfg['FROM_EMAIL'] ?? ($opts['from_email'] ?? 'no-reply@localhost');
        $fromName  = $cfg['FROM_NAME']  ?? ($opts['from_name']  ?? 'TripKo');
        $smtpEnabled = (bool)($cfg['SMTP_ENABLED'] ?? false);
        $smtpDebug   = (bool)($cfg['SMTP_DEBUG'] ?? false);
        $smtpDebugFile = $cfg['SMTP_DEBUG_FILE'] ?? null;
        self::$lastError = '';

        // Try SMTP via PHPMailer if enabled and available
        if ($smtpEnabled) {
            $autoloadPaths = [
                __DIR__ . '/../vendor/autoload.php',          // backend vendor
                __DIR__ . '/../../vendor/autoload.php',       // project root vendor
                __DIR__ . '/vendor/autoload.php',             // tools vendor
            ];
            $autoload = null;
            foreach ($autoloadPaths as $path) {
                if (file_exists($path)) { $autoload = $path; break; }
            }
            if ($autoload) {
                require_once $autoload;
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = (string)($cfg['SMTP_HOST'] ?? '');
                        $mail->Port = (int)($cfg['SMTP_PORT'] ?? 587);
                        // Enable auth only if username is provided; allows local relays without auth
                        $mail->SMTPAuth = !empty($cfg['SMTP_USERNAME'] ?? '');
                        $mail->SMTPSecure = (string)($cfg['SMTP_SECURE'] ?? 'tls');
                        $mail->Username = (string)($cfg['SMTP_USERNAME'] ?? '');
                        $mail->Password = (string)($cfg['SMTP_PASSWORD'] ?? '');

                        // Optional debug logging
                        if ($smtpDebug) {
                            $mail->SMTPDebug = 2; // verbose
                            if (!$smtpDebugFile) {
                                $smtpDebugFile = __DIR__ . '/../mail_debug.log';
                            }
                            $debugFile = $smtpDebugFile;
                            $mail->Debugoutput = function($str, $level) use ($debugFile) {
                                $line = date('c') . " [level $level] " . $str . "\n";
                                @file_put_contents($debugFile, $line, FILE_APPEND | LOCK_EX);
                            };
                        }

                        $mail->setFrom($fromEmail, $fromName);
                        $mail->addAddress($to);
                        $mail->Subject = $subject;
                        $mail->Body = $body;
                        $mail->AltBody = strip_tags($body);
                        $ok = $mail->send();
                        if (!$ok) {
                            self::$lastError = method_exists($mail, 'ErrorInfo') ? $mail->ErrorInfo : 'Unknown mail error';
                        }
                        return $ok;
                    } catch (\Throwable $e) {
                        self::$lastError = 'SMTP exception: ' . $e->getMessage();
                        error_log('Mailer SMTP error: ' . $e->getMessage());
                        // fall through to mail()
                    }
                }
            }
        }

        // Fallback: PHP mail()
        $headers = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $ok = @mail($to, $subject, $body, $headers);
        if (!$ok && self::$lastError === '') {
            $lastPhpError = error_get_last();
            self::$lastError = $lastPhpError['message'] ?? 'mail() returned false';
        }
        return $ok;
    }
}
