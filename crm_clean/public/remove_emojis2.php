<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\BotMessage;

$emojis = ['🤖', '💬'];

$messages = BotMessage::all();
foreach ($messages as $msg) {
    $updated = false;
    
    foreach (['header_text', 'body_text', 'footer_text', 'list_button_label'] as $field) {
        if ($msg->$field) {
            $newText = str_replace($emojis, '', $msg->$field);
            $newText = preg_replace('/[ ]+/', ' ', $newText);
            $newText = trim($newText);
            if ($newText !== $msg->$field) {
                $msg->$field = $newText;
                $updated = true;
            }
        }
    }
    
    if ($msg->buttons) {
        $btns = $msg->buttons;
        foreach ($btns as &$btn) {
            if (isset($btn['label'])) {
                $newLabel = str_replace($emojis, '', $btn['label']);
                $newLabel = trim(preg_replace('/[ ]+/', ' ', $newLabel));
                $btn['label'] = $newLabel;
                $updated = true;
            }
        }
        $msg->buttons = $btns;
    }
    
    if ($msg->list_sections) {
        $sections = $msg->list_sections;
        foreach ($sections as &$sec) {
            if (isset($sec['title'])) {
                $sec['title'] = trim(str_replace($emojis, '', $sec['title']));
                $updated = true;
            }
            if (isset($sec['rows'])) {
                foreach ($sec['rows'] as &$row) {
                    if (isset($row['title'])) {
                        $row['title'] = trim(str_replace($emojis, '', $row['title']));
                        $updated = true;
                    }
                    if (isset($row['description'])) {
                        $row['description'] = trim(str_replace($emojis, '', $row['description']));
                        $updated = true;
                    }
                }
            }
        }
        $msg->list_sections = $sections;
    }
    
    if ($updated) {
        $msg->save();
    }
}

echo "Extra emojis removed successfully!";
