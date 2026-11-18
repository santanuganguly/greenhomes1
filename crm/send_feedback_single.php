<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');

$contacts = [
    '919635071111' => 'Amit Poddar'
];

$templateName = 'vishwakarma_puja_feedback_message';

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
