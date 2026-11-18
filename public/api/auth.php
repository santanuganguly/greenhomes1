<?php
/**
 * Auth API: action=login | signup | logout
 * Returns JSON for AJAX, redirects for non-AJAX form posts.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/src/auth.php';
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/repo/users.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'
    || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

function auth_respond(bool $ok, ?string $error, ?string $redirect, bool $isAjax, ?string $fallback = null): void
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'error' => $error, 'redirect' => $redirect]);
        exit;
    }
    if ($ok && $redirect) {
        header('Location: ' . $redirect);
        exit;
    }
    $back = $_POST['_back'] ?? ($fallback ?: ('/login?err=' . urlencode($error ?? 'error')));
    header('Location: ' . $back);
    exit;
}

if ($action === 'logout') {
    logout_user();
    auth_respond(true, null, '/', $isAjax);
}

csrf_verify($_POST['_csrf'] ?? null);

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $next = $_POST['next'] ?? '/dashboard';
    if (!preg_match('#^/[A-Za-z0-9/_?=&%.\-]*$#', $next)) {
        $next = '/dashboard';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        auth_respond(false, 'Please enter a valid email and password.', null, $isAjax, '/login?err=' . urlencode('Please enter a valid email and password.'));
    }
    $u = null;
    try {
        $u = users_find_by_email($email);
    } catch (PDOException $e) {
        error_log("Database connection failed for auth: " . $e->getMessage());
        auth_respond(false, 'Database connection is temporarily unavailable.', null, $isAjax, '/login?err=' . urlencode('Database connection is temporarily unavailable.'));
    }

    if (!$u || !password_verify($password, $u['password_hash'])) {
        auth_respond(false, 'Email or password is incorrect.', null, $isAjax, '/login?err=' . urlencode('Email or password is incorrect.'));
    }
    login_user((int) $u['id']);
    require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
    gh_sheets_log_user_activity([
        'activity_type' => 'login',
        'user_id' => (int) $u['id'],
        'name' => $u['name'],
        'email' => $u['email'],
        'phone' => $u['phone'] ?? ''
    ]);
    auth_respond(true, null, $next, $isAjax);
}

if ($action === 'signup') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $next = $_POST['next'] ?? '/dashboard';
    if (!preg_match('#^/[A-Za-z0-9/_?=&%.\-]*$#', $next)) {
        $next = '/dashboard';
    }

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        auth_respond(false, 'Please provide name, a valid email, and a password of 8+ characters.', null, $isAjax, '/signup?err=' . urlencode('Please provide name, a valid email, and a password of 8+ characters.'));
    }
    $id = null;
    try {
        if (users_email_taken($email)) {
            auth_respond(false, 'That email is already registered. Try logging in.', null, $isAjax, '/signup?err=' . urlencode('That email is already registered. Try logging in.'));
        }
        $id = users_create($name, $email, $phone, $password);
    } catch (PDOException $e) {
        error_log("Database connection failed for signup: " . $e->getMessage());
        auth_respond(false, 'Database connection is temporarily unavailable.', null, $isAjax, '/signup?err=' . urlencode('Database connection is temporarily unavailable.'));
    }
    login_user($id);
    require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
    gh_sheets_log_user_activity([
        'activity_type' => 'signup',
        'user_id' => $id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ]);
    auth_respond(true, null, $next, $isAjax);
}

auth_respond(false, 'Unknown action.', null, $isAjax);
