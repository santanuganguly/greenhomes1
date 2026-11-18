<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BotEngine;

$filePath = 'C:\Users\91963\Downloads\bLK MESSAGE.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

$zip = new ZipArchive();
if ($zip->open($filePath) !== TRUE) {
    die("Could not open Excel file as ZIP.\n");
}

// Read shared strings
$sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
$sharedStrings = [];
if ($sharedStringsXml) {
    $xml = simplexml_load_string($sharedStringsXml);
    foreach ($xml->si as $si) {
        if (isset($si->t)) {
            $sharedStrings[] = (string)$si->t;
        } else {
            // handle cases where text is nested inside <r><t>
            $text = '';
            if (isset($si->r)) {
                foreach ($si->r as $r) {
                    if (isset($r->t)) $text .= (string)$r->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }
}

// Read first sheet
$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
if (!$sheetXml) {
    die("Could not find sheet1.xml\n");
}

$sheet = simplexml_load_string($sheetXml);
$engine = app(BotEngine::class);
$template = 'mutation';
$components = [];

$ignoreNumbers = ['+91 96350 71111', '9635071111', '919635071111', '+919635071111', '91 96350 71111'];
$sentCount = 0;
$skippedCount = 0;

foreach ($sheet->sheetData->row as $row) {
    $number = '';
    
    foreach ($row->c as $cell) {
        $val = (string)$cell->v;
        if (isset($cell['t']) && (string)$cell['t'] === 's') {
            // String from sharedStrings
            $val = $sharedStrings[(int)$val] ?? '';
        }
        
        $cleanVal = preg_replace('/[^0-9\+]/', '', $val);
        if (strlen($cleanVal) >= 10) {
            $number = $cleanVal;
            break; // found the number
        }
    }
    
    if (!$number) continue;

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
    
    $numberToFormat = preg_replace('/[^0-9]/', '', $number);
    if (strlen($numberToFormat) === 10) {
        $numberToFormat = '91' . $numberToFormat;
    }
    
    echo "Sending template {$template} to {$numberToFormat}...\n";
    
    try {
        $engine->sendTemplate($numberToFormat, $template, 'en', $components);
        echo "Sent!\n";
        $sentCount++;
    } catch (\Exception $e) {
        echo "Error sending to {$numberToFormat}: " . $e->getMessage() . "\n";
    }
}

$zip->close();

echo "\n--- Summary ---\n";
echo "Sent: $sentCount\n";
echo "Skipped: $skippedCount\n";
