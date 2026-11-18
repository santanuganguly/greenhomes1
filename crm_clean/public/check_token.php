<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = \App\Models\Setting::where('key', 'meta_access_token')->first()?->value;
$envToken = env('META_ACCESS_TOKEN');

if (trim($token) === trim($envToken)) {
    echo "Tokens match perfectly.\n";
} else {
    echo "Tokens differ!\n";
    echo "DB ends with: " . substr(trim($token), -10) . "\n";
    echo "ENV ends with: " . substr(trim($envToken), -10) . "\n";
}
