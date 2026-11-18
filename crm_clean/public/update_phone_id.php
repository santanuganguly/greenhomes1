<?php
$path = __DIR__.'/../.env';
$content = file_get_contents($path);

function updateEnv(&$content, $key, $value) {
    if (preg_match("/^{$key}=/m", $content)) {
        $content = preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $content);
    } else {
        $content .= "\n{$key}=\"{$value}\"\n";
    }
}

updateEnv($content, 'META_PHONE_NUMBER_ID', '1194759783721722');
updateEnv($content, 'META_WABA_ID', '930850970042236');

file_put_contents($path, $content);
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('queue:restart');

echo "Updated phone and waba IDs in .env and restarted queue!";
