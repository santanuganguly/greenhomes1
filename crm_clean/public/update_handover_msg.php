<?php
use App\Models\BotMessage;
use App\Models\Setting;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$expertPhone = Setting::get('expert_phone_display', '+91 93393 61344');

$messages = BotMessage::where('body_text', 'LIKE', '%expert will reach out to you shortly%')->get();
foreach ($messages as $msg) {
    if (!str_contains($msg->body_text, $expertPhone)) {
        $msg->body_text .= "\n\n📞 You can also call us directly: " . $expertPhone;
        $msg->save();
        echo "Updated message ID: {$msg->id}\n";
    }
}
echo "Done.";
