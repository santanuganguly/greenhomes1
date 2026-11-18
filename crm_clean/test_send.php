<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$engine = app(\App\Services\BotEngine::class);

$number = '918509335091';
$template = 'mutation';

$components = [];

echo "Sending template {$template} to {$number}...\n";

try {
    $engine->sendTemplate($number, $template, 'en', $components);
    echo "Sent!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
