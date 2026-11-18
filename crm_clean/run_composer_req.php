<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

echo "<pre>";
echo "Running Composer...\n";
putenv('COMPOSER_HOME=' . __DIR__ . '/.composer');
$output = shell_exec('composer require filament/filament:"^3.2" -W 2>&1');
echo $output;
echo "\nDone!\n";
echo "</pre>";
