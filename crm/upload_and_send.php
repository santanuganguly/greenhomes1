<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$service = new \App\Services\WhatsAppService();
$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');

$filePath = 'C:\Users\91963\Downloads\Green Hill Group Projects.pdf';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

echo "Uploading PDF to WhatsApp Media API...\n";
$response = Http::withoutVerifying()->withToken($token)
    ->attach('file', file_get_contents($filePath), 'Green Hill Group Projects.pdf')
    ->post("https://graph.facebook.com/v19.0/{$phoneId}/media", [
        'messaging_product' => 'whatsapp'
    ]);

if (!$response->successful()) {
    die("Failed to upload media: " . $response->body() . "\n");
}

$mediaId = $response->json()['id'];
echo "Media uploaded successfully! Media ID: $mediaId\n";

$testData = [
    ['phone' => '919635071111', 'name' => 'Amit']
];

foreach ($testData as $data) {
    $components = [
        [
            'type' => 'header',
            'parameters' => [
                [
                    'type' => 'document',
                    'document' => [
                        'id' => $mediaId,
                        'filename' => 'Green Hill Group Projects.pdf'
                    ]
                ]
            ]
        ],
        [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $data['name']]
            ]
        ]
    ];

    $response = $service->sendTemplate($data['phone'], 'property_brochure_followup', 'en', $components);
    echo "Sent to {$data['name']} ({$data['phone']}): " . json_encode($response) . "\n";
}
