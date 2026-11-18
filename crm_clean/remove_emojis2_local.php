<?php
$file = 'database/seeders/GreenHomesFlowSeeder.php';
$content = file_get_contents($file);
$emojis = ['🤖', '💬'];
$content = str_replace($emojis, '', $content);
$content = preg_replace('/ {2,}/', ' ', $content); // clean double spaces
file_put_contents($file, $content);
echo "Done replacing robot/speech emojis in seeder.";
