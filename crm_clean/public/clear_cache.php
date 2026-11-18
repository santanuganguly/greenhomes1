<?php
// A simple script to clear caches and OPcache via web
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<pre>";
echo "Clearing caches...\n";

try {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo \Illuminate\Support\Facades\Artisan::output();
    
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "OPcache reset successfully.\n";
    } else {
        echo "OPcache is not enabled or function does not exist.\n";
    }
    
    echo "\nDone!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
