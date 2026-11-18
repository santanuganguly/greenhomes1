<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaigns = \App\Models\Campaign::with('recipients')->latest()->take(5)->get()->toArray();
echo json_encode($campaigns, JSON_PRETTY_PRINT);
