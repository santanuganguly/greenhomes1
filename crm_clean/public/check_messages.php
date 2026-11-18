<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Models\Message;

$msgs = Message::orderBy('created_at', 'desc')->take(10)->get();
echo "<pre>";
foreach ($msgs as $msg) {
    echo "ID: {$msg->id} | Dir: {$msg->direction} | Status: {$msg->status} | Content: {$msg->content}\n";
}
echo "</pre>";
