<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$messages = \App\Models\Message::where('type', 'template')->with('conversation')->get();
$payload = [];
foreach($messages as $m) {
    if (!$m->conversation) continue;
    $payload[] = [
        'phone' => $m->conversation->phone_number,
        'name' => $m->conversation->customer_name
    ];
}

$ch = curl_init('https://crm.thegreen.homes/import_campaign.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Error: " . $error . "\n";
echo "Response: " . $response . "\n";
