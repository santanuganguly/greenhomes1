<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('META_ACCESS_TOKEN');
$wabaId = env('META_WABA_ID');
$templateName = 'vishwakarma_puja_feedback_message';

$response = Http::withoutVerifying()->withToken($token)->timeout(30)
    ->get("https://graph.facebook.com/v20.0/{$wabaId}/message_templates", [
        'name' => $templateName
    ]);

echo json_encode($response->json(), JSON_PRETTY_PRINT);
