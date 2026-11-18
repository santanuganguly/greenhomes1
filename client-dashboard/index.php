<?php
declare(strict_types=1);

$root = dirname(__DIR__);
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!file_exists($root . '/src/config.php')) {
    $root = dirname(__DIR__, 2);
}
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/view.php';
require_once $root . '/src/portal.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

if (preg_match('#^/assets/#', $path) || in_array($path, ['/favicon.ico'])) {
    return false;
}

$routes = [
    '/'                   => ['pages/login',          null],
    '/login'              => ['pages/login',          null],
    '/signup'             => ['pages/signup',         null],
    '/logout'             => ['pages/logout',         null],

    '/dashboard'              => ['pages/client/dashboard',       null],
    '/dashboard/progress'     => ['pages/client/progress',        null],
    '/dashboard/weekly'       => ['pages/client/weekly',          null],
    '/dashboard/gallery'      => ['pages/client/gallery',         null],
    '/dashboard/before-after' => ['pages/client/before-after',    null],
    '/dashboard/documents'    => ['pages/client/documents',       null],
    '/dashboard/payments'     => ['pages/client/payments',        null],
    '/dashboard/materials'    => ['pages/client/materials',       null],
    '/dashboard/upcoming'     => ['pages/client/upcoming',        null],
    '/dashboard/approvals'    => ['pages/client/approvals',       null],
    '/dashboard/issues'       => ['pages/client/issues',          null],
    '/dashboard/notes'        => ['pages/client/notes',           null],
    '/dashboard/appointments' => ['pages/client/appointments',    null],
    '/dashboard/chat'         => ['pages/client/chat',            null],
    '/dashboard/project-chats'=> ['pages/chat/project-chats',    null],
    '/dashboard/suggestions'  => ['pages/client/suggestions',     null],
    '/dashboard/settings'     => ['pages/client/settings',        null],

    '/pm'              => ['pages/pm/dashboard',    null],
    '/pm/projects'     => ['pages/pm/projects',     null],
    '/pm/approvals'    => ['pages/pm/approvals',    null],
    '/pm/schedule'     => ['pages/pm/schedule',     null],
    '/pm/clients'      => ['pages/pm/clients',      null],
    '/pm/design-ideas' => ['pages/pm/design-ideas', null],
    '/pm/project-chats'=> ['pages/chat/project-chats', null],

    '/engineer'           => ['pages/engineer/dashboard',      null],
    '/engineer/today'     => ['pages/engineer/today',          null],
    '/engineer/weekly'    => ['pages/engineer/weekly',         null],
    '/engineer/notes'     => ['pages/engineer/notes',          null],
    '/engineer/materials' => ['pages/engineer/materials',      null],
    '/engineer/issues'    => ['pages/engineer/issues',         null],
    '/engineer/design-ideas' => ['pages/engineer/design-ideas', null],
    '/engineer/project-chats'=> ['pages/chat/project-chats', null],

    '/admin'                 => ['pages/admin/dashboard',     null],
    '/admin/clients'         => ['pages/admin/clients',       null],
    '/admin/projects'        => ['pages/admin/projects',      null],
    '/admin/projects/new'    => ['pages/admin/projects-new',  null],
    '/admin/projects/detail' => ['pages/admin/project-detail',null],
    '/admin/weekly'          => ['pages/admin/weekly',        null],
    '/admin/payments'        => ['pages/admin/payments',      null],
    '/admin/documents'       => ['pages/admin/documents',     null],
    '/admin/notifications'   => ['pages/admin/notifications', null],
    '/admin/reports'         => ['pages/admin/reports',       null],
    '/admin/design-ideas'    => ['pages/admin/design-ideas',  null],
    '/admin/project-chats'   => ['pages/chat/project-chats',  null],

    '/chat'               => ['pages/chat-global',    null],
    '/api/portal'         => ['api/portal',          null],
    '/api/chat'           => ['api/chat',            null],
    '/migrate'            => ['migrate',             null],
    '/fix'                => ['fix-chats', null],
];



$file = __DIR__ . '/' . ($routes[$path][0] ?? '') . '.php';
if (!isset($routes[$path]) || !is_file($file)) {
    http_response_code(404);
    echo '<h1>404 — Page not found</h1><p><a href="/">Go to login</a></p>';
    exit;
}

require $file;
