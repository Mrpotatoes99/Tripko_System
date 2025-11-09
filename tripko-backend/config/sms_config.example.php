<?php
// Copy this file to sms_config.php and fill real credentials. Do NOT commit sms_config.php
return [
    'PROVIDER' => 'none', // 'twilio' | 'semaphore' | 'none'

    // Twilio
    'TWILIO_ACCOUNT_SID' => '',
    'TWILIO_AUTH_TOKEN'  => '',
    'TWILIO_FROM'        => '', // e.g. +15005550006

    // Semaphore
    'SEMAPHORE_API_KEY' => '',
    'SEMAPHORE_SENDER'  => '',

    'FALLBACK_TO_LOG' => true,
];
