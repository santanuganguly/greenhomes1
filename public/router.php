<?php
/**
 * PHP built-in server router.
 * Static assets are served directly; all other requests go through index.php.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/assets/#', $uri) || $uri === '/favicon.ico') {
    return false;
}
require __DIR__ . '/index.php';
