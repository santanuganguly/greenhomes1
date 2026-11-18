<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\Artisan::call('queue:restart');
    echo "Queue worker restarted successfully. Next job will pick up new .env!\n";
} catch (\Exception $e) {
    echo "Failed to restart queue: " . $e->getMessage() . "\n";
}
