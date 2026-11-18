<?php
declare(strict_types=1);

function get_fcm_access_token(): ?string {
    $keyFile = __DIR__ . '/fcm_service_account.json';
    if (!file_exists($keyFile)) return null;
    $keyData = json_decode(file_get_contents($keyFile), true);
    if (!$keyData || empty($keyData['private_key'])) return null;

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $claim = json_encode([
        'iss' => $keyData['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $keyData['token_uri'],
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
    $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;

    $signature = '';
    openssl_sign($signatureInput, $signature, $keyData['private_key'], 'sha256');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $signatureInput . '.' . $base64UrlSignature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $keyData['token_uri']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode((string)$response, true);
    return $json['access_token'] ?? null;
}

function send_push_notification(string $token, string $title, string $body, array $data = []): bool {
    $keyFile = __DIR__ . '/fcm_service_account.json';
    if (!file_exists($keyFile)) return false;
    $keyData = json_decode(file_get_contents($keyFile), true);
    $projectId = $keyData['project_id'] ?? null;
    
    if (!$projectId) return false;

    $accessToken = get_fcm_access_token();
    if (!$accessToken) return false;

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    // Cast data array to strings (FCM requirement)
    $stringData = [];
    foreach ($data as $k => $v) {
        $stringData[$k] = (string)$v;
    }

    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'android' => [
                'priority' => 'HIGH',
                'notification' => [
                    'channelId' => 'high_importance_channel',
                    'sound' => 'default',
                    'notificationPriority' => 'PRIORITY_MAX',
                    'visibility' => 'PUBLIC',
                    'defaultSound' => true,
                    'defaultVibrateTimings' => true,
                    'defaultLightSettings' => true,
                    'clickAction' => 'FCM_PLUGIN_ACTIVITY'
                ]
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10'
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                        'content-available' => 1
                    ]
                ]
            ],
            'data' => empty($stringData) ? null : (object)$stringData
        ]
    ];
    
    if (empty($stringData)) {
        unset($message['message']['data']);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        error_log("FCM Push Error ($status): " . (string)$result);
    }

    return $status === 200;
}
