<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');

$contacts = [
    '919635071111' => 'Amit',
    '919907771279' => 'Kaushal',
    '919339361344' => 'Raju',
    '917384676111' => 'Khusboo',
    '917908874753' => 'Seheli',
    '918509335091' => 'Ronit',
];

$documentUrl = 'https://crm.thegreen.homes/brochures/Green_Hill_Group_Projects.pdf';
$templateName = 'property_brochure_followup';

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
                            'type' => 'document',
                            'document' => [
                                'link' => $documentUrl,
                                'filename' => 'Green_Hill_Group_Projects.pdf'
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
        ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", $payload);

    echo "Sent to {$name} ({$phone}): " . $response->status() . " " . $response->body() . "\n";
}
