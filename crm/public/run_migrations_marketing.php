<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Boot the console kernel to initialize the environment
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "<pre>";
    echo "Running migrations...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
    
    echo "\nRestarting queues...\n";
    \Illuminate\Support\Facades\Artisan::call('queue:restart');
    echo \Illuminate\Support\Facades\Artisan::output();
    echo "</pre>";
    
    // Optionally delete itself for security
    // unlink(__FILE__);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
