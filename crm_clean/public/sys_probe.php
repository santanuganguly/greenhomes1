<?php
header('Content-Type: application/json');

$results = [];

// 1. Hosting Plan Hint
$results['doc_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
$results['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$results['hosting_type'] = strpos($results['doc_root'], '/home/') !== false ? 'Shared Hosting (hPanel)' : 'VPS/Cloud';

// 2. PHP Version
$results['php_version'] = phpversion();

// Test shell_exec capability
$shellExecEnabled = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));
$results['shell_exec_enabled'] = $shellExecEnabled;

// 3, 4, 5, 10. CLI Tools
if ($shellExecEnabled) {
    $results['whoami'] = trim(@shell_exec('whoami'));
    $results['composer_cli'] = trim(@shell_exec('composer --version 2>&1')) ?: 'Not Found / Disabled';
    $results['artisan_cli'] = trim(@shell_exec('php ../artisan --version 2>&1')) ?: 'Not Found / Error';
    $results['supervisor'] = trim(@shell_exec('supervisord -v 2>&1')) ?: 'Not Found / Disabled';
} else {
    $results['whoami'] = 'Disabled';
    $results['composer_cli'] = 'Disabled (No shell_exec)';
    $results['artisan_cli'] = 'Disabled (No shell_exec)';
    $results['supervisor'] = 'Disabled (No shell_exec)';
}

// 6. Redis Extension
$results['redis_extension'] = extension_loaded('redis');

// 7 & 8. Redis Server Connection
$redisConnected = false;
$redisError = null;
if ($results['redis_extension']) {
    try {
        $redis = new Redis();
        $connected = $redis->connect('127.0.0.1', 6379, 1);
        if ($connected) {
            $redisConnected = true;
        } else {
            $redisError = 'Connection refused';
        }
    } catch (\Throwable $e) {
        $redisError = $e->getMessage();
    }
} else {
    $redisError = 'Extension not loaded';
}
$results['redis_connected'] = $redisConnected;
$results['redis_error'] = $redisError;

// 9, 11, 13, 14. Long-running process restrictions
$results['max_execution_time'] = ini_get('max_execution_time');
$results['pcntl_loaded'] = extension_loaded('pcntl');
$disabledFuncs = ini_get('disable_functions');
$results['disabled_functions'] = $disabledFuncs;
$results['horizon_supported'] = $results['pcntl_loaded'] && strpos($disabledFuncs, 'pcntl_fork') === false;

echo json_encode($results, JSON_PRETTY_PRINT);
