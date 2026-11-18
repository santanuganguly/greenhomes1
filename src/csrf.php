<?php
/**
 * CSRF token helpers. One token per session, rotated on demand.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '" />';
}

function csrf_verify(?string $token): void
{
    $valid = !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    if (!$valid) {
        http_response_code(419);
        echo 'CSRF token mismatch. Please reload the page and try again.';
        exit;
    }
}
