<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$phoneId = env('META_PHONE_NUMBER_ID');

$templateName = 'vishwakarma_puja_feedback_message';

$file = 'C:\Users\91963\Downloads\bLK MESSAGE.xlsx';
if (!file_exists($file)) die("File not found");

// Read data
$zip = new ZipArchive();
if ($zip->open($file) === true) {
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    $strings = [];
    if ($sharedStringsXml) {
        $xml = simplexml_load_string($sharedStringsXml);
        foreach ($xml->si as $val) {
            if (isset($val->t)) {
                $strings[] = (string)$val->t;
            } elseif (isset($val->r)) {
                $text = '';
                foreach ($val->r as $r) {
                    $text .= (string)$r->t;
                }
                $strings[] = $text;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $data = [];
    if ($sheetXml) {
        $xml = simplexml_load_string($sheetXml);
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                if (isset($cell['t']) && (string)$cell['t'] == 's') {
                    $val = $strings[$val] ?? $val;
                }
                $rowData[] = trim($val);
            }
            if (!empty($rowData[0]) && !empty($rowData[1])) {
                $data[] = $rowData;
            }
        }
    }
    $zip->close();
} else {
    die("Failed to open zip");
}

echo "Found " . count($data) . " contacts in Excel.\n";

$success = 0;
$failed = 0;
$skipped = 0;

$checklist = "# Feedback Campaign Checklist\n\n| Status | Name | Phone |\n|--------|------|-------|\n";

foreach ($data as $index => $row) {
    if ($index === 0 && strtolower($row[0]) === 'name') continue; // Skip header

    $name = ucwords(strtolower($row[0]));
    
    $rawPhone = $row[1];
    $phone = preg_replace('/[^0-9]/', '', $rawPhone);
    
    if (strlen($phone) == 10) {
        $phone = '91' . $phone;
    } elseif (strlen($phone) > 10 && !str_starts_with($phone, '91')) {
        if (str_starts_with($phone, '0') && strlen($phone) == 11) {
            $phone = '91' . substr($phone, 1);
        }
    }

    if (empty($phone)) continue;

    // Skip Amit Poddar as requested
    if ($phone === '919635071111') {
        echo "⏭️ Skipped {$name} ({$phone})\n";
        $checklist .= "| ⏭️ Skipped | {$name} | {$phone} |\n";
        $skipped++;
        continue;
    }

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

    if ($response->successful()) {
        echo "✅ Sent to {$name} ({$phone})\n";
        $checklist .= "| ✅ Sent | {$name} | {$phone} |\n";
        $success++;
    } else {
        echo "❌ Failed for {$name} ({$phone}): " . $response->body() . "\n";
        $checklist .= "| ❌ Failed | {$name} | {$phone} |\n";
        $failed++;
    }

    usleep(200000); // 200ms delay
}

$checklist .= "\n### Summary\n- Total Sent: {$success}\n- Total Skipped: {$skipped}\n- Total Failed: {$failed}\n";
file_put_contents('C:\Users\91963\.gemini\antigravity-ide\brain\a37e0d42-aba9-4f31-a8b9-d2f93338e911\feedback_checklist.md', $checklist);

echo "\n--- Summary ---\n";
echo "Total Sent Successfully: $success\n";
echo "Total Skipped: $skipped\n";
echo "Total Failed: $failed\n";
