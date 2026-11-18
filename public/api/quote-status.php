<?php
/**
 * POST /api/quote-status  (admin only) — change status of a quote request.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/src/auth.php';
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}
csrf_verify($_POST['_csrf'] ?? null);

$u = current_user();
if (!$u || $u['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only.']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$ok = quotes_update_status($id, $status);
echo json_encode(['ok' => $ok]);
