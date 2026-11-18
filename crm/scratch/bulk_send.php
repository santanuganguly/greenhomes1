<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\WhatsAppService::class);
$csvPath = 'c:\Users\91963\Downloads\campaign_recipients_template.csv';

if (!file_exists($csvPath)) {
    die("CSV file not found at: $csvPath\n");
}

$handle = fopen($csvPath, 'r');
if (!$handle) {
    die("Could not open CSV file\n");
}

$pdfUrl = 'https://crm.thegreen.homes/brochure.pdf';
$templateName = 'property_brochure_followup';

// Read header
$header = fgetcsv($handle);

$count = 0;
$successCount = 0;
$failCount = 0;

echo "Starting bulk blast to 1789 contacts...\n";

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 2) continue;
    $phone = $row[0];
    $name = trim($row[1]);

    if (empty($phone)) continue;
    
    // Ensure 91 prefix for Indian numbers if length is 10
    if (strlen($phone) == 10) {
        $phone = '91' . $phone;
    }

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
                    'text' => mb_substr($name, 0, 15) // Limit name length just in case
                ]
            ]
        ]
    ];

    try {
        $response = $service->sendTemplate($phone, $templateName, 'en', $components);
        
        if (isset($response['messages'][0]['message_status']) && $response['messages'][0]['message_status'] === 'accepted') {
            $successCount++;
        } else {
            $failCount++;
            \Illuminate\Support\Facades\Log::warning("Bulk Blast: Failed to send to $phone", ['response' => $response]);
        }
    } catch (\Exception $e) {
        $failCount++;
        \Illuminate\Support\Facades\Log::error("Bulk Blast: Error sending to $phone: " . $e->getMessage());
    }

    $count++;
    if ($count % 100 == 0) {
        echo "Processed $count contacts...\n";
    }

    // Rate limiting delay (approx 10 messages per second limit for low tier Meta)
    // We use 200,000 microseconds (0.2 seconds) to send ~5 per second.
    usleep(200000); 
}

fclose($handle);

echo "\nBulk blast completed!\n";
echo "Total Processed: $count\n";
echo "Successful: $successCount\n";
echo "Failed: $failCount\n";
