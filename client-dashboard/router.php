<?php
$root = __DIR__;
require_once $root . '/src/config.php';
gh_load_env($root);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . $path;

if (is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
