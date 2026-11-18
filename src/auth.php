<?php
/**
 * Auth + session helpers. Sessions start here.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Keep user logged in for 1 year unless voluntarily logging out
    $oneYear = 86400 * 365;
    ini_set('session.gc_maxlifetime', (string)$oneYear);
    session_set_cookie_params([
        'lifetime' => $oneYear,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('gh_session');
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $stmt = db()->prepare('SELECT id, name, email, phone, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        session_destroy();
        return null;
    }
    $cache = $u;
    return $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'] ?? '', $roles, true)) {
        http_response_code(403);
        echo 'Forbidden.';
        exit;
    }
    return $u;
}

function require_admin(): array
{
    return require_role('admin', 'super_admin');
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u && in_array($u['role'] ?? '', $roles, true);
}

function is_super_admin(): bool { return has_role('super_admin'); }
function is_admin(): bool { return has_role('admin', 'super_admin'); }
function is_project_manager(): bool { return has_role('head_engineer', 'admin', 'super_admin'); }
function is_head_engineer(): bool { return has_role('head_engineer', 'admin', 'super_admin'); }
function is_site_engineer(): bool { return has_role('site_engineer', 'head_engineer', 'admin', 'super_admin'); }
function is_client(): bool { return has_role('client'); }

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
