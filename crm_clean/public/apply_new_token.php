<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = "EAAThE9Sfl9YBSERqz2j0IPT1MHfsbEbyYvOipZBabzojNYwfbmJfmMlAfZAqiFWPqYmYXFPakbQRALyJqRQyVGclTQDX9KvkgDzKNJ4q1MrErLZAY9UYKbdnupwAwxcfNd8fb3uAzeZAqGV6YObZA5P71d1MTDRk4ngUf6XIGYOEjMHZBSKZBxzmk0SYPFiAaDs";
$path = base_path('.env');
$content = file_get_contents($path);
$escapedValue = '"' . str_replace('"', '\"', $token) . '"';

if (preg_match("/^META_ACCESS_TOKEN=/m", $content)) {
    $content = preg_replace("/^META_ACCESS_TOKEN=.*/m", "META_ACCESS_TOKEN={$escapedValue}", $content);
} else {
    $content .= "\nMETA_ACCESS_TOKEN={$escapedValue}\n";
}

file_put_contents($path, $content);
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "Successfully updated META_ACCESS_TOKEN in .env with the new permanent token!";
