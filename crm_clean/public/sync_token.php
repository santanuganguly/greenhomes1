<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$envToken = env('META_ACCESS_TOKEN');
\App\Models\Setting::where('key', 'meta_access_token')->update(['value' => $envToken]);

echo "Forced DB token to match ENV token.\n";
