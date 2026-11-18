<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$token = "EAAThE9Sfl9YBSCmgHyGRYYinU0OXEjtfseEyd2ZAu42dnOaYvZATMsbZBggti7prnQ50mcuGoynLAzaLPHLgXeJoapk5ToauFDUcotyO1QimmgtWZBrbRxmTrrHEAZBYzV1lz2fZBfO5Hb3lrk0WV7OoSasoq6GDqUZBbTt6P9FRjpTZAif2fVBpKFyjKGORze9AZBRBn8hWu973RlSwBfyEDBE9JS9UgUf6GZCQ85HtZA1IftFvpJDeDFp8BJ9tpmbYuuCZAcNWLmksIaHstJTf9Ssi";
$phoneId = "1194759783721722";

$payload = [
    'messaging_product' => 'whatsapp',
    'to'                => '918509335091',
    'type'              => 'text',
    'text'              => ['body' => 'Testing from PHP script']
];

$response = \Illuminate\Support\Facades\Http::withToken($token)
    ->post("https://graph.facebook.com/v17.0/{$phoneId}/messages", $payload);

echo "Status: " . $response->status() . "\n";
echo "Response: " . $response->body() . "\n";
