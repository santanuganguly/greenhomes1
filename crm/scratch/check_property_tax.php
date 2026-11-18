<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$wabaId = env('META_WABA_ID');

$res = Http::withoutVerifying()->withToken($token)->timeout(30)
    ->get("https://graph.facebook.com/v20.0/{$wabaId}/message_templates", [
        'name' => 'property_tax'
    ]);

$data = $res->json()['data'][0] ?? null;
if ($data) {
    echo "=== TEMPLATE OVERVIEW ===\n";
    echo "ID: " . ($data['id'] ?? '') . "\n";
    echo "Name: " . ($data['name'] ?? '') . "\n";
    echo "Status: " . ($data['status'] ?? '') . "\n";
    echo "Category: " . ($data['category'] ?? '') . "\n";
    echo "Language: " . ($data['language'] ?? '') . "\n";
    
    echo "\n=== COMPONENTS ===\n";
    foreach ($data['components'] as $comp) {
        echo "Type: " . $comp['type'] . "\n";
        if (isset($comp['format'])) {
            echo "Format: " . $comp['format'] . "\n";
        }
        if (isset($comp['text'])) {
            echo "--- Text ---\n" . $comp['text'] . "\n------------\n";
        }
        if (isset($comp['buttons'])) {
            echo "Buttons: " . json_encode($comp['buttons'], JSON_PRETTY_PRINT) . "\n";
        }
        if (isset($comp['example'])) {
            echo "Example: " . json_encode($comp['example'], JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";
    }
} else {
    echo "Template not found: " . $res->body() . "\n";
}
