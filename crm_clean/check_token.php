<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = \App\Models\Setting::where('key', 'meta_access_token')->first()?->value;
$envToken = env('META_ACCESS_TOKEN');

echo "DB Token: " . substr($token, 0, 30) . "...\n";
echo "ENV Token: " . substr($envToken, 0, 30) . "...\n";
