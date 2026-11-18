<?php
/**
 * Front controller. Routes URI → page.
 *
 * php -S 127.0.0.1:8000 -t public   (run from inside green-homes/)
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/view.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
if (!gh_is_dev()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

if (preg_match('#^/assets/#', $path) || in_array($path, ['/favicon.ico', '/robots.txt', '/sitemap.xml'])) {
    return false;
}

// Map URL → page file
$routes = [
    '/'                  => ['pages/home',        'home'],
    '/services'          => ['pages/services',     'services'],
    '/estimator'         => ['pages/estimator',    'estimator'],
    '/how-it-works'      => ['pages/how-it-works', 'how'],
    
    '/about'             => ['pages/about',        'about'],
    '/contact'           => ['pages/contact',      'contact'],
    '/privacy'           => ['pages/privacy',      ''],
    '/sitemap'           => ['pages/sitemap',      ''],
    '/careers'           => ['pages/careers',      ''],
    '/login'             => ['pages/auth-login',   'login'],
    '/signup'            => ['pages/auth-signup',  'signup'],
    '/logout'            => ['pages/auth-logout',  ''],
    '/dashboard'         => ['pages/dashboard',    'dashboard'],
    '/dashboard/progress'=> ['pages/dashboard-progress', 'dashboard'],
    '/dashboard/gallery' => ['pages/dashboard-gallery', 'dashboard'],
    '/dashboard/documents'=> ['pages/dashboard-documents', 'dashboard'],
    '/dashboard/payments'=> ['pages/dashboard-payments', 'dashboard'],
    '/dashboard/settings'=> ['pages/dashboard-settings', 'dashboard'],
    '/dashboard/suggestions'=> ['pages/dashboard-suggestions', 'dashboard'],
    '/dashboard/project-chats'=> ['pages/project-chats', 'dashboard'],
    
    '/quote/new'         => ['pages/quote-new',    'quote'],
    
    '/chat'              => ['pages/chat',         null],

    '/admin'             => ['pages/admin',        'admin'],
    '/admin/projects'    => ['pages/admin-projects', 'admin'],
    '/admin/projects/new'=> ['pages/admin-projects-new', 'admin'],
    '/admin/projects/detail'=> ['pages/admin-project-detail', 'admin'],
    '/api/estimate'      => ['api/estimate',       null],
    '/api/quote'         => ['api/quote',          null],
    '/api/contact'       => ['api/contact',        null],
    '/api/career'        => ['api/career',         null],
    '/api/auth'          => ['api/auth',           null],
    '/api/quote-status'  => ['api/quote-status',   null],
    '/api/dashboard-action'=> ['api/dashboard-action', null],
    '/api/project-chats' => ['api/project-chats',  null],
    '/api/portal'         => ['../client-dashboard/api/portal', null],
    '/migrate'           => ['migrate',            null],
];

$file = __DIR__ . '/' . ($routes[$path][0] ?? '') . '.php';
if (!isset($routes[$path]) || !is_file($file)) {
    gh_404();
    exit;
}

require $file;
