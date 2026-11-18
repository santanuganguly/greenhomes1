<?php
/**
 * POST /api/contact  — public, no auth.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/src/config.php';
gh_load_env(dirname(__DIR__, 2));
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/repo/messages.php';
require_once dirname(__DIR__, 2) . '/src/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}
csrf_verify($_POST['_csrf'] ?? null);

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name, valid email, and a message are required.']);
    exit;
}

$id = null;
try {
    $id = messages_create($name, $email, $phone ?: null, $subject ?: null, $body);
} catch (PDOException $e) {
    error_log("Database connection failed for API contact: " . $e->getMessage());
}

require_once dirname(__DIR__, 2) . '/src/google_sheets.php';
gh_sheets_log_contact([
    'id' => $id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'body' => $body
]);

send_mail(
    (string) gh_env('GH_NOTIFY_TO', gh_env('GH_CONTACT_EMAIL', 'contact@thegreen.homes')),
    'Contact form: ' . ($subject ?: 'New message') . ($id ? '' : ' (Local DB Down)'),
    '<p>New contact form submission:</p>' .
    '<ul>' .
    '<li>Name: ' . htmlspecialchars($name) . '</li>' .
    '<li>Email: ' . htmlspecialchars($email) . '</li>' .
    '<li>Phone: ' . htmlspecialchars($phone ?: '—') . '</li>' .
    '<li>Subject: ' . htmlspecialchars($subject ?: '—') . '</li>' .
    '<li>Message: ' . nl2br(htmlspecialchars($body)) . '</li>' .
    '</ul><p>View in admin: ' . gh_app_url() . '/admin</p>',
    $email
);

echo json_encode(['ok' => true, 'id' => $id]);
