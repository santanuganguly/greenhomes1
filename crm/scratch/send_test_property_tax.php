<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');
$recipientPhone = '918509335091'; // Ronit Gupta
$recipientName = 'Ronit Gupta';

echo "Sending property_tax to {$recipientName} ({$recipientPhone})...\n";
echo "Phone ID: {$phoneId}\n";

$imagePath = 'C:\\Users\\91963\\Downloads\\Green Hill Property Tax Relief 2026.png';
if (!file_exists($imagePath)) {
    // Fallback to alternative name if needed
    $imagePath = 'C:\\Users\\91963\\Downloads\\Green Hill Property Tax Relief 2026-1.png';
}

echo "Image file: {$imagePath}\n";
echo "Image exists: " . (file_exists($imagePath) ? 'YES' : 'NO') . "\n";

// Upload image to Meta Media API
echo "Uploading image to Meta...\n";
$uploadRes = Http::withoutVerifying()->withToken($token)
    ->attach('file', file_get_contents($imagePath), 'property_tax.png', ['Content-Type' => 'image/png'])
    ->post("https://graph.facebook.com/v20.0/{$phoneId}/media", [
        'messaging_product' => 'whatsapp'
    ]);

if (!$uploadRes->successful()) {
    echo "Media upload failed:\n" . $uploadRes->body() . "\n";
    exit(1);
}

$mediaId = $uploadRes->json()['id'];
echo "Media uploaded successfully! Media ID: {$mediaId}\n";

// Construct payload for property_tax
$payload = [
    'messaging_product' => 'whatsapp',
    'recipient_type'    => 'individual',
    'to'                => $recipientPhone,
    'type'              => 'template',
    'template'          => [
        'name'     => 'property_tax',
        'language' => [
            'code' => 'en'
        ],
        'components' => [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'id' => $mediaId
                        ]
                    ]
                ]
            ]
        ]
    ]
];

echo "Sending message template...\n";
$sendRes = Http::withoutVerifying()->withToken($token)
    ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", $payload);

echo "HTTP Status: " . $sendRes->status() . "\n";
echo "Response Body:\n" . json_encode($sendRes->json(), JSON_PRETTY_PRINT) . "\n";
