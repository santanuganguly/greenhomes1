<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\WhatsAppService::class);

$phone = '918509335091'; // Ronit
$templateName = 'property_brochure_followup';
$name = 'Ronit';
$pdfUrl = 'https://crm.thegreen.homes/brochure.pdf';

$components = [
    [
        'type' => 'header',
        'parameters' => [
            [
                'type' => 'document',
                'document' => [
                    'link' => $pdfUrl,
                    'filename' => 'Green Hill Group Projects.pdf'
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
];

$response = $service->sendTemplate($phone, $templateName, 'en', $components);
echo "Response:\n";
print_r($response);
