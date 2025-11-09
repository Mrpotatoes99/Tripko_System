<?php
// Lightweight SMS sender abstraction supporting Twilio and Semaphore via HTTP API.

class SMS
{
    public static function send(string $to, string $message): array
    {
        $cfgPath = __DIR__ . '/../config/sms_config.php';
        $cfg = file_exists($cfgPath) ? (require $cfgPath) : [];
        $provider = strtolower($cfg['PROVIDER'] ?? 'none');

        if ($provider === 'twilio') {
            return self::sendTwilio($to, $message, $cfg);
        } elseif ($provider === 'semaphore') {
            return self::sendSemaphore($to, $message, $cfg);
        }
        return ['ok' => false, 'error' => 'sms_not_configured'];
    }

    private static function sendTwilio(string $to, string $message, array $cfg): array
    {
        $sid = trim($cfg['TWILIO_ACCOUNT_SID'] ?? '');
        $token = trim($cfg['TWILIO_AUTH_TOKEN'] ?? '');
        $from = trim($cfg['TWILIO_FROM'] ?? '');
        if ($sid === '' || $token === '' || $from === '') {
            return ['ok'=>false,'error'=>'sms_not_configured'];
        }
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $post = http_build_query([
            'To' => $to,
            'From' => $from,
            'Body' => $message,
        ]);
        $auth = base64_encode($sid . ':' . $token);

        $resp = self::httpPost($url, $post, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        if (!$resp['ok']) return $resp;

        $data = json_decode($resp['body'] ?? '', true);
        if (isset($data['sid'])) return ['ok'=>true];
        return ['ok'=>false,'error'=>'sms_failed','provider'=>'twilio','detail'=>$resp['body'] ?? ''];
    }

    private static function sendSemaphore(string $to, string $message, array $cfg): array
    {
        $apiKey = trim($cfg['SEMAPHORE_API_KEY'] ?? '');
        if ($apiKey === '') return ['ok'=>false,'error'=>'sms_not_configured'];
        $sender = trim($cfg['SEMAPHORE_SENDER'] ?? '');
        $url = 'https://api.semaphore.co/api/v4/messages';
        $fields = [
            'apikey' => $apiKey,
            'number' => $to,
            'message' => $message,
        ];
        if ($sender !== '') $fields['sendername'] = $sender;
        $post = http_build_query($fields);

        $resp = self::httpPost($url, $post, ['Content-Type: application/x-www-form-urlencoded']);
        if (!$resp['ok']) return $resp;
        $data = json_decode($resp['body'] ?? '', true);
        // Semaphore returns an array of message objects on success
        if (is_array($data) && isset($data[0]['status']) && $data[0]['status'] === 'Queued') {
            return ['ok'=>true];
        }
        return ['ok'=>false,'error'=>'sms_failed','provider'=>'semaphore','detail'=>$resp['body'] ?? ''];
    }

    private static function httpPost(string $url, string $postData, array $headers = []): array
    {
        // Prefer cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // reasonable timeouts
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false) {
                return ['ok'=>false,'error'=>'http_error','detail'=>$err];
            }
            if ($code < 200 || $code >= 300) {
                return ['ok'=>false,'error'=>'http_status','status'=>$code,'body'=>$body];
            }
            return ['ok'=>true,'body'=>$body,'status'=>$code];
        }

        // Fallback: stream_context
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $postData,
                'timeout' => 20
            ]
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['ok'=>false,'error'=>'http_error','detail'=>'stream_context failed'];
        }
        return ['ok'=>true,'body'=>$body,'status'=>200];
    }
}
