<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new \App\Services\WhatsAppService();

$testData = [
    ['phone' => '918509335091', 'name' => 'ronit'],
    ['phone' => '919046665256', 'name' => 'Green Hill Group'],
    ['phone' => '919046665257', 'name' => 'Hr']
];

foreach ($testData as $data) {
    $components = [
        [
            'type' => 'header',
            'parameters' => [
                [
                    'type' => 'document',
                    'document' => [
                        'link' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                        'filename' => 'Property_Brochure.pdf'
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
