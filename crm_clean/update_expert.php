<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BotMessage;

$messages = BotMessage::all();
$count = 0;
foreach ($messages as $msg) {
    $updated = false;
    
    // Update buttons
    if ($msg->buttons) {
        $json = json_encode($msg->buttons, JSON_UNESCAPED_UNICODE);
        $newJson = str_replace('Talk to Expert', 'Talk to an Expert', $json);
        $newJson = str_replace('Talk to Our Expert', 'Talk to an Expert', $newJson);
        $newJson = str_replace('talk to our expert', 'Talk to an Expert', $newJson);
        if ($json !== $newJson) {
            $msg->buttons = json_decode($newJson, true);
            $updated = true;
        }
    }
    
    // Update list sections
    if ($msg->list_sections) {
        $json = json_encode($msg->list_sections, JSON_UNESCAPED_UNICODE);
        $newJson = str_replace('Talk to Expert', 'Talk to an Expert', $json);
        $newJson = str_replace('Talk to Our Expert', 'Talk to an Expert', $newJson);
        $newJson = str_replace('talk to our expert', 'Talk to an Expert', $newJson);
        if ($json !== $newJson) {
            $msg->list_sections = json_decode($newJson, true);
            $updated = true;
        }
    }
    
    if ($updated) {
        $msg->save();
        $count++;
    }
}
echo "Updated $count messages.\n";
