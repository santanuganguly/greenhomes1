<?php
$root = dirname(__DIR__, 2);
if (!file_exists($root . '/src/config.php')) { $root = dirname(__DIR__, 3); }
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
logout_user();
header('Location: /login');
exit;
