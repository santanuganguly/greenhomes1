<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    $status = $kernel->call('db:seed', [
        '--class' => 'GreenHomesFlowSeeder',
        '--force' => true
    ]);
    echo "<h1>Seeding Status: {$status}</h1>";
    echo "<pre>" . htmlspecialchars($kernel->output()) . "</pre>";
} catch (\Exception $e) {
    echo "<h1>Error</h1>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
