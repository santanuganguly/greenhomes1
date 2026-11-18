<?php
$dir = new RecursiveDirectoryIterator('resources/views/livewire');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$emojis = ['🏢', '🏡', '👨‍💼', '📞', '🏠', '📍', '📄', '📅', '✅', '❌', '💰', '🤔', '🟢', '🔴', '🟠', '✏️', '💾', '🔗'];

foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $newContent = str_replace($emojis, '', $content);
    // clean up space artifacts
    $newContent = preg_replace('/ {2,}/', ' ', $newContent);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Cleaned $path\n";
    }
}
