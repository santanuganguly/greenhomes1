<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');

// Using the media ID we already uploaded earlier
$mediaId = '2197411477791179';

$contacts = [
    '919339361344' => 'Raju',
    '917908874753' => 'Seheli',
];

$templateName = 'vishwakarma_bulk';

foreach ($contacts as $phone => $name) {
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type'    => 'individual',
        'to'                => $phone,
        'type'              => 'template',
        'template'          => [
            'name' => $templateName,
            'language' => ['code' => 'en'],
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
                ],
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $name
                        ]
                    ]
                ]
            ]
        ],
    ];

    $response = Http::withoutVerifying()->withToken($token)
        ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", $payload);

    echo "Sent to {$name} ({$phone}): " . $response->status() . " " . $response->body() . "\n";
}
