<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\BotEngine;

$filePath = 'C:\Users\91963\Downloads\bLK MESSAGE.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

$spreadsheet = IOFactory::load($filePath);
$worksheet = $spreadsheet->getActiveSheet();

$engine = app(BotEngine::class);
$template = 'mutation';
$components = [];

$ignoreNumbers = ['+91 96350 71111', '9635071111', '919635071111', '+919635071111'];

$sentCount = 0;
$skippedCount = 0;

foreach ($worksheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    
    $number = '';
    
    // Assuming the phone number is in the first column or we just find a numeric string
    foreach ($cellIterator as $cell) {
        $val = trim((string)$cell->getValue());
        // Clean up the number to see if it looks like a phone number
        $cleanVal = preg_replace('/[^0-9\+]/', '', $val);
        
        if (strlen($cleanVal) >= 10) {
            $number = $cleanVal;
            break; // found the number
        }
    }
    
    if (!$number) {
        continue; // Empty row or no valid number found
    }

    // Check if it's the ignored number
    $isIgnored = false;
    foreach ($ignoreNumbers as $ignored) {
        if (str_replace(' ', '', $number) === str_replace(' ', '', $ignored)) {
            $isIgnored = true;
            break;
        }
    }
    
    if ($isIgnored) {
        echo "Skipping ignored number: {$number}\n";
        $skippedCount++;
        continue;
    }
    
    // Format to 91XXXXXXXXXX if necessary
    $numberToFormat = preg_replace('/[^0-9]/', '', $number);
    if (strlen($numberToFormat) === 10) {
        $numberToFormat = '91' . $numberToFormat;
    }
    
    echo "Sending template {$template} to {$numberToFormat}...\n";
    
    try {
        $engine->sendTemplate($numberToFormat, $template, 'en', $components);
        echo "Sent!\n";
        $sentCount++;
        // sleep(1); // Optional: delay between sends to avoid rate limiting
    } catch (\Exception $e) {
        echo "Error sending to {$numberToFormat}: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Summary ---\n";
echo "Sent: $sentCount\n";
echo "Skipped: $skippedCount\n";
